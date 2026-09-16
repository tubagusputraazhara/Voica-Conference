<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\DTOs\VoiceVerificationResult;

class VoiceVerificationService
{
    public function __construct(
        protected AudioProcessingService $audioService,
        protected VoiceProcessorService $processorService
    ) {}

    /**
     * Verify user voice using standard or secure layers.
     *
     * @param User $user
     * @param string $audioPath
     * @param array $options [threshold, challenge_text]
     * @return VoiceVerificationResult
     */
    public function verify(User $user, string $audioPath, array $options = []): VoiceVerificationResult
    {
        try {
            $threshold = $options['threshold'] ?? 0.70;
            $challengeText = $options['challenge_text'] ?? null;
            $enrolledFeatures = $user->voice_embedding;

            if (!$enrolledFeatures) {
                return VoiceVerificationResult::failure("User has no enrolled voice features.");
            }

            // Normalize to forward slashes (prevents Errno 22 on Windows)
            $audioPath = str_replace('\\', '/', $audioPath);

            // 1. Normalize Audio
            $wavPath = $this->audioService->ensureValidWav($audioPath);

            // 2. Determine Command
            if ($challengeText) {
                $command = 'verify_with_challenge';
                $args = [$wavPath, json_encode($enrolledFeatures), $challengeText, $threshold];
                Log::info("Running 3-Layer Secure Verification (STT + AASIST + ECAPA) for user: {$user->id}");
            } else {
                $command = 'verify_secure';
                $args = [$wavPath, json_encode($enrolledFeatures), $threshold];
                Log::info("Running 2-Layer Secure Verification (AASIST + ECAPA) for user: {$user->id}");
            }

            // 3. Execute Verification
            $result = $this->processorService->execute($command, $args);

            // 4. Cleanup
            $this->audioService->cleanup([$wavPath]);
            if ($wavPath !== $audioPath) {
                @unlink($audioPath);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Voice verification service failure for user {$user->id}: " . $e->getMessage());
            return VoiceVerificationResult::failure($e->getMessage());
        }
    }
}
