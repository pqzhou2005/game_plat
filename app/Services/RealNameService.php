<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 实名认证服务
 * 对接中宣部 NPPA 官方接口 + 腾讯云兜底
 */
class RealNameService
{
    private string $appId;
    private string $secretKey;
    private string $bizId;
    private string $tencentSecretId;
    private string $tencentSecretKey;

    public function __construct(?string $bizId = null)
    {
        $this->appId = config('app.nppa_app_id', env('NPPA_APP_ID', ''));
        $this->secretKey = config('app.nppa_secret_key', env('NPPA_SECRET_KEY', ''));
        $this->bizId = $bizId ?? env('NPPA_BIZ_ID', '');
        $this->tencentSecretId = env('TENCENT_SECRET_ID', '');
        $this->tencentSecretKey = env('TENCENT_SECRET_KEY', '');
    }

    /**
     * 实名认证主入口
     * 先调 NPPA，失败则走腾讯云兜底
     */
    public function verify(string $realName, string $idCard, int $userId = 0): array
    {
        $ai = date('YmdHis') . str_pad(substr(microtime(), 2, 2), 2, '0') . str_pad($userId, 16, '0', STR_PAD_LEFT);
        $idCard = strtoupper($idCard);

        // 1. NPPA 官方接口
        $result = $this->nppaCheck($ai, $idCard, $realName);
        if (isset($result['errcode']) && $result['errcode'] === 0 && !empty($result['data']['result'])) {
            $dataResult = $result['data']['result'];
            if ($dataResult['status'] === 1) {
                return ['code' => 0, 'pi' => $dataResult['pi'] ?? '', 'status' => 1];
            }
        }

        // 2. NPPA 失败 → 腾讯云兜底
        Log::warning('NPPA verification failed, falling back to Tencent', [
            'user_id' => $userId, 'result' => $result ?? null,
        ]);

        $tencentResult = $this->tencentCheck($idCard, $realName);
        if ($tencentResult) {
            return ['code' => 0, 'pi' => 'tencent_fallback', 'status' => 0];
        }

        // 3. 所有外部接口失败后，走本地身份证格式校验兜底
        $localCheck = $this->localVerify($idCard);
        if ($localCheck['code'] === 0) {
            return ['code' => 0, 'pi' => 'local_verify', 'status' => 0];
        }
        return ['code' => 4, 'msg' => $localCheck['msg'] ?? '身份证格式校验失败'];
    }

    /**
     * NPPA 官方实名认证接口
     */
    private function nppaCheck(string $ai, string $idNum, string $name): array
    {
        $url = 'https://api.wlc.nppa.gov.cn/idcard/authentication/check';
        $data = ['ai' => $ai, 'idNum' => $idNum, 'name' => $name];
        $body = $this->buildNppaRequest($data);
        $headers = $this->buildNppaHeaders($url, $body);

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($body, 'application/json; charset=utf-8')
                ->post($url);

            if ($response->successful()) {
                return $response->json() ?? [];
            }
            Log::warning('NPPA http error', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('NPPA request failed', ['error' => $e->getMessage()]);
        }
        return [];
    }

    /**
     * AES-128-GCM 加密请求体
     */
    private function buildNppaRequest(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $key = hex2bin($this->secretKey);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-gcm'));
        $tag = '';
        $encrypted = openssl_encrypt($json, 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        $payload = bin2hex($iv) . bin2hex($encrypted) . bin2hex($tag);
        return json_encode(['data' => base64_encode(hex2bin($payload))], JSON_UNESCAPED_UNICODE);
    }

    /**
     * NPPA 请求头签名 (SHA-256)
     */
    private function buildNppaHeaders(string $url, string $body): array
    {
        $timestamps = (int)(microtime(true) * 1000);
        $signData = [
            'appId' => $this->appId,
            'bizId' => $this->bizId,
            'timestamps' => $timestamps,
        ];

        $strToSign = $this->secretKey;
        ksort($signData);
        foreach ($signData as $k => $v) {
            $strToSign .= $k . $v;
        }
        $strToSign .= $body;

        return [
            'appId: ' . $this->appId,
            'bizId: ' . $this->bizId,
            'timestamps: ' . $timestamps,
            'sign: ' . hash('sha256', $strToSign),
            'Content-Type: application/json; charset=utf-8',
        ];
    }

    /**
     * 腾讯云兜底接口
     */
    private function tencentCheck(string $cardNo, string $realName): bool
    {
        $url = 'https://service-hl92rg03-1301232119.bj.apigw.tencentcs.com/release/id/check';
        $datetime = gmdate('D, d M Y H:i:s T');
        $source = 'market';
        $signStr = "x-date: {$datetime}\nx-source: {$source}";
        $sign = base64_encode(hash_hmac('sha1', $signStr, $this->tencentSecretKey, true));
        $auth = sprintf(
            'hmac id="%s", algorithm="hmac-sha1", headers="x-date x-source", signature="%s"',
            $this->tencentSecretId, $sign
        );

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Source: ' . $source,
                    'X-Date: ' . $datetime,
                    'Authorization: ' . $auth,
                ])
                ->asForm()
                ->post($url, ['cardNo' => $cardNo, 'realName' => $realName]);

            if ($response->successful()) {
                $body = $response->json();
                return !empty($body['result']['isok']);
            }
        } catch (\Exception $e) {
            Log::error('Tencent realname check failed', ['error' => $e->getMessage()]);
        }
        return false;
    }

    /**
     * 身份证本地格式校验 + 年龄快速判断
     */
    public function localVerify(string $idCard): array
    {
        $idCard = strtoupper($idCard);
        if (!preg_match('/^\d{17}[\dX]$/', $idCard) && !preg_match('/^\d{15}$/', $idCard)) {
            return ['code' => 4, 'msg' => '身份证号码格式错误'];
        }
        // 15位补成18位再校验
        if (strlen($idCard) === 15) {
            $idCard = $this->upgradeIdCard($idCard);
        }
        // 校验位检查
        if (!$this->validateChecksum($idCard)) {
            return ['code' => 4, 'msg' => '身份证号码校验失败'];
        }
        $birthdate = $this->extractBirthdate($idCard);
        return ['code' => 0, 'birthdate' => $birthdate];
    }

    public function validateIdCard(string $idCard): bool
    {
        $idCard = strtoupper($idCard);
        if (!preg_match('/^\d{17}[\dX]$/', $idCard) && !preg_match('/^\d{15}$/', $idCard)) {
            return false;
        }
        if (strlen($idCard) === 15) {
            $idCard = $this->upgradeIdCard($idCard);
        }
        return $this->validateChecksum($idCard);
    }

    private function validateChecksum(string $idCard): bool
    {
        $coefficients = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checkDigits = '10X98765432';
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += intval($idCard[$i]) * $coefficients[$i];
        }
        return $checkDigits[$sum % 11] === $idCard[17];
    }

    private function upgradeIdCard(string $idCard15): string
    {
        $idCard17 = substr($idCard15, 0, 6) . '19' . substr($idCard15, 6);
        $coefficients = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checkDigits = '10X98765432';
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += intval($idCard17[$i]) * $coefficients[$i];
        }
        return $idCard17 . $checkDigits[$sum % 11];
    }

    public function extractBirthdate(string $idCard): ?string
    {
        $idCard = strtoupper($idCard);
        if (strlen($idCard) === 15) {
            $idCard = $this->upgradeIdCard($idCard);
        }
        if (strlen($idCard) === 18) {
            return substr($idCard, 6, 4) . '-' . substr($idCard, 10, 2) . '-' . substr($idCard, 12, 2);
        }
        return null;
    }

    public function markVerified(User $user): void
    {
        $user->update(['id_card_verified_at' => now()]);
    }
}
