<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function create(Request $request)
    {
        return Inertia::render('Auth/Login', [
            'redirect' => $request->input('redirect', ''),
        ]);
    }

    public function store(LoginRequest $request)
    {
        $user = $this->authService->attemptLogin(
            $request->only('username', 'password'),
            $request->ip(),
            $request->userAgent()
        );

        Auth::login($user, $request->boolean('remember'));

        // 优先使用 redirect 参数，回退到 intended（由 auth 中间件设置）
        $redirect = $request->input('redirect', '');
        if ($redirect && !str_contains($redirect, '/login') && !str_contains($redirect, '/register')) {
            return redirect($redirect);
        }

        return redirect()->intended('/');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
