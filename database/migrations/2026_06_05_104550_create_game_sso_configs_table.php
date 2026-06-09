<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sso_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->unique()->constrained('games')->cascadeOnDelete();
            $table->string('platform_id', 50)->comment('平台ID(游戏方提供)');
            $table->string('login_key', 255)->comment('登录密钥 lkey(平台生成,分发给游戏方)');
            $table->string('login_url', 500)->comment('游戏研发方提供的登录接口地址');
            $table->string('pay_key', 255)->comment('支付密钥 payKey(平台生成,分发给游戏方)');
            $table->string('pay_notify_url', 500)->nullable()->comment('支付回调通知游戏方发货地址');
            $table->tinyInteger('enabled')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sso_configs');
    }
};
