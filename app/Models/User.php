<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->username === 'admin' && $this->status === 1;
    }

    public function getFilamentName(): string
    {
        return $this->username ?? $this->email ?? "User #{$this->id}";
    }
}
