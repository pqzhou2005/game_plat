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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('game_categories');
            $table->string('name', 100);
            $table->string('short_name', 50)->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('banner', 500)->nullable();
            $table->json('screenshots')->nullable();
            $table->text('description')->nullable();
            $table->string('game_type', 20)->nullable()->comment('页游/微端/手游');
            $table->json('tags')->nullable();
            $table->string('developer', 100)->nullable();
            $table->string('operator', 100)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_recommend')->default(0);
            $table->tinyInteger('is_hot')->default(0);
            $table->tinyInteger('is_new')->default(0);
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'sort']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
