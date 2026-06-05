<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->tinyInteger('submit_type')->comment('1=创角 2=升级 3=进游戏 4=改名');
            $table->integer('server_id');
            $table->string('server_name', 100)->nullable();
            $table->string('role_id', 100);
            $table->string('role_name', 100)->nullable();
            $table->integer('role_level')->nullable();
            $table->integer('zone_id')->nullable();
            $table->string('zone_name', 100)->nullable();
            $table->string('profession_id', 50)->nullable();
            $table->string('profession_name', 50)->nullable();
            $table->bigInteger('power')->nullable();
            $table->integer('vip_level')->nullable();
            $table->dateTime('create_time')->nullable()->comment('游戏服时间');
            $table->json('raw_data')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_reports');
    }
};
