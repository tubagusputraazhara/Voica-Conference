<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AccountSettingsController extends Controller
{
    /**
     * Show account settings page
     */
    public function index()
    {
        $user = Auth::user();

        return inertia('Settings', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'voice_enrolled_at' => $user->voice_enrolled_at ? $user->voice_enrolled_at->format('d M Y') : null,
            ]
        ]);
    }

    /**
     * Update account settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'avatar_file' => 'nullable|image|max:2048',
            'current_password' => 'nullable|string',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user = Auth::user();
        $updateData = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'updated_at' => now()
        ];

        // Handle avatar upload
        if ($request->hasFile('avatar_file')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar_file')->store('avatars', 'public');
            $updateData['avatar'] = $path;
        }

        // Update profile
        DB::table('users')
            ->where('id', $user->id)
            ->update($updateData);

        // Update password jika diisi
        if (!empty($validated['password'])) {
            if (empty($request->current_password)) {
                return redirect()->back()->with('error', 'Password lama harus diisi untuk mengubah password');
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()->with('error', 'Password lama yang Anda masukkan salah');
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'password' => Hash::make($validated['password']),
                    'updated_at' => now()
                ]);
        }

        return redirect()->back()->with('success', 'Pengaturan akun berhasil diperbarui');
    }
}
