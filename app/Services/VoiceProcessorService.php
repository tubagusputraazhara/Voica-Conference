<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\DTOs\VoiceVerificationResult;

class VoiceProcessorService
{
    /**
     * Execute a voice processing command via FastAPI Microservice.
     *
     * @param string $command 'enroll' or 'verify'
     * @param array $args Arguments for the command
     * @return \App\DTOs\VoiceVerificationResult
     */
    public function execute(string $command, array $args): \App\DTOs\VoiceVerificationResult
    {
        $apiUrl = rtrim(config('voice.api_url', 'http://127.0.0.1:8026'), '/');
        $timeout = config('voice.timeout', 60);

        // Map command strings to actual API endpoints and request bodies
        $endpoint = '';
        $payload = [];

        try {
            switch ($command) {
                case 'enroll':
                    $endpoint = '/enroll';
                    $payload = [
                        'audio_path' => $args[0]
                    ];
                    break;

                case 'verify':
                    $endpoint = '/verify';
                    $payload = [
                        'test_audio_path' => $args[0],
                        'enrolled_embedding' => is_string($args[1]) ? json_decode($args[1], true) : $args[1],
                        'threshold' => isset($args[2]) ? (float)$args[2] : 0.25
                    ];
                    break;

                case 'verify_secure':
                    $endpoint = '/verify-secure';
                    $payload = [
                        'test_audio_path' => $args[0],
                        'enrolled_embedding' => is_string($args[1]) ? json_decode($args[1], true) : $args[1],
                        'threshold' => isset($args[2]) ? (float)$args[2] : 0.35
                    ];
                    break;

                case 'verify_with_challenge':
                    $endpoint = '/verify-with-challenge';
                    $payload = [
                        'test_audio_path' => $args[0],
                        'enrolled_embedding' => is_string($args[1]) ? json_decode($args[1], true) : $args[1],
                        'expected_text' => $args[2],
                        'threshold' => isset($args[3]) ? (float)$args[3] : 0.35
                    ];
                    break;

                case 'transcribe':
                    $endpoint = '/transcribe';
                    $payload = [
                        'audio_path' => $args[0],
                        'language' => $args[1] ?? 'id-ID'
                    ];
                    break;

                default:
                    return VoiceVerificationResult::failure("Unknown API command: " . $command);
            }

            // HTTP Request to FastAPI
            $response = Http::timeout($timeout)
                ->post($apiUrl . $endpoint, $payload);

            if ($response->failed()) {
                $errorMsg = $response->json('detail') ?: $response->body();
                // Ensure error string is not too long or complex array
                if (is_array($errorMsg)) {
                    $errorMsg = json_encode($errorMsg);
                }
                $this->log("API request failed ($endpoint): " . $errorMsg, 'error');
                return VoiceVerificationResult::failure("API Request failed: " . $errorMsg);
            }

            $result = $response->json();

            if (!$result) {
                $this->log("API output invalid JSON", 'error');
                return VoiceVerificationResult::failure("Invalid JSON output from API");
            }

            return VoiceVerificationResult::fromArray($result);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->log("API connection error: " . $e->getMessage(), 'error');
            return VoiceVerificationResult::failure("Connection refused. Is the Voica FastAPI server running at $apiUrl?");
        } catch (\Exception $e) {
            $this->log("API process error: " . $e->getMessage(), 'error');
            return VoiceVerificationResult::failure($e->getMessage());
        }
    }

    /**
     * Log messages to the dedicated voice log channel.
     */
    protected function log(string $message, string $level = 'info'): void
    {
        Log::channel(config('voice.log_channel', 'stack'))->$level("[VoiceProcessor] $message");
    }
}
