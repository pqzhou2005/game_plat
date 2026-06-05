<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerOpenReport extends Model
{
    protected $fillable = [
        'game_id', 'project', 'open_server', 'open_server_time',
        'created_role_num', 'preset_role_num', 'pay_num', 'preset_pay_num',
        'preset_open_server', 'preset_open_server_time', 'sur_dep_not_ser_num',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'open_server_time' => 'datetime',
            'preset_open_server_time' => 'datetime',
            'raw_data' => 'array',
        ];
    }
}
