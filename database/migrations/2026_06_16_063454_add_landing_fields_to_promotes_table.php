<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotes', function (Blueprint $table) {
            $table->string('landing_title', 100)->nullable()->after('remark');
            $table->string('landing_subtitle', 255)->nullable()->after('landing_title');
            $table->string('landing_hero_image', 500)->nullable()->after('landing_subtitle');
            $table->string('landing_background', 500)->nullable()->after('landing_hero_image');
            $table->string('landing_button_text', 50)->default('立即注册')->after('landing_background');
            $table->string('landing_theme_color', 20)->default('#ff7a00')->after('landing_button_text');
            $table->json('landing_features')->nullable()->after('landing_theme_color');
            $table->json('landing_content')->nullable()->after('landing_features');
        });
    }

    public function down(): void
    {
        Schema::table('promotes', function (Blueprint $table) {
            $table->dropColumn([
                'landing_title',
                'landing_subtitle',
                'landing_hero_image',
                'landing_background',
                'landing_button_text',
                'landing_theme_color',
                'landing_features',
                'landing_content',
            ]);
        });
    }
};
