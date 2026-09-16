<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\DTOs\VoiceVerificationResult;

class VoiceVerificationResultTest extends TestCase
{
    public function test_from_array_creates_dto_correctly()
    {
        $data = [
            'success' => true,
            'is_match' => true,
            'similarity' => 0.85,
            'threshold' => 0.70,
            'liveness' => [
                'bonafide_probability' => 95.0,
                'security_level' => 'high'
            ],
            'device' => 'cuda',
            'embedding_size' => 192
        ];

        $dto = VoiceVerificationResult::fromArray($data);

        $this->assertTrue($dto->success);
        $this->assertTrue($dto->isMatch);
        $this->assertEquals(0.85, $dto->similarity);
        $this->assertEquals(0.70, $dto->threshold);
        $this->assertEquals(95.0, $dto->liveness['bonafide_probability']);
        $this->assertEquals('cuda', $dto->extra['device']);
        $this->assertEquals(192, $dto->extra['embedding_size']);
    }

    public function test_failure_helper_returns_expected_dto()
    {
        $dto = VoiceVerificationResult::failure("Something went wrong");

        $this->assertFalse($dto->success);
        $this->assertFalse($dto->isMatch);
        $this->assertEquals(0.0, $dto->similarity);
        $this->assertEquals("Something went wrong", $dto->error);
    }

    public function test_from_array_handles_missing_keys_gracefully()
    {
        $data = [
            'success' => true
        ];

        $dto = VoiceVerificationResult::fromArray($data);

        $this->assertTrue($dto->success);
        $this->assertFalse($dto->isMatch);
        $this->assertEquals(0.0, $dto->similarity);
        $this->assertEmpty($dto->liveness);
    }
}
