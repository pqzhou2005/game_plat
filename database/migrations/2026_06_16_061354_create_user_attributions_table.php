<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_attributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('promote_id');
            $table->string('promote_code', 32)->nullable();
            $table->string('attribution_type', 30)->default('landing');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('user_id', 'uk_user_id');
            $table->index('promote_id', 'idx_promote_id');
            $table->index('promote_code', 'idx_promote_code');
            $table->index('attribution_type', 'idx_attribution_type');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_attributions');
    }
};
