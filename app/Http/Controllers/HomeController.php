<?php
namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameServer;
use App\Models\GameCategory;
use App\Models\Notice;
use App\Models\Recommendation;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // 从推荐位读取，支持运营配置
        $banners = Recommendation::active()->position('banner')->with('game')->get();
        $hotItems = Recommendation::active()->position('hot')->with('game')->get();
        $newItems = Recommendation::active()->position('new')->with('game')->get();
        $featuredItems = Recommendation::active()->position('featured')->with('game')->get();

        // 推荐位不够时用数据库标记的游戏填充
        $recommendedGames = Game::active()->where('is_recommend', true)->orderBy('sort')->take(8)->get();
        $hotGames = Game::active()->where('is_hot', true)->orderBy('sort')->take(8)->get();

        $latestServers = GameServer::with('game')
            ->where('open_time', '>=', now()->subDays(7))
            ->orderBy('open_time', 'desc')
            ->take(10)
            ->get();

        $categories = GameCategory::active()->orderBy('sort')->get();

        $allGames = Game::active()->with('category')->orderBy('sort')->paginate(24);

        $notices = Notice::active()->platform()->take(6)->get();

        $gameNotices = Notice::active()->whereNotNull('game_id')->with('game')->take(4)->get();

        // 当前用户最近玩过的游戏（用于首页登录浮层"最近在玩"）
        $recentRoleReports = [];
        if (Auth::check()) {
            $recentRoleReports = \App\Models\RoleReport::where('user_id', Auth::id())
                ->whereIn('submit_type', [1, 3])
                ->with('game:id,name,logo')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->unique('game_id')
                ->values();
        }

        return Inertia::render('Home', [
            'banners' => $banners,
            'hotItems' => $hotItems,
            'newItems' => $newItems,
            'featuredItems' => $featuredItems,
            'recommendedGames' => $recommendedGames,
            'hotGames' => $hotGames,
            'latestServers' => $latestServers,
            'categories' => $categories,
            'allGames' => $allGames,
            'notices' => $notices,
            'gameNotices' => $gameNotices,
            'recentRoleReports' => $recentRoleReports,
        ]);
    }
}
