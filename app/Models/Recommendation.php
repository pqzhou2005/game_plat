<?php
namespace App\Models;

use App\Enums\CommonStatus;
use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'position_code', 'title', 'subtitle', 'image',
        'target_type', 'target_id', 'url', 'sort',
        'status', 'start_at', 'end_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'sort' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class, 'target_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', CommonStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            })
            ->orderBy('sort');
    }

    public function scopePosition($query, string $code)
    {
        return $query->where('position_code', $code);
    }
}
