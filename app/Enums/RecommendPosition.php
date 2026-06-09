<?php
namespace App\Enums;

class RecommendPosition
{
    const BANNER   = 'banner';
    const HOT      = 'hot';
    const NEW      = 'new';
    const FEATURED = 'featured';

    public static function labels(): array
    {
        return [
            self::BANNER => '首页轮播', self::HOT => '热门推荐',
            self::NEW => '新游上线', self::FEATURED => '精品游戏',
        ];
    }
}
