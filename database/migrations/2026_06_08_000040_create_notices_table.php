<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('type', 30)->default('platform')->comment('platform/game/maintenance/activity/merge');
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->string('summary', 500)->nullable();
            $table->text('content')->nullable();
            $table->tinyInteger('is_top')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index('game_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
