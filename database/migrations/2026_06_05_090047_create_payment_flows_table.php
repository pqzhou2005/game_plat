<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('payment_orders')->cascadeOnDelete();
            $table->string('channel', 20)->comment('wechat/alipay');
            $table->string('channel_order_no', 100)->nullable();
            $table->json('channel_data')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending/success/failed/refund');
            $table->timestamps();
            $table->index(['channel', 'channel_order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_flows');
    }
};
