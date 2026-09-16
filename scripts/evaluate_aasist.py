import os
import sys
import argparse

# Add current directory to path to import anti_spoofing
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_spoofing import get_detector

def calculate_eer_manual(y_true, y_scores):
    thresholds = sorted(list(set(y_scores)), reverse=True)
    min_diff = 1.0
    eer = 1.0
    eer_threshold = 0.5
    
    total_p = sum(y_true)
    total_n = len(y_true) - total_p
    
    if total_p == 0 or total_n == 0:
        return 0.0, 0.5
        
    for thresh in thresholds:
        tp = sum(1 for i in range(len(y_true)) if y_true[i] == 1 and y_scores[i] >= thresh)
        fp = sum(1 for i in range(len(y_true)) if y_true[i] == 0 and y_scores[i] >= thresh)
        
        fnr = 1 - (tp / total_p)
        fpr = fp / total_n
        
        diff = abs(fnr - fpr)
        if diff < min_diff:
            min_diff = diff
            eer = (fpr + fnr) / 2
            eer_threshold = thresh
            
    return eer, eer_threshold

def evaluate(dataset_dir):
    print(f"Memulai evaluasi AASIST model pada dataset: {dataset_dir}")
    labels_file = os.path.join(dataset_dir, 'labels.txt')
    
    if not os.path.exists(labels_file):
        print(f"Error: File label tidak ditemukan di {labels_file}")
        sys.exit(1)
        
    y_true = []
    y_scores = []
    y_pred = []
    
    print("Memuat model AASIST...")
    detector = get_detector()
    print("Model berhasil dimuat! Mulai memproses audio...\n")
    
    with open(labels_file, 'r') as f:
        lines = [line.strip().split() for line in f.readlines() if line.strip()]
        
    total_files = len(lines)
    processed = 0
    
    for item in lines:
        if len(item) != 2:
            continue
        filename, label = item
        audio_path = os.path.join(dataset_dir, filename)
        
        if not os.path.exists(audio_path):
            print(f"Warning: File {filename} tidak ditemukan, dilewati.")
            continue
            
        # Ground Truth: 1 if Asli, 0 if Spoof (ai/replay)
        is_bonafide_actual = (label.lower() == 'asli')
        
        try:
            res = detector.detect(audio_path)
            bonafide_prob = res['bonafide_probability'] / 100.0
            is_bonafide_pred = res['is_bonafide']
            
            y_true.append(1 if is_bonafide_actual else 0)
            y_scores.append(bonafide_prob)
            y_pred.append(1 if is_bonafide_pred else 0)
            
            processed += 1
            if processed % 10 == 0 or processed == total_files:
                print(f"Progress: {processed}/{total_files} file audio diproses...", end='\r')
                sys.stdout.flush()
                
        except Exception as e:
            print(f"\nError processing {filename}: {e}")
            
    print("\n\nSemua file selesai diproses. Menghitung metrik...")
    
    if len(y_true) == 0:
        print("Tidak ada file audio yang berhasil diproses.")
        sys.exit(1)
        
    # Kalkulasi Manual (Tanpa Sklearn agar tidak ada error dependency)
    TP = sum([1 for i in range(len(y_true)) if y_true[i] == 1 and y_pred[i] == 1])
    TN = sum([1 for i in range(len(y_true)) if y_true[i] == 0 and y_pred[i] == 0])
    FP = sum([1 for i in range(len(y_true)) if y_true[i] == 0 and y_pred[i] == 1])
    FN = sum([1 for i in range(len(y_true)) if y_true[i] == 1 and y_pred[i] == 0])
    
    accuracy = (TP + TN) / len(y_true) if len(y_true) > 0 else 0
    precision = TP / (TP + FP) if (TP + FP) > 0 else 0
    recall = TP / (TP + FN) if (TP + FN) > 0 else 0
    f1 = 2 * (precision * recall) / (precision + recall) if (precision + recall) > 0 else 0
    
    eer, eer_thresh = calculate_eer_manual(y_true, y_scores)
    
    print("\n" + "="*50)
    print("      HASIL EVALUASI AKURASI AASIST (ANTI-SPOOFING)")
    print("="*50)
    print(f"Total Sampel Diuji   : {len(y_true)}")
    print(f"Akurasi Total        : {accuracy * 100:.2f}%")
    print(f"Precision            : {precision * 100:.2f}%")
    print(f"Recall (Sensitivitas): {recall * 100:.2f}%")
    print(f"F1-Score             : {f1 * 100:.2f}%")
    print("-" * 50)
    print(f"Equal Error Rate (EER)   : {eer * 100:.2f}%")
    print(f"Threshold Optimal u/ EER : {eer_thresh:.4f} ({(eer_thresh*100):.2f}%)")
    print("-" * 50)
    print("Confusion Matrix:")
    print("                  | Prediksi PALSU | Prediksi ASLI")
    print(f"Realitas: PALSU   | {TN:<14} | {FP}")
    print(f"Realitas: ASLI    | {FN:<14} | {TP}")
    print("="*50)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Evaluate AASIST model")
    parser.add_argument('--dataset', type=str, required=True, help="Path ke folder dataset (harus ada labels.txt)")
    args = parser.parse_args()
    evaluate(args.dataset)
