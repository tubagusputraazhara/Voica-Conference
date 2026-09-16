<?php

namespace App\Http\Controllers;

use App\Services\AudioProcessingService;
use App\Services\VoiceChallengeService;
use App\Services\VoiceVerificationService;
use App\Services\AdaptiveThresholdService;
use App\Services\VoiceVerificationLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * VoiceLockController
 * 
 * Menangani verifikasi suara 3-Layer untuk aksi-aksi sensitif
 * Layer 1: Text-Dependent Challenge (Liveness Detection via STT)
 * Layer 2: AASIST (Anti-Spoofing)
 * Layer 3: ECAPA-TDNN (Speaker Verification)
 */
class VoiceLockController extends Controller
{
    public function __construct(
        protected VoiceVerificationService $verificationService,
        protected AudioProcessingService $audioService,
        protected VoiceChallengeService $challengeService
    ) {}

    /**
     * Tampilkan halaman verifikasi suara dengan challenge
     */
    public function show()
    {
        $challenge = $this->challengeService->generateChallenge('digits');
        $intendedUrl = session('voice_lock_intended_url', route('dashboard'));

        return view('voice-lock.verify', [
            'intendedUrl' => $intendedUrl,
            'challenge' => $challenge
        ]);
    }

    /**
     * API: Dapatkan challenge baru
     */
    public function getChallenge(Request $request)
    {
        $type = $request->input('type', 'digits');
        $challenge = $this->challengeService->generateChallenge($type);

        return response()->json([
            'success' => true,
            'challenge' => $challenge['text'],
            'type' => $challenge['type'],
            'expires_in' => 120
        ]);
    }

    /**
     * Proses verifikasi suara 3-Layer
     */
    public function verify(Request $request)
    {
        $request->validate([
            'voice_audio' => 'required|file|mimes:webm,wav,mp3,ogg,mp4,m4a,aac,x-m4a',
            'challenge_text' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user->voice_embedding) {
            return back()->withErrors(['voice_audio' => 'Anda belum mendaftarkan suara.']);
        }

        $challenge = $this->challengeService->getActiveChallenge();
        if (!$challenge) {
            return back()->withErrors(['voice_audio' => '⏱️ Challenge expired.']);
        }

        try {
            // Get original path from uploaded file
            $audioPath = $request->file('voice_audio')->getRealPath();

            // Get Adaptive Threshold
            $lastVerified = session('voice_verified_at');
            $thresholdInfo = AdaptiveThresholdService::getThreshold(null, $lastVerified);
            $threshold = $thresholdInfo['threshold'];

            // Delegate to Verification Service (3-Layer automatically detected because challenge given)
            $result = $this->verificationService->verify($user, $audioPath, [
                'threshold' => $threshold,
                'challenge_text' => $challenge['text']
            ]);

            // Add context for logger
            $result->extra['action'] = 'voice_lock';
            VoiceVerificationLogger::log($result);

            if (!$result->success) {
                return back()->withErrors(['voice_audio' => $result->error]);
            }

            if (!$result->isMatch) {
                $reason = $result->extra['rejected_reason'] ?? 'unknown';
                $msg = $this->getRejectionMessage($reason, $result);
                return back()->withErrors(['voice_audio' => $msg]);
            }

            // Success!
            session(['voice_verified_at' => now()->toDateTimeString()]);
            $this->challengeService->clearChallenge();

            $intendedUrl = session('voice_lock_intended_url', route('dashboard'));
            session()->forget('voice_lock_intended_url');

            return redirect($intendedUrl)->with('status', "✅ Verifikasi suara berhasil!");
        } catch (\Exception $e) {
            Log::error('Voice-Lock verification error', ['error' => $e->getMessage()]);
            return back()->withErrors(['voice_audio' => 'Error: ' . $e->getMessage()]);
        }
    }

    protected function getRejectionMessage(string $reason, \App\DTOs\VoiceVerificationResult $result): string
    {
        return match ($reason) {
            'wrong_text' => '❌ ' . ($result->extra['message'] ?? 'Kata tidak cocok'),
            'stt_failed' => '🎤 ' . ($result->extra['message'] ?? 'Suara tidak dikenali'),
            'spoof_detected' => '⚠️ Terdeteksi sebagai rekaman/palsu!',
            default => "Suara tidak cocok. Kemiripan: " . ($result->extra['similarity_percentage'] ?? '0') . "%",
        };
    }
}
