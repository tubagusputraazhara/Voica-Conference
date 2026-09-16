#!/usr/bin/env python3
"""
Download Indonesian Speech Dataset from Kaggle
Dataset: Indonesian Speech with Accents (5 Ethnic Groups)
URL: https://www.kaggle.com/datasets/hengkymulyono/indonesian-speech-with-accents-5-ethnic-groups

Setup:
1. Buat akun Kaggle (jika belum punya)
2. Buka https://www.kaggle.com/settings -> API -> Create New Token
3. File kaggle.json akan terdownload
4. Taruh file kaggle.json di: C:/Users/<username>/.kaggle/kaggle.json
5. Jalankan script ini: python scripts/training/download_dataset.py
"""

import os
import sys
import zipfile

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.dirname(os.path.dirname(SCRIPT_DIR))
DATASET_DIR = os.path.join(PROJECT_DIR, "datasets", "indonesian-speech")

DATASET_SLUG = "hengkymulyono/indonesian-speech-with-accents-5-ethnic-groups"

def check_kaggle_credentials():
    """Check if Kaggle API credentials exist"""
    kaggle_dir = os.path.join(os.path.expanduser("~"), ".kaggle")
    kaggle_json = os.path.join(kaggle_dir, "kaggle.json")
    
    if not os.path.exists(kaggle_json):
        print("=" * 60)
        print("KAGGLE API KEY BELUM ADA!")
        print("=" * 60)
        print()
        print("Langkah-langkah:")
        print("1. Buka https://www.kaggle.com/settings")
        print("2. Scroll ke bagian 'API'")
        print("3. Klik 'Create New Token'")
        print("4. File 'kaggle.json' akan terdownload")
        print(f"5. Pindahkan file ke: {kaggle_dir}")
        print()
        print(f"   Buat folder: mkdir {kaggle_dir}")
        print(f"   Copy file:   copy kaggle.json {kaggle_json}")
        print()
        return False
    
    print(f"✅ Kaggle credentials found at {kaggle_json}")
    return True

def download_dataset():
    """Download dataset from Kaggle"""
    if not check_kaggle_credentials():
        return False
    
    # Import kaggle after credential check
    try:
        from kaggle.api.kaggle_api_extended import KaggleApi
    except ImportError:
        print("❌ Kaggle package not installed. Run: pip install kaggle")
        return False
    
    # Create dataset directory
    os.makedirs(DATASET_DIR, exist_ok=True)
    
    # Check if already downloaded
    existing_files = os.listdir(DATASET_DIR) if os.path.exists(DATASET_DIR) else []
    if len(existing_files) > 2:
        print(f"⚠️ Dataset sudah ada di {DATASET_DIR} ({len(existing_files)} files)")
        resp = input("Download ulang? (y/n): ").strip().lower()
        if resp != 'y':
            print("Skip download.")
            return True
    
    print(f"📥 Downloading dataset: {DATASET_SLUG}")
    print(f"📁 Destination: {DATASET_DIR}")
    print("   (Ini mungkin memakan beberapa menit...)")
    print()
    
    try:
        api = KaggleApi()
        api.authenticate()
        
        # Download and unzip
        api.dataset_download_files(
            DATASET_SLUG,
            path=DATASET_DIR,
            unzip=True
        )
        
        print()
        print("✅ Dataset berhasil didownload!")
        
        # Show structure
        print()
        print("📂 Struktur dataset:")
        for root, dirs, files in os.walk(DATASET_DIR):
            level = root.replace(DATASET_DIR, '').count(os.sep)
            indent = '  ' * level
            print(f"{indent}📁 {os.path.basename(root)}/")
            if level < 2:  # Only show 2 levels deep
                for f in files[:5]:
                    print(f"{indent}  📄 {f}")
                if len(files) > 5:
                    print(f"{indent}  ... dan {len(files)-5} file lainnya")
        
        return True
        
    except Exception as e:
        print(f"❌ Error downloading: {e}")
        return False

def count_speakers():
    """Count speakers and audio files in the dataset"""
    if not os.path.exists(DATASET_DIR):
        print("Dataset belum didownload!")
        return
    
    total_files = 0
    speakers = set()
    
    for root, dirs, files in os.walk(DATASET_DIR):
        wav_files = [f for f in files if f.endswith(('.wav', '.mp3', '.flac'))]
        if wav_files:
            speaker = os.path.basename(root)
            speakers.add(speaker)
            total_files += len(wav_files)
    
    print(f"\n📊 Dataset Summary:")
    print(f"   Jumlah Speaker: {len(speakers)}")
    print(f"   Total Audio Files: {total_files}")
    print(f"   Rata-rata per Speaker: {total_files // max(len(speakers), 1)} files")

if __name__ == "__main__":
    print("=" * 60)
    print("  DOWNLOAD INDONESIAN SPEECH DATASET")
    print("  5 Ethnic Groups: Jawa, Sunda, Batak, Minang, dll")
    print("=" * 60)
    print()
    
    success = download_dataset()
    
    if success:
        count_speakers()
        print()
        print("🚀 Selanjutnya, jalankan fine-tuning:")
        print(f"   python scripts/training/train_finetune.py --dataset_path {DATASET_DIR} --epochs 10")
