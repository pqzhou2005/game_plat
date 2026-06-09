<?php
namespace App\Enums;

class NoticeType
{
    const PLATFORM     = 'platform';
    const GAME         = 'game';
    const MAINTENANCE  = 'maintenance';
    const ACTIVITY     = 'activity';
    const MERGE        = 'merge';

    public static function labels(): array
    {
        return [
            self::PLATFORM => '平台公告', self::GAME => '游戏公告',
            self::MAINTENANCE => '维护公告', self::ACTIVITY => '活动公告',
            self::MERGE => '合服公告',
        ];
    }
}
