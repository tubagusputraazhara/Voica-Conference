#!/usr/bin/env python3
"""
ECAPA-TDNN Voice Processor
Using direct model loading with proper feature extraction
"""

import sys
import json
import os
import numpy as np
import torch
import torchaudio
import torchaudio.transforms as T

# Monkeypatch for speechbrain compatibility with torchaudio >= 2.1
# Older speechbrain calls list_audio_backends() and set_audio_backend() which were removed.
if not hasattr(torchaudio, "list_audio_backends"):
    def list_audio_backends():
        return ["soundfile"]
    torchaudio.list_audio_backends = list_audio_backends

if not hasattr(torchaudio, "set_audio_backend"):
    def set_audio_backend(backend):
        pass
    torchaudio.set_audio_backend = set_audio_backend

# Setup paths
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.dirname(SCRIPT_DIR)
MODEL_DIR = os.path.join(PROJECT_DIR, "pretrained_models", "spkrec-ecapa-voxceleb")

# Check if CUDA is available
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

# Import ECAPA-TDNN model
from speechbrain.lobes.models.ECAPA_TDNN import ECAPA_TDNN

# Model configuration (from hyperparams.yaml of spkrec-ecapa-voxceleb)
MODEL_CONFIG = {
    'input_size': 80,
    'channels': [1024, 1024, 1024, 1024, 3072],
    'kernel_sizes': [5, 3, 3, 3, 1],
    'dilations': [1, 2, 3, 4, 1],
    'attention_channels': 128,
    'lin_neurons': 192
}


class EcapaTdnnVerifier:
    """Speaker Verifier using ECAPA-TDNN model"""
    
    def __init__(self, model_dir: str, device: str = "cpu"):
        self.device = device
        self.model_dir = model_dir
        self.sample_rate = 16000
        
        # Initialize Mel spectrogram extractor (80 mel bands)
        # Keep on CPU to avoid device mismatch issues
        self.mel_spectrogram = T.MelSpectrogram(
            sample_rate=self.sample_rate,
            n_fft=400,
            win_length=400,  # 25ms at 16kHz
            hop_length=160,  # 10ms at 16kHz
            n_mels=80,
            f_min=0,
            f_max=8000,
        )
        
        # Pre-initialize resamplers for common sample rates
        self.resamplers = {}
        
        # Load ECAPA-TDNN model
        self.embedding_model = ECAPA_TDNN(**MODEL_CONFIG)
        
        # Load checkpoint (prefer finetuned model)
        finetuned_path = os.path.join(PROJECT_DIR, "pretrained_models", "finetuned_ecapa.ckpt")
        base_path = os.path.join(model_dir, "embedding_model.ckpt")
        
        if os.path.exists(finetuned_path):
            ckpt_path = finetuned_path
            print(f"Loaded FINETUNED model from {ckpt_path}", file=sys.stderr)
        elif os.path.exists(base_path):
            ckpt_path = base_path
            print(f"Loaded BASE model from {ckpt_path}", file=sys.stderr)
        else:
            raise FileNotFoundError(f"Model not found at {base_path} or {finetuned_path}")
            
        if os.path.exists(ckpt_path):
            state_dict = torch.load(ckpt_path, map_location=device, weights_only=False)
            self.embedding_model.load_state_dict(state_dict)
            print(f"Loaded model from {ckpt_path}", file=sys.stderr)
        else:
            raise FileNotFoundError(f"Model not found: {ckpt_path}")
        
        self.embedding_model.to(device)
        self.embedding_model.eval()
    
    def extract_embedding(self, audio_path: str) -> np.ndarray:
        """Extract speaker embedding from audio file using soundfile to avoid torchaudio backend issues"""
        # Normalize path to avoid Windows backslash double-encoding issues (Errno 22)
        from pathlib import Path
        audio_path = str(Path(audio_path).resolve())
        try:
            import soundfile as sf
            data, fs = sf.read(audio_path)
            
            if data.dtype != 'float32':
                data = data.astype('float32')
            
            if len(data.shape) == 1:
                signal = torch.from_numpy(data).unsqueeze(0)
            else:
                signal = torch.from_numpy(data).transpose(0, 1)
                
        except Exception:
            import librosa
            data, fs = librosa.load(audio_path, sr=None)
            signal = torch.from_numpy(data).unsqueeze(0)
        
        # Resample to 16kHz if needed
        if fs != self.sample_rate:
            if fs not in self.resamplers:
                self.resamplers[fs] = T.Resample(fs, self.sample_rate)
            signal = self.resamplers[fs](signal)
        
        # Convert to mono if stereo
        if signal.shape[0] > 1:
            signal = torch.mean(signal, dim=0, keepdim=True)
        
        # Compute mel spectrogram features on CPU
        with torch.no_grad():
            # Get mel spectrogram: [1, n_mels, time]
            mel = self.mel_spectrogram(signal)
            
            # Apply log
            mel = torch.log(mel + 1e-6)
            
            # Transpose to [batch, time, n_mels]
            mel = mel.transpose(1, 2)  # [1, time, n_mels]
            
            # Move to device for model inference
            mel = mel.to(self.device)
            
            # Get embedding
            embedding = self.embedding_model(mel)
            
        return embedding.squeeze().cpu().numpy()
    
    def compute_similarity(self, emb1: np.ndarray, emb2: np.ndarray) -> float:
        """Compute cosine similarity between two embeddings"""
        # Normalize embeddings
        emb1_norm = emb1 / (np.linalg.norm(emb1) + 1e-10)
        emb2_norm = emb2 / (np.linalg.norm(emb2) + 1e-10)
        
        # Cosine similarity
        similarity = np.dot(emb1_norm, emb2_norm)
        return float(similarity)


# Global verifier instance (loaded once)
_verifier = None


def get_verifier():
    global _verifier
    if _verifier is None:
        print("Loading ECAPA-TDNN model...", file=sys.stderr)
        _verifier = EcapaTdnnVerifier(MODEL_DIR, DEVICE)
        print(f"Model loaded on {DEVICE}!", file=sys.stderr)
    return _verifier


def enroll_voice(audio_path: str) -> dict:
    """Enroll a new voice sample"""
    try:
        v = get_verifier()
        embedding = v.extract_embedding(audio_path)
        
        return {
            'success': True,
            'voice_embedding': embedding.tolist(),
            'embedding_size': len(embedding),
            'model': 'ECAPA-TDNN',
            'device': DEVICE
        }
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def verify_voice(test_audio_path: str, enrolled_embedding: list, threshold: float = 0.25) -> dict:
    """Verify a voice against enrolled embedding"""
    try:
        v = get_verifier()
        
        # Extract embedding from test audio
        test_embedding = v.extract_embedding(test_audio_path)
        
        # Compute similarity
        enrolled_np = np.array(enrolled_embedding)
        similarity = v.compute_similarity(enrolled_np, test_embedding)
        
        # Determine if match
        is_match = similarity >= threshold
        
        # Convert to percentage for display (0-100)
        # Cosine similarity range: [-1, 1] -> map to [0, 100]
        similarity_percentage = (similarity + 1) / 2 * 100
        
        return {
            'success': True,
            'similarity': round(similarity, 4),
            'similarity_percentage': round(similarity_percentage, 2),
            'threshold': threshold,
            'threshold_percentage': round((threshold + 1) / 2 * 100, 2),
            'is_match': is_match,
            'model': 'ECAPA-TDNN',
            'device': DEVICE
        }
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def compare_files(audio1_path: str, audio2_path: str) -> dict:
    """Compare two audio files directly"""
    try:
        v = get_verifier()
        
        emb1 = v.extract_embedding(audio1_path)
        emb2 = v.extract_embedding(audio2_path)
        
        similarity = v.compute_similarity(emb1, emb2)
        
        return {
            'success': True,
            'similarity': round(similarity, 4),
            'similarity_percentage': round((similarity + 1) / 2 * 100, 2),
            'audio1': audio1_path,
            'audio2': audio2_path,
            'device': DEVICE
        }
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def transcribe_audio(audio_path: str, language: str = 'id-ID') -> dict:
    """
    LAYER 3: LIVENESS DETECTION - Speech-to-Text
    Transcribe audio using Google Speech Recognition
    
    Args:
        audio_path: Path to WAV audio file
        language: Language code (default: Indonesian 'id-ID')
    
    Returns:
        dict with 'success', 'transcript', 'confidence'
    """
    try:
        import speech_recognition as sr
        
        recognizer = sr.Recognizer()
        
        # Load audio file
        with sr.AudioFile(audio_path) as source:
            # Adjust for ambient noise
            recognizer.adjust_for_ambient_noise(source, duration=0.5)
            # Record audio
            audio_data = recognizer.record(source)
        
        # Transcribe using Google Speech Recognition
        try:
            transcript = recognizer.recognize_google(audio_data, language=language)
            return {
                'success': True,
                'transcript': transcript,
                'language': language
            }
        except sr.UnknownValueError:
            return {
                'success': False,
                'error': 'Suara tidak dapat dikenali. Silakan ucapkan lebih jelas.',
                'transcript': ''
            }
        except sr.RequestError as e:
            return {
                'success': False,
                'error': f'Error pada layanan Speech-to-Text: {str(e)}',
                'transcript': ''
            }
            
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def verify_secure_with_challenge(
    test_audio_path: str, 
    enrolled_embedding: list, 
    expected_text: str = None,
    base_threshold: float = 0.35
) -> dict:
    """
    3-LAYER SECURE VERIFICATION:
    Layer 1: Text-Dependent Challenge (STT) - LIVENESS
    Layer 2: Anti-spoofing check (AASIST)
    Layer 3: Speaker verification (ECAPA-TDNN)
    
    Args:
        test_audio_path: Path to test audio WAV
        enrolled_embedding: User's enrolled voice embedding
        expected_text: Challenge text that user should say (Layer 1)
        base_threshold: ECAPA-TDNN similarity threshold
    """
    try:
        # ============================================
        # LAYER 1: TEXT-DEPENDENT CHALLENGE (STT)
        # ============================================
        if expected_text:
            print(f"Running Layer 1: Text-Dependent Challenge (STT)...", file=sys.stderr)
            print(f"Expected text: '{expected_text}'", file=sys.stderr)
            
            stt_result = transcribe_audio(test_audio_path)
            
            if not stt_result.get('success', False):
                return {
                    'success': True,
                    'is_match': False,
                    'rejected_reason': 'stt_failed',
                    'rejected_layer': 1,
                    'message': stt_result.get('error', 'STT gagal'),
                    'transcript': '',
                    'expected_text': expected_text
                }
            
            transcript = stt_result.get('transcript', '').lower().strip()
            expected_clean = expected_text.lower().strip()
            
            # Normalize: hapus SEMUA spasi untuk perbandingan digit
            # Karena STT bisa return '609791' tapi expected '6 0 9 7 9 1'
            import re
            transcript_normalized = re.sub(r'\s+', '', transcript)  # Hapus semua spasi
            expected_normalized = re.sub(r'\s+', '', expected_clean)  # Hapus semua spasi
            
            print(f"Transcript original: '{transcript}'", file=sys.stderr)
            print(f"Transcript normalized: '{transcript_normalized}'", file=sys.stderr)
            print(f"Expected normalized: '{expected_normalized}'", file=sys.stderr)
            
            # Calculate similarity using simple matching
            # For digits: exact match or high similarity
            # Levenshtein-based similarity
            def calc_similarity(s1: str, s2: str) -> float:
                if s1 == s2:
                    return 1.0
                len1, len2 = len(s1), len(s2)
                if len1 == 0 or len2 == 0:
                    return 0.0
                
                # Simple Levenshtein
                matrix = [[0] * (len2 + 1) for _ in range(len1 + 1)]
                for i in range(len1 + 1):
                    matrix[i][0] = i
                for j in range(len2 + 1):
                    matrix[0][j] = j
                    
                for i in range(1, len1 + 1):
                    for j in range(1, len2 + 1):
                        cost = 0 if s1[i-1] == s2[j-1] else 1
                        matrix[i][j] = min(
                            matrix[i-1][j] + 1,
                            matrix[i][j-1] + 1,
                            matrix[i-1][j-1] + cost
                        )
                
                distance = matrix[len1][len2]
                return 1 - (distance / max(len1, len2))
            
            # Gunakan normalized strings untuk perbandingan
            text_similarity = calc_similarity(transcript_normalized, expected_normalized)
            text_threshold = 0.70  # 70% similarity for text
            
            print(f"Similarity: {text_similarity * 100:.1f}%", file=sys.stderr)
            
            if text_similarity < text_threshold:
                return {
                    'success': True,
                    'is_match': False,
                    'rejected_reason': 'wrong_text',
                    'rejected_layer': 1,
                    'message': f"Kata yang diucapkan tidak cocok. Anda berkata '{transcript}', seharusnya '{expected_clean}'",
                    'transcript': transcript,
                    'expected_text': expected_clean,
                    'text_similarity': round(text_similarity * 100, 2)
                }
            
            print(f"Layer 1 PASSED! Text match: {text_similarity * 100:.1f}%", file=sys.stderr)
        
        # ============================================
        # LAYER 2 & 3: Continue with AASIST + ECAPA
        # ============================================
        # Call existing verify_secure for layers 2 & 3
        result = verify_secure(test_audio_path, enrolled_embedding, base_threshold)
        
        # Add Layer 1 info to result
        if expected_text:
            result['layer1_passed'] = True
            result['transcript'] = transcript
            result['expected_text'] = expected_clean
            result['text_similarity'] = round(text_similarity * 100, 2)
        
        return result
        
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def verify_secure(test_audio_path: str, enrolled_embedding: list, base_threshold: float = 0.35) -> dict:
    """
    SECURE 2-LAYER VERIFICATION (ADAPTIVE):
    Layer 1: Anti-spoofing check (AASIST)
    Layer 2: Speaker verification (ECAPA-TDNN)
    
    ADAPTIVE LOGIC:
    - If AASIST > 89%: Standard Security (uses base_threshold from PHP)
    - If AASIST 8.75-89%: High Security (base_threshold + 0.10) - "Grey Zone"
    - If AASIST < 8.75%: BLOCKED (Spoof detected)
    """
    try:
        # Import anti-spoofing module
        from anti_spoofing import check_liveness
        
        # ============================================
        # LAYER 1: ANTI-SPOOFING CHECK (AASIST)
        # ============================================
        print("Running Layer 1: Anti-spoofing check (AASIST)...", file=sys.stderr)
        liveness_result = check_liveness(test_audio_path)
        
        if not liveness_result.get('success', False):
            return {
                'success': False,
                'error': f"Anti-spoofing check failed: {liveness_result.get('error', 'Unknown error')}",
                'layer': 'anti_spoofing'
            }
        
        bonafide_prob = liveness_result.get('bonafide_probability', 0)
        spoof_prob = liveness_result.get('spoof_probability', 0)
        
        # Determine Security Level based on AASIST Score
        current_threshold = base_threshold
        security_level = "standard"
        is_spoof_blocked = False
        
        if bonafide_prob >= 89.0:
            # ZONE 1: SAFE (High Confidence Bonafide)
            # Only HD studio quality or very clean mic passes here.
            print(f"Layer 1: SAFE (Bonafide: {bonafide_prob}%) -> Standard Threshold ({base_threshold})", file=sys.stderr)
            security_level = "standard"
            current_threshold = base_threshold  # Use PHP threshold as-is
            
        elif bonafide_prob >= 8.75:
            # ZONE 2: GREY (Review Mode - Risk Based Auth)
            # Includes:
            # - Real User on Web Mic
            # - Replay Attacks
            # Apply a stricter threshold (+0.10) but still respect PHP's base.
            grey_threshold = min(base_threshold + 0.10, 0.85)  # Cap at 0.85 max
            print(f"Layer 1: GREY ZONE (Bonafide: {bonafide_prob}%) -> Elevated Threshold ({grey_threshold})", file=sys.stderr)
            security_level = "elevated"
            current_threshold = grey_threshold
            
        else:
            # ZONE 3: DANGER (Blocked)
            # Pure noise or obvious synthetic
            print(f"Layer 1: DANGER (Bonafide: {bonafide_prob}%) -> BLOCKED", file=sys.stderr)
            is_spoof_blocked = True

        # Handle Blocked Spoof
        if is_spoof_blocked:
            return {
                'success': True,
                'is_match': False,
                'rejected_reason': 'spoof_detected',
                'rejected_layer': 1,
                'message': f"Suara terdeteksi PALSU/REKAMAN (spoof probability: {spoof_prob}%)",
                'liveness': {
                    'is_bonafide': False,  # Force false
                    'bonafide_probability': bonafide_prob,
                    'spoof_probability': spoof_prob,
                    'security_level': 'blocked'
                },
                'model': 'AASIST + ECAPA-TDNN',
                'device': DEVICE
            }
        
        # ============================================
        # LAYER 2: SPEAKER VERIFICATION (ECAPA-TDNN)
        # ============================================
        print(f"Running Layer 2: Speaker verification (Threshold: {current_threshold:.2f})...", file=sys.stderr)
        
        v = get_verifier()
        
        # Extract embedding from test audio
        test_embedding = v.extract_embedding(test_audio_path)
        
        # Compute similarity
        enrolled_np = np.array(enrolled_embedding)
        similarity = v.compute_similarity(enrolled_np, test_embedding)
        
        # Determine if match using ADAPTED threshold
        is_match = similarity >= current_threshold
        
        # Convert to percentage
        similarity_percentage = (similarity + 1) / 2 * 100
        threshold_percentage = (current_threshold + 1) / 2 * 100
        
        if not is_match:
            print(f"Layer 2 FAILED! Similarity: {similarity_percentage:.2f}% < {threshold_percentage:.2f}% (Level: {security_level})", file=sys.stderr)
        else:
            print(f"Layer 2 PASSED! Similarity: {similarity_percentage:.2f}% >= {threshold_percentage:.2f}% (Level: {security_level})", file=sys.stderr)
        
        return {
            'success': True,
            'is_match': is_match,
            'similarity': round(similarity, 4),
            'similarity_percentage': round(similarity_percentage, 2),
            'threshold': current_threshold,
            'threshold_percentage': round(threshold_percentage, 2),
            'liveness': {
                'is_bonafide': True, # Passed adaptive check
                'bonafide_probability': bonafide_prob,
                'spoof_probability': spoof_prob,
                'security_level': security_level
            },
            'model': 'AASIST + ECAPA-TDNN',
            'device': DEVICE
        }
        
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def main():
    """CLI interface"""
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'error': 'Usage: voice_processor_ecapa.py [enroll|verify|compare|test] <args>'
        }))
        sys.exit(1)
    
    command = sys.argv[1]
    
    try:
        if command == 'enroll':
            if len(sys.argv) < 3:
                raise ValueError('Audio path required')
            result = enroll_voice(sys.argv[2])
            print(json.dumps(result))
        
        elif command == 'verify':
            if len(sys.argv) < 4:
                raise ValueError('Test audio path and enrolled embedding required')
            enrolled_embedding = json.loads(sys.argv[3])
            threshold = float(sys.argv[4]) if len(sys.argv) > 4 else 0.25
            result = verify_voice(sys.argv[2], enrolled_embedding, threshold)
            print(json.dumps(result))
        
        elif command == 'compare':
            if len(sys.argv) < 4:
                raise ValueError('Two audio paths required')
            result = compare_files(sys.argv[2], sys.argv[3])
            print(json.dumps(result))
        
        elif command == 'test':
            print(json.dumps({
                'device': DEVICE,
                'model_dir': MODEL_DIR,
                'cuda_available': torch.cuda.is_available(),
                'gpu_name': torch.cuda.get_device_name(0) if torch.cuda.is_available() else None
            }, indent=2))
        
        elif command == 'verify_secure':
            # 2-Layer verification: AASIST + ECAPA-TDNN
            if len(sys.argv) < 4:
                raise ValueError('Test audio path and enrolled embedding required')
            enrolled_embedding = json.loads(sys.argv[3])
            threshold = float(sys.argv[4]) if len(sys.argv) > 4 else 0.35
            result = verify_secure(sys.argv[2], enrolled_embedding, threshold)
            print(json.dumps(result))
        
        elif command == 'transcribe':
            # Speech-to-Text only
            if len(sys.argv) < 3:
                raise ValueError('Audio path required')
            language = sys.argv[3] if len(sys.argv) > 3 else 'id-ID'
            result = transcribe_audio(sys.argv[2], language)
            print(json.dumps(result))
        
        elif command == 'verify_with_challenge':
            # 3-Layer verification: STT + AASIST + ECAPA-TDNN
            if len(sys.argv) < 5:
                raise ValueError('Test audio path, enrolled embedding, and challenge text required')
            enrolled_embedding = json.loads(sys.argv[3])
            expected_text = sys.argv[4]
            threshold = float(sys.argv[5]) if len(sys.argv) > 5 else 0.35
            result = verify_secure_with_challenge(sys.argv[2], enrolled_embedding, expected_text, threshold)
            print(json.dumps(result))
        
        else:
            raise ValueError(f'Unknown command: {command}')
    
    except Exception as e:
        import traceback
        print(json.dumps({
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }))
        sys.exit(1)


if __name__ == '__main__':
    main()
