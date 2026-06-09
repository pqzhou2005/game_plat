<?php
namespace App\Enums;

class CommonStatus
{
    const DISABLED = 0;
    const ACTIVE   = 1;

    public static function labels(): array
    {
        return [self::DISABLED => '禁用', self::ACTIVE => '启用'];
    }
}
