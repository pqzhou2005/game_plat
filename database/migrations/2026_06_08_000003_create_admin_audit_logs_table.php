<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operator', 100)->nullable()->comment('操作人用户名');
            $table->string('action', 50)->comment('操作类型: top_up/refund/retry_notify/update_config/update_user/update_sso');
            $table->string('target_type', 50)->nullable()->comment('操作目标类型: payment_order/payment_config/game_sso_config/user');
            $table->string('target_id', 50)->nullable()->comment('操作目标ID');
            $table->json('before_data')->nullable()->comment('操作前数据');
            $table->json('after_data')->nullable()->comment('操作后数据');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->string('ip', 45)->nullable()->comment('操作IP');
            $table->timestamps();
            $table->index(['action', 'created_at']);
            $table->index('operator');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
