<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_no', 'user_id', 'game_id', 'server_id',
        'game_account', 'amount', 'status', 'paid_at',
        'product_id', 'product_name', 'product_desc',
        'role_id', 'role_name', 'ext',
        'notify_status', 'notify_times', 'last_notify_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'last_notify_at' => 'datetime',
            'notify_times' => 'integer',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function flows(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentFlow::class, 'order_id');
    }

    public function gameNotifyLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GameNotifyLog::class, 'payment_order_id');
    }

    public function operationLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentOperationLog::class, 'payment_order_id');
    }
}
