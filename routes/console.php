<?php

use App\Services\GamePayService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 每2分钟重试发货失败的订单（最多3次）
Schedule::call(function () {
    $retried = app(GamePayService::class)->retryFailedNotifications(3);
    if ($retried > 0) {
        \Illuminate\Support\Facades\Log::info('发货重试完成', ['success_count' => $retried]);
    }
})->everyTwoMinutes()->name('payment:retry-notify');
