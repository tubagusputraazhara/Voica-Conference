import os
import sys
import pandas as pd
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_spoofing import get_detector
from voice_processor_ecapa import get_verifier
from evaluate_aasist import calculate_eer_manual

def run_reports(dataset_dir, output_dir):
    print("Memulai pembuatan Laporan Pengujian (CSV)...")
    os.makedirs(output_dir, exist_ok=True)
    
    # ---------------------------------------------------------
    # 1. EVALUASI AASIST (ANTI-SPOOFING)
    # ---------------------------------------------------------
    print("\n--- Mengeksekusi AASIST ---")
    detector = get_detector()
    labels_file = os.path.join(dataset_dir, 'labels.txt')
    
    lines = []
    with open(labels_file, 'r', encoding='utf-8') as f:
        lines = [line.strip().split() for line in f.readlines() if line.strip()]
        
    aasist_results = []
    y_true_aasist = []
    y_scores_aasist = []
    y_pred_aasist = []
    
    for item in lines:
        if len(item) < 2: continue
        filename, label = item[1], item[5] if len(item) > 5 else item[-1]
        # In the new format, the first element is Speaker ID, second is filename, etc.
        # But wait, earlier I saw: `LA_0054 asli_DF_E_2088017 high_m4a asvspoof - bonafide notrim progress bonafide - - - -`
        # Let's just rely on the filename prefix 'asli', 'ai', 'replay'
        
        is_bonafide_actual = True if filename.startswith('asli') else False
        actual_class = 'Asli' if is_bonafide_actual else 'Palsu (Spoof)'
        
        audio_path = os.path.join(dataset_dir, filename + '.flac')
        if not os.path.exists(audio_path):
            audio_path = os.path.join(dataset_dir, filename) # fallback
            
        if not os.path.exists(audio_path):
            continue
            
        try:
            res = detector.detect(audio_path)
            score = res['bonafide_probability'] / 100.0
            is_bonafide_pred = res['is_bonafide']
            pred_class = 'Asli' if is_bonafide_pred else 'Palsu (Spoof)'
            
            is_correct = (is_bonafide_actual == is_bonafide_pred)
            
            aasist_results.append({
                'File Audio': filename,
                'Realitas (Ground Truth)': actual_class,
                'Prediksi Model': pred_class,
                'Skor Bonafide (0-1)': score,
                'Status': 'BENAR' if is_correct else 'SALAH'
            })
            
            y_true_aasist.append(1 if is_bonafide_actual else 0)
            y_scores_aasist.append(score)
            y_pred_aasist.append(1 if is_bonafide_pred else 0)
        except Exception as e:
            pass
            
    df_aasist = pd.DataFrame(aasist_results)
    aasist_csv_path = os.path.join(output_dir, '1_Detail_Pengujian_AntiSpoofing.csv')
    df_aasist.to_csv(aasist_csv_path, index=False)
    
    eer_aasist, eer_thresh_aasist = calculate_eer_manual(y_true_aasist, y_scores_aasist)
    TP = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 1 and y_pred_aasist[i] == 1])
    TN = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 0 and y_pred_aasist[i] == 0])
    FP = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 0 and y_pred_aasist[i] == 1])
    FN = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 1 and y_pred_aasist[i] == 0])
    acc_aasist = (TP + TN) / len(y_true_aasist) if len(y_true_aasist) > 0 else 0
    
    summary_aasist = [{
        'Total Sampel': len(y_true_aasist),
        'Akurasi': f"{acc_aasist*100:.2f}%",
        'Equal Error Rate (EER)': f"{eer_aasist*100:.2f}%",
        'True Positive (Asli ditebak Asli)': TP,
        'True Negative (Palsu ditebak Palsu)': TN,
        'False Positive (Palsu lolos jd Asli)': FP,
        'False Negative (Asli tertolak jd Palsu)': FN
    }]
    df_aasist_sum = pd.DataFrame(summary_aasist)
    df_aasist_sum.to_csv(os.path.join(output_dir, '1_Ringkasan_AntiSpoofing.csv'), index=False)


    # ---------------------------------------------------------
    # 2. EVALUASI ECAPA-TDNN (VERIFIKASI)
    # ---------------------------------------------------------
    print("\n--- Mengeksekusi ECAPA-TDNN ---")
    verifier = get_verifier()
    
    asli_files = []
    for item in lines:
        if len(item) < 2: continue
        spk_id = item[0]
        filename = item[1]
        if filename.startswith('asli'):
            filepath = os.path.join(dataset_dir, filename + '.flac')
            if os.path.exists(filepath):
                asli_files.append((spk_id, filepath, filename))
                
    embeddings = {}
    for spk_id, filepath, fname in asli_files:
        try:
            emb = verifier.extract_embedding(filepath)
            embeddings[fname] = (spk_id, emb)
        except:
            pass
            
    ecapa_results = []
    y_true_ecapa = []
    y_scores_ecapa = []
    
    all_fnames = list(embeddings.keys())
    for i in range(len(all_fnames)):
        for j in range(i+1, len(all_fnames)):
            f1, f2 = all_fnames[i], all_fnames[j]
            spk1, emb1 = embeddings[f1]
            spk2, emb2 = embeddings[f2]
            
            is_same_actual = (spk1 == spk2)
            similarity = verifier.compute_similarity(emb1, emb2)
            sim_score = (similarity + 1) / 2.0
            
            y_true_ecapa.append(1 if is_same_actual else 0)
            y_scores_ecapa.append(sim_score)
            
            ecapa_results.append({
                'Audio 1': f1,
                'Audio 2': f2,
                'Speaker 1': spk1,
                'Speaker 2': spk2,
                'Realitas (Ground Truth)': 'SAMA' if is_same_actual else 'BEDA',
                'Skor Kemiripan (0-1)': sim_score
            })
            
    eer_ecapa, eer_thresh_ecapa = calculate_eer_manual(y_true_ecapa, y_scores_ecapa)
    y_pred_ecapa = [1 if s >= eer_thresh_ecapa else 0 for s in y_scores_ecapa]
    
    for idx, res in enumerate(ecapa_results):
        is_same_pred = y_pred_ecapa[idx] == 1
        res['Prediksi Model'] = 'SAMA' if is_same_pred else 'BEDA'
        res['Status'] = 'BENAR' if (y_true_ecapa[idx] == y_pred_ecapa[idx]) else 'SALAH'
        
    df_ecapa = pd.DataFrame(ecapa_results)
    ecapa_csv_path = os.path.join(output_dir, '2_Detail_Pengujian_Verifikasi_Identitas.csv')
    df_ecapa.to_csv(ecapa_csv_path, index=False)
    
    TP_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 1 and y_pred_ecapa[i] == 1])
    TN_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 0 and y_pred_ecapa[i] == 0])
    FP_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 0 and y_pred_ecapa[i] == 1])
    FN_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 1 and y_pred_ecapa[i] == 0])
    acc_ecapa = (TP_E + TN_E) / len(y_true_ecapa) if len(y_true_ecapa) > 0 else 0
    
    summary_ecapa = [{
        'Total Pasangan Diuji': len(y_true_ecapa),
        'Akurasi Identitas': f"{acc_ecapa*100:.2f}%",
        'Equal Error Rate (EER)': f"{eer_ecapa*100:.2f}%",
        'Threshold Optimal': eer_thresh_ecapa,
        'True Positive (Sama ditebak Sama)': TP_E,
        'True Negative (Beda ditebak Beda)': TN_E,
        'False Positive (Beda lolos jd Sama)': FP_E,
        'False Negative (Sama tertolak jd Beda)': FN_E
    }]
    df_ecapa_sum = pd.DataFrame(summary_ecapa)
    df_ecapa_sum.to_csv(os.path.join(output_dir, '2_Ringkasan_Verifikasi_Identitas.csv'), index=False)
    
    print(f"\nSELESAI! Semua file CSV telah disimpan di: {output_dir}")

def run_all_reports(base_dataset_dir, output_dir):
    print("Memulai pembuatan Laporan Pengujian (CSV)...")
    os.makedirs(output_dir, exist_ok=True)
    
    # Kumpulkan semua baris dari train dan test
    train_labels_file = os.path.join(base_dataset_dir, 'train', 'labels.txt')
    test_labels_file = os.path.join(base_dataset_dir, 'test', 'labels.txt')
    
    lines = []
    if os.path.exists(train_labels_file):
        with open(train_labels_file, 'r', encoding='utf-8') as f:
            lines.extend([line.strip().split() + ['train'] for line in f.readlines() if line.strip()])
    if os.path.exists(test_labels_file):
        with open(test_labels_file, 'r', encoding='utf-8') as f:
            lines.extend([line.strip().split() + ['test'] for line in f.readlines() if line.strip()])
            
    # ---------------------------------------------------------
    # 1. EVALUASI AASIST (ANTI-SPOOFING)
    # ---------------------------------------------------------
    print("\n--- Mengeksekusi AASIST ---")
    detector = get_detector()
    
    aasist_results = []
    y_true_aasist = []
    y_scores_aasist = []
    y_pred_aasist = []
    
    for item in lines:
        if len(item) < 3: continue
        split_dir = item[-1]
        item_data = item[:-1]
        
        filename = item_data[1]
        
        is_bonafide_actual = True if filename.startswith('asli') else False
        actual_class = 'Asli' if is_bonafide_actual else 'Palsu (Spoof)'
        
        audio_path = os.path.join(base_dataset_dir, split_dir, filename + '.flac')
        if not os.path.exists(audio_path):
            audio_path = os.path.join(base_dataset_dir, split_dir, filename) # fallback
            
        if not os.path.exists(audio_path):
            continue
            
        try:
            res = detector.detect(audio_path)
            score = res['bonafide_probability'] / 100.0
            is_bonafide_pred = res['is_bonafide']
            pred_class = 'Asli' if is_bonafide_pred else 'Palsu (Spoof)'
            
            is_correct = (is_bonafide_actual == is_bonafide_pred)
            
            aasist_results.append({
                'File Audio': filename,
                'Split': split_dir,
                'Realitas (Ground Truth)': actual_class,
                'Prediksi Model': pred_class,
                'Skor Bonafide (0-1)': score,
                'Status': 'BENAR' if is_correct else 'SALAH'
            })
            
            y_true_aasist.append(1 if is_bonafide_actual else 0)
            y_scores_aasist.append(score)
            y_pred_aasist.append(1 if is_bonafide_pred else 0)
        except Exception as e:
            pass
            
    df_aasist = pd.DataFrame(aasist_results)
    aasist_csv_path = os.path.join(output_dir, '1_Detail_Pengujian_AntiSpoofing.csv')
    df_aasist.to_csv(aasist_csv_path, index=False)
    
    eer_aasist, eer_thresh_aasist = calculate_eer_manual(y_true_aasist, y_scores_aasist)
    TP = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 1 and y_pred_aasist[i] == 1])
    TN = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 0 and y_pred_aasist[i] == 0])
    FP = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 0 and y_pred_aasist[i] == 1])
    FN = sum([1 for i in range(len(y_true_aasist)) if y_true_aasist[i] == 1 and y_pred_aasist[i] == 0])
    acc_aasist = (TP + TN) / len(y_true_aasist) if len(y_true_aasist) > 0 else 0
    
    summary_aasist = [{
        'Total Sampel': len(y_true_aasist),
        'Akurasi': f"{acc_aasist*100:.2f}%",
        'Equal Error Rate (EER)': f"{eer_aasist*100:.2f}%",
        'True Positive (Asli ditebak Asli)': TP,
        'True Negative (Palsu ditebak Palsu)': TN,
        'False Positive (Palsu lolos jd Asli)': FP,
        'False Negative (Asli tertolak jd Palsu)': FN
    }]
    df_aasist_sum = pd.DataFrame(summary_aasist)
    df_aasist_sum.to_csv(os.path.join(output_dir, '1_Ringkasan_AntiSpoofing.csv'), index=False)


    # ---------------------------------------------------------
    # 2. EVALUASI ECAPA-TDNN (VERIFIKASI)
    # ---------------------------------------------------------
    print("\n--- Mengeksekusi ECAPA-TDNN ---")
    verifier = get_verifier()
    
    asli_files = []
    for item in lines:
        if len(item) < 3: continue
        split_dir = item[-1]
        item_data = item[:-1]
        
        spk_id = item_data[0]
        filename = item_data[1]
        if filename.startswith('asli'):
            filepath = os.path.join(base_dataset_dir, split_dir, filename + '.flac')
            if os.path.exists(filepath):
                asli_files.append((spk_id, filepath, filename))
                
    embeddings = {}
    for spk_id, filepath, fname in asli_files:
        try:
            emb = verifier.extract_embedding(filepath)
            embeddings[fname] = (spk_id, emb)
        except:
            pass
            
    ecapa_results = []
    y_true_ecapa = []
    y_scores_ecapa = []
    
    all_fnames = list(embeddings.keys())
    for i in range(len(all_fnames)):
        for j in range(i+1, len(all_fnames)):
            f1, f2 = all_fnames[i], all_fnames[j]
            spk1, emb1 = embeddings[f1]
            spk2, emb2 = embeddings[f2]
            
            is_same_actual = (spk1 == spk2)
            similarity = verifier.compute_similarity(emb1, emb2)
            sim_score = (similarity + 1) / 2.0
            
            y_true_ecapa.append(1 if is_same_actual else 0)
            y_scores_ecapa.append(sim_score)
            
            ecapa_results.append({
                'Audio 1': f1,
                'Audio 2': f2,
                'Speaker 1': spk1,
                'Speaker 2': spk2,
                'Realitas (Ground Truth)': 'SAMA' if is_same_actual else 'BEDA',
                'Skor Kemiripan (0-1)': sim_score
            })
            
    if len(y_true_ecapa) > 0:
        eer_ecapa, eer_thresh_ecapa = calculate_eer_manual(y_true_ecapa, y_scores_ecapa)
        y_pred_ecapa = [1 if s >= eer_thresh_ecapa else 0 for s in y_scores_ecapa]
        
        for idx, res in enumerate(ecapa_results):
            is_same_pred = y_pred_ecapa[idx] == 1
            res['Prediksi Model'] = 'SAMA' if is_same_pred else 'BEDA'
            res['Status'] = 'BENAR' if (y_true_ecapa[idx] == y_pred_ecapa[idx]) else 'SALAH'
            
        df_ecapa = pd.DataFrame(ecapa_results)
        ecapa_csv_path = os.path.join(output_dir, '2_Detail_Pengujian_Verifikasi_Identitas.csv')
        df_ecapa.to_csv(ecapa_csv_path, index=False)
        
        TP_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 1 and y_pred_ecapa[i] == 1])
        TN_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 0 and y_pred_ecapa[i] == 0])
        FP_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 0 and y_pred_ecapa[i] == 1])
        FN_E = sum([1 for i in range(len(y_true_ecapa)) if y_true_ecapa[i] == 1 and y_pred_ecapa[i] == 0])
        acc_ecapa = (TP_E + TN_E) / len(y_true_ecapa) if len(y_true_ecapa) > 0 else 0
        
        summary_ecapa = [{
            'Total Pasangan Diuji': len(y_true_ecapa),
            'Akurasi Identitas': f"{acc_ecapa*100:.2f}%",
            'Equal Error Rate (EER)': f"{eer_ecapa*100:.2f}%",
            'Threshold Optimal': eer_thresh_ecapa,
            'True Positive (Sama ditebak Sama)': TP_E,
            'True Negative (Beda ditebak Beda)': TN_E,
            'False Positive (Beda lolos jd Sama)': FP_E,
            'False Negative (Sama tertolak jd Beda)': FN_E
        }]
        df_ecapa_sum = pd.DataFrame(summary_ecapa)
        df_ecapa_sum.to_csv(os.path.join(output_dir, '2_Ringkasan_Verifikasi_Identitas.csv'), index=False)
    
    print(f"\nSELESAI! Semua file CSV telah disimpan di: {output_dir}")

if __name__ == "__main__":
    # dataset_path = r"D:\dataset_asv\test_dataset_300\train"
    # output_path = r"D:\dataset_asv\Laporan_Pengujian_AI"
    # run_reports(dataset_path, output_path)
    
    # Menjalankan evaluasi pada dataset yang baru (375 sampel)
    base_dataset_path = r"D:\dataset_asv\test_dataset_375"
    new_output_path = r"D:\dataset_asv\Laporan_Pengujian_AI_375"
    run_all_reports(base_dataset_path, new_output_path)
