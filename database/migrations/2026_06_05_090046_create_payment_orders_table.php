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
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->nullable()->constrained('games');
            $table->foreignId('server_id')->nullable()->constrained('game_servers');
            $table->string('game_account', 100)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending')->comment('pending/success/failed/closed');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
