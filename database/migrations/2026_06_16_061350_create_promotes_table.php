<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotes', function (Blueprint $table) {
            $table->id();
            $table->string('promote_code', 32)->unique();
            $table->unsignedBigInteger('game_id')->nullable();
            $table->string('promote_name', 100);
            $table->string('promote_type', 30)->default('landing');
            $table->tinyInteger('status')->default(1);
            $table->string('remark', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->index('game_id', 'idx_game_id');
            $table->index('promote_type', 'idx_promote_type');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotes');
    }
};
