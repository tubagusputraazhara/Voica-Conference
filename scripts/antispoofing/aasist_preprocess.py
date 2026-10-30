"""
Audio preprocessing shared by AASIST fine-tuning and inference.

The pretrained AASIST.pth keys on loudness and silence: silence scores as bona fide and
loud speech as spoof, so phone/browser AGC made real users look like attacks. The
fine-tuned model is trained on speech only, at one fixed level, and inference must
apply exactly the same steps.
"""

import numpy as np

SR = 16000
NB_SAMP = 64600  # ~4 s, the AASIST input length
TRIM_FLOOR_DB = -40.0
TARGET_DBFS = -26.0


def trim_silence(x, floor_db=TRIM_FLOOR_DB, frame=400, hop=160, margin_s=0.1):
    """Keep from the first to the last 25 ms frame within floor_db of the loudest frame."""
    if len(x) < frame:
        return x
    n = 1 + (len(x) - frame) // hop
    idx = np.arange(frame)[None, :] + hop * np.arange(n)[:, None]
    rms = np.sqrt(np.mean(x[idx] ** 2, axis=1) + 1e-12)
    voiced = np.flatnonzero(20 * np.log10(rms / rms.max()) > floor_db)
    if voiced.size == 0:
        return x
    margin = int(margin_s * SR)
    start = max(0, voiced[0] * hop - margin)
    end = min(len(x), voiced[-1] * hop + frame + margin)
    return x[start:end]


def normalize(x, target_dbfs=TARGET_DBFS):
    rms = float(np.sqrt(np.mean(x ** 2)))
    if rms < 1e-5:  # digital silence: nothing to scale
        return x
    return np.clip(x * (10 ** (target_dbfs / 20) / rms), -1.0, 1.0)


def fix_length(x, n=NB_SAMP, rng=None):
    """Tile short clips; crop long ones (random crop when rng is given, else from the start)."""
    if len(x) >= n:
        start = int(rng.integers(0, len(x) - n + 1)) if rng is not None else 0
        return x[start:start + n]
    return np.tile(x, int(np.ceil(n / len(x))))[:n]


def preprocess(x, rng=None):
    """16 kHz mono float waveform -> fixed-length AASIST input."""
    x = np.asarray(x, dtype=np.float32)
    return fix_length(normalize(trim_silence(x)), rng=rng).astype(np.float32)
