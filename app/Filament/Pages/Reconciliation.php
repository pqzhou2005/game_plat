<?php
namespace App\Filament\Pages;

use App\Enums\NotifyStatus;
use App\Enums\PaymentFlowStatus;
use App\Enums\PaymentOrderStatus;
use App\Models\PaymentOrder;
use Filament\Pages\Page;

class Reconciliation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = '支付管理';
    protected static ?string $navigationLabel = '财务对账';
    protected static ?string $title = '财务对账';
    protected static string $view = 'filament.pages.reconciliation';

    public function getChannelData(): array
    {
        return PaymentOrder::selectRaw("DATE(payment_orders.created_at) as date,
            COALESCE(f.channel, 'unknown') as channel,
            COUNT(*) as total_orders,
            SUM(CASE WHEN payment_orders.status = '" . PaymentOrderStatus::SUCCESS . "' THEN 1 ELSE 0 END) as success_orders,
            SUM(CASE WHEN payment_orders.status = '" . PaymentOrderStatus::FAILED . "' THEN 1 ELSE 0 END) as failed_orders,
            SUM(CASE WHEN payment_orders.status = '" . PaymentOrderStatus::PENDING . "' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN payment_orders.status = '" . PaymentOrderStatus::CLOSED . "' THEN 1 ELSE 0 END) as refund_orders,
            COALESCE(SUM(CASE WHEN payment_orders.status = '" . PaymentOrderStatus::SUCCESS . "' THEN payment_orders.amount ELSE 0 END), 0) as total_amount
        ")
        ->leftJoin('payment_flows as f', function ($join) {
            $join->on('payment_orders.id', '=', 'f.order_id')
                 ->where('f.status', PaymentFlowStatus::SUCCESS);
        })
        ->groupByRaw('DATE(payment_orders.created_at), f.channel')
        ->orderByRaw('DATE(payment_orders.created_at) DESC')
        ->take(50)
        ->get()
        ->toArray();
    }

    public function getAbnormalOrders(): \Illuminate\Support\Collection
    {
        return PaymentOrder::with('user')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    // 已支付未发货
                    $q2->where('status', PaymentOrderStatus::SUCCESS)
                       ->where('notify_status', NotifyStatus::PENDING)
                       ->whereNotNull('game_id');
                })->orWhere(function ($q2) {
                    // 发货失败
                    $q2->where('status', PaymentOrderStatus::SUCCESS)
                       ->where('notify_status', NotifyStatus::FAILED);
                })->orWhere(function ($q2) {
                    // 退款订单
                    $q2->where('status', PaymentOrderStatus::CLOSED);
                });
            })
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();
    }

    public function getGameDeliveryData(): array
    {
        return PaymentOrder::selectRaw("
            games.name as game_name,
            payment_orders.game_id,
            COUNT(*) as total_orders,
            SUM(CASE WHEN payment_orders.status = '" . PaymentOrderStatus::SUCCESS . "' THEN 1 ELSE 0 END) as pay_success,
            SUM(CASE WHEN payment_orders.notify_status = '" . NotifyStatus::SUCCESS . "' THEN 1 ELSE 0 END) as deliver_success,
            SUM(CASE WHEN payment_orders.notify_status = '" . NotifyStatus::FAILED . "' THEN 1 ELSE 0 END) as deliver_failed,
            SUM(CASE WHEN payment_orders.notify_status = '" . NotifyStatus::PENDING . "' AND payment_orders.status = '" . PaymentOrderStatus::SUCCESS . "' THEN 1 ELSE 0 END) as deliver_pending,
            COALESCE(SUM(CASE WHEN payment_orders.status = '" . PaymentOrderStatus::SUCCESS . "' THEN payment_orders.amount ELSE 0 END), 0) as total_amount
        ")
        ->join('games', 'payment_orders.game_id', '=', 'games.id')
        ->whereNotNull('payment_orders.game_id')
        ->groupBy('payment_orders.game_id', 'games.name')
        ->orderByDesc('total_orders')
        ->take(50)
        ->get()
        ->toArray();
    }
}
