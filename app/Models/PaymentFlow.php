<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFlow extends Model
{
    protected $fillable = ['order_id', 'channel', 'channel_order_no', 'channel_data', 'status'];

    protected function casts(): array
    {
        return ['channel_data' => 'array'];
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }
}
