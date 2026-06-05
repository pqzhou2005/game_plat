<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request)
    {
        $user = $this->authService->createUser($request->validated());

        if ($request->filled('real_name') && $request->filled('id_card')) {
            $user->update(['id_card_verified_at' => now()]);
        }

        Auth::login($user);

        return redirect('/');
    }
}
