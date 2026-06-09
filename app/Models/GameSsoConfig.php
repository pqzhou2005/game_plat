<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSsoConfig extends Model
{
    protected $fillable = [
        'game_id', 'platform_id', 'login_key', 'login_url',
        'pay_key', 'pay_notify_url', 'enabled',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
