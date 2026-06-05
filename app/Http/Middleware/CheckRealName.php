<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRealName
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->isRealNameVerified()) {
            return redirect()->route('user.settings')
                ->with('warning', '请先完成实名认证');
        }

        return $next($request);
    }
}
