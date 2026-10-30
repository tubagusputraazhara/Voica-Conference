#!/usr/bin/env python3
"""
WavLM Voice Processor
Comparison backbone against ECAPA-TDNN (see voice_processor_ecapa.py).

Checkpoint: microsoft/wavlm-base-sv (via transformers.WavLMForXVector)
"""

import sys
import json
import os
import numpy as np
import torch

# Setup paths
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.dirname(SCRIPT_DIR)

CHECKPOINT = "microsoft/wavlm-base-sv"
MODEL_NAME = "WavLM-Base"

# Check if CUDA is available
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"


class WavLMVerifier:
    """Speaker Verifier using WavLM-Base-SV (via transformers)"""

    def __init__(self, device: str = "cpu"):
        from transformers import WavLMForXVector, Wav2Vec2FeatureExtractor
        import torchaudio.transforms as T

        self.device = device
        self._T = T
        self.sample_rate = 16000
        self.resamplers = {}

        self.feature_extractor = Wav2Vec2FeatureExtractor.from_pretrained(CHECKPOINT)
        self.embedding_model = WavLMForXVector.from_pretrained(CHECKPOINT)
        self.embedding_model.to(device)
        self.embedding_model.eval()

    def extract_embedding(self, audio_path: str) -> np.ndarray:
        """Extract speaker embedding from audio file using soundfile"""
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
                self.resamplers[fs] = self._T.Resample(fs, self.sample_rate)
            signal = self.resamplers[fs](signal)

        # Convert to mono
        if signal.shape[0] > 1:
            signal = torch.mean(signal, dim=0, keepdim=True)

        waveform_1d = signal.squeeze(0).numpy()

        inputs = self.feature_extractor(
            waveform_1d, sampling_rate=self.sample_rate, return_tensors="pt"
        )
        inputs = {k: v.to(self.device) for k, v in inputs.items()}

        with torch.no_grad():
            output = self.embedding_model(**inputs)

        return output.embeddings.squeeze().cpu().numpy()

    def compute_similarity(self, emb1: np.ndarray, emb2: np.ndarray) -> float:
        """Compute cosine similarity between two embeddings (range: [-1, 1])"""
        emb1_norm = emb1 / (np.linalg.norm(emb1) + 1e-10)
        emb2_norm = emb2 / (np.linalg.norm(emb2) + 1e-10)
        similarity = np.dot(emb1_norm, emb2_norm)
        return float(similarity)


# Global verifier instance (loaded once)
_verifier = None


def get_verifier():
    global _verifier
    if _verifier is None:
        print(f"Loading {MODEL_NAME} model ({CHECKPOINT})...", file=sys.stderr)
        _verifier = WavLMVerifier(DEVICE)
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
            'model': MODEL_NAME,
            'device': DEVICE
        }
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


# DUMMY threshold sementara supaya enroll/verify bisa langsung dicoba.
# TODO: GANTI dengan threshold optimal hasil EER (bukan nilai ECAPA 0.25 ini) --
# jalankan: python scripts/evaluate_wavlm.py --dataset <path_ke_dataset>
# lalu isi nilai 'threshold_cosine_similarity' dari scripts/eval/results_wavlm.json
DEFAULT_THRESHOLD = 0.25


def verify_voice(test_audio_path: str, enrolled_embedding: list, threshold: float = DEFAULT_THRESHOLD) -> dict:
    """Verify a voice against enrolled embedding"""
    try:
        if threshold is None:
            raise ValueError(
                "threshold belum diisi. Jalankan scripts/eval/compute_eer.py untuk model "
                f"'{MODEL_NAME}' terlebih dahulu, lalu isi DEFAULT_THRESHOLD / parameter threshold."
            )

        v = get_verifier()

        test_embedding = v.extract_embedding(test_audio_path)

        enrolled_np = np.array(enrolled_embedding)
        similarity = v.compute_similarity(enrolled_np, test_embedding)

        is_match = similarity >= threshold

        # Cosine similarity range: [-1, 1] -> map to [0, 100]
        similarity_percentage = (similarity + 1) / 2 * 100

        return {
            'success': True,
            'similarity': round(similarity, 4),
            'similarity_percentage': round(similarity_percentage, 2),
            'threshold': threshold,
            'threshold_percentage': round((threshold + 1) / 2 * 100, 2),
            'is_match': is_match,
            'model': MODEL_NAME,
            'device': DEVICE
        }
    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def verify_secure(test_audio_path: str, enrolled_embedding: list, base_threshold: float = DEFAULT_THRESHOLD) -> dict:
    """2-Layer verification: AASIST + WavLM (logic shared via verification_pipeline.py)"""
    if base_threshold is None:
        return {'success': False, 'error': 'base_threshold belum diisi (lihat DEFAULT_THRESHOLD di file ini)'}
    from verification_pipeline import verify_secure as _verify_secure
    return _verify_secure(get_verifier(), test_audio_path, enrolled_embedding, base_threshold, MODEL_NAME, DEVICE)


def verify_secure_with_challenge(test_audio_path: str, enrolled_embedding: list,
                                  expected_text: str = None, base_threshold: float = DEFAULT_THRESHOLD) -> dict:
    """3-Layer verification: STT + AASIST + WavLM (logic shared via verification_pipeline.py)"""
    if base_threshold is None:
        return {'success': False, 'error': 'base_threshold belum diisi (lihat DEFAULT_THRESHOLD di file ini)'}
    from verification_pipeline import verify_secure_with_challenge as _verify_secure_with_challenge
    return _verify_secure_with_challenge(
        get_verifier(), test_audio_path, enrolled_embedding, expected_text, base_threshold, MODEL_NAME, DEVICE
    )


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
            'model': MODEL_NAME,
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
            'error': 'Usage: voice_processor_wavlm.py [enroll|verify|compare|test] <args>'
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
            threshold = float(sys.argv[4]) if len(sys.argv) > 4 else DEFAULT_THRESHOLD
            result = verify_voice(sys.argv[2], enrolled_embedding, threshold)
            print(json.dumps(result))

        elif command == 'compare':
            if len(sys.argv) < 4:
                raise ValueError('Two audio paths required')
            result = compare_files(sys.argv[2], sys.argv[3])
            print(json.dumps(result))

        elif command == 'test':
            print(json.dumps({
                'model': MODEL_NAME,
                'checkpoint': CHECKPOINT,
                'device': DEVICE,
                'cuda_available': torch.cuda.is_available(),
                'gpu_name': torch.cuda.get_device_name(0) if torch.cuda.is_available() else None
            }, indent=2))

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
