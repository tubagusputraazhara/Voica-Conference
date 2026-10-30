#!/usr/bin/env python3
"""
Fine-tune AASIST for Voica on datasets/aasist_publik_siap (see docs/DATASET_AASIST.md).

Run from the project root with the CUDA training venv:
    .venv-train\\Scripts\\python scripts/training/train_aasist.py
    .venv-train\\Scripts\\python scripts/training/train_aasist.py --epochs 20 --lr 5e-5
    .venv-train\\Scripts\\python scripts/training/train_aasist.py --eval-only

Starts from the pretrained AASIST.pth, feeds it audio through the shared preprocessing in
scripts/antispoofing/aasist_preprocess.py, keeps the epoch with the lowest balanced dev EER and saves it to
scripts/aasist/models/weights/AASIST_voica.pth together with its preprocessing settings and
the dev EER threshold. Then scores every eval protocol with the old and the new model and
writes datasets/aasist_publik_siap/eval_report.json.
"""

import argparse
import json
import math
import sys
import time
from collections import Counter
from datetime import datetime
from pathlib import Path

import numpy as np
import soundfile as sf
import torch
import torch.nn as nn
from torch.utils.data import DataLoader, Dataset, WeightedRandomSampler

SCRIPTS_DIR = Path(__file__).resolve().parents[1]
PROJECT_DIR = SCRIPTS_DIR.parent
sys.path.insert(0, str(SCRIPTS_DIR))

from antispoofing import aasist_preprocess as pp  # noqa: E402
from antispoofing.aasist_engine import AASIST, MODEL_CONFIG, MODEL_PATH  # noqa: E402

DATA_DIR = PROJECT_DIR / 'datasets' / 'aasist_publik_siap'
OUT_PATH = Path(MODEL_PATH).with_name('AASIST_voica.pth')
REPORT_PATH = DATA_DIR / 'eval_report.json'

# What Voica does with the pretrained model (verify_secure in voice_processor_ecapa.py and
# verification_pipeline.py):
# bona fide probability below 8.75% is blocked, 89% and up is "safe", anything in between is a
# grey zone that only raises the ECAPA threshold.
LEGACY_BLOCK_BELOW = 0.0875
LEGACY_SAFE_FROM = 0.89
SAFE_ZONE_MAX_FAR = 0.01  # proposed safe zone for the new model: at most 1% of dev spoofs reach it


def log(msg):
    print(f'[{datetime.now():%H:%M:%S}] {msg}', flush=True)


def logit(p):
    return math.log(p / (1 - p))


def read_protocol(name):
    items = []
    for line in (DATA_DIR / 'protocols' / f'{name}.txt').read_text(encoding='utf-8').splitlines():
        _, key, source, attack, label = line.split()
        items.append({'key': key, 'source': source, 'attack': attack, 'y': int(label == 'bonafide')})
    return items


def group_of(item):
    return f"{item['source']}/{'bonafide' if item['y'] else item['attack']}"


class AudioSet(Dataset):
    """mode: 'train' (random crop + random gain), 'eval' (deterministic) or
    'legacy' (what anti_spoofing.py fed the pretrained model: truncate or zero-pad)."""

    def __init__(self, items, mode, max_gain_db=6.0):
        self.items, self.mode, self.max_gain_db = items, mode, max_gain_db

    def __len__(self):
        return len(self.items)

    def __getitem__(self, i):
        item = self.items[i]
        x, _ = sf.read(str(DATA_DIR / 'flac' / f"{item['key']}.flac"), dtype='float32')
        if self.mode == 'legacy':
            x = np.pad(x[:pp.NB_SAMP], (0, max(0, pp.NB_SAMP - len(x))))
        elif self.mode == 'train':
            rng = np.random.default_rng()
            x = pp.preprocess(x, rng=rng)
            # level jitter around the normalised level, so loudness alone is never a cue
            x = np.clip(x * 10 ** (rng.uniform(-self.max_gain_db, self.max_gain_db) / 20), -1.0, 1.0)
        else:
            x = pp.preprocess(x)
        return torch.from_numpy(np.ascontiguousarray(x, dtype=np.float32)), item['y']


def balanced_weights(items):
    """Sampling weights: both classes equally likely, and within a class every
    source/attack group equally likely (EchoFake would otherwise dominate)."""
    groups = Counter(group_of(it) for it in items)
    per_class = Counter(it['y'] for it in {group_of(it): it for it in items}.values())
    return [0.5 / per_class[it['y']] / groups[group_of(it)] for it in items]


def balanced_eer(scores, items):
    """EER with every source/attack group weighted equally. Returns (eer, threshold) where a
    score above the threshold counts as bona fide, or (None, None) if a class is missing."""
    y = np.array([it['y'] for it in items])
    if y.min() == y.max():
        return None, None
    groups = Counter(group_of(it) for it in items)
    w = np.array([1.0 / groups[group_of(it)] for it in items])
    order = np.argsort(scores, kind='stable')
    s, y, w = np.asarray(scores, dtype=np.float64)[order], y[order], w[order]
    wb, ws = np.where(y == 1, w, 0.0), np.where(y == 0, w, 0.0)
    frr = np.cumsum(wb) / wb.sum()      # bona fide at or below the threshold are rejected
    far = 1 - np.cumsum(ws) / ws.sum()  # spoofs above the threshold are accepted
    i = int(np.argmin(np.abs(frr - far)))
    thr = (s[i] + s[i + 1]) / 2 if i + 1 < len(s) else s[i]  # midway to the next score, not on it
    return max(0.0, float((frr[i] + far[i]) / 2)), float(thr)


def threshold_at_far(scores, items, target):
    """Lowest threshold at which at most `target` of the (group-balanced) spoofs score above it."""
    spoof = [(s, it) for s, it in zip(scores, items) if not it['y']]
    groups = Counter(group_of(it) for _, it in spoof)
    s = np.array([v for v, _ in spoof], dtype=np.float64)
    w = np.array([1.0 / groups[group_of(it)] for _, it in spoof])
    order = np.argsort(-s, kind='stable')
    s, cum = s[order], np.cumsum(w[order]) / w.sum()
    k = int(np.searchsorted(cum, target, side='right'))  # spoofs allowed above the threshold
    return float(s[min(k, len(s) - 1)])


def group_zones(scores, items, block_thr, safe_thr):
    """Per group, as Voica would treat it: % blocked (score <= block_thr), % grey, % safe
    (score > safe_thr), and % handled correctly (bona fide not blocked, spoof blocked)."""
    stats = {}
    for s, it in zip(scores, items):
        c = stats.setdefault(group_of(it), Counter())
        c['n'] += 1
        c['blok' if s <= block_thr else 'aman' if s > safe_thr else 'abu'] += 1
        c['benar'] += int((s > block_thr) == bool(it['y']))
    return {g: {'n': c['n'], **{f'{k}_%': round(100 * c[k] / c['n'], 1) for k in ('benar', 'blok', 'abu', 'aman')}}
            for g, c in sorted(stats.items())}


def group_accuracy(scores, items, thr):
    """Percentage of each group the model gets right at threshold thr."""
    hits, totals = Counter(), Counter()
    for s, it in zip(scores, items):
        g = group_of(it)
        totals[g] += 1
        hits[g] += int((s > thr) == bool(it['y']))
    return {g: {'n': totals[g], 'benar_%': round(100 * hits[g] / totals[g], 1)} for g in sorted(totals)}


def load_model(path, device):
    model = AASIST(MODEL_CONFIG)
    ckpt = torch.load(path, map_location='cpu', weights_only=False)
    model.load_state_dict(ckpt['state_dict'] if isinstance(ckpt, dict) and 'state_dict' in ckpt else ckpt)
    return model.to(device)


@torch.no_grad()
def score(model, items, mode, args, device):
    """Logit difference bonafide - spoof per item; sigmoid of it is the bona fide probability."""
    loader = DataLoader(AudioSet(items, mode), batch_size=args.eval_batch_size, shuffle=False,
                        num_workers=args.workers, pin_memory=device.type == 'cuda')
    model.eval()
    out, t0 = [], time.time()
    for i, (x, _) in enumerate(loader, 1):
        _, logits = model(x.to(device, non_blocking=True))
        out.append((logits[:, 1] - logits[:, 0]).float().cpu())
        if len(items) > 4000 and i % 100 == 0:
            done = min(i * args.eval_batch_size, len(items))
            log(f'    {done}/{len(items)} klip ({done / (time.time() - t0):.0f} klip/s)')
    return torch.cat(out).numpy()


def train(args, device):
    train_items, dev_items = read_protocol('train'), read_protocol('dev')
    log(f'train {len(train_items)} item, dev {len(dev_items)} item, device {device}')
    sampler = WeightedRandomSampler(balanced_weights(train_items), num_samples=args.epoch_size, replacement=True)
    loader = DataLoader(AudioSet(train_items, 'train'), batch_size=args.batch_size, sampler=sampler,
                        num_workers=args.workers, pin_memory=device.type == 'cuda', drop_last=True,
                        persistent_workers=args.workers > 0)

    model = load_model(MODEL_PATH, device)
    eer, thr = balanced_eer(score(model, dev_items, 'eval', args, device), dev_items)
    log(f'epoch 0 (AASIST.pth + pra-proses baru): dev EER {eer * 100:.2f}%')

    opt = torch.optim.Adam(model.parameters(), lr=args.lr, weight_decay=1e-4)
    sched = torch.optim.lr_scheduler.CosineAnnealingLR(opt, T_max=args.epochs * len(loader), eta_min=args.lr / 100)
    loss_fn = nn.CrossEntropyLoss()
    best_eer, stale = math.inf, 0

    for epoch in range(1, args.epochs + 1):
        model.train()
        t0, seen, loss_sum, correct = time.time(), 0, 0.0, 0
        for step, (x, y) in enumerate(loader, 1):
            x, y = x.to(device, non_blocking=True), y.to(device, non_blocking=True)
            # bf16 trains ~1.5x faster on a 6 GB laptop GPU; scoring stays fp32 to match Voica
            with torch.autocast(device.type, dtype=torch.bfloat16, enabled=args.amp):
                _, logits = model(x)
            logits = logits.float()
            loss = loss_fn(logits, y)
            opt.zero_grad(set_to_none=True)
            loss.backward()
            opt.step()
            sched.step()
            seen += len(y)
            loss_sum += loss.item() * len(y)
            correct += (logits.argmax(1) == y).sum().item()
            if step % 100 == 0:
                log(f'  epoch {epoch} step {step}/{len(loader)} loss {loss_sum / seen:.4f} '
                    f'acc {100 * correct / seen:.1f}% ({seen / (time.time() - t0):.0f} klip/s)')

        dev_scores = score(model, dev_items, 'eval', args, device)
        eer, thr = balanced_eer(dev_scores, dev_items)
        log(f'epoch {epoch}: train loss {loss_sum / seen:.4f} acc {100 * correct / seen:.1f}% | '
            f'dev EER {eer * 100:.2f}% (threshold p={1 / (1 + math.exp(-thr)):.4f}) | {time.time() - t0:.0f}s')
        for g, r in group_accuracy(dev_scores, dev_items, thr).items():
            log(f'    {g:22s} {r["benar_%"]:5.1f}% benar dari {r["n"]}')

        if eer < best_eer:
            best_eer, stale = eer, 0
            safe_thr = max(thr, threshold_at_far(dev_scores, dev_items, SAFE_ZONE_MAX_FAR))
            torch.save({
                'state_dict': model.state_dict(),
                'model_config': MODEL_CONFIG,
                'preprocess': {'name': 'voica_v1', 'sr': pp.SR, 'nb_samp': pp.NB_SAMP,
                               'trim_floor_db': pp.TRIM_FLOOR_DB, 'target_dbfs': pp.TARGET_DBFS},
                # Proposed Voica zones, not applied anywhere until someone decides to:
                # block below `threshold` (dev EER point), "safe" from `safe_threshold` (dev FAR 1%)
                'threshold': 1 / (1 + math.exp(-thr)),
                'safe_threshold': 1 / (1 + math.exp(-safe_thr)),
                'dev_eer': eer,
                'epoch': epoch,
                'created': datetime.now().isoformat(timespec='seconds'),
                'data': 'datasets/aasist_publik_siap (docs/DATASET_AASIST.md)',
            }, OUT_PATH)
            log(f'  -> tersimpan sebagai {OUT_PATH.name} (terbaik sejauh ini)')
        else:
            stale += 1
            if stale >= args.patience:
                log(f'dev EER tidak membaik {args.patience} epoch berturut-turut, berhenti')
                break
    log(f'training selesai, dev EER terbaik {best_eer * 100:.2f}%')


def evaluate(args, device):
    ckpt = torch.load(OUT_PATH, map_location='cpu', weights_only=False)
    models = {  # name: (model, input mode, block cutoff, safe cutoff), cutoffs as bona fide probability
        'lama (AASIST.pth)': (load_model(MODEL_PATH, device), 'legacy', LEGACY_BLOCK_BELOW, LEGACY_SAFE_FROM),
        'baru (AASIST_voica.pth)': (load_model(OUT_PATH, device), 'eval', ckpt['threshold'], ckpt['safe_threshold']),
    }
    report = {'zona': {name: {'blok_jika_bonafide_kurang_dari_%': round(100 * b, 2),
                              'aman_jika_bonafide_minimal_%': round(100 * s, 2)}
                       for name, (_, _, b, s) in models.items()},
              'epoch': ckpt['epoch'], 'dev_eer_%': round(100 * ckpt['dev_eer'], 2), 'protokol': {}}
    rng = np.random.default_rng(args.seed)

    for proto in sorted(p.stem for p in (DATA_DIR / 'protocols').glob('eval_*.txt')):
        items = read_protocol(proto)
        if args.eval_limit and len(items) > args.eval_limit:
            items = [items[i] for i in sorted(rng.choice(len(items), args.eval_limit, replace=False))]
        log(f'== {proto} ({len(items)} item)')
        report['protokol'][proto] = {}
        for name, (model, mode, block_p, safe_p) in models.items():
            # Cache scores so an interrupted evaluation resumes where it stopped
            tag = 'lama' if mode == 'legacy' else f"baru_e{ckpt['epoch']}_{ckpt['created'].replace(':', '')}"
            cache = DATA_DIR / 'scores' / f'{proto}__{tag}__n{len(items)}_s{args.seed}.npy'
            if cache.exists():
                s = np.load(cache)
            else:
                s = score(model, items, mode, args, device)
                cache.parent.mkdir(exist_ok=True)
                np.save(cache, s)
            eer, _ = balanced_eer(s, items)
            entry = {'eer_%': None if eer is None else round(100 * eer, 2),
                     'per_kelompok': group_zones(s, items, logit(block_p), logit(safe_p))}
            if proto == 'eval_voica':
                entry['per_file_bonafide_%'] = {it['key']: round(100 / (1 + math.exp(-v)), 2) for it, v in zip(items, s)}
            report['protokol'][proto][name] = entry
            log(f"  {name:24s} EER {'-' if eer is None else f'{100 * eer:.2f}%'}")
            for g, r in entry['per_kelompok'].items():
                log(f"      {g:22s} benar {r['benar_%']:5.1f}% | blok {r['blok_%']:5.1f}% "
                    f"abu {r['abu_%']:5.1f}% aman {r['aman_%']:5.1f}% (n={r['n']})")
            if proto == 'eval_voica':
                for k, v in entry['per_file_bonafide_%'].items():
                    log(f'      {k:22s} bonafide {v:6.2f}%')

    REPORT_PATH.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding='utf-8')
    log(f'laporan: {REPORT_PATH}')


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--epochs', type=int, default=15)
    parser.add_argument('--epoch-size', type=int, default=16000, help='clips sampled per epoch')
    parser.add_argument('--batch-size', type=int, default=16, help='16 fits a 6 GB GPU with bf16')
    parser.add_argument('--eval-batch-size', type=int, default=16, help='scoring runs in fp32')
    parser.add_argument('--no-amp', dest='amp', action='store_false', help='train in fp32 instead of bf16')
    parser.add_argument('--lr', type=float, default=1e-4)
    parser.add_argument('--patience', type=int, default=4)
    parser.add_argument('--workers', type=int, default=6)
    parser.add_argument('--seed', type=int, default=1234)
    parser.add_argument('--eval-only', action='store_true', help='skip training, only score eval protocols')
    parser.add_argument('--eval-limit', type=int, default=0, help='random items per eval protocol (0 = all)')
    args = parser.parse_args()

    torch.manual_seed(args.seed)
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    torch.backends.cudnn.benchmark = True
    if device.type == 'cuda':
        # On Windows the driver silently spills past VRAM into shared RAM and everything
        # crawls; capping the allocator turns that into a clear out-of-memory error instead.
        torch.cuda.set_per_process_memory_fraction(0.9)
    else:
        args.amp = False
    if not args.eval_only:
        train(args, device)
    evaluate(args, device)


if __name__ == '__main__':
    main()
