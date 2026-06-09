<?php
namespace App\Models;

use App\Enums\CommonStatus;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $fillable = [
        'title', 'type', 'game_id', 'summary',
        'content', 'is_top', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_top' => 'boolean',
            'status' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', CommonStatus::ACTIVE)
            ->where('published_at', '<=', now())
            ->orderBy('is_top', 'desc')
            ->orderBy('published_at', 'desc');
    }

    public function scopePlatform($query)
    {
        return $query->whereNull('game_id');
    }

    public function scopeForGame($query, int $gameId)
    {
        return $query->where(function ($q) use ($gameId) {
            $q->whereNull('game_id')->orWhere('game_id', $gameId);
        });
    }
}
