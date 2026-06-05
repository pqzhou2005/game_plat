<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'short_name', 'logo', 'banner',
        'screenshots', 'description', 'game_type', 'tags',
        'developer', 'operator', 'status', 'is_recommend',
        'is_hot', 'is_new', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'screenshots' => 'array',
            'tags' => 'array',
            'status' => 'integer',
            'is_recommend' => 'boolean',
            'is_hot' => 'boolean',
            'is_new' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GameCategory::class, 'category_id');
    }

    public function servers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GameServer::class);
    }

    public function entries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GameEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
