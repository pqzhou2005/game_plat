<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameNotifyLog extends Model
{
    protected $fillable = [
        'payment_order_id', 'game_id', 'url', 'status',
        'http_code', 'request_params', 'response_body', 'error_message',
    ];

    protected function casts(): array
    {
        return ['request_params' => 'array'];
    }

    public function paymentOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }
}
