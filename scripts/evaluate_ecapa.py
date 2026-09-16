import os
import sys
import argparse
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from voice_processor_ecapa import get_verifier
from evaluate_aasist import calculate_eer_manual

def evaluate_ecapa(dataset_dir):
    print(f"Memulai evaluasi Verifikasi Identitas (ECAPA-TDNN) pada dataset: {dataset_dir}")
    
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
                # Karena kita ingin tahu apakah ECAPA bisa bedain Orang A dan Orang B (bukan manusia vs bot)
                if fname.startswith('asli'):
                    filepath = os.path.join(dataset_dir, fname + '.flac')
                    if os.path.exists(filepath):
                        asli_files.append((spk_id, filepath))
                        
    if len(asli_files) < 2:
        print("Tidak cukup data suara asli untuk pengujian pasangan (minimal 2 file).")
        return
        
    print(f"Ditemukan {len(asli_files)} file suara asli (bonafide).")
    
    print("Memuat model ECAPA-TDNN dan mengekstrak voice embedding...")
    verifier = get_verifier()
    
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
    
    y_true = []
    y_scores = []
    
    all_filepaths = list(embeddings.keys())
    
    for i in range(len(all_filepaths)):
        for j in range(i+1, len(all_filepaths)):
            fp1 = all_filepaths[i]
            fp2 = all_filepaths[j]
            
            spk1, emb1 = embeddings[fp1]
            spk2, emb2 = embeddings[fp2]
            
            is_same = 1 if spk1 == spk2 else 0
            
            similarity = verifier.compute_similarity(emb1, emb2)
            sim_score = (similarity + 1) / 2.0
            
            y_true.append(is_same)
            y_scores.append(sim_score)
            
    total_target = sum(y_true)
    total_nontarget = len(y_true) - total_target
    
    print(f"Total Pasangan (Trials) : {len(y_true)}")
    print(f"- Target (Sama)         : {total_target}")
    print(f"- Non-Target (Beda)     : {total_nontarget}")
    
    if total_target == 0 or total_nontarget == 0:
        print("\nPeringatan: Tidak ada cukup variasi speaker (Harus ada yg sama dan ada yg beda).")
        return
        
    print("\nMenghitung EER dan metrik lainnya...")
    eer, eer_thresh = calculate_eer_manual(y_true, y_scores)
    
    y_pred = [1 if s >= eer_thresh else 0 for s in y_scores]
    
    TP = sum([1 for i in range(len(y_true)) if y_true[i] == 1 and y_pred[i] == 1])
    TN = sum([1 for i in range(len(y_true)) if y_true[i] == 0 and y_pred[i] == 0])
    FP = sum([1 for i in range(len(y_true)) if y_true[i] == 0 and y_pred[i] == 1])
    FN = sum([1 for i in range(len(y_true)) if y_true[i] == 1 and y_pred[i] == 0])
    
    accuracy = (TP + TN) / len(y_true)
    
    print("\n" + "="*50)
    print("      HASIL EVALUASI ECAPA-TDNN (VERIFIKASI)")
    print("="*50)
    print(f"Equal Error Rate (EER)   : {eer * 100:.2f}%")
    print(f"Akurasi Identitas        : {accuracy * 100:.2f}%")
    print(f"Threshold Optimal u/ EER : {eer_thresh:.4f} ({(eer_thresh*100):.2f}% Similarity)")
    print("-" * 50)
    print("Confusion Matrix (Deteksi Orang yang Sama/Beda):")
    print("                  | Prediksi BEDA  | Prediksi SAMA")
    print(f"Realitas: BEDA    | {TN:<14} | {FP}")
    print(f"Realitas: SAMA    | {FN:<14} | {TP}")
    print("="*50)

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument('--dataset', type=str, required=True)
    args = parser.parse_args()
    evaluate_ecapa(args.dataset)
