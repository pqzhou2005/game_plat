<?php

use App\Http\Controllers\Api\SsoController;
use App\Http\Controllers\Api\PaymentNotifyController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServerOpenController;
use Illuminate\Support\Facades\Route;

// SSO - 前端获取登录签名参数
Route::get('/sso/token', [SsoController::class, 'token'])->middleware('auth');

// 支付回调 - 游戏方调用的发货结果通知（由游戏方服务器调用）
Route::post('/game/pay/notify', [PaymentNotifyController::class, 'notify']);

// 角色上报
Route::post('/game/role', [RoleController::class, 'report'])->middleware('auth');
Route::post('/game/roles', [RoleController::class, 'batchReport'])->middleware('auth');

// 自动开服
Route::post('/server/auto-open', [ServerOpenController::class, 'autoOpen']);

// Payment API
Route::post('/payments/create', [\App\Http\Controllers\PaymentController::class, 'apiCreate'])
    ->middleware('auth');
Route::get('/payments/status/{orderNo}', [\App\Http\Controllers\PaymentController::class, 'status']);
