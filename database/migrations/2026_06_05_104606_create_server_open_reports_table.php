<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_open_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games');
            $table->string('project', 100);
            $table->integer('open_server');
            $table->dateTime('open_server_time')->nullable();
            $table->integer('created_role_num')->default(0);
            $table->integer('preset_role_num')->default(0);
            $table->integer('pay_num')->default(0);
            $table->integer('preset_pay_num')->default(0);
            $table->integer('preset_open_server')->default(0);
            $table->dateTime('preset_open_server_time')->nullable();
            $table->integer('sur_dep_not_ser_num')->default(0);
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_open_reports');
    }
};
