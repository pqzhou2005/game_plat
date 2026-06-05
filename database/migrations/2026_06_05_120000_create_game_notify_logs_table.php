<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_notify_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->constrained('payment_orders')->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained('games');
            $table->string('url', 500)->nullable()->comment('回调地址');
            $table->string('status', 20)->comment('success/failed');
            $table->integer('http_code')->nullable()->comment('HTTP状态码');
            $table->text('request_params')->nullable()->comment('请求参数');
            $table->text('response_body')->nullable()->comment('响应内容');
            $table->string('error_message', 500)->nullable()->comment('错误信息');
            $table->timestamps();
            $table->index(['payment_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_notify_logs');
    }
};
