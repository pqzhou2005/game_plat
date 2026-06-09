<?php
namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Services\RealNameService;
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

    public function updateSettings(Request $request, RealNameService $realNameService)
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

        // 实名认证（如果提交了姓名和身份证）
        if (!$user->isRealNameVerified() && $request->filled('real_name') && $request->filled('id_card')) {
            $result = $realNameService->verify(
                $request->real_name,
                $request->id_card,
                $user->id
            );

            if ($result['code'] === 0) {
                $user->update([
                    'real_name' => $request->real_name,
                    'id_card' => strtoupper($request->id_card),
                    'id_card_verified_at' => now(),
                ]);
                return back()->with('success', '实名认证成功');
            } else {
                return back()->with('error', $result['msg'] ?? '实名认证失败');
            }
        }

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

    public function verifyForm(Request $request)
    {
        return Inertia::render('Auth/VerifyRealName', [
            'user' => Auth::user(),
            'redirect' => $request->query('redirect'),
        ]);
    }

    public function verifyRealName(Request $request, RealNameService $realNameService)
    {
        $user = Auth::user();

        if ($user->isRealNameVerified()) {
            return back();
        }

        $validated = $request->validate([
            'real_name' => 'required|string|max:255',
            'id_card' => 'required|string|max:18',
        ]);

        $result = $realNameService->verify(
            $validated['real_name'],
            $validated['id_card'],
            $user->id
        );

        if ($result['code'] === 0) {
            $user->update([
                'real_name' => $validated['real_name'],
                'id_card' => strtoupper($validated['id_card']),
                'id_card_verified_at' => now(),
            ]);
            return back()->with('success', '实名认证成功');
        }

        return back()->with('error', $result['msg'] ?? '实名认证失败');
    }
}
