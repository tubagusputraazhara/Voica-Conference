#!/usr/bin/env python3
"""
Compute EER + optimal threshold from an embeddings/trials file produced by
scripts/eval/extract_embeddings.py.

Reuses the exact same EER calculation (calculate_eer_manual) that
scripts/evaluate_ecapa.py uses via scripts/evaluate_aasist.py, so numbers
are comparable across all 3 backbones (ECAPA, ResNet-34, WavLM).

Usage:
    python compute_eer.py --embeddings embeddings_ecapa.json
    python compute_eer.py --embeddings embeddings_resnet34.json --output results_resnet34.json
"""

import os
import sys
import json
import argparse
import numpy as np

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
SCRIPTS_DIR = os.path.dirname(SCRIPT_DIR)
sys.path.insert(0, SCRIPTS_DIR)

from evaluate_aasist import calculate_eer_manual  # noqa: E402 (reuse, not duplicate)


def compute_similarity(emb1: np.ndarray, emb2: np.ndarray) -> float:
    """Plain cosine similarity, range [-1, 1] regardless of backbone."""
    emb1_norm = emb1 / (np.linalg.norm(emb1) + 1e-10)
    emb2_norm = emb2 / (np.linalg.norm(emb2) + 1e-10)
    return float(np.dot(emb1_norm, emb2_norm))


def compute_eer(embeddings_path: str, output_path: str = None):
    with open(embeddings_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    backbone = data['backbone']
    files = data['files']
    trials = data['trials']

    print(f"Menghitung EER untuk backbone: {backbone}")
    print(f"Total Pasangan (Trials): {len(trials)}")

    embeddings_cache = {fp: np.array(v['embedding']) for fp, v in files.items()}

    y_true = []
    y_scores = []

    for trial in trials:
        emb1 = embeddings_cache[trial['file1']]
        emb2 = embeddings_cache[trial['file2']]

        similarity = compute_similarity(emb1, emb2)
        # Sama seperti evaluate_ecapa.py: mapping [-1,1] -> [0,1] sebelum EER
        sim_score = (similarity + 1) / 2.0

        y_true.append(trial['is_same'])
        y_scores.append(sim_score)

    total_target = sum(y_true)
    total_nontarget = len(y_true) - total_target

    if total_target == 0 or total_nontarget == 0:
        print("Peringatan: Tidak ada cukup variasi speaker (Harus ada yg sama dan ada yg beda).")
        return

    eer, eer_thresh_mapped = calculate_eer_manual(y_true, y_scores)

    # eer_thresh_mapped berada dalam skala [0,1] (hasil mapping (sim+1)/2).
    # Konversi balik ke skala cosine similarity asli [-1,1] supaya bisa
    # langsung dipakai sebagai `threshold` di verify_voice() masing-masing
    # voice_processor_*.py (yang beroperasi pada cosine similarity mentah).
    eer_threshold_cosine = eer_thresh_mapped * 2.0 - 1.0

    y_pred = [1 if s >= eer_thresh_mapped else 0 for s in y_scores]
    TP = sum(1 for i in range(len(y_true)) if y_true[i] == 1 and y_pred[i] == 1)
    TN = sum(1 for i in range(len(y_true)) if y_true[i] == 0 and y_pred[i] == 0)
    FP = sum(1 for i in range(len(y_true)) if y_true[i] == 0 and y_pred[i] == 1)
    FN = sum(1 for i in range(len(y_true)) if y_true[i] == 1 and y_pred[i] == 0)
    accuracy = (TP + TN) / len(y_true)

    print("\n" + "=" * 50)
    print(f"      HASIL EVALUASI {backbone.upper()} (VERIFIKASI)")
    print("=" * 50)
    print(f"Equal Error Rate (EER)         : {eer * 100:.2f}%")
    print(f"Akurasi Identitas @ EER thresh : {accuracy * 100:.2f}%")
    print(f"Threshold Optimal (skala similarity_percentage) : {(eer_thresh_mapped * 100):.2f}%")
    print(f"Threshold Optimal (cosine similarity mentah)     : {eer_threshold_cosine:.4f}")
    print("-" * 50)
    print("Confusion Matrix (Deteksi Orang yang Sama/Beda):")
    print("                  | Prediksi BEDA  | Prediksi SAMA")
    print(f"Realitas: BEDA    | {TN:<14} | {FP}")
    print(f"Realitas: SAMA    | {FN:<14} | {TP}")
    print("=" * 50)
    print(
        "\n>>> TODO: isi threshold ini ke DEFAULT_THRESHOLD di "
        f"scripts/voice_processor_{backbone}.py "
        f"(gunakan nilai cosine similarity mentah: {eer_threshold_cosine:.4f})"
        if backbone != 'ecapa' else ""
    )

    result = {
        'backbone': backbone,
        'total_trials': len(y_true),
        'total_target': total_target,
        'total_nontarget': total_nontarget,
        'eer': eer,
        'eer_percentage': eer * 100,
        'threshold_similarity_percentage': eer_thresh_mapped * 100,
        'threshold_cosine_similarity': eer_threshold_cosine,
        'accuracy_at_eer_threshold': accuracy,
        'confusion_matrix': {'TP': TP, 'TN': TN, 'FP': FP, 'FN': FN},
    }

    output_path = output_path or os.path.join(SCRIPT_DIR, f'results_{backbone}.json')
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(result, f, indent=2)
    print(f"\nHasil disimpan ke: {output_path}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument('--embeddings', type=str, required=True,
                         help='Path ke file JSON hasil extract_embeddings.py')
    parser.add_argument('--output', type=str, default=None,
                         help='Default: scripts/eval/results_<backbone>.json')
    args = parser.parse_args()

    compute_eer(args.embeddings, args.output)
