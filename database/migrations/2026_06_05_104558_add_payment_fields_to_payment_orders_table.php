<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->string('product_id', 100)->nullable()->after('amount')->comment('商品ID');
            $table->string('product_name', 200)->nullable()->after('product_id')->comment('商品名');
            $table->string('product_desc', 500)->nullable()->after('product_name')->comment('商品描述');
            $table->string('role_id', 100)->nullable()->after('product_desc')->comment('游戏角色ID');
            $table->string('role_name', 100)->nullable()->after('role_id')->comment('游戏角色名');
            $table->string('ext', 500)->nullable()->after('role_name')->comment('透传参数');
            $table->string('notify_status', 20)->default('pending')->after('ext')->comment('pending/success/failed 游戏方回调状态');
            $table->integer('notify_times')->default(0)->after('notify_status')->comment('回调次数');
            $table->timestamp('last_notify_at')->nullable()->after('notify_times')->comment('最后回调时间');
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn([
                'product_id', 'product_name', 'product_desc',
                'role_id', 'role_name', 'ext',
                'notify_status', 'notify_times', 'last_notify_at',
            ]);
        });
    }
};
