<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AudioProcessingService;
use App\Services\VoiceEnrollmentService;
use App\Services\VoiceVerificationService;
use App\Services\AdaptiveThresholdService;
use App\Services\VoiceVerificationLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        protected VoiceEnrollmentService $enrollmentService,
        protected VoiceVerificationService $verificationService,
        protected AudioProcessingService $audioService
    ) {}

    /**
     * Show login/register form
     */
    public function showAuthForm($mode = 'login')
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return inertia('Auth', [
            'mode' => $mode,
            'status' => session('status'),
        ]);
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'numeric', 'digits_between:10,15', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'voice_audio_file' => ['nullable', 'file', 'mimes:wav,webm,ogg,mp3,mp4,m4a,aac,x-m4a', 'max:10240'],
            'voice_audio_base64' => ['nullable', 'string'],
        ], [
            'phone.unique' => 'Nomor telepon sudah terdaftar',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$request->hasFile('voice_audio_file') && !$request->filled('voice_audio_base64')) {
                $validator->errors()->add('voice_audio', 'Rekaman suara diperlukan untuk pendaftaran');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('mode', 'register');
        }

        try {
            // Temporary path for enrollment
            $audioPath = $request->hasFile('voice_audio_file')
                ? $request->file('voice_audio_file')->getRealPath()
                : $this->audioService->base64ToTempFile($request->voice_audio_base64);

            $user = new User([
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // Delegate enrollment to Service
            $enrollmentResult = $this->enrollmentService->enroll($user, $audioPath);

            if (!$enrollmentResult->success) {
                return back()->withErrors(['voice_audio' => $enrollmentResult->error])->withInput()->with('mode', 'register');
            }

            // Save User (embedding is already updated on the object by service)
            $user->save();

            cookie()->queue(cookie()->forever('has_logged_in', '1'));
            return redirect()->route('login')->with('status', 'Registrasi berhasil! Silakan login.');
        } catch (\Exception $e) {
            Log::error("Registration failure: " . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])->withInput()->with('mode', 'register');
        }
    }

    /**
     * Handle traditional login (password-based)
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->input('phone');
        $fieldType = is_numeric($loginField) ? 'phone' : 'name';

        if (!Auth::attempt([$fieldType => $loginField, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors(['phone' => 'Kredensial salah'])->withInput()->with('mode', 'login');
        }

        cookie()->queue(cookie()->forever('has_logged_in', '1'));
        $request->session()->regenerate();
        return redirect()->intended('dashboard');
    }

    /**
     * Handle voice-based login
     */
    public function voiceLogin(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->orWhere('name', $request->phone)->first();

        if (!$user || !$user->hasVoiceEnrolled()) {
            return back()->withErrors(['voice_audio' => 'Pengguna belum terdaftar atau belum mendaftarkan suara'])->with('mode', 'login');
        }

        try {
            $audioPath = $request->hasFile('voice_audio_file')
                ? $request->file('voice_audio_file')->getRealPath()
                : $this->audioService->base64ToTempFile($request->voice_audio_base64);

            $threshold = AdaptiveThresholdService::getThreshold()['threshold'];

            $result = $this->verificationService->verify($user, $audioPath, [
                'threshold' => $threshold
            ]);

            // Log for analytics
            VoiceVerificationLogger::log($result);

            if (!$result->success) {
                return back()->withErrors(['voice_audio' => 'Error: ' . $result->error])->with('mode', 'login');
            }

            if (!$result->isMatch) {
                $msg = ($result->liveness['security_level'] ?? '') === 'blocked'
                    ? "⚠️ TERDETEKSI SEBAGAI REKAMAN/PALSU!"
                    : "Suara tidak cocok. Kemiripan: " . ($result->extra['similarity_percentage'] ?? '0') . "%";

                return back()->withErrors(['voice_audio' => $msg])->with('mode', 'login');
            }

            Auth::login($user);
            cookie()->queue(cookie()->forever('has_logged_in', '1'));
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('status', "✅ Login berhasil!");
        } catch (\Exception $e) {
            Log::error("Voice login failure: " . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal login: ' . $e->getMessage()])->with('mode', 'login');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
