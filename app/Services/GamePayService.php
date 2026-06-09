<?php
namespace App\Services;

use App\Enums\NotifyStatus;
use App\Enums\PaymentOrderStatus;
use App\Models\GameNotifyLog;
use App\Models\GameSsoConfig;
use App\Models\PaymentOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GamePayService
{
    public function notifyGameServer(PaymentOrder $order): bool
    {
        $config = GameSsoConfig::where('game_id', $order->game_id)->first();
        if (!$config || !$config->pay_notify_url) {
            Log::warning('发货失败：游戏未配置发货回调地址', [
                'order_id' => $order->id, 'order_no' => $order->order_no, 'game_id' => $order->game_id,
            ]);
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
                        'status' => NotifyStatus::SUCCESS,
                        'http_code' => $httpCode,
                        'request_params' => $params,
                        'response_body' => mb_substr($body, 0, 2000),
                    ]);

                    $this->markNotifyAttempt($order, true);
                    return true;
                }

                // Game dev returned non-zero status (e.g. order duplicate, role not found)
                GameNotifyLog::create([
                    'payment_order_id' => $order->id,
                    'game_id' => $order->game_id,
                    'url' => $url,
                    'status' => NotifyStatus::FAILED,
                    'http_code' => $httpCode,
                    'request_params' => $params,
                    'response_body' => mb_substr($body, 0, 2000),
                ]);
                Log::warning('发货失败：游戏方回调返回失败状态', [
                    'order_id' => $order->id, 'order_no' => $order->order_no,
                    'http_code' => $httpCode, 'response' => mb_substr($body, 0, 500),
                    'notify_times' => $order->notify_times + 1,
                ]);
            } else {
                GameNotifyLog::create([
                    'payment_order_id' => $order->id,
                    'game_id' => $order->game_id,
                    'url' => $url,
                    'status' => NotifyStatus::FAILED,
                    'http_code' => $httpCode,
                    'request_params' => $params,
                    'response_body' => mb_substr($body, 0, 2000),
                    'error_message' => "HTTP {$httpCode}",
                ]);
                Log::warning('发货失败：HTTP请求异常', [
                    'order_id' => $order->id, 'order_no' => $order->order_no,
                    'http_code' => $httpCode, 'notify_times' => $order->notify_times + 1,
                ]);
            }

            $this->markNotifyAttempt($order, false);
            return false;
        } catch (\Exception $e) {
            GameNotifyLog::create([
                'payment_order_id' => $order->id,
                'game_id' => $order->game_id,
                'url' => $url,
                'status' => NotifyStatus::FAILED,
                'http_code' => 0,
                'request_params' => $params,
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);
            Log::warning('发货失败：请求异常', [
                'order_id' => $order->id, 'order_no' => $order->order_no,
                'error' => $e->getMessage(), 'notify_times' => $order->notify_times + 1,
            ]);

            $this->markNotifyAttempt($order, false);
            return false;
        }
    }

    /**
     * 递增重试次数，超过3次自动标记为发货失败
     */
    private function markNotifyAttempt(PaymentOrder $order, bool $success): void
    {
        if ($success) {
            $order->update([
                'notify_status' => NotifyStatus::SUCCESS,
                'notify_times' => $order->notify_times + 1,
                'last_notify_at' => now(),
            ]);
        } else {
            $order->increment('notify_times');
            $order->update(['last_notify_at' => now()]);

            // 超过3次自动标记失败
            if ($order->notify_times >= 3) {
                $order->update(['notify_status' => NotifyStatus::FAILED]);
                Log::warning('发货已达上限，标记为失败', [
                    'order_id' => $order->id, 'order_no' => $order->order_no,
                    'notify_times' => $order->notify_times,
                ]);
            }
        }
    }

    public function resetNotifyStatus(PaymentOrder $order): bool
    {
        $order->update([
            'notify_status' => NotifyStatus::PENDING,
            'notify_times' => 0,
            'last_notify_at' => null,
        ]);
        return $this->notifyGameServer($order);
    }

    public function retryFailedNotifications(int $maxAttempts = 3): int
    {
        $orders = PaymentOrder::where('status', PaymentOrderStatus::SUCCESS)
            ->where('notify_status', NotifyStatus::PENDING)
            ->where('notify_times', '<', $maxAttempts)
            ->get();

        $successCount = 0;
        $failCount = 0;
        foreach ($orders as $order) {
            if ($this->notifyGameServer($order)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        if ($failCount > 0) {
            Log::warning('定时重试发货完成', [
                '总处理' => count($orders), '成功' => $successCount, '失败' => $failCount,
            ]);
        }

        return $successCount;
    }
}
