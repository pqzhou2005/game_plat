<?php
namespace App\Enums;

class PaymentOrderStatus
{
    const PENDING = 'pending';
    const SUCCESS = 'success';
    const FAILED  = 'failed';
    const CLOSED  = 'closed';

    public static function labels(): array
    {
        return [
            self::PENDING => '处理中',
            self::SUCCESS => '成功',
            self::FAILED  => '失败',
            self::CLOSED  => '已关闭',
        ];
    }
}
