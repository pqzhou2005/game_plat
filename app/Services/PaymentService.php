<?php
namespace App\Services;

use App\Models\PaymentConfig;
use App\Models\PaymentOrder;
use App\Models\PaymentFlow;
use Illuminate\Support\Str;

class PaymentService
{
    public function createOrder(int $userId, float $amount, ?int $gameId = null, ?int $serverId = null, ?string $gameAccount = null): PaymentOrder
    {
        $order = PaymentOrder::create([
            'order_no' => date('YmdHis') . Str::random(8),
            'user_id' => $userId,
            'game_id' => $gameId,
            'server_id' => $serverId,
            'game_account' => $gameAccount,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return $order;
    }

    public function createPaymentData(PaymentOrder $order, string $channel): array
    {
        if ($channel === 'alipay') {
            return [
                'out_trade_no' => $order->order_no,
                'total_amount' => $order->amount,
                'subject' => '602游戏平台 - 充值',
                'channel' => 'alipay',
            ];
        }

        if ($channel === 'wechat') {
            return [
                'out_trade_no' => $order->order_no,
                'total_fee' => $order->amount * 100,
                'body' => '602游戏平台 - 充值',
                'channel' => 'wechat',
            ];
        }

        throw new \InvalidArgumentException('不支持的支付渠道');
    }

    public function handleNotify(string $channel, array $data): string
    {
        $orderNo = $data['out_trade_no'] ?? '';
        $channelOrderNo = $data['trade_no'] ?? '';

        $order = PaymentOrder::where('order_no', $orderNo)->first();
        if (!$order || $order->status === 'success') {
            return 'success';
        }

        PaymentFlow::create([
            'order_id' => $order->id,
            'channel' => $channel,
            'channel_order_no' => $channelOrderNo,
            'channel_data' => $data,
            'status' => 'success',
        ]);

        $order->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // Notify game developer server
        if ($order->game_id) {
            try {
                app(\App\Services\GamePayService::class)->notifyGameServer($order);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Game pay notify failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }
        }

        return 'success';
    }
}
