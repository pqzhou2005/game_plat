<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Services\RealNameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private RealNameService $realNameService,
    ) {}

    public function create(Request $request)
    {
        return Inertia::render('Auth/Register', [
            'redirect' => $request->input('redirect', ''),
        ]);
    }

    public function store(RegisterRequest $request)
    {
        $user = $this->authService->createUser($request->validated());

        if ($request->filled('real_name') && $request->filled('id_card')) {
            // 调中宣部 NPPA 接口实名认证
            $result = $this->realNameService->verify(
                $request->real_name,
                $request->id_card,
                $user->id
            );

            if ($result['code'] === 0) {
                $user->update([
                    'real_name' => $request->real_name,
                    'id_card' => strtoupper($request->id_card),
                    'id_card_verified_at' => now(),
                ]);
            } else {
                // 实名失败不阻塞注册，但提醒用户
                session()->flash('warning', $result['msg'] ?? '实名认证失败，请稍后重试');
            }
        }

        Auth::login($user);

        // 注册成功后跳转到 redirect 页或首页
        $redirect = $request->input('redirect', '');
        if ($redirect && !str_contains($redirect, '/login') && !str_contains($redirect, '/register')) {
            return redirect($redirect);
        }

        return redirect('/');
    }
}
