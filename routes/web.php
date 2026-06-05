<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/games', [GameController::class, 'index'])->name('games.index');
Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');
Route::get('/servers', [GameController::class, 'servers'])->name('servers');

// Game play
Route::get('/game/play/{game}', [\App\Http\Controllers\GameController::class, 'play'])->name('game.play');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    // OAuth
    Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

    // Password reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
});

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // User center
    Route::get('/user', [UserController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/user/settings', [UserController::class, 'settings'])->name('user.settings');
    Route::put('/user/settings', [UserController::class, 'updateSettings'])->name('user.settings.update');
    Route::get('/user/orders', [UserController::class, 'orders'])->name('user.orders');

    // Payment
    Route::get('/recharge', [PaymentController::class, 'create'])->name('recharge');
    Route::post('/recharge', [PaymentController::class, 'store'])->name('recharge.store');
    Route::get('/payment/result/{order}', [PaymentController::class, 'result'])->name('payment.result');
});

// Payment callbacks (no CSRF)
Route::post('/payment/notify/{channel}', [PaymentController::class, 'notify'])->name('payment.notify')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
