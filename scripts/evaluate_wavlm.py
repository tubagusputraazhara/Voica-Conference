#!/usr/bin/env python3
"""
Evaluasi EER untuk Model 3 (WavLM-Base-SV) dengan satu command, setara
dengan scripts/evaluate_ecapa.py tapi untuk backbone WavLM.

Usage:
    python scripts/evaluate_wavlm.py --dataset "D:\\path\\ke\\dataset"

Dataset harus berisi labels.txt (format "<spk_id> <nama_file>") dan file
.flac -- struktur sama persis dengan yang dipakai evaluate_ecapa.py.

Script ini hanya orkestrasi tipis di atas scripts/eval/extract_embeddings.py
dan scripts/eval/compute_eer.py (reuse, logic pair-generation & EER tidak
diduplikasi di sini).
"""

import os
import sys
import argparse

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
EVAL_DIR = os.path.join(SCRIPT_DIR, 'eval')
sys.path.insert(0, EVAL_DIR)

from extract_embeddings import extract_embeddings  # noqa: E402
from compute_eer import compute_eer  # noqa: E402

BACKBONE = 'wavlm'

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Evaluasi EER Model 3 (WavLM)")
    parser.add_argument('--dataset', type=str, required=True,
                         help='Path ke folder dataset (harus ada labels.txt + file .flac)')
    parser.add_argument('--output', type=str, default=None,
                         help='Default: scripts/eval/results_wavlm.json')
    args = parser.parse_args()

    embeddings_path = os.path.join(EVAL_DIR, f'embeddings_{BACKBONE}.json')
    extract_embeddings(args.dataset, BACKBONE, embeddings_path)
    compute_eer(embeddings_path, args.output)
