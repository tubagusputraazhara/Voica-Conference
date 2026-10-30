#!/usr/bin/env python3
"""
Convert the downloaded public datasets (plus the team's own recordings) into one AASIST-ready set.

Usage:
    python scripts/aasist_dataset/prepare_public_datasets.py [--workers 6] [--no-sim]

Input : datasets/aasist_publik_mentah/   (from download_public_datasets.py)
        datasets/aasist_mentah/{asli,replay}/   (team recordings, used for eval only)
Output: datasets/aasist_publik_siap/
          flac/<KEY>.flac        16 kHz mono FLAC, original loudness kept (no trim/normalize)
          protocols/train.txt, dev.txt, eval.txt, eval_<SOURCE>.txt, eval_voica.txt
          metadata.csv, summary.json

Protocol lines follow the ASVspoof 2019 layout read by scripts/aasist/data_utils.py:
    SPEAKER KEY SOURCE ATTACK LABEL        (LABEL is bonafide or spoof)

Sources:
    A17  ASVspoof 2017 V2      real replays, bona fide recorded on smartphones (RedDots)
    EF   EchoFake              Common Voice bona fide, phone/laptop replays, zero-shot TTS
    RDF  ReplayDF (subset)     replayed bona fide over 109 loudspeaker/mic pairs
    FLR  FLEURS id_id          Indonesian bona fide
    SIM  FLEURS id_id x RIR    Indonesian replays simulated with ReplayDF's measured RIRs,
                               so Indonesian speech is not bona fide-only (language shortcut)
    VOC  datasets/aasist_mentah   team's real asli/replay recordings (eval_voica.txt only)
Re-running skips FLAC files that already exist.
"""

import argparse
import csv
import io
import json
import random
import shutil
import subprocess
import sys
from collections import Counter, defaultdict
from concurrent.futures import FIRST_COMPLETED, ProcessPoolExecutor, wait
from math import gcd
from pathlib import Path

import numpy as np
import pyarrow.parquet as pq
import soundfile as sf
from scipy.signal import fftconvolve, resample_poly

PROJECT_DIR = Path(__file__).resolve().parents[2]
RAW_DIR = PROJECT_DIR / 'datasets' / 'aasist_publik_mentah'
TEAM_DIR = PROJECT_DIR / 'datasets' / 'aasist_mentah'
OUT_DIR = PROJECT_DIR / 'datasets' / 'aasist_publik_siap'
FLAC_DIR = OUT_DIR / 'flac'
SR = 16000

_local_ffmpeg = PROJECT_DIR / 'ffmpeg' / 'bin' / 'ffmpeg.exe'
FFMPEG = str(_local_ffmpeg) if _local_ffmpeg.exists() else shutil.which('ffmpeg')

LICENSES = {
    'A17': 'CC BY-NC 4.0',
    'EF': 'MIT (bona fide from Common Voice, CC0)',
    'RDF': 'CC BY-NC 4.0',
    'FLR': 'CC BY 4.0',
    'SIM': 'derived: FLEURS CC BY 4.0 + ReplayDF RIR CC BY-NC 4.0',
    'VOC': 'internal team recordings, do not redistribute',
}

META_FIELDS = ['key', 'split', 'label', 'attack', 'source', 'subset', 'speaker', 'language',
               'player', 'recorder', 'environment', 'distance', 'tts_model', 'original',
               'duration_s', 'license']


# ---------------------------------------------------------------- audio helpers (run in workers)

def _ffmpeg_decode(src=None, data=None):
    if not FFMPEG:
        raise RuntimeError('ffmpeg not found')
    cmd = [FFMPEG, '-v', 'error', '-i', 'pipe:0' if data is not None else str(src),
           '-ac', '1', '-ar', str(SR), '-f', 'f32le', 'pipe:1']
    out = subprocess.run(cmd, input=data, capture_output=True, check=True).stdout
    return np.frombuffer(out, dtype=np.float32).copy(), SR


def _load(kind, payload):
    if kind == 'ffmpeg':
        return _ffmpeg_decode(src=payload)
    try:
        src = io.BytesIO(payload) if kind == 'bytes' else payload
        return sf.read(src, dtype='float32', always_2d=False)
    except Exception:
        return _ffmpeg_decode(data=payload) if kind == 'bytes' else _ffmpeg_decode(src=payload)


def _to_mono_16k(x, sr):
    if x.ndim > 1:
        x = x.mean(axis=1)
    if sr != SR:
        g = gcd(int(sr), SR)
        x = resample_poly(x, SR // g, int(sr) // g)
    return x.astype(np.float32)


def _load_rir(path):
    h, sr = sf.read(path, dtype='float32', always_2d=False)
    h = _to_mono_16k(h, sr)
    start = max(0, int(np.argmax(np.abs(h))) - SR // 1000)  # align to direct path, keep 1 ms
    h = h[start:start + SR]  # 1 s of reverb tail is plenty for these rooms
    return h / (np.sqrt(np.sum(h ** 2)) + 1e-9)


def _rms(x):
    return float(np.sqrt(np.mean(x ** 2)) + 1e-9)


def convert(job):
    """Decode/resample one item to flac/<key>.flac. Returns (key, duration_s, error)."""
    key = job['key']
    out = FLAC_DIR / f'{key}.flac'
    try:
        if out.exists():
            return key, sf.info(str(out)).duration, None
        if job['kind'] == 'sim':
            x, _ = sf.read(job['payload'], dtype='float32')
            y = fftconvolve(x, _load_rir(job['rir']))[:len(x)]
            x = y * (_rms(x) / _rms(y))  # keep loudness equal so level is not a cue
        else:
            x = _to_mono_16k(*_load(job['kind'], job['payload']))
        if len(x) < SR // 10:
            return key, 0.0, 'shorter than 0.1 s'
        tmp = out.with_name(out.name + '.part')
        sf.write(str(tmp), np.clip(x, -1.0, 1.0), SR, format='FLAC', subtype='PCM_16')
        tmp.replace(out)
        return key, len(x) / SR, None
    except Exception as e:
        return key, 0.0, f'{type(e).__name__}: {e}'


# ---------------------------------------------------------------- collectors (main process)

def _row(key, split, label, attack, source, **extra):
    row = {f: '-' for f in META_FIELDS}
    row.update(key=key, split=split, label=label, attack=attack, source=source,
               license=LICENSES[source], duration_s=0.0)
    row.update({k: (v if v not in (None, '') else '-') for k, v in extra.items()})
    return row


def collect_a17(seed):
    base = RAW_DIR / 'asvspoof2017_v2'
    protocols = {'train': 'ASVspoof2017_V2_train.trn.txt', 'dev': 'ASVspoof2017_V2_dev.trl.txt',
                 'eval': 'ASVspoof2017_V2_eval.trl.txt'}
    for split, proto in protocols.items():
        proto_path = base / 'protocol_V2' / proto
        audio_dir = base / f'ASVspoof2017_V2_{split}'
        if not proto_path.exists() or not audio_dir.exists():
            print(f'  [A17] {split}: belum ada, dilewati', flush=True)
            continue
        for line in proto_path.read_text().splitlines():
            name, label, speaker, phrase, env, player, recorder = line.split()
            genuine = label == 'genuine'
            row = _row(f'A17_{Path(name).stem}', split, 'bonafide' if genuine else 'spoof',
                       '-' if genuine else 'replay', 'A17', speaker=speaker, language='en',
                       player=player, recorder=recorder, environment=env, original=name)
            yield row, {'key': row['key'], 'kind': 'file', 'payload': str(audio_dir / name)}


def _iter_parquet(path, columns=None, batch_size=64):
    for batch in pq.ParquetFile(path).iter_batches(batch_size=batch_size, columns=columns):
        yield from batch.to_pylist()


def collect_ef(seed):
    attacks = {'bonafide': '-', 'replay_bonafide': 'replay', 'fake': 'tts', 'replay_fake': 'replay_tts'}
    files = sorted((RAW_DIR / 'echofake' / 'data').glob('*.parquet'))
    if not files:
        print('  [EF] belum ada, dilewati', flush=True)
    tags = {'train': 'tr', 'dev': 'dv', 'closed_set_eval': 'ce', 'open_set_eval': 'oe'}
    for path in files:
        subset = path.name.split('-')[0]  # train, dev, closed_set_eval, open_set_eval
        split = subset if subset in ('train', 'dev') else 'eval'
        for r in _iter_parquet(path):
            rep, syn = r.get('replay_details') or {}, r.get('synthesis_details') or {}
            src = r.get('source') or ''
            lang = src.split('_')[2] if src.startswith('common_voice_') else '-'
            # utt_id alone is not unique: dev's fakes reuse 1986 "EF_T_*" ids of train's bona fide
            key = f"EF_{tags[subset]}_{r['utt_id'][3:]}"
            row = _row(key, split, 'bonafide' if r['label'] == 'bonafide' else 'spoof',
                       attacks.get(r['label'], r['label']), 'EF', subset=subset,
                       speaker=(r.get('source_speaker_id') or '-')[:16], language=lang,
                       player=rep.get('player'), recorder=rep.get('recorder'),
                       environment=rep.get('room_size'), distance=rep.get('distance'),
                       tts_model=syn.get('model'), original=f"{r['utt_id']}:{src}")
            yield row, {'key': row['key'], 'kind': 'bytes', 'payload': r['path']['bytes']}


def replaydf_splits(seed):
    """Split ReplayDF by loudspeaker/mic config so dev/eval use devices never seen in training."""
    uids = sorted(p.name for p in (RAW_DIR / 'replaydf' / 'wav').glob('*') if p.is_dir())
    random.Random(seed).shuffle(uids)
    n_hold = max(1, round(len(uids) * 0.1)) if uids else 0
    split_of = {u: 'dev' for u in uids[:n_hold]}
    split_of.update({u: 'eval' for u in uids[n_hold:2 * n_hold]})
    split_of.update({u: 'train' for u in uids[2 * n_hold:]})
    return split_of


def _replaydf_devices(uid):
    info = RAW_DIR / 'replaydf' / 'aux' / uid / 'info.txt'
    text = info.read_text(encoding='utf-8') if info.exists() else ''
    lines = [line.strip() for line in text.splitlines() if line.strip()]
    return (lines + ['-', '-'])[:2]  # loudspeaker, microphone


def collect_rdf(seed):
    base = RAW_DIR / 'replaydf'
    meta = {}
    for meta_csv in (base / 'wav').glob('*/meta.csv'):
        with open(meta_csv, encoding='utf-8') as f:
            for m in csv.DictReader(f, delimiter='|'):
                meta[m['recorded_file']] = m
    split_of = replaydf_splits(seed)
    for wav in sorted((base / 'wav').glob('*/benign/**/*.wav')):
        rel = wav.relative_to(base).as_posix()
        uid = rel.split('/')[1]
        m = meta.get(rel, {})
        player, recorder = _replaydf_devices(uid)
        orig = m.get('original_file', '-')
        speaker = orig.split('/')[3] if orig.count('/') >= 4 else '-'  # <lang>/by_book/<gender>/<speaker>/...
        row = _row(f'RDF_{uid}_{wav.stem}', split_of[uid], 'spoof', 'replay', 'RDF', subset=uid,
                   speaker=speaker, language=m.get('language', rel.split('/')[3]),
                   player=m.get('speaker', player), recorder=m.get('mic', recorder), original=orig)
        yield row, {'key': row['key'], 'kind': 'file', 'payload': str(wav)}


def collect_flr(seed):
    base = RAW_DIR / 'fleurs_id' / 'parquet-data' / 'id_id'
    splits = {'train': ('train', 'tr'), 'validation': ('dev', 'dv'), 'test': ('eval', 'ev')}
    files = sorted(base.glob('*.parquet'))
    if not files:
        print('  [FLR] belum ada, dilewati', flush=True)
    for path in files:
        split, tag = splits[path.name.split('-')[0]]
        for i, r in enumerate(_iter_parquet(path)):
            audio = r.get('audio') or {}
            # FLEURS has no speaker ids, only a sentence id shared by its 1-3 readers
            row = _row(f'FLR_{tag}_{i:05d}', split, 'bonafide', '-', 'FLR', subset=f'fleurs_{tag}',
                       language='id', original=f"sentence_{r.get('id')}/{audio.get('path') or '-'}")
            yield row, {'key': row['key'], 'kind': 'bytes', 'payload': audio['bytes']}


def collect_sim(seed, flr_rows):
    split_of = replaydf_splits(seed)
    rirs = defaultdict(list)
    for uid, split in split_of.items():
        rir = RAW_DIR / 'replaydf' / 'aux' / uid / 'RIR.wav'
        if rir.exists():
            rirs[split].append(uid)
    if not rirs:
        print('  [SIM] RIR ReplayDF belum ada, dilewati', flush=True)
        return
    for src in flr_rows:
        if src['duration_s'] <= 0 or not rirs[src['split']]:
            continue
        uid = random.Random(f"{seed}-{src['key']}").choice(sorted(rirs[src['split']]))
        player, recorder = _replaydf_devices(uid)
        row = _row('SIM' + src['key'][3:], src['split'], 'spoof', 'sim_replay', 'SIM', subset=uid,
                   speaker=src['speaker'], language='id', player=player, recorder=recorder,
                   original=src['key'])
        yield row, {'key': row['key'], 'kind': 'sim', 'payload': str(FLAC_DIR / f"{src['key']}.flac"),
                    'rir': str(RAW_DIR / 'replaydf' / 'aux' / uid / 'RIR.wav')}


def collect_voc(seed):
    for label_dir, label, attack in (('asli', 'bonafide', '-'), ('replay', 'spoof', 'replay')):
        for f in sorted((TEAM_DIR / label_dir).glob('*')):
            if not f.is_file():
                continue
            speaker = f.name.split('.')[0].split('_')[-1].upper()
            row = _row(f'VOC_{label_dir}_{speaker}', 'eval_voica', label, attack, 'VOC',
                       speaker=speaker, language='id', original=f'aasist_mentah/{label_dir}/{f.name}')
            yield row, {'key': row['key'], 'kind': 'ffmpeg', 'payload': str(f)}


# ---------------------------------------------------------------- driver

def process(pool, items, rows, workers, name):
    pending, errors, count = set(), Counter(), 0

    def drain(done):
        nonlocal count
        for fut in done:
            key, dur, err = fut.result()
            rows[key]['duration_s'] = round(dur, 3)
            if err:
                errors[err.split(':')[0]] += 1
                if sum(errors.values()) <= 5:
                    print(f'  [{name}] gagal {key}: {err}', flush=True)
            count += 1
            if count % 2000 == 0:
                print(f'  [{name}] {count} file', flush=True)

    for row, job in items:
        if row['key'] in rows:  # a reused key would pair one row's label with another's audio
            raise ValueError(f"duplicate key {row['key']} ({rows[row['key']]['split']} vs {row['split']})")
        rows[row['key']] = row
        pending.add(pool.submit(convert, job))
        if len(pending) >= workers * 8:
            done, pending = wait(pending, return_when=FIRST_COMPLETED)
            drain(done)
    drain(wait(pending)[0] if pending else [])
    print(f'  [{name}] selesai: {count} file, gagal {sum(errors.values())} {dict(errors) or ""}', flush=True)


def write_outputs(rows):
    ok = [r for r in rows.values() if r['duration_s'] > 0]
    ok.sort(key=lambda r: r['key'])

    with open(OUT_DIR / 'metadata.csv', 'w', newline='', encoding='utf-8') as f:
        w = csv.DictWriter(f, fieldnames=META_FIELDS)
        w.writeheader()
        w.writerows(ok)

    proto_dir = OUT_DIR / 'protocols'
    proto_dir.mkdir(exist_ok=True)
    groups = defaultdict(list)
    for r in ok:
        line = f"{r['speaker'].replace(' ', '_')} {r['key']} {r['source']} {r['attack']} {r['label']}"
        groups[r['split']].append(line)
        if r['split'] == 'eval':
            groups[f"eval_{r['source']}"].append(line)
    for name, lines in groups.items():
        (proto_dir / f'{name}.txt').write_text('\n'.join(lines) + '\n', encoding='utf-8')

    summary = defaultdict(lambda: defaultdict(Counter))
    hours = defaultdict(float)
    for r in ok:
        summary[r['split']][r['source']][r['attack'] if r['label'] == 'spoof' else 'bonafide'] += 1
        hours[r['split']] += r['duration_s'] / 3600
    out = {split: {'jam': round(hours[split], 1),
                   'per_sumber': {s: dict(c) for s, c in sorted(srcs.items())},
                   'bonafide': sum(c['bonafide'] for c in srcs.values()),
                   'spoof': sum(sum(v for k, v in c.items() if k != 'bonafide') for c in srcs.values())}
           for split, srcs in sorted(summary.items())}
    (OUT_DIR / 'summary.json').write_text(json.dumps(out, indent=2), encoding='utf-8')
    print(json.dumps(out, indent=2))


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--workers', type=int, default=6)
    parser.add_argument('--seed', type=int, default=1234)
    parser.add_argument('--no-sim', action='store_true', help='skip simulated Indonesian replays')
    args = parser.parse_args()

    FLAC_DIR.mkdir(parents=True, exist_ok=True)
    rows = {}
    with ProcessPoolExecutor(max_workers=args.workers) as pool:
        for name, collector in (('A17', collect_a17), ('EF', collect_ef), ('RDF', collect_rdf),
                                ('FLR', collect_flr), ('VOC', collect_voc)):
            print(f'== {name}', flush=True)
            process(pool, collector(args.seed), rows, args.workers, name)
        if not args.no_sim:
            print('== SIM', flush=True)
            flr_rows = [r for r in rows.values() if r['source'] == 'FLR']
            process(pool, collect_sim(args.seed, flr_rows), rows, args.workers, 'SIM')
    write_outputs(rows)


if __name__ == '__main__':
    sys.exit(main())
