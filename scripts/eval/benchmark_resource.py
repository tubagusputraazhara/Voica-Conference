#!/usr/bin/env python3
"""
Benchmark Efisiensi & Resource (Test 2 di skenario paper): inference
latency, model size, dan RAM/VRAM usage untuk backbone ECAPA-TDNN,
ResNet-34, dan WavLM.

Tidak bergantung pada threshold/EER sama sekali -- hanya butuh model yang
bisa di-load, jadi bisa dijalankan kapan saja tanpa menunggu hasil evaluasi
EER (Test 3) selesai.

Setiap backbone diukur di proses Python TERPISAH (lewat subprocess) supaya
angka RAM/VRAM bersih dan tidak tercampur sama backbone lain yang sudah
ter-load sebelumnya di proses yang sama.

Usage:
    python scripts/eval/benchmark_resource.py --backbone ecapa
    python scripts/eval/benchmark_resource.py --backbone all --runs 30
    python scripts/eval/benchmark_resource.py --backbone all --audio "D:\\path\\sample.flac"

Kalau --audio tidak diisi, script generate audio sintetis (sine+noise, 3
detik) hanya untuk mengukur latency -- isi kontennya tidak berpengaruh ke
latency/resource, tapi untuk laporan paper SEBAIKNYA pakai sample suara
asli (representatif dengan skenario Test 1 / durasi nyata).
"""

import os
import sys
import json
import time
import argparse
import subprocess

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
SCRIPTS_DIR = os.path.dirname(SCRIPT_DIR)
sys.path.insert(0, SCRIPTS_DIR)

BACKBONES = {
    'ecapa': 'voice_processor_ecapa',
    'resnet34': 'voice_processor_resnet34',
    'wavlm': 'voice_processor_wavlm',
}


def _make_synthetic_audio(path: str, duration: float = 3.0, sr: int = 16000, seed: int = 0):
    import numpy as np
    import soundfile as sf

    rng = np.random.RandomState(seed)
    t = np.linspace(0, duration, int(sr * duration))
    signal = (0.3 * np.sin(2 * np.pi * 150 * t) + 0.05 * rng.randn(len(t))).astype('float32')
    sf.write(path, signal, sr)


def _run_single_backbone(backbone: str, audio_path: str, runs: int, warmup: int) -> dict:
    """Dijalankan DI DALAM subprocess terisolasi untuk satu backbone."""
    import numpy as np
    import psutil
    import torch

    proc = psutil.Process(os.getpid())
    rss_before = proc.memory_info().rss

    module = __import__(BACKBONES[backbone], fromlist=['get_verifier'])

    t0 = time.perf_counter()
    verifier = module.get_verifier()
    load_time_s = time.perf_counter() - t0

    rss_after_load = proc.memory_info().rss
    ram_model_mb = (rss_after_load - rss_before) / (1024 ** 2)

    # Model size = total parameter * ukuran per elemen (byte), bukan ukuran file
    # checkpoint di disk -- supaya konsisten walau format file beda (.ckpt/.bin/.safetensors).
    embedding_model = verifier.embedding_model
    param_bytes = sum(p.numel() * p.element_size() for p in embedding_model.parameters())
    model_size_mb = param_bytes / (1024 ** 2)
    num_params = sum(p.numel() for p in embedding_model.parameters())

    # Warmup (buang efek lazy-init/caching pertama kali)
    for _ in range(warmup):
        verifier.extract_embedding(audio_path)

    if torch.cuda.is_available():
        torch.cuda.synchronize()
        torch.cuda.reset_peak_memory_stats()

    latencies_ms = []
    for _ in range(runs):
        t0 = time.perf_counter()
        verifier.extract_embedding(audio_path)
        if torch.cuda.is_available():
            torch.cuda.synchronize()
        latencies_ms.append((time.perf_counter() - t0) * 1000)

    vram_mb = None
    if torch.cuda.is_available():
        vram_mb = torch.cuda.max_memory_allocated() / (1024 ** 2)

    latencies_ms = np.array(latencies_ms)
    # 1 "pair matching" = ekstrak embedding 2 file + cosine similarity (cosine sim ~0ms, diabaikan)
    pair_latency_mean_ms = float(latencies_ms.mean()) * 2

    return {
        'backbone': backbone,
        'model_display_name': getattr(module, 'MODEL_NAME', backbone),
        'device': module.DEVICE,
        'load_time_s': round(load_time_s, 3),
        'model_size_mb': round(model_size_mb, 2),
        'num_parameters': int(num_params),
        'ram_usage_mb': round(ram_model_mb, 2),
        'vram_usage_mb': round(vram_mb, 2) if vram_mb is not None else None,
        'single_embedding_latency_ms': {
            'mean': round(float(latencies_ms.mean()), 3),
            'std': round(float(latencies_ms.std()), 3),
            'min': round(float(latencies_ms.min()), 3),
            'max': round(float(latencies_ms.max()), 3),
        },
        'pair_matching_latency_ms_mean': round(pair_latency_mean_ms, 3),
        'runs': runs,
        'warmup': warmup,
    }


def build_arg_parser():
    parser = argparse.ArgumentParser(description="Benchmark latency/size/RAM/VRAM per backbone")
    parser.add_argument('--backbone', required=True, choices=list(BACKBONES.keys()) + ['all'])
    parser.add_argument('--audio', default=None,
                         help='Path ke file audio contoh. Default: generate audio sintetis 3 detik.')
    parser.add_argument('--runs', type=int, default=20, help='Jumlah pengulangan inference yang diukur')
    parser.add_argument('--warmup', type=int, default=3, help='Jumlah pengulangan pemanasan (tidak dihitung)')
    parser.add_argument('--output', default=None, help='Default: scripts/eval/results_resource_benchmark.json')
    parser.add_argument('--_internal-single-run', action='store_true', help=argparse.SUPPRESS)
    return parser


def main():
    args = build_arg_parser().parse_args()

    if args._internal_single_run:
        # Dipanggil oleh proses induk untuk 1 backbone spesifik di subprocess terisolasi.
        result = _run_single_backbone(args.backbone, args.audio, args.runs, args.warmup)
        print(json.dumps(result))
        return

    tmp_audio = None
    audio_path = args.audio
    if audio_path is None:
        import tempfile
        tmp_audio = os.path.join(tempfile.gettempdir(), 'voica_benchmark_synthetic.wav')
        _make_synthetic_audio(tmp_audio)
        audio_path = tmp_audio
        print("PERINGATAN: --audio tidak diisi, pakai audio sintetis (sine+noise, 3 detik).")
        print("Untuk hasil yang representatif di paper, jalankan ulang dengan --audio <sample_asli>.\n")

    backbones_to_run = list(BACKBONES.keys()) if args.backbone == 'all' else [args.backbone]

    all_results = []
    for bb in backbones_to_run:
        print(f"=== Benchmarking '{bb}' (subprocess terisolasi) ===")
        cmd = [
            sys.executable, os.path.abspath(__file__),
            '--backbone', bb,
            '--audio', audio_path,
            '--runs', str(args.runs),
            '--warmup', str(args.warmup),
            '--_internal-single-run',
        ]
        proc = subprocess.run(cmd, capture_output=True, text=True)
        if proc.returncode != 0:
            print(f"Gagal benchmark '{bb}':\n{proc.stderr}")
            continue

        json_line = proc.stdout.strip().splitlines()[-1]
        result = json.loads(json_line)
        all_results.append(result)
        print(f"  model_size_mb={result['model_size_mb']}  "
              f"ram_usage_mb={result['ram_usage_mb']}  "
              f"latency_mean_ms={result['single_embedding_latency_ms']['mean']}\n")

    if tmp_audio and os.path.exists(tmp_audio):
        os.remove(tmp_audio)

    if not all_results:
        print("Tidak ada hasil (semua backbone gagal).")
        return

    print("=" * 90)
    print("      RINGKASAN BENCHMARK EFISIENSI & RESOURCE (TEST 2)")
    print("=" * 90)
    print(f"{'Model':<14}{'Size(MB)':>10}{'#Params':>14}{'RAM(MB)':>10}{'VRAM(MB)':>10}"
          f"{'Lat/emb(ms)':>14}{'Lat/pair(ms)':>14}")
    for r in all_results:
        vram = r['vram_usage_mb'] if r['vram_usage_mb'] is not None else 'N/A'
        print(f"{r['model_display_name']:<14}{r['model_size_mb']:>10.2f}{r['num_parameters']:>14,}"
              f"{r['ram_usage_mb']:>10.2f}{str(vram):>10}"
              f"{r['single_embedding_latency_ms']['mean']:>14.3f}"
              f"{r['pair_matching_latency_ms_mean']:>14.3f}")
    print("=" * 90)

    output_path = args.output or os.path.join(SCRIPT_DIR, 'results_resource_benchmark.json')
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(all_results, f, indent=2)
    print(f"\nHasil lengkap disimpan ke: {output_path}")


if __name__ == '__main__':
    main()
