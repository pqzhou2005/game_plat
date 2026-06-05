<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameServer extends Model
{
    protected $fillable = ['game_id', 'name', 'server_id', 'open_time', 'status', 'is_recommend'];

    protected function casts(): array
    {
        return [
            'open_time' => 'datetime',
            'status' => 'integer',
            'is_recommend' => 'boolean',
        ];
    }

    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
