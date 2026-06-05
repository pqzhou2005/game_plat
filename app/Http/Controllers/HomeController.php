<?php
namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameServer;
use App\Models\GameCategory;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $recommendedGames = Game::active()
            ->where('is_recommend', true)
            ->orderBy('sort')
            ->take(8)
            ->get();

        $hotGames = Game::active()
            ->where('is_hot', true)
            ->orderBy('sort')
            ->take(8)
            ->get();

        $latestServers = GameServer::with('game')
            ->where('open_time', '>=', now()->subDays(7))
            ->orderBy('open_time', 'desc')
            ->take(10)
            ->get();

        $categories = GameCategory::where('status', 1)
            ->orderBy('sort')
            ->get();

        $allGames = Game::active()
            ->with('category')
            ->orderBy('sort')
            ->paginate(24);

        return Inertia::render('Home', [
            'recommendedGames' => $recommendedGames,
            'hotGames' => $hotGames,
            'latestServers' => $latestServers,
            'categories' => $categories,
            'allGames' => $allGames,
        ]);
    }
}
