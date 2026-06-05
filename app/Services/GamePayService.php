<?php
namespace App\Services;

use App\Models\GameSsoConfig;
use App\Models\PaymentOrder;
use Illuminate\Support\Facades\Http;

class GamePayService
{
    public function notifyGameServer(PaymentOrder $order): bool
    {
        $config = GameSsoConfig::where('game_id', $order->game_id)->first();
        if (!$config || !$config->pay_notify_url) {
            return false;
        }

        $params = [
            'uid'      => (string)$order->user_id,
            'serverId' => (string)$order->server_id,
            'orderId'  => $order->order_no,
            'money'    => (string)$order->amount,
            'goodsId'  => $order->product_id ?? '',
            'time'     => (string)time(),
            'rid'      => $order->role_id ?? '',
            'ext'      => $order->ext ?? '',
        ];

        ksort($params);
        $sign = strtolower(md5(http_build_query($params) . $config->pay_key));
        $params['sign'] = $sign;

        try {
            $response = Http::timeout(10)->post($config->pay_notify_url, $params);

            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['status']) && (int)$body['status'] === 0) {
                    $order->update([
                        'notify_status' => 'success',
                        'notify_times' => $order->notify_times + 1,
                        'last_notify_at' => now(),
                    ]);
                    return true;
                }
            }

            $order->increment('notify_times');
            $order->update(['last_notify_at' => now()]);
            return false;
        } catch (\Exception $e) {
            $order->increment('notify_times');
            $order->update(['last_notify_at' => now()]);
            return false;
        }
    }

    public function retryFailedNotifications(int $maxAttempts = 3): int
    {
        $orders = PaymentOrder::where('status', 'success')
            ->where('notify_status', 'pending')
            ->where('notify_times', '<', $maxAttempts)
            ->get();

        $successCount = 0;
        foreach ($orders as $order) {
            if ($this->notifyGameServer($order)) {
                $successCount++;
            }
        }

        return $successCount;
    }
}
