<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * VoiceChallengeService
 * 
 * Service untuk generate dan validasi challenge text
 * untuk Layer 3: Liveness Detection (Text-Dependent Challenge)
 */
class VoiceChallengeService
{
    /**
     * Waktu expired challenge dalam detik (2 menit)
     */
    protected int $challengeExpirySeconds = 120;

    /**
     * Kata-kata bahasa Indonesia untuk challenge
     */
    protected array $indonesianWords = [
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan',
        'nol',
        'biru',
        'merah',
        'hijau',
        'kuning',
        'putih',
        'hitam',
        'ungu',
        'oranye',
        'coklat',
        'pink',
        'apel',
        'mangga',
        'jeruk',
        'anggur',
        'pisang',
        'durian',
        'salak',
        'rambutan',
        'semangka',
        'melon',
        'kucing',
        'anjing',
        'burung',
        'ikan',
        'kuda',
        'sapi',
        'kambing',
        'ayam',
        'bebek',
        'kelinci',
        'rumah',
        'mobil',
        'motor',
        'pintu',
        'jendela',
        'meja',
        'kursi',
        'lampu',
        'buku',
        'pensil'
    ];

    /**
     * Generate challenge baru (6 digit atau 3 kata)
     * 
     * @param string $type 'digits' atau 'words'
     * @return array
     */
    public function generateChallenge(string $type = 'digits'): array
    {
        if ($type === 'words') {
            $challenge = $this->generateWordChallenge();
        } else {
            $challenge = $this->generateDigitChallenge();
        }

        $challengeData = [
            'text' => $challenge,
            'type' => $type,
            'created_at' => now()->timestamp,
            'expires_at' => now()->addSeconds($this->challengeExpirySeconds)->timestamp,
        ];

        // Simpan di session
        Session::put('voice_challenge', $challengeData);

        Log::info('Voice Challenge generated', [
            'type' => $type,
            'challenge' => $challenge,
            'expires_in' => $this->challengeExpirySeconds . ' seconds'
        ]);

        return $challengeData;
    }

    /**
     * Generate 6 digit random
     */
    protected function generateDigitChallenge(): string
    {
        $digits = [];
        for ($i = 0; $i < 6; $i++) {
            $digits[] = rand(0, 9);
        }
        return implode(' ', $digits);
    }

    /**
     * Generate 3 kata random bahasa Indonesia
     */
    protected function generateWordChallenge(): string
    {
        $selectedWords = array_rand(array_flip($this->indonesianWords), 3);
        return implode(' ', $selectedWords);
    }

    /**
     * Ambil challenge yang aktif dari session
     * 
     * @return array|null
     */
    public function getActiveChallenge(): ?array
    {
        $challenge = Session::get('voice_challenge');

        if (!$challenge) {
            return null;
        }

        // Cek apakah sudah expired
        if (now()->timestamp > $challenge['expires_at']) {
            Session::forget('voice_challenge');
            return null;
        }

        return $challenge;
    }

    /**
     * Validasi transcript dari STT dengan challenge
     * 
     * @param string $transcript Hasil STT
     * @return array
     */
    public function validateChallenge(string $transcript): array
    {
        $challenge = $this->getActiveChallenge();

        if (!$challenge) {
            return [
                'valid' => false,
                'error' => 'Challenge expired atau tidak ditemukan. Silakan muat ulang halaman.',
                'similarity' => 0
            ];
        }

        $expectedText = strtolower(trim($challenge['text']));
        $spokenText = strtolower(trim($transcript));

        // Normalize: hapus spasi berlebih
        $expectedText = preg_replace('/\s+/', ' ', $expectedText);
        $spokenText = preg_replace('/\s+/', ' ', $spokenText);

        // Hitung similarity
        $similarity = $this->calculateSimilarity($expectedText, $spokenText);
        $threshold = 0.7; // 70% similarity required

        $isValid = $similarity >= $threshold;

        Log::info('Voice Challenge validation', [
            'expected' => $expectedText,
            'spoken' => $spokenText,
            'similarity' => $similarity,
            'threshold' => $threshold,
            'valid' => $isValid
        ]);

        if ($isValid) {
            // Hapus challenge setelah berhasil divalidasi
            Session::forget('voice_challenge');
        }

        return [
            'valid' => $isValid,
            'expected' => $expectedText,
            'spoken' => $spokenText,
            'similarity' => round($similarity * 100, 2),
            'threshold' => round($threshold * 100, 2),
            'error' => $isValid ? null : "Kata yang diucapkan tidak cocok. Kemiripan: " . round($similarity * 100) . "%"
        ];
    }

    /**
     * Hitung similarity antara dua string menggunakan Levenshtein distance
     * 
     * @param string $str1
     * @param string $str2
     * @return float 0.0 - 1.0
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $distance = levenshtein($str1, $str2);
        $maxLen = max($len1, $len2);

        return 1 - ($distance / $maxLen);
    }

    /**
     * Clear challenge dari session
     */
    public function clearChallenge(): void
    {
        Session::forget('voice_challenge');
    }
}
