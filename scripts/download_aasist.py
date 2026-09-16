#!/usr/bin/env python3
"""
Download AASIST pre-trained model
"""

import urllib.request
import os
import sys

# Get project directory
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_DIR = os.path.dirname(SCRIPT_DIR)
MODEL_DIR = os.path.join(PROJECT_DIR, "pretrained_models", "aasist")
MODEL_PATH = os.path.join(MODEL_DIR, "AASIST.pth")

# Model URL from AASIST releases
MODEL_URL = "https://github.com/clovaai/aasist/releases/download/v0.1/AASIST.pth"

print(f"Model directory: {MODEL_DIR}")
print(f"Model path: {MODEL_PATH}")
print()

# Create directory if not exists
os.makedirs(MODEL_DIR, exist_ok=True)

if os.path.exists(MODEL_PATH):
    size = os.path.getsize(MODEL_PATH) / (1024 * 1024)
    print(f"Model already exists! ({size:.2f} MB)")
else:
    print("Downloading AASIST model...")
    print(f"URL: {MODEL_URL}")
    print("This may take a minute...")
    
    try:
        # Download with progress
        def show_progress(block_num, block_size, total_size):
            downloaded = block_num * block_size
            percent = min(100, downloaded * 100 / total_size)
            sys.stdout.write(f"\rDownloading: {percent:.1f}%")
            sys.stdout.flush()
        
        urllib.request.urlretrieve(MODEL_URL, MODEL_PATH, show_progress)
        print()
        
        size = os.path.getsize(MODEL_PATH) / (1024 * 1024)
        print(f"\n✅ Model downloaded successfully! ({size:.2f} MB)")
        
    except Exception as e:
        print(f"\n❌ Error downloading: {e}")
        print("\nTry downloading manually from:")
        print(MODEL_URL)
        sys.exit(1)

print(f"\nModel saved to: {MODEL_PATH}")
