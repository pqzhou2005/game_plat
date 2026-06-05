<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameEntry extends Model
{
    protected $fillable = ['game_id', 'entry_name', 'entry_url', 'sort'];

    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
