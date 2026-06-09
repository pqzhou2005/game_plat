<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOperationLog extends Model
{
    protected $fillable = ['payment_order_id', 'action', 'operator', 'remark', 'extra'];

    protected function casts(): array
    {
        return ['extra' => 'array'];
    }

    public function paymentOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    public static function log(int $orderId, string $action, ?string $remark = null, ?array $extra = null): self
    {
        $operator = null;
        if (auth('admin')->check()) {
            $operator = auth('admin')->user()->username;
        }

        return static::create([
            'payment_order_id' => $orderId,
            'action' => $action,
            'operator' => $operator,
            'remark' => $remark,
            'extra' => $extra,
        ]);
    }
}
