<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class AudioProcessingService
{
    /**
     * Convert base64 audio to a temporary file.
     *
     * @param string $base64Data
     * @return string Absolute path to the temporary file
     * @throws \Exception
     */
    public function base64ToTempFile(string $base64Data): string
    {
        // Extract the base64 encoded binary data
        if (preg_match('/^data:audio\/([^;]+)(?:;[^,]+)*;base64,/', $base64Data, $matches)) {
            $type = strtolower($matches[1]);
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        } else {
            Log::error('Invalid base64 audio header format');
            throw new \Exception('Invalid base64 audio data format');
        }

        $audioData = base64_decode($base64Data);

        if ($audioData === false) {
            throw new \Exception('Failed to decode base64 data');
        }

        $tempFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('voice_', true) . '.' . $type;
        file_put_contents($tempFilePath, $audioData);

        return $tempFilePath;
    }

    /**
     * Ensure the audio file is a valid RIFF WAV with correct sampling for the engine.
     *
     * @param string $path Absolute path to the file
     * @param int $sampleRate Target sample rate (default 16000)
     * @return string Path to the converted file (original path if no conversion needed)
     */
    public function ensureValidWav(string $path, int $sampleRate = 16000): string
    {
        if (!file_exists($path)) {
            Log::error("Audio file not found for conversion: {$path}");
            return $path;
        }

        // Check if it's already a RIFF WAV
        $firstFour = file_get_contents($path, false, null, 0, 4);
        if ($firstFour === 'RIFF') {
            // Check sample rate using FFmpeg or assume engine handles it?
            // For now, let's always normalize to 16kHz via FFmpeg to be safe
            // if it's not already correct, or if we want high reliability.
            return $this->convertToStandardWav($path, $sampleRate);
        }

        return $this->convertToStandardWav($path, $sampleRate);
    }

    /**
     * Convert audio to standard WAV format using FFmpeg.
     *
     * @param string $inputPath
     * @param int $sampleRate
     * @return string
     */
    protected function convertToStandardWav(string $inputPath, int $sampleRate): string
    {
        // Use forward slashes to prevent path double-encoding issues (Errno 22) on Windows
        $outputPath = str_replace('\\', '/', $inputPath . '_normalized.wav');
        $inputPath  = str_replace('\\', '/', $inputPath);

        // Command: ffmpeg -y -i input -ar 16000 -ac 1 output.wav
        // Use configured FFmpeg path (defaults to 'ffmpeg' in system PATH)
        $ffmpegPath = config('voice.ffmpeg_path', 'ffmpeg');

        $process = new Process([
            $ffmpegPath,
            '-y',
            '-i',
            $inputPath,
            '-ar',
            (string)$sampleRate,
            '-ac',
            '1',
            $outputPath
        ]);

        try {
            $process->mustRun();

            // If processing original, we might want to swap files or just return new path
            // For safety in this brownfield, return the new path and let caller handle cleanup
            return $outputPath;
        } catch (\Exception $e) {
            $errOutput = $process->getErrorOutput();
            Log::error("FFmpeg conversion failed for [{$inputPath}]: " . $e->getMessage());
            Log::error("FFmpeg stderr: " . $errOutput);
            return $inputPath; // Fallback to original
        }
    }

    /**
     * Cleanup converted files.
     *
     * @param array $paths
     * @return void
     */
    public function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            if (str_contains($path, '_normalized.wav') && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
