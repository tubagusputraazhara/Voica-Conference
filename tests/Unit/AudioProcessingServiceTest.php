<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AudioProcessingService;
use Illuminate\Support\Facades\Storage;

class AudioProcessingServiceTest extends TestCase
{
    protected AudioProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AudioProcessingService();
        Storage::fake('public');
    }

    public function test_base64_to_temp_file_decodes_correctly()
    {
        // Simple base64 for a tiny wav/audio
        $base64 = 'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=';

        $path = $this->service->base64ToTempFile($base64);

        $this->assertFileExists($path);
        $this->assertStringContainsString('voice_', $path);

        unlink($path);
    }

    public function test_cleanup_removes_normalized_files()
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_normalized.wav';
        file_put_contents($path, 'dummy content');

        $this->assertFileExists($path);

        $this->service->cleanup([$path]);

        $this->assertFileDoesNotExist($path);
    }
}
