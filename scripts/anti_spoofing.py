#!/usr/bin/env python3
"""
Anti-Spoofing Detection using AASIST
Detects replay attacks, TTS, and deepfake audio
"""

import sys
import json
import os
import torch
import torchaudio
import torchaudio.transforms as T

# Setup paths
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.dirname(SCRIPT_DIR)
AASIST_DIR = os.path.join(SCRIPT_DIR, 'aasist')
MODEL_PATH = os.path.join(AASIST_DIR, 'models', 'weights', 'AASIST.pth')

# Add AASIST to path
sys.path.insert(0, AASIST_DIR)

# Import AASIST model
from models.AASIST import Model as AASIST

# Device
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

# Model config (from AASIST.conf)
MODEL_CONFIG = {
    "architecture": "AASIST",
    "nb_samp": 64600,
    "first_conv": 128,
    "filts": [70, [1, 32], [32, 32], [32, 64], [64, 64]],
    "gat_dims": [64, 32],
    "pool_ratios": [0.5, 0.7, 0.5, 0.5],
    "temperatures": [2.0, 2.0, 100.0, 100.0]
}


class AntiSpoofingDetector:
    """Detects spoofed/fake audio using AASIST model"""
    
    def __init__(self, model_path: str, device: str = "cpu"):
        self.device = device
        self.sample_rate = 16000
        self.nb_samp = MODEL_CONFIG["nb_samp"]
        
        # Load AASIST model
        self.model = AASIST(MODEL_CONFIG)
        
        # Load checkpoint (set weights_only=False for PyTorch 2.6+ compatibility with older models)
        checkpoint = torch.load(model_path, map_location=device, weights_only=False)
        self.model.load_state_dict(checkpoint)
        self.model.to(device)
        self.model.eval()
        
        print(f"AASIST model loaded on {device}", file=sys.stderr)
    
    def _preprocess_audio(self, audio_path: str) -> torch.Tensor:
        """Load and preprocess audio for AASIST using soundfile to avoid torchaudio backend issues"""
        # Normalize path to avoid Windows backslash double-encoding issues (Errno 22)
        from pathlib import Path
        audio_path = str(Path(audio_path).resolve())
        try:
            import soundfile as sf
            
            # Load audio using soundfile
            data, sr = sf.read(audio_path)
            
            # Convert to float32 if not already
            if data.dtype != 'float32':
                data = data.astype('float32')
                
            # Convert to tensor and reshape to [channels, samples]
            # soundfile returns [samples, channels]
            if len(data.shape) == 1:
                waveform = torch.from_numpy(data).unsqueeze(0)
            else:
                waveform = torch.from_numpy(data).transpose(0, 1)
                
        except Exception as e:
            # Fallback to librosa if soundfile fails
            import librosa
            data, sr = librosa.load(audio_path, sr=None)
            waveform = torch.from_numpy(data).unsqueeze(0)
            
        # Resample if needed
        if sr != self.sample_rate:
            resampler = T.Resample(sr, self.sample_rate)
            waveform = resampler(waveform)
        
        # Convert to mono
        if waveform.shape[0] > 1:
            waveform = torch.mean(waveform, dim=0, keepdim=True)
        
        # Flatten to 1D
        waveform = waveform.squeeze(0)
        
        # Pad or truncate to fixed length (64600 samples ~ 4 seconds)
        if waveform.shape[0] < self.nb_samp:
            # Pad with zeros
            padding = self.nb_samp - waveform.shape[0]
            waveform = torch.nn.functional.pad(waveform, (0, padding))
        else:
            # Truncate
            waveform = waveform[:self.nb_samp]
        
        return waveform
    
    def detect(self, audio_path: str) -> dict:
        """
        Detect if audio is bonafide or spoof
        
        Returns:
            dict with:
            - is_bonafide: True if real voice, False if spoof
            - bonafide_probability: Probability of being real (0-100)
            - spoof_probability: Probability of being fake (0-100)
        """
        try:
            # Preprocess audio
            waveform = self._preprocess_audio(audio_path)
            
            # Add batch dimension
            waveform = waveform.unsqueeze(0).to(self.device)
            
            with torch.no_grad():
                # AASIST output: (last_hidden, output)
                # output shape: [batch, 2] where index 0 = spoof, 1 = bonafide
                _, output = self.model(waveform)
                
                # Apply softmax to get probabilities
                probs = torch.softmax(output, dim=1)
                
                spoof_prob = probs[0, 0].item()
                bonafide_prob = probs[0, 1].item()
            
            # Threshold for bonafide detection
            # 
            # Based on testing:
            # - Live browser audio: 0.26% - 92.61% (highly variable)
            # - Replay attack (phone speaker): ~37%
            # 
            # Setting to 0.40 (40%) - balanced security
            # Based on testing:
            # - Live voice (you): 80-81%
            # - Live voice (friend): 42%
            # - Replay from HP: ~37%
            # 
            # 40% blocks replay (37%) but allows legitimate low-quality audio (42%+)
            BONAFIDE_THRESHOLD = 0.40  # 40%
            
            is_bonafide = bonafide_prob > BONAFIDE_THRESHOLD
            
            return {
                'is_bonafide': is_bonafide,
                'bonafide_probability': round(bonafide_prob * 100, 2),
                'spoof_probability': round(spoof_prob * 100, 2),
                'confidence': round(max(bonafide_prob, spoof_prob) * 100, 2),
                'threshold_used': BONAFIDE_THRESHOLD
            }
            
        except Exception as e:
            raise Exception(f"Error detecting spoof: {str(e)}")


# Global detector instance
_detector = None


def get_detector():
    global _detector
    if _detector is None:
        print("Loading AASIST anti-spoofing model...", file=sys.stderr)
        _detector = AntiSpoofingDetector(MODEL_PATH, DEVICE)
    return _detector


def check_liveness(audio_path: str) -> dict:
    """Check if audio is from a live person (not spoofed)"""
    try:
        detector = get_detector()
        result = detector.detect(audio_path)
        
        return {
            'success': True,
            'model': 'AASIST',
            'device': DEVICE,
            **result
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
            'error': 'Usage: anti_spoofing.py [check <audio_path> | test]'
        }))
        sys.exit(1)
    
    command = sys.argv[1]
    
    if command == 'test':
        print(json.dumps({
            'model_path': MODEL_PATH,
            'model_exists': os.path.exists(MODEL_PATH),
            'device': DEVICE,
            'cuda_available': torch.cuda.is_available()
        }, indent=2))
    elif command == 'check':
        if len(sys.argv) < 3:
            print(json.dumps({
                'success': False,
                'error': 'Usage: anti_spoofing.py check <audio_path>'
            }))
            sys.exit(1)
        audio_path = sys.argv[2]
        result = check_liveness(audio_path)
        print(json.dumps(result))
    else:
        print(json.dumps({
            'success': False,
            'error': f'Unknown command: {command}'
        }))
        sys.exit(1)


if __name__ == '__main__':
    main()
