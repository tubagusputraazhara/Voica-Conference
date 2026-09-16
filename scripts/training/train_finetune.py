#!/usr/bin/env python3
"""
ECAPA-TDNN Fine-tuning Script for Indonesian Language
Using GE2E Loss for better Speaker Verification performance.

Dataset: Indonesian Speech with Accents (5 Ethnic Groups)
Source: https://www.kaggle.com/datasets/hengkymulyono/indonesian-speech-with-accents-5-ethnic-groups

Usage:
    python scripts/training/train_finetune.py --dataset_path datasets/indonesian-speech/indonesian-accent-raw --epochs 20
"""

import os
import sys
import re
import glob
import random
import argparse
import numpy as np
import torch
import torch.nn as nn
import torch.optim as optim
import torchaudio
import torchaudio.transforms as T
from torch.utils.data import Dataset, DataLoader, Sampler
from tqdm import tqdm
import subprocess

# Add scripts directory to path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.dirname(os.path.dirname(SCRIPT_DIR))
sys.path.append(os.path.join(PROJECT_DIR, 'scripts'))

from speechbrain.lobes.models.ECAPA_TDNN import ECAPA_TDNN
from training.loss import GE2ELoss  # Custom GE2E Loss

# Configuration
SAMPLE_RATE = 16000
N_MELS = 80
EMBEDDING_SIZE = 192
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

# GE2E Hyperparameters
N_SPEAKERS_PER_BATCH = 4   # Number of speakers per batch
M_UTTERANCES_PER_SPEAKER = 4 # Number of utterances per speaker
BATCH_SIZE = N_SPEAKERS_PER_BATCH * M_UTTERANCES_PER_SPEAKER # 16

print(f"🖥️ Device: {DEVICE}")
if DEVICE == "cuda":
    print(f"   GPU: {torch.cuda.get_device_name(0)}")
    print(f"   VRAM: {torch.cuda.get_device_properties(0).total_memory / 1e9:.1f} GB")


# =============================================================================
# 1. Audio Pre-processing
# =============================================================================
def convert_to_wav(input_path, output_dir):
    """Convert audio/video file to 16kHz mono WAV using FFmpeg"""
    basename = os.path.splitext(os.path.basename(input_path))[0]
    output_path = os.path.join(output_dir, f"{basename}.wav")
    
    if os.path.exists(output_path):
        return output_path
    
    try:
        cmd = [
            'ffmpeg', '-y', '-i', input_path,
            '-ar', '16000', '-ac', '1', '-acodec', 'pcm_s16le',
            output_path
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
        return output_path if result.returncode == 0 and os.path.exists(output_path) else None
    except Exception as e:
        print(f"  ❌ Convert failed for {input_path}: {e}")
        return None

def collect_all_audio(dataset_root):
    """Collect vocal data and convert to 16kHz WAV"""
    all_wav_files = []
    wav_output_dir = os.path.join(dataset_root, "indonesian-accent-wav-16k")
    os.makedirs(wav_output_dir, exist_ok=True)
    
    # Raw folders
    raw_dir = os.path.join(dataset_root, "indonesian-accent-raw")
    if os.path.exists(raw_dir):
        raw_files = []
        for ext in ('*.wav', '*.mp3', '*.m4a', '*.mp4', '*.ogg', '*.flac', '*.MP3'):
            raw_files.extend(glob.glob(os.path.join(raw_dir, ext)))
        
        print(f"\n📂 [RAW] Found {len(raw_files)} files")
        for f in tqdm(raw_files, desc="Converting RAW"):
            wav_path = convert_to_wav(f, wav_output_dir)
            if wav_path: all_wav_files.append(wav_path)

    # 8khz folder
    hz_dir = os.path.join(dataset_root, "indonesian-accent-8000hz")
    if os.path.exists(hz_dir):
        hz_files = glob.glob(os.path.join(hz_dir, "*.wav"))
        print(f"\n📂 [8000hz] Found {len(hz_files)} files")
        for f in tqdm(hz_files, desc="Converting 8kHz→16kHz"):
            wav_path = convert_to_wav(f, wav_output_dir)
            if wav_path: all_wav_files.append(wav_path)
            
    print(f"\n📊 Total WAV files ready: {len(all_wav_files)}")
    return all_wav_files

def parse_speaker_info(filename):
    basename = os.path.splitext(os.path.basename(filename))[0]
    # Format 1: 01-jawa-003-F-restu
    match = re.match(r'(\d+)-(\w+)-(\d+)-([FM]F?)-(.+)', basename)
    if match:
        ethnic_id, ethnic, speaker_num, gender, name = match.groups()
        return f"{ethnic}-{gender}-{name.split('-')[0]}"
    
    # Format 2: ethnic-gender-name
    match = re.match(r'(\w+)-([FM]F?)-(.+)', basename)
    if match:
        ethnic, gender, name = match.groups()
        return f"{ethnic}-{gender}-{name.split('split')[0].rstrip('0123456789')}"
    
    return basename

# =============================================================================
# 2. Dataset & Sampling
# =============================================================================
class IndonesianSpeakerDataset(Dataset):
    def __init__(self, wav_files, max_duration=3.0):
        self.max_len = int(max_duration * SAMPLE_RATE)
        self.speaker_to_files = {}
        self.speaker_to_id = {}
        self.files = []
        
        # Group files by speaker
        for wav_path in wav_files:
            speaker_id = parse_speaker_info(wav_path)
            if speaker_id not in self.speaker_to_files:
                self.speaker_to_files[speaker_id] = []
                self.speaker_to_id[speaker_id] = len(self.speaker_to_id)
            self.speaker_to_files[speaker_id].append(wav_path)
        
        # Filter speakers with too few utterances
        to_remove = [k for k, v in self.speaker_to_files.items() if len(v) < 2]
        for k in to_remove:
            del self.speaker_to_files[k]
            del self.speaker_to_id[k]
            
        self.speakers = list(self.speaker_to_files.keys())
        
        print(f"\n📊 Valid Speakers (>=2 samples): {len(self.speakers)}")
        
    def __len__(self):
        return len(self.speakers)

    def load_audio(self, file_path):
        try:
            waveform, sample_rate = torchaudio.load(file_path)
            if sample_rate != SAMPLE_RATE:
                resampler = T.Resample(sample_rate, SAMPLE_RATE)
                waveform = resampler(waveform)
            
            if waveform.shape[0] > 1:
                waveform = torch.mean(waveform, dim=0, keepdim=True)
                
            # Random segment
            if waveform.shape[1] > self.max_len:
                start = random.randint(0, waveform.shape[1] - self.max_len)
                waveform = waveform[:, start:start + self.max_len]
            elif waveform.shape[1] < self.max_len:
                pad_size = self.max_len - waveform.shape[1]
                waveform = torch.cat([waveform, torch.zeros(1, pad_size)], dim=1)
                
            # Augmentation
            if random.random() < 0.5:
                noise = torch.randn_like(waveform) * random.uniform(0.001, 0.005)
                waveform += noise
                
            return waveform
            
        except Exception as e:
            print(f"Error loading {file_path}: {e}")
            return torch.zeros(1, self.max_len)


class BalancedBatchSampler(Sampler):
    def __init__(self, dataset, n_speakers, n_utterances):
        self.dataset = dataset
        self.n_speakers = n_speakers
        self.n_utterances = n_utterances
        self.batch_size = n_speakers * n_utterances
        self.n_batches = len(dataset.speakers) // n_speakers
        
    def __iter__(self):
        # Determine speakers for this epoch
        speakers = list(self.dataset.speaker_to_files.keys())
        # Shuffle speakers
        random.shuffle(speakers)
        
        for i in range(self.n_batches):
            batch_speakers = speakers[i*self.n_speakers : (i+1)*self.n_speakers]
            
            batch = []
            for spk in batch_speakers:
                # Sample M utterances for this speaker
                files = self.dataset.speaker_to_files[spk]
                # If not enough files, allow replacement
                chosen = random.choices(files, k=self.n_utterances)
                batch.extend([(f, self.dataset.speaker_to_id[spk]) for f in chosen])
                
            yield batch
            
    def __len__(self):
        return self.n_batches


# =============================================================================
# 3. Training Loop
# =============================================================================
def train(args):
    print("=" * 60)
    print("  ECAPA-TDNN Fine-tuning with GE2E Loss")
    print("=" * 60)
    
    # 1. Prepare Data
    wav_files = collect_all_audio(args.dataset_path)
    if len(wav_files) < 10:
        print("❌ Not enough data.")
        return

    dataset = IndonesianSpeakerDataset(wav_files)
    if len(dataset.speakers) < N_SPEAKERS_PER_BATCH:
        print(f"❌ Not enough speakers ({len(dataset.speakers)}). Need at least {N_SPEAKERS_PER_BATCH}.")
        return

    # Build batches manually (no DataLoader needed for GE2E)
    def build_epoch_batches():
        """Generate balanced batches: N speakers x M utterances each"""
        speakers = list(dataset.speaker_to_files.keys())
        random.shuffle(speakers)
        batches = []
        
        for i in range(len(speakers) // N_SPEAKERS_PER_BATCH):
            batch_speakers = speakers[i * N_SPEAKERS_PER_BATCH : (i + 1) * N_SPEAKERS_PER_BATCH]
            waveforms = []
            labels = []
            
            for spk in batch_speakers:
                files = dataset.speaker_to_files[spk]
                chosen = random.choices(files, k=M_UTTERANCES_PER_SPEAKER)
                for f in chosen:
                    waveforms.append(dataset.load_audio(f))
                    labels.append(dataset.speaker_to_id[spk])
            
            batches.append((torch.stack(waveforms), torch.tensor(labels)))
        
        return batches

    # 2. Model
    print(f"\n🔧 Loading pretrained ECAPA-TDNN model...")
    model = ECAPA_TDNN(
        input_size=N_MELS,
        channels=[1024, 1024, 1024, 1024, 3072],
        kernel_sizes=[5, 3, 3, 3, 1],
        dilations=[1, 2, 3, 4, 1],
        attention_channels=128,
        lin_neurons=EMBEDDING_SIZE
    ).to(DEVICE)
    
    model_path = os.path.join(PROJECT_DIR, "pretrained_models", "spkrec-ecapa-voxceleb", "embedding_model.ckpt")
    if os.path.exists(model_path):
        model.load_state_dict(torch.load(model_path, map_location=DEVICE, weights_only=False))
        print("✅ Loaded pretrained weights")
    
    mel_spec = T.MelSpectrogram(
        sample_rate=SAMPLE_RATE, n_fft=400, win_length=400, 
        hop_length=160, n_mels=N_MELS
    ).to(DEVICE)
    
    # 3. Loss & Optimizer
    criterion = GE2ELoss(loss_method='softmax').to(DEVICE)
    optimizer = optim.Adam([
        {'params': model.parameters(), 'lr': 1e-5}, # Very low learning rate for fine-tuning
        {'params': criterion.parameters(), 'lr': 1e-4}
    ])
    scheduler = optim.lr_scheduler.CosineAnnealingLR(optimizer, T_max=args.epochs)
    
    # 4. Training
    print(f"\n🚀 Starting GE2E training: {args.epochs} epochs")
    print(f"   Batch: {N_SPEAKERS_PER_BATCH} speakers x {M_UTTERANCES_PER_SPEAKER} utterances = {BATCH_SIZE} samples")
    
    save_dir = os.path.join(PROJECT_DIR, "pretrained_models")
    os.makedirs(save_dir, exist_ok=True)
    best_loss = float('inf')
    
    model.train()
    
    for epoch in range(args.epochs):
        running_loss = 0.0
        num_batches = 0
        batches = build_epoch_batches()
        pbar = tqdm(batches, desc=f"Epoch {epoch+1}/{args.epochs}")
        
        for waveforms, labels in pbar:
            waveforms = waveforms.to(DEVICE)
            
            # Feature extraction
            with torch.no_grad():
                # waveforms: [B, 1, time] -> squeeze to [B, time] for mel_spec
                mels = mel_spec(waveforms.squeeze(1))  # [B, n_mels, time]
                mels = torch.log(mels + 1e-6)
                # ECAPA-TDNN expects [B, time, n_mels]
                mels = mels.transpose(1, 2)  # [B, time, n_mels]
            
            # Forward
            optimizer.zero_grad()
            embeddings = model(mels) # (Batch, 1, 192)
            if embeddings.dim() == 3:
                embeddings = embeddings.squeeze(1) # (Batch, 192)
            
            # Reshape for GE2E: (N, M, D)
            # Batch is organized as [S1_U1, S1_U2..., S2_U1...]
            embeddings = embeddings.reshape(N_SPEAKERS_PER_BATCH, M_UTTERANCES_PER_SPEAKER, EMBEDDING_SIZE)
            
            loss = criterion(embeddings)
            
            loss.backward()
            torch.nn.utils.clip_grad_norm_(model.parameters(), 3.0)
            optimizer.step()
            
            running_loss += loss.item()
            num_batches += 1
            pbar.set_postfix(loss=f"{loss.item():.4f}")
            
        scheduler.step()
        epoch_loss = running_loss / max(num_batches, 1)
        
        print(f"  Epoch {epoch+1}: Loss={epoch_loss:.4f}")
        
        if epoch_loss < best_loss:
            best_loss = epoch_loss
            # Save properly
            save_path = os.path.join(save_dir, "finetuned_ecapa.ckpt")
            torch.save(model.state_dict(), save_path)
            print(f"  💾 Best model saved!")
    
    print(f"\n✅ Training selesai! Best loss: {best_loss:.4f}")
    print(f"   Model saved to: {save_path}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--dataset_path", type=str, 
                        default=os.path.join(PROJECT_DIR, "datasets", "indonesian-speech"),
                        help="Path to root dataset folder")
    parser.add_argument("--epochs", type=int, default=20)
    args = parser.parse_args()
    train(args)
