import sys
import os
import tempfile
import subprocess
import traceback
from contextlib import asynccontextmanager
from typing import List, Optional, Dict, Any
from pathlib import Path

# ── Resolve FFmpeg path ───────────────────────────────────────────
# Prefer the bundled ffmpeg in project root/ffmpeg/bin/ffmpeg.exe
# Falls back to system 'ffmpeg' if the bundled binary isn't present.
_PROJECT_DIR = Path(__file__).resolve().parent.parent
_BUNDLED_FFMPEG = _PROJECT_DIR / "ffmpeg" / "bin" / "ffmpeg.exe"
FFMPEG_BIN = str(_BUNDLED_FFMPEG) if _BUNDLED_FFMPEG.exists() else "ffmpeg"
print(f"Using FFmpeg: {FFMPEG_BIN}", file=sys.stderr)

from fastapi import FastAPI, HTTPException, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn

# Ensure the script directory is in path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# Import the actual processing functions
from voice_processor_ecapa import (
    get_verifier,
    enroll_voice,
    verify_voice,
    verify_secure,
    verify_secure_with_challenge,
    compare_files,
    DEVICE
)

# ── Faster Whisper (Offline STT) ─────────────────────────────────
from faster_whisper import WhisperModel

_whisper_model = None

def get_whisper_model():
    global _whisper_model
    if _whisper_model is None:
        print("Loading Faster Whisper model (small)...", file=sys.stderr)
        # small = keseimbangan terbaik kecepatan & akurasi untuk Bahasa Indonesia
        # int8 = hemat RAM, tidak butuh GPU
        _whisper_model = WhisperModel("small", device="cpu", compute_type="int8")
        print("\u2705 Faster Whisper loaded!", file=sys.stderr)
    return _whisper_model

# Optional: Load anti_spoofing detector at startup as well
from anti_spoofing import get_detector


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Load the heavy ML models into RAM/VRAM
    print(f"Loading models on {DEVICE}...", file=sys.stderr)
    try:
        # Pre-load ECAPA-TDNN model
        get_verifier()
        # Pre-load AASIST Anti-Spoofing model
        get_detector()
        # Pre-load Faster Whisper
        get_whisper_model()
        print("\u2705 All models loaded successfully into memory!", file=sys.stderr)
    except Exception as e:
        print(f"\u274c Failed to load models: {str(e)}", file=sys.stderr)
        traceback.print_exc()
        
    yield
    
    # Shutdown
    print("Shutting down AI service...", file=sys.stderr)


app = FastAPI(
    title="Voica AI Processing Service",
    description="Microservice for Fast Voice Enrollment & Verification\n\nOffline STT via Google Speech Recognition (path-based) or Faster Whisper (upload-based).",
    lifespan=lifespan
)

# Allow requests from Laravel (localhost)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://127.0.0.1:8000", "http://localhost:8000", "http://127.0.0.1:80", "http://localhost"],
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)

# --- Request Models ---

class EnrollRequest(BaseModel):
    audio_path: str

class VerifyRequest(BaseModel):
    test_audio_path: str
    enrolled_embedding: List[float]
    threshold: float = 0.25

class VerifySecureRequest(BaseModel):
    test_audio_path: str
    enrolled_embedding: List[float]
    threshold: float = 0.35

class VerifyChallengeRequest(BaseModel):
    test_audio_path: str
    enrolled_embedding: List[float]
    expected_text: str
    threshold: float = 0.35

class TranscribeRequest(BaseModel):
    audio_path: str
    language: str = 'id-ID'


# --- Endpoints ---

@app.get("/")
@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "device": DEVICE,
        "models": {
            "ecapa_tdnn": True,
            "aasist": True,
        },
        "message": "Models are loaded in RAM and ready for instant inference."
    }

@app.post("/enroll")
def enroll(req: EnrollRequest):
    if not os.path.exists(req.audio_path):
        raise HTTPException(status_code=400, detail="Audio file not found")
        
    result = enroll_voice(req.audio_path)
    if not result.get('success', False):
        raise HTTPException(status_code=500, detail=result)
        
    return result

@app.post("/verify")
def verify(req: VerifyRequest):
    if not os.path.exists(req.test_audio_path):
        raise HTTPException(status_code=400, detail="Test audio file not found")
        
    result = verify_voice(req.test_audio_path, req.enrolled_embedding, req.threshold)
    return result

@app.post("/verify-secure")
def verify_secure_endpoint(req: VerifySecureRequest):
    if not os.path.exists(req.test_audio_path):
        raise HTTPException(status_code=400, detail="Test audio file not found")
        
    result = verify_secure(req.test_audio_path, req.enrolled_embedding, req.threshold)
    return result

@app.post("/verify-with-challenge")
def verify_with_challenge_endpoint(req: VerifyChallengeRequest):
    if not os.path.exists(req.test_audio_path):
        raise HTTPException(status_code=400, detail="Test audio file not found")
        
    result = verify_secure_with_challenge(
        req.test_audio_path, 
        req.enrolled_embedding, 
        req.expected_text, 
        req.threshold
    )
    return result

@app.post("/transcribe")
def transcribe_endpoint(req: TranscribeRequest):
    """Transcribe audio using file path (untuk backward compatibility dari PHP CLI calls)."""
    if not os.path.exists(req.audio_path):
        raise HTTPException(status_code=400, detail="Audio file not found")
        
    result = transcribe_audio(req.audio_path, req.language)
    return result


@app.post("/transcribe-upload")
async def transcribe_upload_endpoint(audio: UploadFile = File(...), language: str = "id"):
    """
    Transcribe audio blob dari browser MediaRecorder menggunakan Faster Whisper (100% Offline).
    
    Bahasa Indonesia ('id') dipilih sebagai default karena:
    - Whisper tidak punya kode bahasa Sunda ('su')
    - Namun model 'small'/'medium' cukup mampu menangkap kata-kata Sunda Priangan
      yang bunyinya mirip Indonesia (contoh: 'sampun', 'abdi', 'kanggo', dll.)
    - Kata kunci navigasi & transaksi di-handle oleh NLP parser di Layer berikutnya
    
    Proses: Upload -> FFmpeg convert ke WAV 16kHz -> Faster Whisper -> JSON
    """
    tmp_orig_path = None
    tmp_wav_path = None
    try:
        # 1. Simpan blob ke temp file
        suffix = Path(audio.filename).suffix if audio.filename else ".webm"
        with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp_orig:
            content = await audio.read()
            tmp_orig.write(content)
            tmp_orig_path = tmp_orig.name

        # 2. Convert ke WAV PCM 16kHz Mono via FFmpeg
        tmp_wav_path = tmp_orig_path + ".wav"
        ffmpeg_cmd = [
            FFMPEG_BIN, "-y", "-i", tmp_orig_path,
            "-acodec", "pcm_s16le", "-ar", "16000", "-ac", "1",
            tmp_wav_path
        ]
        print(f"Converting {tmp_orig_path} to {tmp_wav_path}...", file=sys.stderr)
        proc = subprocess.run(ffmpeg_cmd, capture_output=True, text=True)
        if proc.returncode != 0:
            return {"success": False, "error": f"FFmpeg gagal: {proc.stderr}"}

        # 3. Faster Whisper transcription (100% offline, no internet)
        whisper = get_whisper_model()
        
        # Gunakan 'id' untuk Indonesia + Sunda Priangan.
        # 'language=None' = auto-detect (lebih lambat tapi lebih fleksibel).
        lang = language if language in ["id", "en", "jv"] else "id"
        
        segments, info = whisper.transcribe(
            tmp_wav_path,
            language=lang,
            beam_size=5,
            vad_filter=True,          # Filter silence otomatis
            vad_parameters=dict(min_silence_duration_ms=300)
        )
        
        transcript = " ".join(seg.text.strip() for seg in segments).strip()
        
        print(f"[Whisper] Detected lang: {info.language} | Transcript: '{transcript}'", file=sys.stderr)
        
        if not transcript:
            return {"success": False, "error": "Suara tidak terdeteksi. Coba ucapkan lebih jelas."}
        
        return {
            "success": True,
            "transcript": transcript,
            "language": info.language,
            "language_probability": round(info.language_probability, 3)
        }

    except Exception as e:
        return {"success": False, "error": str(e), "traceback": traceback.format_exc()}
    finally:
        if tmp_orig_path and os.path.exists(tmp_orig_path):
            os.unlink(tmp_orig_path)
        if tmp_wav_path and os.path.exists(tmp_wav_path):
            os.unlink(tmp_wav_path)


if __name__ == "__main__":
    uvicorn.run("api_server:app", host="127.0.0.1", port=8001, reload=True)
