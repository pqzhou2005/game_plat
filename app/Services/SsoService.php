<?php
namespace App\Services;

use App\Models\GameSsoConfig;
use App\Models\User;

class SsoService
{
    public function generateLoginParams(User $user, int $gameId, int $serverId): array
    {
        $config = GameSsoConfig::where('game_id', $gameId)->where('enabled', true)->firstOrFail();

        $isAdult = $user->getAntiAddictionStatus(); // 0=未实名 1=已成年 2=未成年

        $params = [
            'uid'       => $user->id,
            'platform'  => $config->platform_id,
            'serverid'  => $serverId,
            'logintime' => time(),
            'is_adult'  => $isAdult,
            'game_id'   => $gameId,
            'client'    => 1,
        ];

        ksort($params);
        $queryString = http_build_query($params);
        $token = strtolower(md5($queryString . $config->login_key));

        $params['token'] = $token;
        $params['login_url'] = $config->login_url;

        return $params;
    }

    public function buildLoginUrl(array $params): string
    {
        $loginUrl = $params['login_url'];
        unset($params['login_url']);

        $query = http_build_query($params);
        return str_contains($loginUrl, '?')
            ? $loginUrl . '&' . $query
            : $loginUrl . '?' . $query;
    }
}
