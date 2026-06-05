<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use Illuminate\Http\Request;

class SsoController extends Controller
{
    public function __construct(private SsoService $ssoService) {}

    public function token(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'server_id' => 'required|integer',
        ]);

        $params = $this->ssoService->generateLoginParams(
            $request->user(),
            (int)$request->game_id,
            (int)$request->server_id
        );

        return response()->json($params);
    }
}
