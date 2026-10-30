<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\AudioProcessingService;
use App\Services\VoiceEnrollmentService;

class AccountSettingsController extends Controller
{
    public function __construct(
        protected AudioProcessingService $audioService,
        protected VoiceEnrollmentService $enrollmentService
    ) {}

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
                'voice_model' => $user->voice_model,
            ]
        ]);
    }

    /**
     * Ganti model verifikasi suara (re-enroll dengan backbone berbeda) untuk
     * user yang SUDAH login. Ini meng-update embedding/voice_model di akun
     * yang sama (user_id sama) -- bukan bikin akun baru -- jadi transaksi
     * & data lain tidak ikut hilang.
     */
    public function updateVoiceModel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voice_model' => ['required', 'string', 'in:ecapa,resnet34,wavlm'],
            'current_password' => ['required', 'string'],
            'voice_audio_file' => ['nullable', 'file', 'mimes:wav,webm,ogg,mp3,mp4,m4a,aac,x-m4a', 'max:10240'],
            'voice_audio_base64' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$request->hasFile('voice_audio_file') && !$request->filled('voice_audio_base64')) {
                $validator->errors()->add('voice_audio', 'Rekaman suara diperlukan');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('activeSection', 'voice-model');
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi salah'])->with('activeSection', 'voice-model');
        }

        try {
            $audioPath = $request->hasFile('voice_audio_file')
                ? $request->file('voice_audio_file')->getRealPath()
                : $this->audioService->base64ToTempFile($request->voice_audio_base64);

            $oldVoicePath = $user->voice_path;

            $result = $this->enrollmentService->enroll($user, $audioPath, $request->voice_model);

            if (!$result->success) {
                return back()->withErrors(['voice_audio' => $result->error])->with('activeSection', 'voice-model');
            }

            // Bersihkan rekaman enrollment lama (model/embedding sebelumnya sudah diganti)
            if ($oldVoicePath && $oldVoicePath !== $user->voice_path) {
                Storage::disk('public')->delete($oldVoicePath);
            }

            Log::info("User {$user->id} berganti model verifikasi suara ke: {$request->voice_model}");

            return redirect()->back()->with('success', 'Model verifikasi suara berhasil diperbarui ke ' . strtoupper($request->voice_model));
        } catch (\Exception $e) {
            Log::error("Gagal ganti model suara untuk user {$user->id}: " . $e->getMessage());
            return back()->withErrors(['voice_audio' => 'Terjadi kesalahan: ' . $e->getMessage()])->with('activeSection', 'voice-model');
        }
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
