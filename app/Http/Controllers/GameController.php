<?php
namespace App\Http\Controllers;

use App\Enums\CommonStatus;
use App\Enums\GameServerStatus;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameCategory;
use App\Models\GameServer;
use App\Models\GameSsoConfig;
use App\Models\Notice;
use App\Models\RoleReport;
use App\Services\SsoService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $query = Game::active()->with('category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('game_type', $request->type);
        }

        $sort = $request->get('sort', 'sort');
        $query->orderBy($sort);

        return Inertia::render('Games/Index', [
            'games' => $query->paginate(24)->withQueryString(),
            'categories' => GameCategory::active()->orderBy('sort')->get(),
            'filters' => $request->only(['category', 'search', 'type', 'sort']),
        ]);
    }

    public function show(Game $game)
    {
        $game->load(['category', 'servers' => fn($q) => $q->orderBy('open_time', 'desc'), 'entries']);

        // 获取用户最近玩过的服务器ID
        $recentServerIds = [];
        if (Auth::check()) {
            $recentServerIds = RoleReport::where('user_id', Auth::id())
                ->where('game_id', $game->id)
                ->whereIn('submit_type', [1, 3])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->pluck('server_id')
                ->unique()
                ->values()
                ->toArray();
        }

        $notices = Notice::active()->forGame($game->id)->take(5)->get();

        return Inertia::render('Games/Show', [
            'game' => $game,
            'recommended' => Game::active()
                ->where('is_recommend', true)
                ->where('id', '!=', $game->id)
                ->take(4)
                ->get(),
            'recentServerIds' => $recentServerIds,
            'notices' => $notices,
        ]);
    }

    public function servers(Request $request)
    {
        $tab = $request->get('tab', 'today');
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $allServers = GameServer::with('game')->orderBy('open_time', 'desc');

        // 今日开服
        $todayServers = GameServer::with('game')
            ->whereBetween('open_time', [$todayStart, $todayEnd])
            ->orderBy('open_time', 'desc')
            ->take(20)
            ->get();

        // 即将开服（今日之后7天内）
        $upcomingServers = GameServer::with('game')
            ->where('open_time', '>', $todayEnd)
            ->where('open_time', '<=', now()->addDays(7)->endOfDay())
            ->orderBy('open_time')
            ->take(20)
            ->get();

        // 筛选
        $query = GameServer::with('game')->orderBy('open_time', 'desc');

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        if ($tab === 'today') {
            $query->whereBetween('open_time', [$todayStart, $todayEnd]);
        } elseif ($tab === 'upcoming') {
            $query->where('open_time', '>', $todayEnd)
                  ->where('open_time', '<=', now()->addDays(7)->endOfDay());
        }

        return Inertia::render('Servers', [
            'todayServers' => $todayServers,
            'upcomingServers' => $upcomingServers,
            'servers' => $query->paginate(30)->withQueryString(),
            'games' => Game::active()->orderBy('name')->get(['id', 'name']),
            'currentTab' => $tab,
            'filters' => $request->only(['game_id', 'tab']),
        ]);
    }

    public function play(Game $game, Request $request, SsoService $ssoService)
    {
        // 检查游戏状态
        if ($game->status === GameStatus::DISABLED) {
            abort(410, '该游戏已下架');
        }

        // 实名+防沉迷校验
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (!$user->isRealNameVerified()) {
            $redirectUrl = route('game.play', [
                'game' => $game->id,
                'server_id' => $request->integer('server_id'),
            ]);
            return redirect()->route('verify.real-name', ['redirect' => $redirectUrl])
                ->with('warning', '请先完成实名认证后再进入游戏');
        }

        if (!$user->isAdult()) {
            return Inertia::render('Game/Blocked', [
                'reason' => '根据国家防沉迷政策，未满18周岁的用户无法进入游戏。',
            ]);
        }

        // 检查SSO配置
        $config = GameSsoConfig::where('game_id', $game->id)->where('enabled', true)->first();
        if (!$config) {
            abort(404, '游戏接入配置未找到');
        }

        // 验证服务器
        $serverId = $request->integer('server_id');
        $server = null;

        if ($serverId) {
            $server = GameServer::where('id', $serverId)->where('game_id', $game->id)->first();
            if (!$server) {
                abort(404, '服务器不存在');
            }
        } else {
            // 自动选择服务器：上次登录的 > 最新开服的
            $lastReport = RoleReport::where('user_id', $user->id)
                ->where('game_id', $game->id)
                ->whereIn('submit_type', [1, 3])
                ->latest()
                ->first();

            if ($lastReport) {
                $server = GameServer::where('id', $lastReport->server_id)
                    ->where('game_id', $game->id)
                    ->first();
            }

            if (!$server) {
                $server = GameServer::where('game_id', $game->id)
                    ->where('status', '!=', GameServerStatus::MAINTENANCE)
                    ->orderBy('open_time', 'desc')
                    ->first();
            }

            if (!$server) {
                abort(404, '该游戏暂无可用服务器');
            }
        }

        if ($server->status === GameServerStatus::MAINTENANCE) {
            return Inertia::render('Game/Play', [
                'game' => $game->load('category'),
                'server' => $server,
                'loginUrl' => $config->login_url,
                'hasConfig' => true,
                'error' => '该服务器正在维护中，请选择其他服务器',
            ]);
        }

        $ssoParams = $ssoService->generateLoginParams($user, $game->id, $server->id);

        return Inertia::render('Game/Play', [
            'game' => $game->load('category'),
            'server' => $server,
            'loginUrl' => $config->login_url,
            'hasConfig' => true,
            'error' => null,
            'ssoToken' => $ssoParams,
        ]);
    }
}
