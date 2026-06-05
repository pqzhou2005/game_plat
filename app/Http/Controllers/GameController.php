<?php
namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameCategory;
use App\Models\GameServer;
use App\Models\GameSsoConfig;
use App\Models\RoleReport;
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
            'categories' => GameCategory::where('status', 1)->orderBy('sort')->get(),
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

        return Inertia::render('Games/Show', [
            'game' => $game,
            'recommended' => Game::active()
                ->where('is_recommend', true)
                ->where('id', '!=', $game->id)
                ->take(4)
                ->get(),
            'recentServerIds' => $recentServerIds,
        ]);
    }

    public function servers(Request $request)
    {
        $query = GameServer::with('game')->orderBy('open_time', 'desc');

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        return Inertia::render('Servers', [
            'servers' => $query->paginate(30),
            'games' => Game::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function play(Game $game, Request $request)
    {
        // 检查游戏状态
        if ($game->status === 0) {
            abort(410, '该游戏已下架');
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
            if ($server->status === 3) {
                return Inertia::render('Game/Play', [
                    'game' => $game->load('category'),
                    'server' => $server,
                    'loginUrl' => $config->login_url,
                    'hasConfig' => true,
                    'error' => '该服务器正在维护中，请选择其他服务器',
                ]);
            }
        } else {
            // 未选择服务器，跳回游戏详情页
            return redirect()->route('games.show', $game->id)
                ->with('warning', '请先选择服务器');
        }

        return Inertia::render('Game/Play', [
            'game' => $game->load('category'),
            'server' => $server,
            'loginUrl' => $config->login_url,
            'hasConfig' => true,
            'error' => null,
        ]);
    }
}
