<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // TODO: 配置邮件服务后接入真正的密码重置
        // 目前返回提示信息
        return back()->with('success', '如果该邮箱已注册，重置链接已发送');
    }
}
