<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileApiController extends Controller
{
    /**
     * GET /api/profile
     */
    public function show()
    {
        $user = Auth::user();

        return response()->json([
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'balance'           => (float) $user->balance,
            'balance_formatted' => number_format((float) $user->balance, 0, ',', '.') . 'đ',
            'avatar_url'        => $user->getAvatarUrl(128),
            'email_verified'    => $user->hasVerifiedEmail(),
            'telegram_id'       => $user->telegram_id ?? null,
            'created_at'        => $user->created_at ? $user->created_at->toISOString() : null,
        ]);
    }

    /**
     * PUT /api/profile
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $user->update(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'user'    => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    /**
     * POST /api/profile/password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }
}
