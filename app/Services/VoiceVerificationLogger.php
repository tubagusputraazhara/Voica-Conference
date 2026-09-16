<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * VoiceVerificationLogger
 * 
 * Service untuk logging semua voice verification attempts
 * untuk analisis false rejection rate dan threshold optimization
 */
class VoiceVerificationLogger
{
    /**
     * Log hasil verifikasi suara
     */
    public static function log(\App\DTOs\VoiceVerificationResult|array $data): void
    {
        if (is_array($data)) {
            $data = \App\DTOs\VoiceVerificationResult::fromArray($data);
        }

        $userId = Auth::id() ?? ($data->extra['user_id'] ?? null);

        $logData = [
            'user_id' => $userId,
            'timestamp' => now()->toDateTimeString(),
            'action' => $data->extra['action'] ?? 'voice_verification',
            'success' => $data->success,

            // Layer 1: STT (jika ada)
            'stt_transcript' => $data->extra['transcript'] ?? null,
            'stt_expected' => $data->extra['expected_text'] ?? null,
            'stt_similarity' => $data->extra['text_similarity'] ?? null,

            // Layer 2: AASIST
            'aasist_bonafide' => $data->liveness['bonafide_probability'] ?? null,
            'aasist_spoof' => $data->liveness['spoof_probability'] ?? null,
            'aasist_security_level' => $data->liveness['security_level'] ?? null,

            // Layer 3: ECAPA-TDNN
            'ecapa_similarity' => $data->similarity,
            'ecapa_similarity_pct' => $data->extra['similarity_percentage'] ?? null,
            'ecapa_threshold' => $data->threshold,
            'ecapa_threshold_pct' => $data->extra['threshold_percentage'] ?? null,

            // Metadata
            'is_match' => $data->isMatch,
            'rejected_reason' => $data->extra['rejected_reason'] ?? null,
            'rejected_layer' => $data->extra['rejected_layer'] ?? null,
            'transaction_amount' => $data->extra['transaction_amount'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Log ke Laravel log
        if ($logData['success'] && $logData['is_match']) {
            Log::channel('voice_verification')->info('Voice verification SUCCESS', $logData);
        } else {
            Log::channel('voice_verification')->warning('Voice verification FAILED', $logData);
        }

        // Juga log ke database untuk analisis (opsional)
        try {
            DB::table('voice_verification_logs')->insert([
                'user_id' => $userId,
                'action' => $logData['action'],
                'success' => $logData['success'],
                'is_match' => $logData['is_match'],
                'aasist_bonafide' => $logData['aasist_bonafide'],
                'ecapa_similarity' => $logData['ecapa_similarity_pct'],
                'ecapa_threshold' => $logData['ecapa_threshold_pct'],
                'rejected_reason' => $logData['rejected_reason'],
                'transaction_amount' => $logData['transaction_amount'],
                'ip_address' => $logData['ip_address'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table might not exist yet, just log to file
            Log::debug('Voice verification DB logging skipped: ' . $e->getMessage());
        }
    }

    /**
     * Get false rejection rate untuk user tertentu
     */
    public static function getFalseRejectionRate(int $userId, int $days = 30): array
    {
        try {
            $stats = DB::table('voice_verification_logs')
                ->where('user_id', $userId)
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('
                    COUNT(*) as total_attempts,
                    SUM(CASE WHEN is_match = 1 THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN is_match = 0 AND rejected_reason IS NULL THEN 1 ELSE 0 END) as false_rejections,
                    AVG(ecapa_similarity) as avg_similarity
                ')
                ->first();

            $frr = $stats->total_attempts > 0
                ? ($stats->false_rejections / $stats->total_attempts) * 100
                : 0;

            return [
                'total_attempts' => $stats->total_attempts ?? 0,
                'successful' => $stats->successful ?? 0,
                'false_rejections' => $stats->false_rejections ?? 0,
                'false_rejection_rate' => round($frr, 2),
                'avg_similarity' => round($stats->avg_similarity ?? 0, 2),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Stats not available',
            ];
        }
    }
}
