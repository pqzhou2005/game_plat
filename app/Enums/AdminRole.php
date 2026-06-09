<?php
namespace App\Enums;

class AdminRole
{
    const SUPER_ADMIN = 'super_admin';
    const ADMIN       = 'admin';
    const FINANCE     = 'finance';
    const SUPPORT     = 'support';
    const OPERATOR    = 'operator';

    public static function labels(): array
    {
        return [
            self::SUPER_ADMIN => '超级管理员',
            self::ADMIN => '管理员',
            self::FINANCE => '财务',
            self::SUPPORT => '客服',
            self::OPERATOR => '运营',
        ];
    }
}
