#!/usr/bin/env python3
"""
Shared 2-layer (AASIST + speaker model) / 3-layer (STT + AASIST + speaker
model) verification pipeline, used by Model 2 (ResNet-34) and Model 3
(WavLM) -- see voice_processor_resnet34.py / voice_processor_wavlm.py.

ECAPA-TDNN (voice_processor_ecapa.py) keeps its OWN inline copy of this
logic by design (that file is intentionally left untouched as the paper's
baseline). This module exists so Model 2 & 3 don't duplicate the adaptive
AASIST-threshold logic between themselves.
"""

import sys
import re
import numpy as np

from antispoofing.aasist_engine import check_liveness
# Reuse STT (Google Speech Recognition) from the ECAPA module -- read-only
# import, does not modify voice_processor_ecapa.py.
from voice_processor_ecapa import transcribe_audio


def _text_similarity(s1: str, s2: str) -> float:
    """Levenshtein-based similarity, range [0, 1]."""
    if s1 == s2:
        return 1.0
    len1, len2 = len(s1), len(s2)
    if len1 == 0 or len2 == 0:
        return 0.0

    matrix = [[0] * (len2 + 1) for _ in range(len1 + 1)]
    for i in range(len1 + 1):
        matrix[i][0] = i
    for j in range(len2 + 1):
        matrix[0][j] = j

    for i in range(1, len1 + 1):
        for j in range(1, len2 + 1):
            cost = 0 if s1[i - 1] == s2[j - 1] else 1
            matrix[i][j] = min(
                matrix[i - 1][j] + 1,
                matrix[i][j - 1] + 1,
                matrix[i - 1][j - 1] + cost
            )

    distance = matrix[len1][len2]
    return 1 - (distance / max(len1, len2))


def verify_secure(verifier, test_audio_path: str, enrolled_embedding: list,
                   base_threshold: float, model_label: str, device: str) -> dict:
    """
    2-LAYER SECURE VERIFICATION (ADAPTIVE):
    Layer 1: Anti-spoofing check (AASIST)
    Layer 2: Speaker verification (backbone-agnostic, via `verifier`)

    ADAPTIVE LOGIC (identik dengan voice_processor_ecapa.py; zona berasal dari
    antispoofing/aasist_engine.py, batas di bawah adalah default-nya untuk semua bobot,
    bisa diubah lewat AASIST_BLOCK_BELOW / AASIST_SAFE_FROM):
    - AASIST > 89%: Standard Security (pakai base_threshold apa adanya)
    - AASIST 8.75-89%: High Security (base_threshold + 0.10, cap 0.85) - "Grey Zone"
    - AASIST < 8.75%: BLOCKED (Spoof detected)
    """
    try:
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
        # The zone cutoffs depend on which AASIST weights are loaded
        zone = liveness_result.get('zone') or (
            'safe' if bonafide_prob >= 89.0 else 'grey' if bonafide_prob >= 8.75 else 'blocked')

        current_threshold = base_threshold
        security_level = "standard"
        is_spoof_blocked = False

        if zone == 'safe':
            security_level = "standard"
            current_threshold = base_threshold
        elif zone == 'grey':
            grey_threshold = min(base_threshold + 0.10, 0.85)
            security_level = "elevated"
            current_threshold = grey_threshold
        else:
            is_spoof_blocked = True

        if is_spoof_blocked:
            return {
                'success': True,
                'is_match': False,
                'rejected_reason': 'spoof_detected',
                'rejected_layer': 1,
                'message': f"Suara terdeteksi PALSU/REKAMAN (spoof probability: {spoof_prob}%)",
                'liveness': {
                    'is_bonafide': False,
                    'bonafide_probability': bonafide_prob,
                    'spoof_probability': spoof_prob,
                    'security_level': 'blocked',
                    'weights': liveness_result.get('weights')  # which AASIST weights decided
                },
                'model': f"AASIST + {model_label}",
                'device': device
            }

        print(f"Running Layer 2: Speaker verification ({model_label}, "
              f"Threshold: {current_threshold:.2f})...", file=sys.stderr)

        test_embedding = verifier.extract_embedding(test_audio_path)
        enrolled_np = np.array(enrolled_embedding)
        similarity = verifier.compute_similarity(enrolled_np, test_embedding)

        is_match = similarity >= current_threshold
        similarity_percentage = (similarity + 1) / 2 * 100
        threshold_percentage = (current_threshold + 1) / 2 * 100

        return {
            'success': True,
            'is_match': is_match,
            'similarity': round(similarity, 4),
            'similarity_percentage': round(similarity_percentage, 2),
            'threshold': current_threshold,
            'threshold_percentage': round(threshold_percentage, 2),
            'liveness': {
                'is_bonafide': True,
                'bonafide_probability': bonafide_prob,
                'spoof_probability': spoof_prob,
                'security_level': security_level,
                'weights': liveness_result.get('weights')  # which AASIST weights decided
            },
            'model': f"AASIST + {model_label}",
            'device': device
        }

    except Exception as e:
        import traceback
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }


def verify_secure_with_challenge(verifier, test_audio_path: str, enrolled_embedding: list,
                                  expected_text: str, base_threshold: float,
                                  model_label: str, device: str) -> dict:
    """
    3-LAYER SECURE VERIFICATION:
    Layer 1: Text-Dependent Challenge (STT) - LIVENESS
    Layer 2: Anti-spoofing check (AASIST)
    Layer 3: Speaker verification (backbone-agnostic, via `verifier`)
    """
    try:
        if expected_text:
            print("Running Layer 1: Text-Dependent Challenge (STT)...", file=sys.stderr)

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

            transcript_normalized = re.sub(r'\s+', '', transcript)
            expected_normalized = re.sub(r'\s+', '', expected_clean)

            text_similarity = _text_similarity(transcript_normalized, expected_normalized)
            text_threshold = 0.70

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

        result = verify_secure(verifier, test_audio_path, enrolled_embedding,
                                base_threshold, model_label, device)

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
