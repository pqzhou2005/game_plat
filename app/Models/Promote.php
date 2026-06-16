<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promote extends Model
{
    use HasFactory;

    protected $fillable = [
        'promote_code',
        'game_id',
        'promote_name',
        'promote_type',
        'status',
        'remark',
        'created_by',
        'landing_title',
        'landing_subtitle',
        'landing_hero_image',
        'landing_background',
        'landing_button_text',
        'landing_theme_color',
        'landing_features',
        'landing_content',
    ];

    protected function casts(): array
    {
        return [
            'game_id' => 'integer',
            'status' => 'integer',
            'created_by' => 'integer',
            'landing_features' => 'array',
            'landing_content' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function userAttributions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\UserAttribution::class, 'promote_id');
    }

    /**
     * 落地页链接
     */
    public function getLandingUrlAttribute(): string
    {
        return url("/p/{$this->promote_code}");
    }

    /**
     * 推广链接（路径形式）
     */
    public function getUrlPathAttribute(): string
    {
        return "https://lp.602.com/p/{$this->promote_code}";
    }

    /**
     * 推广链接（参数形式）
     */
    public function getUrlParamAttribute(): string
    {
        return "https://lp.602.com/index.html?promote_code={$this->promote_code}";
    }
}
