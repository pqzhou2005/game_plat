<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->constrained('payment_orders')->cascadeOnDelete();
            $table->string('action', 50)->comment('top_up/refund/retry_notify/note');
            $table->string('operator', 100)->nullable()->comment('操作人');
            $table->string('remark', 500)->nullable()->comment('操作备注');
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->index(['payment_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_operation_logs');
    }
};
