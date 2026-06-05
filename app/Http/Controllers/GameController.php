<?php
namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameCategory;
use App\Models\GameServer;
use Inertia\Inertia;
use Illuminate\Http\Request;

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

        return Inertia::render('Games/Show', [
            'game' => $game,
            'recommended' => Game::active()
                ->where('is_recommend', true)
                ->where('id', '!=', $game->id)
                ->take(4)
                ->get(),
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
}
