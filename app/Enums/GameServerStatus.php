<?php
namespace App\Enums;

class GameServerStatus
{
    const HOT        = 1;
    const RECOMMEND  = 2;
    const MAINTENANCE = 3;
    const FULL       = 4;

    public static function labels(): array
    {
        return [
            self::HOT => '火爆', self::RECOMMEND => '推荐',
            self::MAINTENANCE => '维护', self::FULL => '已满',
        ];
    }

    public static function badgeColor(int $s): string
    {
        return match ($s) {
            self::HOT => 'text-green-600 bg-green-50',
            self::RECOMMEND => 'text-orange-600 bg-orange-50',
            self::MAINTENANCE => 'text-gray-400 bg-gray-100',
            self::FULL => 'text-red-600 bg-red-50',
            default => 'text-gray-500 bg-gray-50',
        };
    }
}
