# Voice Processing Engine (Python)

This directory contains the core voice processing logic using State-of-the-Art (SOTA) AI models.

## 🚀 Components

### 1. `voice_processor_ecapa.py`

The main CLI entry point. It uses the **ECAPA-TDNN** model from SpeechBrain for speaker verification.

- **Features**: Speaker Embedding extraction, Similarity computation.
- **Library**: `speechbrain`, `torch`, `torchaudio`.

### 2. `anti_spoofing.py`

Implements the **AASIST** model for liveness detection (Anti-Spoofing).

- **Goal**: Detect if the audio is a recording (replayed) or synthesized (deepfake).
- **Security Levels**:
    - `blocked`: High probability of spoofing.
    - `suspicious`: Medium probability.
    - `bonafide`: Likely original human voice.

### 3. `setup.ps1`

Automation script to setup the Python environment on Windows.

- Creates `.venv`.
- Installs dependencies from `requirements.txt`.

## ⚙️ How it Works (Bridge Protocol)

Laravel calls these scripts with specific commands:

- `enroll`: Extracts and returns a 192-dimensional embedding.
- `verify_secure`: Performs anti-spoofing + similarity check.
- `verify_with_challenge`: Performs STT transcript validation + anti-spoofing + similarity check.

### JSON Output Format

The engine always returns a JSON object on stdout:

```json
{
    "success": true,
    "is_match": true,
    "similarity": 0.92,
    "threshold": 0.85,
    "liveness": {
        "security_level": "bonafide",
        "bonafide_probability": 99.8
    },
    "extra": {
        "similarity_percentage": 92.0
    }
}
```

## 🔧 Maintenance

- Ensure your GPU drivers are up to date if using CUDA.
- The models are automatically downloaded from Hugging Face on the first run.
