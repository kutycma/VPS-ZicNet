<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return \Laravel\Socialite\Facades\Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')->user();

            $user = \App\Models\User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                    'role' => 'user',
                    'status' => 'active',
                    'balance' => 0,
                    'telegram_verify_token' => \Illuminate\Support\Str::random(12),
                    'email_verified_at' => now(),
                    'avatar' => $googleUser->getAvatar(),
                    'google_id' => $googleUser->getId(),
                ]);
            } else {
                // Cập nhật avatar mỗi lần đăng nhập (phòng user đổi ảnh Google)
                $updateData = [];
                if ($googleUser->getAvatar()) {
                    $updateData['avatar'] = $googleUser->getAvatar();
                }
                if ($googleUser->getId() && !$user->google_id) {
                    $updateData['google_id'] = $googleUser->getId();
                }
                if (!empty($updateData)) {
                    $user->update($updateData);
                }
            }

            \Illuminate\Support\Facades\Auth::login($user, true);

            return redirect()->route('client.dashboard');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Auth Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('login')->with('error', 'Lỗi đăng nhập bằng Google: ' . $e->getMessage());
        }
    }
}
