<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OAuthProvider;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->user();

        $oauth = OAuthProvider::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($oauth) {
            $user = $oauth->user;
        } else {
            if (Auth::check()) {
                $user = Auth::user();
            } else {
                $username = $provider . '_' . Str::random(8);
                $user = User::create([
                    'username' => $username,
                    'password' => Str::random(32),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            }

            OAuthProvider::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_data' => $socialUser->getRaw(),
            ]);
        }

        $this->authService->recordLogin($user, request()->ip(), request()->userAgent(), 'oauth');

        Auth::login($user);

        return redirect('/');
    }
}
