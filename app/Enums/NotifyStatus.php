<?php
namespace App\Enums;

class NotifyStatus
{
    const PENDING = 'pending';
    const SUCCESS = 'success';
    const FAILED  = 'failed';

    public static function labels(): array
    {
        return [
            self::PENDING => '待发货',
            self::SUCCESS => '已发货',
            self::FAILED  => '发货失败',
        ];
    }
}
