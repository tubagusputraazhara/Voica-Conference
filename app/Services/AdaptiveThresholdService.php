<?php

namespace App\Services;

/**
 * AdaptiveThresholdService
 * 
 * Service untuk menentukan threshold ECAPA-TDNN berdasarkan:
 * 1. Nilai transaksi
 * 2. Waktu sejak verifikasi terakhir
 * 3. Risk level
 */
class AdaptiveThresholdService
{
    /**
     * Threshold defaults
     */
    const THRESHOLD_RELAXED = 0.25;    // 62.5% - Untuk re-verify dalam 10 menit
    const THRESHOLD_STANDARD = 0.35;   // 67.5% - Default (Evaluated Optimal EER)
    const THRESHOLD_HIGH = 0.45;       // 72.5% - Transaksi besar
    const THRESHOLD_STRICT = 0.55;     // 77.5% - Transaksi sangat besar / high risk

    /**
     * Amount thresholds (dalam Rupiah)
     */
    const AMOUNT_HIGH = 10000000;      // 10 juta
    const AMOUNT_VERY_HIGH = 50000000; // 50 juta

    /**
     * Get adaptive threshold berdasarkan konteks
     * 
     * @param float|null $transactionAmount Nilai transaksi (null = tidak ada transaksi)
     * @param string|null $lastVerifiedAt Waktu verifikasi terakhir
     * @param string $riskLevel 'low', 'medium', 'high'
     * @return array ['threshold' => float, 'threshold_percentage' => float, 'reason' => string]
     */
    public static function getThreshold(
        ?float $transactionAmount = null,
        ?string $lastVerifiedAt = null,
        string $riskLevel = 'medium'
    ): array {
        $threshold = self::THRESHOLD_STANDARD;
        $reason = 'default';

        // 1. Cek waktu sejak verifikasi terakhir
        if ($lastVerifiedAt) {
            $lastVerified = \Carbon\Carbon::parse($lastVerifiedAt);
            $minutesSince = now()->diffInMinutes($lastVerified);

            if ($minutesSince <= 10) {
                // Re-verify dalam 10 menit: gunakan threshold relaxed
                return [
                    'threshold' => self::THRESHOLD_RELAXED,
                    'threshold_percentage' => self::toPercentage(self::THRESHOLD_RELAXED),
                    'reason' => "re_verify_within_10min (last: {$minutesSince}m ago)",
                ];
            }
        }

        // 2. Cek nilai transaksi
        if ($transactionAmount !== null) {
            if ($transactionAmount >= self::AMOUNT_VERY_HIGH) {
                $threshold = self::THRESHOLD_STRICT;
                $reason = "very_high_amount (>= Rp" . number_format(self::AMOUNT_VERY_HIGH) . ")";
            } elseif ($transactionAmount >= self::AMOUNT_HIGH) {
                $threshold = self::THRESHOLD_HIGH;
                $reason = "high_amount (>= Rp" . number_format(self::AMOUNT_HIGH) . ")";
            } else {
                $threshold = self::THRESHOLD_STANDARD;
                $reason = "standard_amount";
            }
        }

        // 3. Override berdasarkan risk level
        if ($riskLevel === 'high') {
            $threshold = max($threshold, self::THRESHOLD_HIGH);
            $reason .= " + high_risk";
        } elseif ($riskLevel === 'low') {
            $threshold = min($threshold, self::THRESHOLD_STANDARD);
            $reason .= " + low_risk";
        }

        return [
            'threshold' => $threshold,
            'threshold_percentage' => self::toPercentage($threshold),
            'reason' => $reason,
        ];
    }

    /**
     * Convert cosine similarity threshold to percentage
     * Cosine range: [-1, 1] -> Percentage: [0, 100]
     */
    public static function toPercentage(float $cosineThreshold): float
    {
        return round(($cosineThreshold + 1) / 2 * 100, 2);
    }

    /**
     * Convert percentage to cosine similarity threshold
     */
    public static function fromPercentage(float $percentage): float
    {
        return ($percentage / 100 * 2) - 1;
    }

    /**
     * Get threshold info untuk logging/display
     */
    public static function getThresholdInfo(): array
    {
        return [
            'relaxed' => [
                'threshold' => self::THRESHOLD_RELAXED,
                'percentage' => self::toPercentage(self::THRESHOLD_RELAXED),
                'description' => 'Re-verify dalam 10 menit',
            ],
            'standard' => [
                'threshold' => self::THRESHOLD_STANDARD,
                'percentage' => self::toPercentage(self::THRESHOLD_STANDARD),
                'description' => 'Default threshold',
            ],
            'high' => [
                'threshold' => self::THRESHOLD_HIGH,
                'percentage' => self::toPercentage(self::THRESHOLD_HIGH),
                'description' => 'Transaksi >= Rp10 juta',
            ],
            'strict' => [
                'threshold' => self::THRESHOLD_STRICT,
                'percentage' => self::toPercentage(self::THRESHOLD_STRICT),
                'description' => 'Transaksi >= Rp50 juta atau high risk',
            ],
        ];
    }
}
