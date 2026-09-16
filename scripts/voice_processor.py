#!/usr/bin/env python3
"""
Voice Recognition Processor for Laravel
Extracts MFCC features and compares voice samples
"""

import sys
import json
import numpy as np
import librosa
from scipy.spatial.distance import cosine
from scipy.stats import pearsonr

from typing import List, Dict, Any, Union, Optional

def extract_mfcc_features(audio_path: str, n_mfcc: int = 13, duration: Optional[float] = None) -> List[float]:
    """
    Extract MFCC features from audio file
    
    Args:
        audio_path: Path to audio file
        n_mfcc: Number of MFCC coefficients
        duration: Max duration to process (seconds)
    
    Returns:
        List of MFCC features (averaged)
    """
    try:
        # Load audio file - 5 detik optimal untuk keamanan & kecepatan
        y, sr = librosa.load(audio_path, duration=duration, sr=16000)
        
        # 1. Extract MFCC features
        mfcc = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=n_mfcc)
        mfcc_mean = np.mean(mfcc, axis=1)
        mfcc_std = np.std(mfcc, axis=1)
        
        # 2. Extract Pitch (F0)
        pitches, magnitudes = librosa.piptrack(y=y, sr=sr)
        pitch_mean = np.mean(pitches[pitches > 0]) if np.any(pitches > 0) else 0
        pitch_std = np.std(pitches[pitches > 0]) if np.any(pitches > 0) else 0
        
        # 3. Extract Spectral Contrast
        spectral_contrast = librosa.feature.spectral_contrast(y=y, sr=sr)
        contrast_mean = np.mean(spectral_contrast, axis=1)
        contrast_std = np.std(spectral_contrast, axis=1)
        
        # 4. Extract Zero Crossing Rate
        zcr = librosa.feature.zero_crossing_rate(y)
        zcr_mean = np.mean(zcr)
        zcr_std = np.std(zcr)
        
        # Combine all features
        features = np.concatenate([
            mfcc_mean, mfcc_std,
            [pitch_mean, pitch_std],
            contrast_mean, contrast_std,
            [zcr_mean, zcr_std]
        ])
        
        return features.tolist()
    
    except Exception as e:
        raise RuntimeError(f"Feature extraction failed: {str(e)}")

def compare_voices(features1: List[float], features2: List[float]) -> float:
    """
    Compare two voice samples using cosine similarity and correlation
    
    Args:
        features1: First voice features
        features2: Second voice features
    
    Returns:
        Similarity score (0-100)
    """
    try:
        f1 = np.array(features1)
        f2 = np.array(features2)
        
        # Cosine similarity (1 - distance)
        cosine_sim = 1 - cosine(f1, f2)
        
        # Pearson correlation
        pearson_corr, _ = pearsonr(f1, f2)
        
        # Combined similarity score
        similarity = (cosine_sim * 0.6 + pearson_corr * 0.4)
        
        # Convert to percentage
        return max(0.0, min(100.0, similarity * 100))
    
    except Exception:
        return 0.0

def enroll_voice(audio_path: str) -> Dict[str, Any]:
    """Enroll a new voice sample."""
    features = extract_mfcc_features(audio_path, duration=5.0)
    return {
        'success': True,
        'voice_path': audio_path,
        'features': features,
        'feature_count': len(features)
    }

def verify_voice(test_audio_path: str, enrolled_features: List[float]) -> Dict[str, Any]:
    """Verify a voice against enrolled features."""
    test_features = extract_mfcc_features(test_audio_path, duration=5.0)
    similarity = compare_voices(enrolled_features, test_features)
    
    threshold = 97.0
    is_match = bool(similarity >= threshold)
    
    return {
        'success': True,
        'similarity': round(similarity, 2),
        'is_match': is_match,
        'threshold': threshold
    }

def main():
    """Main CLI interface."""
    if len(sys.argv) < 2:
        print(json.dumps({'success': False, 'error': 'Missing command. Usage: [enroll|verify]'}))
        sys.exit(1)
    
    command = sys.argv[1]
    
    try:
        if command == 'enroll':
            if len(sys.argv) < 3:
                raise ValueError('Audio path required')
            print(json.dumps(enroll_voice(sys.argv[2])))
        
        elif command == 'verify':
            if len(sys.argv) < 4:
                raise ValueError('Audio path and features required')
            
            test_audio_path = sys.argv[2]
            enrolled_features = json.loads(sys.argv[3])
            print(json.dumps(verify_voice(test_audio_path, enrolled_features)))
        
        else:
            raise ValueError(f'Unknown command: {command}')
    
    except Exception as e:
        print(json.dumps({'success': False, 'error': str(e)}))
        sys.exit(1)

if __name__ == '__main__':
    main()
