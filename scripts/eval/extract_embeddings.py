#!/usr/bin/env python3
"""
Extract speaker embeddings + generate verification trial pairs for a chosen
backbone (ecapa / resnet34 / wavlm), so EER results are comparable
apple-to-apple across the 3 models.

Trial-pair generation and ground-truth logic below is REPLICATED VERBATIM
from scripts/evaluate_ecapa.py (bonafide-only filtering via 'asli' prefix,
nested-loop unique pair combinations, ground truth = same spk_id) so the
same 3.160-pair trial set is used regardless of backbone.

Output: a JSON file with per-file embeddings, speaker ids, and the
generated trial pairs (list of [filepath1, filepath2, is_same]), consumed
by scripts/eval/compute_eer.py.
"""

import os
import sys
import json
import argparse

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
SCRIPTS_DIR = os.path.dirname(SCRIPT_DIR)
sys.path.insert(0, SCRIPTS_DIR)

BACKBONES = {
    'ecapa': 'voice_processor_ecapa',
    'resnet34': 'voice_processor_resnet34',
    'wavlm': 'voice_processor_wavlm',
}


def get_verifier_for_backbone(backbone: str):
    if backbone not in BACKBONES:
        raise ValueError(f"Unknown backbone '{backbone}'. Pilih salah satu: {list(BACKBONES.keys())}")
    module = __import__(BACKBONES[backbone], fromlist=['get_verifier'])
    return module.get_verifier()


def extract_embeddings(dataset_dir: str, backbone: str, output_path: str):
    print(f"Ekstraksi embedding ({backbone}) pada dataset: {dataset_dir}")

    # ==================================================================
    # Bagian ini REPLIKASI PERSIS dari scripts/evaluate_ecapa.py supaya
    # trial set identik antar backbone (apple-to-apple EER comparison).
    # ==================================================================
    asli_files = []

    labels_file = os.path.join(dataset_dir, 'labels.txt')
    if not os.path.exists(labels_file):
        print(f"Error: {labels_file} tidak ditemukan.")
        return

    with open(labels_file, 'r', encoding='utf-8') as f:
        for line in f:
            parts = line.strip().split()
            if len(parts) >= 2:
                spk_id = parts[0]
                fname = parts[1]

                # Kita hanya menguji file Asli (Bonafide) untuk Verifikasi Identitas
                # Karena kita ingin tahu apakah model bisa bedain Orang A dan Orang B (bukan manusia vs bot)
                if fname.startswith('asli'):
                    filepath = os.path.join(dataset_dir, fname + '.flac')
                    if os.path.exists(filepath):
                        asli_files.append((spk_id, filepath))

    if len(asli_files) < 2:
        print("Tidak cukup data suara asli untuk pengujian pasangan (minimal 2 file).")
        return

    print(f"Ditemukan {len(asli_files)} file suara asli (bonafide).")

    print(f"Memuat model {backbone} dan mengekstrak voice embedding...")
    verifier = get_verifier_for_backbone(backbone)

    embeddings = {}
    processed = 0

    for spk_id, filepath in asli_files:
        try:
            emb = verifier.extract_embedding(filepath)
            embeddings[filepath] = (spk_id, emb)
            processed += 1
            print(f"Ekstraksi: {processed}/{len(asli_files)}", end='\r')
        except Exception as e:
            print(f"\nGagal ekstrak {filepath}: {e}")

    print("\n\nMembuat pasangan pengujian (Trials)...")

    all_filepaths = list(embeddings.keys())

    trials = []
    for i in range(len(all_filepaths)):
        for j in range(i + 1, len(all_filepaths)):
            fp1 = all_filepaths[i]
            fp2 = all_filepaths[j]

            spk1, emb1 = embeddings[fp1]
            spk2, emb2 = embeddings[fp2]

            is_same = 1 if spk1 == spk2 else 0

            trials.append((fp1, fp2, is_same))
    # ==================================================================
    # Akhir bagian replikasi evaluate_ecapa.py
    # ==================================================================

    total_target = sum(t[2] for t in trials)
    total_nontarget = len(trials) - total_target

    print(f"Total Pasangan (Trials) : {len(trials)}")
    print(f"- Target (Sama)         : {total_target}")
    print(f"- Non-Target (Beda)     : {total_nontarget}")

    output = {
        'backbone': backbone,
        'dataset_dir': dataset_dir,
        'embedding_size': len(next(iter(embeddings.values()))[1]) if embeddings else 0,
        'files': {
            fp: {'spk_id': spk_id, 'embedding': emb.tolist()}
            for fp, (spk_id, emb) in embeddings.items()
        },
        'trials': [{'file1': fp1, 'file2': fp2, 'is_same': is_same} for fp1, fp2, is_same in trials],
    }

    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(output, f)

    print(f"\nEmbeddings + trial pairs disimpan ke: {output_path}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument('--dataset', type=str, required=True)
    parser.add_argument('--backbone', type=str, required=True, choices=list(BACKBONES.keys()))
    parser.add_argument('--output', type=str, default=None,
                         help='Default: scripts/eval/embeddings_<backbone>.json')
    args = parser.parse_args()

    output_path = args.output or os.path.join(SCRIPT_DIR, f'embeddings_{args.backbone}.json')
    extract_embeddings(args.dataset, args.backbone, output_path)
