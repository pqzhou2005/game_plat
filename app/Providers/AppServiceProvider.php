<?php

namespace App\Providers;

use App\Models\AdminAuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Builder::defaultStringLength(191);

        // 后台登录成功 → 更新登录时间和IP + 审计
        app('events')->listen(Login::class, function (Login $event) {
            if ($event->guard === 'admin' && $event->user) {
                $event->user->forceFill([
                    'last_login_at' => now(),
                    'last_login_ip' => request()->ip(),
                ])->save();
                AdminAuditLog::record('login', 'admin_user', (string)$event->user->id,
                    null, null, '后台登录'
                );
            }
        });

        // 后台退出 → 审计
        app('events')->listen(Logout::class, function (Logout $event) {
            if ($event->guard === 'admin' && $event->user) {
                AdminAuditLog::record('logout', 'admin_user', (string)$event->user->id,
                    null, null, '后台退出'
                );
            }
        });
    }
}
