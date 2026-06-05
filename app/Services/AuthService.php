<?php
namespace App\Services;

use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function attemptLogin(array $credentials, string $ip, string $userAgent): User
    {
        $user = User::where('username', $credentials['username'])
            ->orWhere('mobile', $credentials['username'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['账号或密码错误'],
            ]);
        }

        if ($user->status === 0) {
            throw ValidationException::withMessages([
                'username' => ['该账号已被禁用'],
            ]);
        }

        $this->recordLogin($user, $ip, $userAgent, 'password');

        return $user;
    }

    public function recordLogin(User $user, string $ip, string $userAgent, string $type): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        LoginLog::create([
            'user_id' => $user->id,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'login_type' => $type,
        ]);
    }

    public function createUser(array $data): User
    {
        return User::create([
            'username' => $data['username'],
            'password' => $data['password'],
            'mobile' => $data['mobile'] ?? null,
            'real_name' => $data['real_name'] ?? null,
            'id_card' => $data['id_card'] ?? null,
        ]);
    }
}
