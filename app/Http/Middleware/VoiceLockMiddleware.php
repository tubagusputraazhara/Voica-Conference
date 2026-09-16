<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * VoiceLockMiddleware
 * 
 * Middleware untuk memproteksi aksi sensitif dengan verifikasi suara.
 * Mendukung request web biasa dan AJAX request.
 */
class VoiceLockMiddleware
{
    /**
     * Waktu validitas voice verification (dalam menit)
     * Setelah waktu ini, user harus verifikasi ulang
     */
    protected int $verificationValidityMinutes = 5;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'voice_lock_required' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Cek apakah user punya voice_embedding terdaftar
        // Jika tidak punya, skip voice-lock (legacy user / user tanpa voice)
        if (empty($user->voice_embedding)) {
            // User tidak punya voice embedding, langsung lanjut tanpa voice-lock
            // Log untuk monitoring
            Log::info('Voice-Lock skipped: User has no voice_embedding', ['user_id' => $user->id]);
            return $next($request);
        }

        // Cek apakah voice verification masih valid
        $lastVerified = session('voice_verified_at');

        if (!$lastVerified) {
            // Belum pernah verifikasi voice
            return $this->requireVoiceVerification($request);
        }

        $verifiedAt = \Carbon\Carbon::parse($lastVerified);
        $expiresAt = $verifiedAt->addMinutes($this->verificationValidityMinutes);

        if (now()->gt($expiresAt)) {
            // Verifikasi sudah expired
            session()->forget('voice_verified_at');
            return $this->requireVoiceVerification($request);
        }

        // Voice verification valid, lanjut
        return $next($request);
    }

    /**
     * Return response untuk meminta voice verification
     */
    protected function requireVoiceVerification(Request $request): Response
    {
        // Simpan intended URL
        // Jika AJAX/API request, simpan Referer (halaman asal) sebagai intended URL
        // karena kita tidak bisa redirect browser ke API endpoint (POST)
        if ($request->expectsJson() || $request->ajax()) {
            session(['voice_lock_intended_url' => $request->headers->get('referer') ?? route('dashboard')]);
        } else {
            session(['voice_lock_intended_url' => $request->url()]);
        }

        // Jika AJAX request, return JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'voice_lock_required' => true,
                'message' => 'Aksi ini memerlukan verifikasi suara untuk keamanan.',
                'verify_url' => route('voice-lock.verify')
            ], 403);
        }

        // Jika request biasa, redirect
        return redirect()->route('voice-lock.verify')
            ->with('warning', 'Aksi ini memerlukan verifikasi suara untuk keamanan.');
    }
}
