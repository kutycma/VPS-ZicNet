<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        if (!$user->telegram_id && !$user->telegram_verify_token) {
            $user->telegram_verify_token = \Illuminate\Support\Str::random(12);
            $user->save();
        }
        return view('client.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['name', 'email', 'phone']));

        return redirect()->back()->with('success', 'Cập nhật thông tin thành công');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->back()->with('success', 'Đổi mật khẩu thành công');
    }

    public function unlinkTelegram()
    {
        $user = Auth::user();
        $user->telegram_id = null;
        $user->telegram_verify_token = \Illuminate\Support\Str::random(12);
        $user->save();
        return back()->with('success', 'Đã hủy liên kết Telegram thành công!');
    }
}
