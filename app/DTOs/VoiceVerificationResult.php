<?php

namespace App\DTOs;

readonly class VoiceVerificationResult
{
    /**
     * @param bool $success Whether the script/process executed successfully
     * @param bool $isMatch Whether the voice matched (Speaker Verification)
     * @param float $similarity Cosine similarity score [-1.0 to 1.0]
     * @param float $threshold Threshold used for match
     * @param array $liveness Liveness detection data (bonafide_probability, etc)
     * @param string|null $error Error message if success is false
     * @param array $extra Additional data from the engine
     */
    public function __construct(
        public bool $success,
        public bool $isMatch,
        public float $similarity,
        public float $threshold,
        public array $liveness = [],
        public ?string $error = null,
        public array $extra = []
    ) {}

    /**
     * Create a DTO instance from the raw array output of the Python engine.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            isMatch: $data['is_match'] ?? false,
            similarity: (float) ($data['similarity'] ?? 0.0),
            threshold: (float) ($data['threshold'] ?? 0.0),
            liveness: $data['liveness'] ?? [],
            error: $data['error'] ?? null,
            extra: array_diff_key($data, array_flip([
                'success',
                'is_match',
                'similarity',
                'threshold',
                'liveness',
                'error'
            ]))
        );
    }

    /**
     * Helper to get a failure result.
     *
     * @param string $error
     * @return self
     */
    public static function failure(string $error): self
    {
        return new self(
            success: false,
            isMatch: false,
            similarity: 0.0,
            threshold: 0.0,
            error: $error
        );
    }
}
