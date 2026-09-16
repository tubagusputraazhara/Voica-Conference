# Research: Phase 3 — Verification & Documentation

## Integration Testing (Laravel + Python)

### 1. Mocking vs. Integration

- **Unit Tests**: Mock `VoiceProcessorService` to return pre-defined `VoiceVerificationResult` objects. This tests the Laravel logic without the overhead of Python.
- **Integration Tests**: Run the actual Python script with a small "test" audio sample. This verifies that the environment, permissions, and Python dependencies are correct.

**Proposed Test Cases:**

- `VoiceVerificationResultTest`: Verify DTO creation from sample JSON.
- `AudioProcessingServiceTest`: Verify that files are correctly normalized (using a mock or temp files).
- `VoiceProcessorIntegrationTest`: A test that runs `enroll` and `verify` on a short sample audio file to ensure the bridge works.

### 2. Documentation Standards

We need a `README_DEV.md` that covers:

- **Environment Setup**: Python venv, PHP dependencies, SQLite setup.
- **Subsystem Architecture**: The bridge between Laravel and Python.
- **Model Details**: Explanation of the models used (ECAPA-TDNN, AASIST).
- **Troubleshooting**: Common Windows-specific issues (e.g., Winsock errors).

### 3. Performance Audit

We will measure:

- **Enrollment Latency**: Time taken to extract features.
- **Verification Latency**: Time for 2-layer vs 3-layer verification.
- **Memory Usage**: Peaks during model loading.

## Tools & Commands

- `php artisan test`: Main test runner.
- `Measure-Command`: PowerShell tool for simple timing if needed.
- `cProfile` / `time`: For Python-side profiling.
