<?php
namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayAggregateController extends Controller
{
    public function redirect(string $orderNo)
    {
        $order = PaymentOrder::where('order_no', $orderNo)->firstOrFail();

        if ($order->status === 'success') {
            return response('订单已支付', 200);
        }

        $ua = request()->userAgent();

        if (str_contains($ua, 'MicroMessenger')) {
            // WeChat browser → redirect to WeChat pay URL
            return $this->wechatPay($order);
        }

        if (str_contains($ua, 'AlipayClient')) {
            // Alipay browser → Alipay WAP
            return $this->alipayPay($order);
        }

        // Desktop → show QR scan page
        return Inertia::render('Payment/Scan', [
            'order' => $order,
            'qrcode_url' => url('/v2/pay/' . $orderNo),
        ]);
    }

    private function wechatPay(PaymentOrder $order)
    {
        try {
            $pay = \Yansongda\Pay\Pay::wechat(config('pay.wechat.default', []));
            $result = $pay->scan([
                'out_trade_no' => $order->order_no,
                'total_fee' => (int)($order->amount * 100),
                'body' => '602游戏平台-充值',
            ]);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function alipayPay(PaymentOrder $order)
    {
        try {
            $pay = \Yansongda\Pay\Pay::alipay(config('pay.alipay.default', []));
            $result = $pay->scan([
                'out_trade_no' => $order->order_no,
                'total_amount' => $order->amount,
                'subject' => '602游戏平台-充值',
            ]);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
