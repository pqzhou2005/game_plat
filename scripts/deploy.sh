#!/usr/bin/env bash
set -Eeuo pipefail

# new602 线上重复发布脚本：repo 构建 + releases 发布 + current 切换
#
# 目录结构建议：
#   /www/wwwroot/new602
#   ├── repo                 # Git 拉代码、npm 构建目录，不直接对外访问
#   ├── releases             # 每次发布生成一个新版本目录
#   │   ├── 20260617103000
#   │   └── 20260617110000
#   ├── current -> releases/20260617110000
#   └── shared
#       ├── .env             # 线上环境配置，所有版本共享
#       ├── storage          # 上传文件、日志、缓存目录共享
#       └── vendor           # PHP 依赖共享；本脚本不执行 composer install
#
# 宝塔站点根目录请设置为：
#   /www/wwwroot/new602/current/public
#
# 使用方式：
#   cd /www/wwwroot/new602/repo
#   bash scripts/deploy.sh
#
# 第一次运行前建议：
#   1. 先创建 shared/.env
#   2. 你自己准备 PHP 依赖到 shared/vendor
#   3. 执行 bash -n scripts/deploy.sh 检查语法
#
# 注意：
#   本脚本不执行 composer install/update。
#   前端会在线上 repo 目录执行 npm ci + npm run build。

# =========================
# 基础配置区：按线上实际情况修改
# =========================

# 发布根目录，不是项目 public，也不是 repo。
BASE_DIR="/www/wwwroot/gameplat.bauniv.cn"

# Git 仓库地址、远程名、分支。
GIT_URL="git@github.com:pqzhou2005/game_plat.git"
REMOTE="origin"
BRANCH="master"

# 各目录路径。
REPO_DIR="$BASE_DIR/repo"
RELEASES_DIR="$BASE_DIR/releases"
SHARED_DIR="$BASE_DIR/shared"
CURRENT_LINK="$BASE_DIR/current"

# PHP 和 npm 命令。
# 宝塔如果默认 php 不是 8.2，建议改成完整路径：
#   PHP_BIN="/www/server/php/82/bin/php"
PHP_BIN="/www/server/php/82/bin/php"
NPM_BIN="npm"

# Web 服务运行用户。宝塔默认通常是 www:www。
WEB_USER="www"
WEB_GROUP="www"

# 是否强制覆盖 repo 工作区。
# 0 = 安全模式：repo 有未提交改动时停止发布。
# 1 = 强制模式：repo 会 reset --hard 到远程分支。
FORCE_RESET=0

# 是否执行数据库迁移。
# 正式上线通常保持 1。
RUN_MIGRATIONS=1

# 是否执行前端构建。
# 正式上线通常保持 1。
RUN_FRONTEND_BUILD=1

# 是否修复目录权限。
RUN_PERMISSION_FIX=1

# 保留最近几个版本，旧版本自动删除。
KEEP_RELEASES=5

# PHP 依赖处理方式。
# shared = release/vendor 链接到 shared/vendor，推荐给“PHP 依赖自己维护”的方式。
# copy   = 如果 repo/vendor 存在，就复制到 release/vendor。
# none   = 不处理 vendor，适合你自己在每个 release 里提前处理。
VENDOR_MODE="shared"

# 防止重复发布的锁文件。
LOCK_FILE="/tmp/new602-deploy.lock"

# 内部状态：用于失败时自动恢复站点。
APP_DOWN=0
NEW_RELEASE=""

# =========================
# 工具函数
# =========================

log() {
    printf '\n[%s] %s\n' "$(date '+%F %T')" "$*"
}

fail() {
    printf '\n[ERROR] %s\n' "$*" >&2
    exit 1
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "命令不存在：$1"
}

current_artisan() {
    [ -f "$CURRENT_LINK/artisan" ]
}

release_artisan() {
    [ -n "$NEW_RELEASE" ] && [ -f "$NEW_RELEASE/artisan" ]
}

cleanup() {
    # 如果脚本中途失败，并且已经进入维护模式，尽量恢复当前线上版本。
    if [ "${APP_DOWN:-0}" = "1" ] && current_artisan; then
        (cd "$CURRENT_LINK" && "$PHP_BIN" artisan up >/dev/null 2>&1) || true
    fi

    rm -f "$LOCK_FILE"
}

run_in_release() {
    (cd "$NEW_RELEASE" && "$PHP_BIN" artisan "$@")
}

# =========================
# 发布开始
# =========================

if [ -e "$LOCK_FILE" ]; then
    fail "发布锁已存在：$LOCK_FILE。可能有另一个发布正在执行。"
fi
trap cleanup EXIT
echo "$$" > "$LOCK_FILE"

require_cmd git
require_cmd rsync
require_cmd "$PHP_BIN"

if [ "$RUN_FRONTEND_BUILD" = "1" ]; then
    require_cmd "$NPM_BIN"
fi

log "准备发布目录"
mkdir -p "$BASE_DIR" "$RELEASES_DIR" "$SHARED_DIR"

if [ ! -f "$SHARED_DIR/.env" ]; then
    fail "缺少线上配置：$SHARED_DIR/.env。请先创建 shared/.env。"
fi

mkdir -p "$SHARED_DIR/storage"

if [ "$VENDOR_MODE" = "shared" ] && [ ! -d "$SHARED_DIR/vendor" ]; then
    fail "VENDOR_MODE=shared，但缺少 $SHARED_DIR/vendor。请先准备 PHP 依赖。"
fi

# =========================
# 1. 准备 repo 代码目录
# =========================

if [ ! -d "$REPO_DIR/.git" ]; then
    log "repo 不存在，开始 clone：$GIT_URL"
    git clone --branch "$BRANCH" "$GIT_URL" "$REPO_DIR"
fi

cd "$REPO_DIR"
[ -f artisan ] || fail "repo 目录不是 Laravel 项目：$REPO_DIR"

log "repo 目录：$REPO_DIR"
log "目标分支：$REMOTE/$BRANCH"

# 确保 remote 使用 SSH 协议（HTTPS 在大陆服务器上常被限速/超时）。
git remote set-url "$REMOTE" "$GIT_URL"

if [ "$FORCE_RESET" = "1" ]; then
    # 强制模式会丢弃 repo 里的所有本地改动。
    log "强制模式：拉取代码并 reset 到远程分支"
    git fetch "$REMOTE" "$BRANCH"
    git checkout "$BRANCH"
    git reset --hard "$REMOTE/$BRANCH"
    git clean -fd
else
    # 安全模式避免你在 repo 目录临时改的文件被覆盖。
    log "安全模式：检查 repo 是否有本地改动"
    if ! git diff --quiet || ! git diff --cached --quiet; then
        fail "repo 有未提交改动。请提交/暂存/清理后再发布，或将 FORCE_RESET 改为 1。"
    fi

    log "拉取最新代码"
    git fetch "$REMOTE" "$BRANCH"

    current_branch="$(git branch --show-current)"
    if [ "$current_branch" != "$BRANCH" ]; then
        log "切换分支：$current_branch -> $BRANCH"
        git checkout "$BRANCH"
    fi

    log "快进合并远程代码"
    git merge --ff-only "$REMOTE/$BRANCH"
fi

# =========================
# 2. 在 repo 里构建前端
# =========================

if [ "$RUN_FRONTEND_BUILD" = "1" ]; then
    log "安装前端依赖"
    if [ -f package-lock.json ]; then
        "$NPM_BIN" ci
    else
        "$NPM_BIN" install
    fi

    log "构建前端资源"
    "$NPM_BIN" run build
fi

# =========================
# 3. 创建新 release
# =========================

RELEASE_NAME="$(date '+%Y%m%d%H%M%S')"
NEW_RELEASE="$RELEASES_DIR/$RELEASE_NAME"

log "创建新版本目录：$NEW_RELEASE"
mkdir -p "$NEW_RELEASE"

# 复制代码到 release。
# 排除 Git、node_modules、.env、storage；这些不应该进入版本目录。
# vendor 根据 VENDOR_MODE 单独处理。
log "复制代码到新版本目录"
RSYNC_EXCLUDES=(
    "--exclude=.git"
    "--exclude=node_modules"
    "--exclude=.env"
    "--exclude=storage"
)

if [ "$VENDOR_MODE" = "shared" ] || [ "$VENDOR_MODE" = "none" ]; then
    RSYNC_EXCLUDES+=("--exclude=vendor")
fi

rsync -a --delete "${RSYNC_EXCLUDES[@]}" "$REPO_DIR/" "$NEW_RELEASE/"

# 链接线上共享配置。
log "链接 shared/.env 和 shared/storage"
ln -sfn "$SHARED_DIR/.env" "$NEW_RELEASE/.env"
rm -rf "$NEW_RELEASE/storage"
ln -sfn "$SHARED_DIR/storage" "$NEW_RELEASE/storage"

# 处理 PHP vendor。
case "$VENDOR_MODE" in
    shared)
        log "链接 shared/vendor"
        ln -sfn "$SHARED_DIR/vendor" "$NEW_RELEASE/vendor"
        ;;
    copy)
        if [ -d "$REPO_DIR/vendor" ]; then
            log "从 repo 复制 vendor"
            rsync -a --delete "$REPO_DIR/vendor/" "$NEW_RELEASE/vendor/"
        else
            fail "VENDOR_MODE=copy，但 repo/vendor 不存在。"
        fi
        ;;
    none)
        log "跳过 vendor 处理"
        ;;
    *)
        fail "未知 VENDOR_MODE：$VENDOR_MODE"
        ;;
esac

[ -f "$NEW_RELEASE/artisan" ] || fail "新版本目录缺少 artisan。"
[ -d "$NEW_RELEASE/vendor" ] || fail "新版本目录缺少 vendor。请检查 VENDOR_MODE。"

# =========================
# 4. 在新 release 上准备 Laravel
# =========================

log "清理新版本 Laravel 缓存"
run_in_release optimize:clear

if [ "$RUN_MIGRATIONS" = "1" ]; then
    # 迁移会改数据库。简单业务可以在切换前执行；如果以后有复杂不兼容迁移，需要改成兼容式迁移流程。
    log "执行数据库迁移"
    run_in_release migrate --force
fi

log "确保 storage 软链接存在"
run_in_release storage:link || true

log "生成生产缓存"
run_in_release config:cache
run_in_release route:cache
run_in_release view:cache
run_in_release event:cache

# =========================
# 5. 切换 current
# =========================

if current_artisan; then
    log "当前线上版本进入维护模式"
    (cd "$CURRENT_LINK" && "$PHP_BIN" artisan down --render="errors::503") || true
    APP_DOWN=1
fi

log "原子切换 current 到新版本"
ln -sfn "$NEW_RELEASE" "$BASE_DIR/current_tmp"
mv -Tf "$BASE_DIR/current_tmp" "$CURRENT_LINK"

if [ "$RUN_PERMISSION_FIX" = "1" ]; then
    # 权限修复失败不阻断发布，避免某些服务器权限限制导致整次发布失败。
    log "修复目录权限"
    chown -R "$WEB_USER:$WEB_GROUP" "$SHARED_DIR/storage" "$NEW_RELEASE/bootstrap/cache" "$NEW_RELEASE/public/build" 2>/dev/null || true
    chmod -R ug+rwX "$SHARED_DIR/storage" "$NEW_RELEASE/bootstrap/cache" "$NEW_RELEASE/public/build" 2>/dev/null || true
fi

log "恢复站点访问"
(cd "$CURRENT_LINK" && "$PHP_BIN" artisan up)
APP_DOWN=0

# =========================
# 6. 清理旧 release
# =========================

if [ "$KEEP_RELEASES" -gt 0 ]; then
    log "清理旧版本，仅保留最近 $KEEP_RELEASES 个"
    # shellcheck disable=SC2012
    old_releases="$(ls -1dt "$RELEASES_DIR"/* 2>/dev/null | tail -n +"$((KEEP_RELEASES + 1))" || true)"
    if [ -n "$old_releases" ]; then
        printf '%s\n' "$old_releases" | xargs rm -rf
    fi
fi

log "发布完成：$RELEASE_NAME"
log "当前版本：$(readlink -f "$CURRENT_LINK")"
