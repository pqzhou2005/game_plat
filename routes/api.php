<?php

/**
 * 前端 API（依赖 session 登录态）
 * 游戏方服务端接口见 routes/api-game.php
 */
use App\Http\Controllers\Api\SsoController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

// SSO - 前端获取登录签名参数
Route::get('/sso/token', [SsoController::class, 'token'])->middleware('auth');

// 角色上报（前端 iframe 内 postMessage 触发）
Route::post('/game/role', [RoleController::class, 'report'])->middleware('auth');
Route::post('/game/roles', [RoleController::class, 'batchReport'])->middleware('auth');

// Payment API（前端支付弹窗调用）
Route::post('/payments/create', [\App\Http\Controllers\PaymentController::class, 'apiCreate'])
    ->middleware('auth');
Route::get('/payments/status/{orderNo}', [\App\Http\Controllers\PaymentController::class, 'status']);
