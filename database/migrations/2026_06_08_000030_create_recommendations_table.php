<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('position_code', 50)->comment('position: banner/hot/new/featured/sidebar');
            $table->string('title', 200);
            $table->string('subtitle', 300)->nullable();
            $table->string('image', 500)->nullable();
            $table->string('target_type', 20)->default('game')->comment('game/url/none');
            $table->foreignId('target_id')->nullable()->constrained('games')->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->integer('sort')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamps();
            $table->index('position_code');
            $table->index(['position_code', 'status', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
