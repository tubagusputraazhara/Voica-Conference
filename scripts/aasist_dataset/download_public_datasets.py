#!/usr/bin/env python3
"""
Download public anti-spoofing datasets for fine-tuning AASIST against replay attacks.

Usage:
    python scripts/aasist_dataset/download_public_datasets.py
    python scripts/aasist_dataset/download_public_datasets.py --only echofake fleurs_id
    python scripts/aasist_dataset/download_public_datasets.py --replaydf-per-config 100

Files land in datasets/aasist_publik_mentah/<dataset>/. Re-running skips finished files
and resumes partial ones. See docs/DATASET_AASIST.md for contents, sources and licenses.
"""

import argparse
import json
import random
import re
import shutil
import sys
import time
import urllib.error
import urllib.request
import zipfile
from collections import defaultdict
from concurrent.futures import ThreadPoolExecutor, as_completed, wait
from datetime import datetime, timezone
from pathlib import Path

PROJECT_DIR = Path(__file__).resolve().parents[2]
RAW_DIR = PROJECT_DIR / 'datasets' / 'aasist_publik_mentah'

DATASHARE = 'https://datashare.ed.ac.uk/bitstreams'
ASVSPOOF2017_FILES = {
    'README_V2.txt': f'{DATASHARE}/ab9df60c-3909-4667-bff3-09b4925ea00a/download',
    'protocol_V2.zip': f'{DATASHARE}/dbf267c4-517e-4193-9cdf-c8eadc82ec78/download',
    'ASVspoof2017_V2_train.zip': f'{DATASHARE}/4c7e2262-fe78-497e-839f-0ceabbbf2d1e/download',
    'ASVspoof2017_V2_dev.zip': f'{DATASHARE}/4daef0d3-f9e8-49e4-9ffc-a7362842a8f2/download',
    'ASVspoof2017_V2_eval.zip': f'{DATASHARE}/77c52086-76ed-4517-a72a-cc94e54a2c0a/download',
}

HF_REPOS = {
    'echofake': 'EchoFake/EchoFake',
    'fleurs_id': 'google/fleurs',
    'replaydf': 'mueller91/ReplayDF',
}

ALL_DATASETS = ['asvspoof2017_v2', 'echofake', 'fleurs_id', 'replaydf']
USER_AGENT = 'voica-aasist-dataset-downloader'


def log(msg):
    print(f'[{datetime.now():%H:%M:%S}] {msg}', flush=True)


def _wait_seconds(err, attempt):
    """Seconds to wait before retrying, honouring Hugging Face's RateLimit header."""
    if isinstance(err, urllib.error.HTTPError) and err.code == 429:
        match = re.search(r't=(\d+)', err.headers.get('RateLimit', '') or '')
        return int(match.group(1)) + 2 if match else 60
    return min(2 ** attempt, 60)


def _total_size(resp):
    """Full file size from Content-Range (206) or Content-Length (200), if the server sent it."""
    if resp.status == 206:
        total = (resp.headers.get('Content-Range') or '').rsplit('/', 1)[-1]
    else:
        total = resp.headers.get('Content-Length') or ''
    return int(total) if total.isdigit() else None


def fetch(url, dest, size=None, retries=10):
    """Download url to dest, resuming .part files and checking the final size.

    Servers sometimes close the stream early without an error, so a download only
    counts as done once it matches the expected size (from the caller or the headers).
    """
    if dest.exists() and (size is None or dest.stat().st_size == size):
        return 'skip'
    dest.parent.mkdir(parents=True, exist_ok=True)
    tmp = dest.with_name(dest.name + '.part')
    if dest.exists():  # truncated by an earlier run: it is a valid prefix, so resume from it
        if tmp.exists():
            tmp.unlink()
        dest.replace(tmp)

    failures = 0
    while failures < retries:
        headers = {'User-Agent': USER_AGENT}
        offset = tmp.stat().st_size if tmp.exists() else 0
        if size is not None and offset == size:
            tmp.replace(dest)
            return 'ok'
        if offset:
            headers['Range'] = f'bytes={offset}-'
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=headers), timeout=120) as resp:
                resumed = offset and resp.status == 206
                total = _total_size(resp) or size
                with open(tmp, 'ab' if resumed else 'wb') as f:
                    while chunk := resp.read(1 << 20):
                        f.write(chunk)
            got = tmp.stat().st_size
            if total is None or got == total:
                tmp.replace(dest)
                return 'ok'
            if got > offset:  # made progress: resume right away without using up a retry
                failures = 0
                continue
            err = IOError(f'stream ended early at {got}/{total} bytes')
        except urllib.error.HTTPError as e:
            if e.code == 416:  # nothing left to fetch from this offset
                if size is None or offset == size:
                    tmp.replace(dest)
                    return 'ok'
                tmp.unlink()
            elif e.code != 429 and e.code < 500:
                raise
            err = e
        except (urllib.error.URLError, TimeoutError, ConnectionError, OSError) as e:
            err = e
        failures += 1
        wait = _wait_seconds(err, failures)
        log(f'  retry {failures}/{retries} in {wait}s ({type(err).__name__}: {err}) {dest.name}')
        time.sleep(wait)
    raise RuntimeError(f'Failed to download {url}')


def _remote_size(url):
    req = urllib.request.Request(url, headers={'User-Agent': USER_AGENT, 'Range': 'bytes=0-0'})
    with urllib.request.urlopen(req, timeout=60) as resp:
        return _total_size(resp)


def _fetch_range(url, seg, start, end, retries=30):
    """Fetch bytes start..end (inclusive) into seg, resuming whatever seg already holds."""
    want = end - start + 1
    failures = 0
    while failures < retries:
        have = seg.stat().st_size if seg.exists() else 0
        if have >= want:
            return
        headers = {'User-Agent': USER_AGENT, 'Range': f'bytes={start + have}-{end}'}
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=headers), timeout=60) as resp:
                if resp.status != 206:
                    raise RuntimeError(f'server ignored Range request (HTTP {resp.status})')
                with open(seg, 'ab') as f:
                    while chunk := resp.read(1 << 16):
                        f.write(chunk)
        except urllib.error.HTTPError as e:
            if e.code != 429 and e.code < 500:
                raise
        except (urllib.error.URLError, TimeoutError, ConnectionError, OSError):
            pass
        if (seg.stat().st_size if seg.exists() else 0) > have:
            failures = 0  # timeouts mid-stream still made progress
            continue
        failures += 1
        time.sleep(min(2 ** failures, 60))
    raise RuntimeError(f'Failed to download bytes {start}-{end} of {url}')


def fetch_segmented(url, dest, parts=8):
    """Like fetch(), but pulls the remaining bytes over several parallel Range requests.

    DataShare throttles each connection to a few tens of KB/s, so a single stream for the
    1 GB eval zip would take more than a day. Segments are named by byte range, so an
    interrupted run resumes them.
    """
    if dest.exists():
        return 'skip'
    tmp = dest.with_name(dest.name + '.part')
    total = _remote_size(url)
    if total is None:
        return fetch(url, dest)
    start = tmp.stat().st_size if tmp.exists() else 0

    if start < total:
        step = -(-(total - start) // parts)
        ranges = [(a, min(a + step, total) - 1) for a in range(start, total, step)]
        segs = [dest.with_name(f'{dest.name}.{a}-{b}.seg') for a, b in ranges]
        for stale in set(dest.parent.glob(f'{dest.name}.*.seg')) - set(segs):
            stale.unlink()
        with ThreadPoolExecutor(max_workers=len(ranges)) as pool:
            pending = {pool.submit(_fetch_range, url, seg, a, b) for seg, (a, b) in zip(segs, ranges)}
            while pending:
                done, pending = wait(pending, timeout=60)
                for fut in done:
                    fut.result()
                have = start + sum(s.stat().st_size for s in segs if s.exists())
                log(f'  {dest.name}: {have / 1e6:.0f}/{total / 1e6:.0f} MB')
        with open(tmp, 'ab') as out:
            for seg in segs:
                with open(seg, 'rb') as f:
                    shutil.copyfileobj(f, out)
                seg.unlink()

    if tmp.stat().st_size != total:
        raise RuntimeError(f'{dest.name}: got {tmp.stat().st_size} of {total} bytes')
    tmp.replace(dest)
    return 'ok'


def hf_repo_info(repo_id):
    url = f'https://huggingface.co/api/datasets/{repo_id}?blobs=true'
    with urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent': USER_AGENT}), timeout=120) as r:
        return json.load(r)


def hf_url(repo_id, sha, filename):
    return f'https://huggingface.co/datasets/{repo_id}/resolve/{sha}/{filename}'


def run_jobs(jobs, workers, label):
    """jobs: list of (url, dest, expected_size or None). Prints progress every ~5%."""
    done = failed = 0
    step = max(1, len(jobs) // 20)
    with ThreadPoolExecutor(max_workers=workers) as pool:
        futures = {pool.submit(fetch, url, dest, size): dest for url, dest, size in jobs}
        for fut in as_completed(futures):
            try:
                fut.result()
            except Exception as e:  # keep going; a re-run picks up what failed
                failed += 1
                log(f'  FAILED {futures[fut].name}: {e}')
            done += 1
            if done % step == 0 or done == len(jobs) or len(jobs) <= 20:
                log(f'  {label}: {done}/{len(jobs)} files')
    return failed


def download_asvspoof2017(out_dir):
    todo = {name: url for name, url in ASVSPOOF2017_FILES.items()
            if not (out_dir / f'.{name}.extracted').exists()}
    small = [(url, out_dir / name, None) for name, url in todo.items() if 'ASVspoof2017_V2_' not in name]
    failed = run_jobs(small, workers=3, label='asvspoof2017_v2')
    for name, url in todo.items():
        if 'ASVspoof2017_V2_' in name:  # the audio zips: DataShare is slow per connection
            try:
                fetch_segmented(url, out_dir / name)
            except Exception as e:
                failed += 1
                log(f'  FAILED {name}: {e}')

    for name in ASVSPOOF2017_FILES:
        archive, marker = out_dir / name, out_dir / f'.{name}.extracted'
        if not name.endswith('.zip') or marker.exists() or not archive.exists():
            continue
        log(f'  extracting {name}')
        with zipfile.ZipFile(archive) as zf:
            zf.extractall(out_dir)
        marker.touch()
        archive.unlink()
    return {'source': 'https://datashare.ed.ac.uk/handle/10283/3055', 'files': list(ASVSPOOF2017_FILES)}, failed


def download_hf_files(key, out_dir, pattern):
    repo = HF_REPOS[key]
    info = hf_repo_info(repo)
    sizes = {s['rfilename']: s.get('size') for s in info['siblings']}
    files = sorted(f for f in sizes if re.fullmatch(pattern, f))
    jobs = [(hf_url(repo, info['sha'], f), out_dir / f, sizes[f]) for f in files]
    failed = run_jobs(jobs, workers=3, label=key)
    return {'source': f'https://huggingface.co/datasets/{repo}', 'revision': info['sha'], 'files': files}, failed


def download_replaydf(out_dir, per_config, seed):
    """All configs' metadata + RIR, and a fixed random sample of replayed bona fide clips per config."""
    repo = HF_REPOS['replaydf']
    info = hf_repo_info(repo)
    sizes = {s['rfilename']: s.get('size') for s in info['siblings']}
    names = list(sizes)

    benign = defaultdict(list)
    for n in names:
        parts = n.split('/')
        if len(parts) >= 4 and parts[0] == 'wav' and parts[2] == 'benign' and n.endswith('.wav'):
            benign[parts[1]].append(n)

    wanted = ['README.md', 'mic_loudspeaker_matrix.png']
    for uid in sorted(benign):
        wanted += [f'aux/{uid}/info.txt', f'aux/{uid}/RIR.wav', f'wav/{uid}/meta.csv']
        clips = sorted(benign[uid])
        wanted += random.Random(f'{seed}-{uid}').sample(clips, min(per_config, len(clips)))
    wanted = [w for w in wanted if w in sizes]

    jobs = [(hf_url(repo, info['sha'], f), out_dir / f, sizes[f]) for f in wanted]
    # Many small files: per-request latency dominates, so use more workers (HF allows 3000 req / 5 min)
    failed = run_jobs(jobs, workers=16, label='replaydf')
    return {
        'source': f'https://huggingface.co/datasets/{repo}', 'revision': info['sha'],
        'configs': len(benign), 'benign_per_config': per_config, 'seed': seed, 'files': wanted,
    }, failed


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--only', nargs='+', choices=ALL_DATASETS, default=ALL_DATASETS)
    parser.add_argument('--replaydf-per-config', type=int, default=100,
                        help='replayed bona fide clips sampled from each ReplayDF loudspeaker/mic config')
    parser.add_argument('--seed', type=int, default=1234)
    args = parser.parse_args()

    RAW_DIR.mkdir(parents=True, exist_ok=True)
    manifest_path = RAW_DIR / 'download_manifest.json'
    total_failed = 0

    for key in args.only:
        out_dir = RAW_DIR / key
        log(f'== {key} -> {out_dir}')
        if key == 'asvspoof2017_v2':
            entry, failed = download_asvspoof2017(out_dir)
        elif key == 'echofake':
            entry, failed = download_hf_files(key, out_dir, r'(README\.md|data/.*\.parquet)')
        elif key == 'fleurs_id':
            entry, failed = download_hf_files(key, out_dir, r'(README\.md|parquet-data/id_id/.*\.parquet)')
        else:
            entry, failed = download_replaydf(out_dir, args.replaydf_per_config, args.seed)
        entry['downloaded_at'] = datetime.now(timezone.utc).isoformat(timespec='seconds')
        entry['failed'] = failed
        # Re-read so parallel runs with different --only values don't drop each other's entries
        manifest = json.loads(manifest_path.read_text()) if manifest_path.exists() else {}
        manifest[key] = entry
        manifest_path.write_text(json.dumps(manifest, indent=2))
        total_failed += failed

    log('selesai' if not total_failed else f'selesai dengan {total_failed} file gagal; jalankan ulang untuk melanjutkan')
    sys.exit(1 if total_failed else 0)


if __name__ == '__main__':
    main()
