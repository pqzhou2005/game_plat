<?php
namespace App\Http\Controllers;

use App\Http\Requests\Payment\CreateOrderRequest;
use App\Models\PaymentOrder;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function create(Request $request)
    {
        return Inertia::render('Payment/Recharge', [
            'game_id' => $request->game_id,
        ]);
    }

    public function store(CreateOrderRequest $request)
    {
        $order = $this->paymentService->createOrder(
            $request->user()->id,
            $request->amount,
            $request->game_id,
            $request->server_id,
            $request->game_account,
        );

        $payData = $this->paymentService->createPaymentData($order, $request->channel);

        if ($request->wantsJson()) {
            return response()->json([
                'order' => $order,
                'pay_data' => $payData,
            ]);
        }

        return Inertia::render('Payment/Result', [
            'order' => $order->load('flows'),
            'payData' => $payData,
        ]);
    }

    public function result(PaymentOrder $order)
    {
        return Inertia::render('Payment/Result', [
            'order' => $order->load('flows'),
        ]);
    }

    public function notify(string $channel, Request $request)
    {
        return $this->paymentService->handleNotify($channel, $request->all());
    }
}
