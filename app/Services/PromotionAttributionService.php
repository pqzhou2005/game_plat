<?php
namespace App\Services;

use App\Models\Promote;
use App\Models\User;
use App\Models\UserAttribution;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PromotionAttributionService
{
    /**
     * 根据 promote_code 解析有效的推广入口
     */
    public function resolvePromote(string $promoteCode): ?Promote
    {
        return Promote::where('promote_code', $promoteCode)
            ->where('status', 1)
            ->where('promote_type', 'landing')
            ->first();
    }

    /**
     * 注册成功后记录归因
     *
     * @param User $user  已创建的用户
     * @param string|null $promoteCode  前端提交的推广码
     */
    public function recordRegisterAttribution(User $user, ?string $promoteCode): void
    {
        // 未传 promote_code：自然注册，不做任何处理
        if (empty($promoteCode)) {
            return;
        }

        $promote = $this->resolvePromote($promoteCode);

        // 无效 promote_code：记录 warning 日志
        if (!$promote) {
            Log::warning('注册归因失败：无效 promote_code', [
                'user_id' => $user->id,
                'promote_code' => $promoteCode,
                'reason' => $this->getInvalidReason($promoteCode),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            return;
        }

        // 写入归因（唯一索引兜底，不覆盖不重复写入）
        // 任何写入异常只记日志，不抛出——归因失败不能影响注册成功
        try {
            UserAttribution::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'promote_id' => $promote->id,
                    'promote_code' => $promote->promote_code,
                    'attribution_type' => 'landing',
                ]
            );
        } catch (\Throwable $e) {
            Log::error('注册归因写入异常', [
                'user_id' => $user->id,
                'promote_id' => $promote->id,
                'promote_code' => $promote->promote_code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 生成 promote_code（8位随机字母数字组合）
     */
    public static function generatePromoteCode(): string
    {
        $attempts = 0;
        do {
            $code = Str::random(8);
            $attempts++;
            if ($attempts > 10) {
                // 极端冲突场景，加长到 12 位
                $code = Str::random(12);
            }
        } while (Promote::where('promote_code', $code)->exists());

        return $code;
    }

    /**
     * 获取 promote_code 无效的具体原因
     */
    private function getInvalidReason(string $promoteCode): string
    {
        $promote = Promote::where('promote_code', $promoteCode)->first();

        if (!$promote) {
            return 'promote_code 不存在';
        }

        if ($promote->status !== 1) {
            return '推广入口已停用';
        }

        if ($promote->promote_type !== 'landing') {
            return '推广类型不是 landing，当前类型：' . $promote->promote_type;
        }

        return '未知原因';
    }
}
