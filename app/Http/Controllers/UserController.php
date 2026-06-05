<?php
namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        return Inertia::render('User/Dashboard', [
            'user' => $user,
            'recentOrders' => PaymentOrder::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function settings()
    {
        return Inertia::render('User/Settings', [
            'user' => Auth::user(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'mobile' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if (isset($validated['new_password'])) {
            $validated['password'] = Hash::make($validated['new_password']);
        }

        $user->update($validated);

        return back()->with('success', '设置已更新');
    }

    public function orders()
    {
        $orders = PaymentOrder::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return Inertia::render('User/Orders', [
            'orders' => $orders,
        ]);
    }
}
