<?php
namespace App\Http\Controllers;

use App\Models\Promote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingController extends Controller
{
    /**
     * 展示推广落地页
     */
    public function show(string $promoteCode)
    {
        $promote = Promote::where('promote_code', $promoteCode)
            ->where('status', 1)
            ->where('promote_type', 'landing')
            ->with('game')
            ->first();

        // 不存在、停用、类型不匹配 => 404
        if (!$promote) {
            abort(404);
        }

        return Inertia::render('Landing/Show', [
            'promote' => [
                'promote_code' => $promote->promote_code,
                'promote_name' => $promote->promote_name,
                'landing_title' => $promote->landing_title,
                'landing_subtitle' => $promote->landing_subtitle,
                'landing_hero_image' => $promote->landing_hero_image,
                'landing_background' => $promote->landing_background,
                'landing_button_text' => $promote->landing_button_text ?: '立即注册',
                'landing_theme_color' => $promote->landing_theme_color ?: '#ff7a00',
                'landing_features' => $promote->landing_features ?: [],
                'game_name' => $promote->game?->name,
            ],
        ]);
    }
}
