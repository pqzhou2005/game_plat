<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'mobile', 'email', 'password',
        'avatar', 'status', 'real_name', 'id_card',
        'id_card_verified_at', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token', 'id_card'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'id_card_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'status' => 'integer',
        ];
    }

    public function oauthProviders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OAuthProvider::class);
    }

    public function loginLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function paymentOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function isRealNameVerified(): bool
    {
        return !is_null($this->id_card_verified_at);
    }

    /**
     * 脱敏身份证号: 110101********1234
     */
    public function getMaskedIdCardAttribute(): ?string
    {
        if (empty($this->id_card)) {
            return null;
        }
        $card = $this->id_card;
        if (strlen($card) >= 10) {
            return substr($card, 0, 6) . str_repeat('*', strlen($card) - 10) . substr($card, -4);
        }
        return substr($card, 0, 2) . '****' . substr($card, -2);
    }

    /**
     * 实名状态标签
     */
    public function getRealNameStatusAttribute(): string
    {
        if (!$this->isRealNameVerified()) {
            return '未实名';
        }
        return $this->isAdult() ? '已成年' : '未成年';
    }

    /**
     * 从身份证号提取出生日期
     * 18位: 第7-14位 YYYYMMDD
     * 15位: 第7-12位 YYMMDD，补19前缀
     */
    public function getBirthdate(): ?string
    {
        if (empty($this->id_card)) {
            return null;
        }

        $idCard = strtoupper($this->id_card);

        if (strlen($idCard) === 18) {
            $year = substr($idCard, 6, 4);
            $month = substr($idCard, 10, 2);
            $day = substr($idCard, 12, 2);
            return "{$year}-{$month}-{$day}";
        }

        if (strlen($idCard) === 15) {
            $year = '19' . substr($idCard, 6, 2);
            $month = substr($idCard, 8, 2);
            $day = substr($idCard, 10, 2);
            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    /**
     * 判断用户是否已成年（≥18周岁）
     */
    public function isAdult(): bool
    {
        if (!$this->isRealNameVerified()) {
            return false;
        }

        $birthdate = $this->getBirthdate();
        if (!$birthdate) {
            return false;
        }

        return \Carbon\Carbon::parse($birthdate)->addYears(18)->lte(now());
    }

    /**
     * 防沉迷状态: 0=未实名 1=已成年 2=未成年
     */
    public function getAntiAddictionStatus(): int
    {
        if (!$this->isRealNameVerified()) {
            return 0;
        }
        return $this->isAdult() ? 1 : 2;
    }

}
