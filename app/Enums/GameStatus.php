<?php
namespace App\Enums;

class GameStatus
{
    const DISABLED = 0;
    const ACTIVE   = 1;

    public static function labels(): array
    {
        return [self::DISABLED => '下架', self::ACTIVE => '上架'];
    }
}
