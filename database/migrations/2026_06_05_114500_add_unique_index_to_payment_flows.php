<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_flows', function (Blueprint $table) {
            $table->dropIndex(['channel', 'channel_order_no']);
            $table->unique(['channel', 'channel_order_no'], 'uq_payment_flows_channel_order');
        });
    }

    public function down(): void
    {
        Schema::table('payment_flows', function (Blueprint $table) {
            $table->dropUnique('uq_payment_flows_channel_order');
            $table->index(['channel', 'channel_order_no']);
        });
    }
};
