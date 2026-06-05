<?php
namespace App\Services;

use App\Models\GameNotifyLog;
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

        $url = $config->pay_notify_url;

        try {
            $response = Http::timeout(10)->post($url, $params);
            $httpCode = $response->status();
            $body = $response->body();

            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['status']) && (int)$json['status'] === 0) {
                    GameNotifyLog::create([
                        'payment_order_id' => $order->id,
                        'game_id' => $order->game_id,
                        'url' => $url,
                        'status' => 'success',
                        'http_code' => $httpCode,
                        'request_params' => $params,
                        'response_body' => mb_substr($body, 0, 2000),
                    ]);

                    $order->update([
                        'notify_status' => 'success',
                        'notify_times' => $order->notify_times + 1,
                        'last_notify_at' => now(),
                    ]);
                    return true;
                }

                // Game dev returned non-zero status (e.g. order duplicate, role not found)
                GameNotifyLog::create([
                    'payment_order_id' => $order->id,
                    'game_id' => $order->game_id,
                    'url' => $url,
                    'status' => 'failed',
                    'http_code' => $httpCode,
                    'request_params' => $params,
                    'response_body' => mb_substr($body, 0, 2000),
                ]);
            } else {
                GameNotifyLog::create([
                    'payment_order_id' => $order->id,
                    'game_id' => $order->game_id,
                    'url' => $url,
                    'status' => 'failed',
                    'http_code' => $httpCode,
                    'request_params' => $params,
                    'response_body' => mb_substr($body, 0, 2000),
                    'error_message' => "HTTP {$httpCode}",
                ]);
            }

            $order->increment('notify_times');
            $order->update(['last_notify_at' => now()]);
            return false;
        } catch (\Exception $e) {
            GameNotifyLog::create([
                'payment_order_id' => $order->id,
                'game_id' => $order->game_id,
                'url' => $url,
                'status' => 'failed',
                'http_code' => 0,
                'request_params' => $params,
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);

            $order->increment('notify_times');
            $order->update(['last_notify_at' => now()]);
            return false;
        }
    }

    public function resetNotifyStatus(PaymentOrder $order): bool
    {
        $order->update([
            'notify_status' => 'pending',
            'notify_times' => 0,
            'last_notify_at' => null,
        ]);
        return $this->notifyGameServer($order);
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
