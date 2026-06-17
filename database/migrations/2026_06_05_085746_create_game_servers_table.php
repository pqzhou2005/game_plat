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
        Schema::create('game_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('server_id', 50)->nullable();
            $table->dateTime('open_time');
            $table->tinyInteger('status')->default(1)->comment('1火爆 2推荐 3维护 4已满');
            $table->tinyInteger('is_recommend')->default(0);
            $table->timestamps();
            $table->index('open_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_servers');
    }
};
