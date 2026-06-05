<?php

/**
 * 游戏方服务端接口
 * 不依赖 session，由游戏方服务器调用，内部做签名验证
 */
use Illuminate\Support\Facades\Route;

Route::post('/game/pay/notify', [App\Http\Controllers\Api\PaymentNotifyController::class, 'notify']);
Route::post('/server/auto-open', [App\Http\Controllers\Api\ServerOpenController::class, 'autoOpen']);
