<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentConfig extends Model
{
    protected $fillable = ['channel', 'config', 'enabled'];

    protected function casts(): array
    {
        return ['config' => 'array', 'enabled' => 'boolean'];
    }
}
