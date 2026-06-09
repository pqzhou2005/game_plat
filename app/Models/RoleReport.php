<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleReport extends Model
{
    protected $fillable = [
        'user_id', 'game_id', 'submit_type', 'server_id', 'server_name',
        'role_id', 'role_name', 'role_level', 'zone_id', 'zone_name',
        'profession_id', 'profession_name', 'power', 'vip_level',
        'create_time', 'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'create_time' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
