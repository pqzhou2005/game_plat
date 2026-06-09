<?php
namespace App\Http\Controllers\Api;

use App\Enums\PaymentOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\GameSsoConfig;
use App\Models\PaymentOrder;
use App\Http\Requests\Api\GamePayNotifyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentNotifyController extends Controller
{
    public function notify(GamePayNotifyRequest $request): JsonResponse
    {
        $order = PaymentOrder::where('order_no', $request->orderId)->first();
        if (!$order) {
            Log::warning('游戏方发货回调：订单不存在', ['order_no' => $request->orderId]);
            return response()->json(['msg' => '订单不存在', 'status' => 1]);
        }

        $config = GameSsoConfig::where('game_id', $order->game_id)->first();
        if (!$config) {
            Log::warning('游戏方发货回调：游戏配置不存在', [
                'order_no' => $request->orderId, 'game_id' => $order->game_id,
            ]);
            return response()->json(['msg' => '游戏配置不存在', 'status' => 1]);
        }

        $params = $request->only(['uid', 'serverId', 'orderId', 'money', 'goodsId', 'time', 'rid', 'ext']);
        ksort($params);
        $expectedSign = strtolower(md5(http_build_query($params) . $config->pay_key));

        if ($request->sign !== $expectedSign) {
            Log::warning('游戏方发货回调：签名校验失败', [
                'order_no' => $request->orderId, 'sign' => $request->sign, 'expected' => $expectedSign,
            ]);
            return response()->json(['msg' => '签名校验失败', 'status' => 2]);
        }

        if ($order->status !== PaymentOrderStatus::SUCCESS) {
            Log::warning('游戏方发货回调：订单未支付', [
                'order_no' => $request->orderId, 'status' => $order->status,
            ]);
            return response()->json(['msg' => '订单未支付', 'status' => 1]);
        }

        // 金额校验 — 防止游戏方回调金额被伪造
        $callbackAmount = (float)$request->money;
        if (abs($callbackAmount - (float)$order->amount) > 0.01) {
            Log::warning('游戏方回调金额不匹配', [
                'order_no' => $order->order_no,
                'expected' => $order->amount,
                'actual' => $callbackAmount,
            ]);
            return response()->json(['msg' => '金额不匹配', 'status' => 1]);
        }

        return response()->json(['msg' => '充值成功', 'status' => 0]);
    }
}
