<?php
namespace App\Services;

use App\Enums\PaymentFlowStatus;
use App\Enums\PaymentOrderStatus;
use App\Models\PaymentConfig;
use App\Models\PaymentOrder;
use App\Models\PaymentFlow;
use Illuminate\Support\Str;
use Yansongda\Pay\Pay;

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
            'status' => PaymentOrderStatus::PENDING,
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

    /**
     * 处理支付网关异步通知
     * 1. 用 yansongda/pay 验证回调签名
     * 2. 校验支付金额和订单金额一致
     * 3. 防止重复通知
     */
    public function handleNotify(string $channel, array $data): string
    {
        try {
            // 1. 验证支付网关签名，获取已验证的支付结果
            $config = $this->getChannelConfig($channel);
            $pay = Pay::{$channel}($config);
            $result = $pay->callback($data);

            // 2. 解析支付金额（支付宝返回元，微信返回分）
            if ($channel === 'alipay') {
                $paidAmount = (float)($result->total_amount ?? 0);
            } else {
                $paidAmount = (float)($result->total_fee ?? 0) / 100;
            }

            $orderNo = (string)($result->out_trade_no ?? '');
            $channelOrderNo = (string)($result->trade_no ?? '');

            if (empty($orderNo)) {
                \Illuminate\Support\Facades\Log::error('支付通知缺少订单号', ['channel' => $channel, 'data' => $data]);
                return 'fail';
            }

            $order = PaymentOrder::where('order_no', $orderNo)->first();
            if (!$order) {
                \Illuminate\Support\Facades\Log::error('支付通知订单不存在', ['order_no' => $orderNo]);
                return 'fail';
            }

            // 3. 防止重复通知
            if ($order->status === PaymentOrderStatus::SUCCESS) {
                return 'success';
            }

            // 4. 金额校验 — 防止回调金额被篡改
            if (abs($paidAmount - (float)$order->amount) > 0.01) {
                \Illuminate\Support\Facades\Log::error('支付金额不匹配', [
                    'order_no' => $orderNo,
                    'expected' => $order->amount,
                    'actual' => $paidAmount,
                ]);
                return 'fail';
            }

            // 5. 幂等处理 — 检查渠道订单号是否已处理过
            $existingFlow = PaymentFlow::where('channel', $channel)
                ->where('channel_order_no', $channelOrderNo)
                ->first();

            if ($existingFlow) {
                // 已处理过该渠道订单，直接返回成功
                return 'success';
            }

            // 6. 写入支付流水（DB唯一约束 + try-catch 防并发重复）
            try {
                PaymentFlow::create([
                    'order_id' => $order->id,
                    'channel' => $channel,
                    'channel_order_no' => $channelOrderNo,
                    'channel_data' => json_decode(json_encode($result), true),
                    'status' => PaymentFlowStatus::SUCCESS,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'uq_payment_flows_channel_order')) {
                    // 唯一索引冲突 → 另一请求已处理，直接返回成功
                    return 'success';
                }
                throw $e;
            }

            // 7. 更新订单状态
            $order->update([
                'status' => PaymentOrderStatus::SUCCESS,
                'paid_at' => now(),
            ]);

            // 8. 通知游戏方发货
            if ($order->game_id) {
                try {
                    $notifyOk = app(GamePayService::class)->notifyGameServer($order);
                    if (!$notifyOk) {
                        \Illuminate\Support\Facades\Log::warning('支付回调后首次发货失败', [
                            'order_id' => $order->id, 'order_no' => $order->order_no,
                            'game_id' => $order->game_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('游戏发货通知异常', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return 'success';
        } catch (\Yansongda\Pay\Exception\ContainerException $e) {
            \Illuminate\Support\Facades\Log::error('支付回调验签失败', ['channel' => $channel, 'error' => $e->getMessage()]);
            return 'fail';
        } catch (\Yansongda\Pay\Exception\ServiceException $e) {
            \Illuminate\Support\Facades\Log::error('支付回调服务异常', ['channel' => $channel, 'error' => $e->getMessage()]);
            return 'fail';
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('支付回调未知异常', ['channel' => $channel, 'error' => $e->getMessage()]);
            return 'fail';
        }
    }

    /**
     * 退款
     */
    public function refund(PaymentOrder $order): array
    {
        if ($order->status !== PaymentOrderStatus::SUCCESS) {
            return ['code' => 1, 'msg' => '该订单无法退款'];
        }

        $flow = PaymentFlow::where('order_id', $order->id)
            ->where('status', PaymentFlowStatus::SUCCESS)
            ->first();

        if (!$flow || !$flow->channel_order_no) {
            return ['code' => 1, 'msg' => '未找到支付流水，无法退款'];
        }

        $channel = $flow->channel;
        $config = $this->getChannelConfig($channel);

        if (empty($config)) {
            return ['code' => 1, 'msg' => '支付渠道未配置'];
        }

        try {
            $pay = Pay::{$channel}($config);

            if ($channel === 'alipay') {
                $result = $pay->refund([
                    'out_trade_no' => $order->order_no,
                    'trade_no' => $flow->channel_order_no,
                    'refund_amount' => $order->amount,
                    'refund_reason' => '用户申请退款',
                ]);
            } else {
                // WeChat
                $result = $pay->refund([
                    'out_trade_no' => $order->order_no,
                    'out_refund_no' => $order->order_no . 'R',
                    'total_fee' => (int)($order->amount * 100),
                    'refund_fee' => (int)($order->amount * 100),
                ]);
            }

            // 记录退款流水
            PaymentFlow::create([
                'order_id' => $order->id,
                'channel' => $channel,
                'channel_order_no' => $flow->channel_order_no,
                'channel_data' => json_decode(json_encode($result), true),
                'status' => PaymentFlowStatus::REFUND,
            ]);

            // 更新订单
            $order->update(['status' => PaymentOrderStatus::CLOSED]);

            // 通知游戏方退款
            if ($order->game_id) {
                try {
                    app(GamePayService::class)->notifyGameServer($order);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('退款通知游戏方失败', [
                        'order_id' => $order->id, 'error' => $e->getMessage(),
                    ]);
                }
            }

            return ['code' => 0, 'msg' => '退款成功'];
        } catch (\Yansongda\Pay\Exception\ContainerException $e) {
            return ['code' => 2, 'msg' => '退款调用失败: ' . $e->getMessage()];
        } catch (\Yansongda\Pay\Exception\ServiceException $e) {
            return ['code' => 2, 'msg' => '退款服务异常: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['code' => 2, 'msg' => '退款异常: ' . $e->getMessage()];
        }
    }

    private function getChannelConfig(string $channel): array
    {
        $dbConfig = PaymentConfig::where('channel', $channel)->where('enabled', true)->first();
        return $dbConfig ? $dbConfig->config : config('pay.' . $channel . '.default', []);
    }
}
