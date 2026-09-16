<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Voice Processor Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Python-based voice verification engine.
    | Runs as a FastAPI Microservice for performance (no cold-start).
    | Start the server: php artisan voice:serve  OR  scripts/start-fastapi.bat
    |
    */

    'api_url' => env('VOICE_API_URL', 'http://127.0.0.1:8026'),

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    'log_channel' => 'voice_verification',

    'timeout' => env('VOICE_TIMEOUT', 60), // seconds

    // Faster Whisper model size: tiny | small | medium | large-v3
    // 'small' recommended: ~244MB, good accuracy for Bahasa Indonesia, fast on CPU
    'whisper_model' => env('WHISPER_MODEL', 'small'),
];
