<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\DTOs\VoiceVerificationResult;

class VoiceEnrollmentService
{
    public function __construct(
        protected AudioProcessingService $audioService,
        protected VoiceProcessorService $processorService
    ) {}

    /**
     * Enroll a user's voice from an audio file path.
     *
     * @param User $user
     * @param string $audioPath Absolute path to the source audio
     * @return VoiceVerificationResult
     */
    public function enroll(User $user, string $audioPath): VoiceVerificationResult
    {
        try {
            // Normalize to forward slashes (prevents Errno 22 on Windows)
            $audioPath = str_replace('\\', '/', $audioPath);

            // 1. Normalize Audio
            $wavPath = $this->audioService->ensureValidWav($audioPath);

            // 2. Execute Enrollment via Engine
            Log::info("Enrolling voice for user: " . ($user->id ?? 'new_user') . " using file: {$wavPath}");
            $result = $this->processorService->execute('enroll', [$wavPath]);

            if ($result->success) {
                // 3. Move file to permanent storage
                $fileName = 'enroll_' . uniqid() . '_' . time() . '.wav';
                $storagePath = 'voice_enrollments/' . $fileName;

                // Ensure directory exists
                if (!Storage::disk('public')->exists('voice_enrollments')) {
                    Storage::disk('public')->makeDirectory('voice_enrollments');
                }

                // Copy from local path to storage
                Storage::disk('public')->put($storagePath, file_get_contents($wavPath));

                // 4. Update Model Attributes
                $user->voice_path = $storagePath;
                $user->voice_embedding = $result->extra['voice_embedding'] ?? null;
                $user->voice_enrolled_at = now();

                // If user already exists, save immediately
                if ($user->exists) {
                    $user->save();
                }

                Log::info("Voice enrolled successfully for user: " . ($user->id ?? 'new_user'));
            }

            // 5. Cleanup temp files
            $this->audioService->cleanup([$wavPath]);
            if ($wavPath !== $audioPath && file_exists($audioPath)) {
                @unlink($audioPath);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Voice enrollment failed: " . $e->getMessage());
            return VoiceVerificationResult::failure($e->getMessage());
        }
    }
}
