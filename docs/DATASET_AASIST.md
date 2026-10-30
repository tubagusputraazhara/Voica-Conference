# Dataset Pelatihan AASIST (Deteksi Replay)

Dokumen ini mencatat dataset publik yang dipakai untuk melatih ulang (fine-tune) model anti-spoofing AASIST di Voica, beserta sumber, lisensi, dan cara menyiapkannya ulang.

**Masalah yang mau diselesaikan.** `AASIST.pth` bawaan dilatih dengan ASVspoof 2019 LA, yang isinya suara TTS dan voice conversion tanpa replay. Model itu belum pernah melihat:

1. suara asli yang direkam langsung lewat mic HP atau laptop di browser, yang harus **lolos**;
2. rekaman yang diputar ulang lewat speaker ke mic, yang harus **ditolak**.

Akibatnya pengguna asli ikut terblokir. Dataset di bawah dipilih supaya model belajar dua hal itu tanpa tim harus merekam suara sendiri.

> Folder `datasets/` tidak masuk git. Untuk menyiapkan data di komputer lain, jalankan dua skrip di bagian [Cara menyiapkan ulang](#cara-menyiapkan-ulang).

## Ringkasan

Total **111.738 klip (183,6 jam)**: ~12 GB hasil unduhan, dan ~10 GB setelah dikonversi ke FLAC 16 kHz mono.

| Kode | Dataset | Isi yang dipakai | Bahasa | Klip | Jam | Lisensi |
|---|---|---|---|---:|---:|---|
| A17 | ASVspoof 2017 V2 | suara asli dari smartphone + replay nyata | Inggris | 18.030 | 15,6 | CC BY-NC 4.0 |
| EF | EchoFake | suara asli (Common Voice) + replay HP/laptop + TTS + replay TTS | Inggris | 75.468 | 116,2 | MIT |
| RDF | ReplayDF (subset) | replay suara asli lewat 110 pasangan speaker–mikrofon | 6 bahasa Eropa | 11.000 | 26,5 | CC BY-NC 4.0 |
| FLR | FLEURS `id_id` | suara asli | **Indonesia** | 3.616 | 12,6 | CC BY 4.0 |
| SIM | FLEURS × RIR ReplayDF | replay hasil simulasi | **Indonesia** | 3.616 | 12,6 | non-komersial (turunan) |
| VOC | Rekaman tim | suara asli + replay (khusus ujian) | **Indonesia** | 8 | <0,1 | internal |

Pembagian datanya:

| Split | Asli (bonafide) | Spoof | Jam | Dipakai untuk |
|---|---:|---:|---:|---|
| `train` | 14.086 | 42.794 | 104,1 | melatih model |
| `dev` | 2.110 | 5.373 | 12,7 | memilih epoch terbaik dan threshold |
| `eval` | 9.885 | 37.482 | 66,8 | menguji model pada data yang belum pernah dilihat |
| `eval_voica` | 4 | 4 | <0,1 | ujian akhir dengan rekaman tim |

Selama training, setiap batch diambil seimbang: 50% asli dan 50% spoof, dan setiap kelompok sumber/serangan mendapat porsi yang sama. Dengan begitu EchoFake yang paling besar tidak mendominasi.

Label di protokol hanya dua, yaitu `bonafide` (suara asli) dan `spoof`. Kolom `attack` di `metadata.csv` menyimpan jenis spoof-nya:

| `attack` | Arti |
|---|---|
| `-` | suara asli (bonafide) |
| `replay` | suara asli yang diputar ulang lewat speaker lalu direkam lagi |
| `tts` | suara sintetis (text-to-speech / kloning suara) |
| `replay_tts` | suara sintetis yang diputar ulang lewat speaker |
| `sim_replay` | replay hasil simulasi (lihat bagian SIM) |

## Detail per dataset

### A17: ASVspoof 2017 Version 2.0

- **Isi:** replay attack nyata dari 179 sesi replay dengan 61 kombinasi ruangan, speaker, dan perangkat rekam (42 pembicara). Suara aslinya diambil dari korpus RedDots, yang direkam relawan memakai **smartphone Android**. Ini mirip skenario Voica: orang bicara ke HP, lalu penyerang memutar ulang rekamannya.
- **Yang dipakai:** semua bagian (train, dev, eval) beserta protokol labelnya.
- **Pembagian:** mengikuti pembagian resmi.
- **Lisensi:** CC BY-NC 4.0 (tertulis di `README_V2.txt`).
- **Sumber:** <https://datashare.ed.ac.uk/handle/10283/3055>, DOI [10.7488/ds/2332](https://doi.org/10.7488/ds/2332)
- **Sitasi:**
  - T. Kinnunen, M. Sahidullah, H. Delgado, M. Todisco, N. Evans, J. Yamagishi, K. A. Lee, "The ASVspoof 2017 Challenge: Assessing the Limits of Replay Spoofing Attack Detection", Interspeech 2017.
  - H. Delgado dkk., "ASVspoof 2017 Version 2.0: meta-data analysis and baseline enhancements", Odyssey 2018.

### EF: EchoFake

- **Isi:** dataset tahun 2025 yang dibuat khusus untuk replay di dunia nyata, sekitar 126 jam dari lebih dari 13.000 pembicara. Ada empat kelas:
  - `bonafide`: suara asli dari Common Voice 17.0, yang direkam relawan lewat browser atau HP masing-masing;
  - `replay_bonafide`: suara asli yang diputar ulang;
  - `fake`: hasil 11 sistem TTS zero-shot modern;
  - `replay_fake`: suara TTS yang diputar ulang.
- **Perangkat replay:**
  - pemutar: MacBook Pro 14", iPad Mini, speaker Edifier MR4, Xiaomi 13 Ultra;
  - perekam: iPhone 13 mini, Samsung Galaxy A54, earphone kabel bermikrofon;
  - ruangan: ruang rapat, kamar rumah, dan kantor, dengan jarak 15, 30, dan 50 cm.
- **Kenapa penting:** perangkat dan kondisinya paling dekat dengan pemakaian Voica (HP dan laptop). Di dataset ini replay suara asli memang dilabeli **spoof**, sama dengan kebutuhan kita.
- **Yang dipakai:** semua split (`train`, `dev`, `closed_set_eval`, `open_set_eval`). Kedua split eval digabung ke `eval`, dan asalnya tetap tercatat di kolom `subset`. Semua suara aslinya berbahasa Inggris (Common Voice `en`).
- **Dua masalah di data aslinya, dan cara menanganinya:**
  - Ada **22 klip TTS yang kosong** (±0,04 detik, hasil gagal model LLaSA). Klip ini dibuang.
  - Ada **1.986 `utt_id` yang dipakai dua kali**: di `train` untuk suara asli, dan di `dev` untuk suara TTS yang berbeda. Kalau ID-nya dipakai apa adanya, label suara asli akan menempel ke audio palsu. Karena itu kunci EchoFake diberi kode split, misalnya `EF_tr_T_00001` (train), `EF_dv_…` (dev), `EF_ce_…` (closed eval), `EF_oe_…` (open eval). ID aslinya tetap tercatat di kolom `original` pada `metadata.csv`.
- **Lisensi:** MIT (tertulis di kartu dataset Hugging Face). Suara aslinya berasal dari Common Voice yang berlisensi CC0.
- **Sumber:** <https://huggingface.co/datasets/EchoFake/EchoFake>, kode di <https://github.com/EchoFake/EchoFake>
- **Sitasi:** T. Zhang, Y. Huang, Y. Ren, "EchoFake: A Replay-Aware Dataset for Practical Speech Deepfake Detection", ICASSP 2026, arXiv:2510.19414.

### RDF: ReplayDF (subset)

- **Isi:** rekaman audiobook M-AILABS dan audio TTS MLAAD yang diputar lewat speaker lalu direkam ulang, dengan 6 bahasa Eropa. Makalahnya menyebut 109 pasangan speaker dan mikrofon, tetapi repositorinya berisi 110 folder konfigurasi.
- **Yang dipakai:**
  - **100 klip replay suara asli** per konfigurasi, dipilih acak dengan seed tetap, dari semua 110 konfigurasi (11.000 klip);
  - `RIR.wav` (respons impuls speaker, ruangan, dan mikrofon) dan `info.txt` (nama perangkat) dari setiap konfigurasi.

  Klip replay TTS tidak diambil, karena EchoFake sudah punya.
- **Kenapa penting:** variasi perangkatnya paling banyak. Dengan begitu model belajar ciri replay secara umum, bukan ciri satu perangkat saja.
- **Pembagian:** dibagi **per konfigurasi perangkat**: 88 konfigurasi untuk train, 11 untuk dev, dan 11 untuk eval. Perangkat di dev/eval tidak pernah muncul saat training, sehingga hasil evaluasi menunjukkan kemampuan model menghadapi perangkat yang belum dikenal.
- **Catatan:** beberapa `meta.csv` tersimpan di folder konfigurasi yang salah. Karena itu metadata dicocokkan lewat kolom `recorded_file`, bukan lewat nama folder.
- **Lisensi:** CC BY-NC 4.0 menurut metadata Hugging Face. Teks README-nya menyebut "Attribution-NonCommercial-ShareAlike", jadi anggap saja ada syarat ShareAlike juga.
- **Sumber:** <https://huggingface.co/datasets/mueller91/ReplayDF>, halaman proyek <https://deepfake-total.com/replay_df>
- **Sitasi:** N. Müller, P. Kawa, W.-H. Choong, A. Stan, A. T. Bukkapatnam, K. Pizzi, A. Wagner, P. Sperl, "Replay Attacks Against Audio Deepfake Detection", Interspeech 2025, arXiv:2505.14862.

### FLR: FLEURS bahasa Indonesia (`id_id`)

- **Isi:** kalimat Wikipedia yang dibacakan penutur asli bahasa Indonesia, 16 kHz. Ada 1 sampai 3 rekaman untuk setiap kalimat.
- **Kenapa penting:** sumber lain hampir semuanya berbahasa Inggris atau Eropa, sedangkan pengguna Voica berbicara bahasa Indonesia. FLEURS menambah contoh **suara asli berbahasa Indonesia** tanpa perlu akun.
- **Pembagian:** train, validation, dan test resmi menjadi train, dev, dan eval.
- **Lisensi:** CC BY 4.0.
- **Sumber:** <https://huggingface.co/datasets/google/fleurs>
- **Sitasi:** A. Conneau, M. Ma, S. Khanuja, Y. Zhang, V. Axelrod, S. Dalmia, J. Riesa, C. Rivera, A. Bapna, "FLEURS: Few-shot Learning Evaluation of Universal Representations of Speech", arXiv:2205.12446, 2022.

### SIM: Replay bahasa Indonesia hasil simulasi

- **Masalah yang dicegah:** kalau semua audio bahasa Indonesia berlabel asli, model bisa belajar jalan pintas "bahasa Indonesia = asli", padahal yang harus dipelajari adalah ciri replay. Model seperti itu akan meloloskan replay berbahasa Indonesia.
- **Cara membuat:**
  1. Setiap klip FLEURS dikonvolusi dengan `RIR.wav` hasil pengukuran sungguhan dari satu konfigurasi ReplayDF. RIR ini mewakili jalur speaker, ruangan, lalu mikrofon.
  2. Konfigurasi RIR diambil dari split yang sama: klip train memakai RIR dari konfigurasi train, dan seterusnya.
  3. Volume hasilnya disamakan dengan klip aslinya (RMS sama), supaya model tidak bisa membedakan dari keras-pelannya suara. Ini menghindari jalan pintas yang membuat AASIST bawaan salah.
- **Batasan:** simulasi ini linear, jadi distorsi speaker murah, noise latar, dan AGC HP tidak ikut tersimulasikan. SIM hanya pelengkap. Ciri replay yang sesungguhnya tetap dipelajari dari A17, EF, dan RDF.
- **Lisensi:** turunan FLEURS (CC BY 4.0) dan RIR ReplayDF (CC BY-NC 4.0), jadi berlaku non-komersial.

### VOC: Rekaman tim Voica (khusus evaluasi)

- **Isi:** rekaman `datasets/aasist_mentah/asli` dan `replay` (Hilmi, Ismi, Tubagus, Wildan). Rekaman ini **hanya ada di repo voica**. Di repo ini `eval_voica.txt` baru terbentuk kalau folder `datasets/aasist_mentah/` ikut disalin.
- **Pemakaian:** **hanya** untuk pengujian akhir (`protocols/eval_voica.txt`), tidak dipakai untuk training. Rekaman ini paling mirip kondisi nyata Voica, jadi menjadi ujian terakhir apakah model hasil latihan benar-benar bekerja.
- **Lisensi:** milik internal tim, jangan disebarkan.

## Dataset yang dipertimbangkan tetapi tidak dipakai

| Dataset | Alasan tidak dipakai | Tautan |
|---|---|---|
| Common Voice Indonesia | Sangat cocok karena direkam lewat browser dalam bahasa Indonesia. Namun sejak Oktober 2025 unduhannya wajib memakai akun Mozilla Data Collective, jadi tidak bisa diunduh otomatis. Layak ditambahkan manual kalau tim mau membuat akun. | <https://datacollective.mozillafoundation.org> |
| ASVspoof 2019 PA | Ukurannya 16,45 GB, dan replay-nya hasil simulasi akustik, bukan rekaman sungguhan. Data simulasi seperti ini terbukti kurang menggeneralisasi ke replay nyata (lihat makalah EchoFake). | <https://datashare.ed.ac.uk/handle/10283/3336> |
| ASVspoof 2021 PA | 45,4 GB, hanya berisi set evaluasi, dan labelnya diunduh terpisah. | <https://zenodo.org/records/4834716> |
| ReMASC | Replay nyata pada perangkat asisten suara, tetapi wajib login akun IEEE DataPort. | <https://ieee-dataport.org/open-access/remasc-realistic-replay-attack-corpus-voice-controlled-systems> |
| SEA-Spoof | Ada bahasa Indonesia, tetapi aksesnya harus disetujui manual (gated). Lisensinya CC BY-NC-ND, ukurannya sekitar 82 GB, dan tidak ada data replay (hanya TTS). | <https://huggingface.co/datasets/Jack-ppkdczgx/SEA-Spoof> |
| MLAAD | Hanya TTS, dan aksesnya gated (perlu login Hugging Face). | <https://huggingface.co/datasets/mueller91/MLAAD> |
| InaSpoof-v1 | Dataset spoofing bahasa Indonesia (ITB/JAIST), tetapi tidak ditemukan tautan unduhan publik. Bisa diminta langsung ke penulisnya. | <https://www.nowpublishers.com/article/Details/SIP-20240080> |
| VSDC | Replay multi-order, tetapi tidak ditemukan tautan unduhan publik. | <https://arxiv.org/abs/1909.00935> |

## Struktur folder

```
datasets/
├── aasist_mentah/                 rekaman tim (hanya di repo voica)
├── aasist_publik_mentah/          hasil unduhan, apa adanya
│   ├── asvspoof2017_v2/
│   ├── echofake/data/*.parquet
│   ├── fleurs_id/parquet-data/id_id/*.parquet
│   ├── replaydf/{wav,aux}/<id_konfigurasi>/...
│   └── download_manifest.json     URL, revisi, dan daftar file yang diunduh
└── aasist_publik_siap/            siap dipakai AASIST
    ├── flac/<KEY>.flac            16 kHz, mono, FLAC 16-bit
    ├── protocols/
    │   ├── train.txt  dev.txt  eval.txt
    │   ├── eval_A17.txt  eval_EF.txt  eval_RDF.txt  eval_FLR.txt  eval_SIM.txt
    │   └── eval_voica.txt         rekaman tim
    ├── metadata.csv               detail setiap file (sumber, perangkat, bahasa, durasi, lisensi)
    └── summary.json               jumlah file per split, sumber, dan jenis serangan
```

Format baris protokol mengikuti ASVspoof 2019, jadi langsung terbaca oleh `genSpoof_list()` di [scripts/aasist/data_utils.py](../scripts/aasist/data_utils.py):

```
SPEAKER  KEY  SUMBER  ATTACK  LABEL
M0002 A17_T_1000001 A17 - bonafide
c4f2ea6e76144b35 EF_tr_T_00002 EF - bonafide
- RDF_0129dbe27753_02f79cc84bcf RDF replay spoof
```

File audionya ada di `aasist_publik_siap/flac/<KEY>.flac`, sesuai yang dibaca `Dataset_ASVspoof2019_train`.

**Audio sengaja tidak dipotong heningnya dan tidak dinormalisasi.** Langkah itu (potong hening, normalisasi -26 dBFS, augmentasi gain acak) dilakukan saat training, lewat [scripts/antispoofing/aasist_preprocess.py](../scripts/antispoofing/aasist_preprocess.py). Modul yang sama juga dipakai saat inferensi, supaya kondisi latihan dan pemakaian sama.

## Cara menyiapkan ulang

```bash
# 1. Unduh (sekitar 12 GB; bisa dijalankan ulang untuk melanjutkan yang terputus)
python scripts/aasist_dataset/download_public_datasets.py

# 2. Konversi ke format AASIST (hasilnya sekitar 10 GB)
python scripts/aasist_dataset/prepare_public_datasets.py --workers 6
```

Butuh Python dengan `numpy scipy soundfile pyarrow`, serta `ffmpeg` (sudah ada di `ffmpeg/bin`).

Catatan unduhan: server Universitas Edinburgh (ASVspoof 2017) membatasi kecepatan per koneksi, jadi file zip besarnya diunduh dalam 8 potongan paralel. Kalau unduhan terputus, jalankan ulang skripnya. File yang sudah lengkap dilewati, dan file yang belum lengkap dilanjutkan dari posisi terakhir.

## Melatih dan memakai model

**Training** butuh GPU NVIDIA. Pakai venv terpisah supaya server AI Voica tidak terganggu:

```bat
python -m venv .venv-train
.venv-train\Scripts\python -m pip install torch torchaudio --index-url https://download.pytorch.org/whl/cu128
.venv-train\Scripts\python -m pip install numpy scipy soundfile tqdm
.venv-train\Scripts\python scripts\training\train_aasist.py
```

- Training dimulai dari `AASIST.pth`, lalu menyimpan epoch dengan EER dev terbaik ke `scripts/aasist/models/weights/AASIST_voica.pth` (~1,3 MB).
- Di akhir training, model lama dan model baru dibandingkan pada semua protokol `eval_*`. Laporannya ditulis ke `datasets/aasist_publik_siap/eval_report.json`.
- Di RTX 3050 6 GB, training memakai bf16 dengan batch 16. Batch yang lebih besar membuat memori GPU tumpah ke RAM biasa, dan prosesnya jadi sangat lambat.

**Memakai di voica_conference.** AASIST di repo ini dipakai bersama oleh ketiga model pembicara (ECAPA-TDNN, ResNet-34, WavLM) lewat [scripts/antispoofing/aasist_engine.py](../scripts/antispoofing/aasist_engine.py), jadi cukup satu file bobot untuk ketiganya. Default-nya sama dengan yang berjalan di hosting Voica, dan tidak perlu sakelar apa pun:

| | Default | Sakelar untuk mengubah |
|---|---|---|
| Model | `AASIST_voica.pth` (otomatis `AASIST.pth` kalau file itu belum ada) | `AASIST_WEIGHTS` |
| Diblokir jika skor asli di bawah | 8,75% | `AASIST_BLOCK_BELOW` (persen) |
| Zona "aman" mulai dari | 89% | `AASIST_SAFE_FROM` (persen) |

Di antara kedua batas itu ada zona abu-abu: AASIST meloloskan suara, tetapi threshold model pembicara dinaikkan +0,10.

**Untuk mereproduksi angka yang dihitung dengan AASIST bawaan**, cukup ganti modelnya. Zonanya sudah sama dengan saat paper dihitung:

```bat
:: cmd
set AASIST_WEIGHTS=AASIST.pth
scripts\start-fastapi.bat
```

```powershell
# PowerShell
$env:AASIST_WEIGHTS = "AASIST.pth"; .\scripts\start-fastapi.bat
```

Checkpoint `AASIST_voica.pth` juga menyimpan batas zona usulan dari data dev: blokir di bawah 62,79% (titik EER) dan aman mulai 96,26% (skor yang hanya dicapai ≤1% spoof dev). Batas ini lebih ketat dan tidak dipakai secara default. Untuk mencobanya, set `AASIST_BLOCK_BELOW=62.79` dan `AASIST_SAFE_FROM=96.26`.

Hasil `check_liveness()` sekarang juga mengembalikan `zone` (`safe` / `grey` / `blocked`), `zone_block_below`, `zone_safe_from`, dan `weights`. [voice_processor_ecapa.py](../scripts/voice_processor_ecapa.py) (ECAPA-TDNN) dan [verification_pipeline.py](../scripts/verification_pipeline.py) (ResNet-34, WavLM) memakai `zone` itu dan mencatat nama model AASIST di log verifikasi (kolom `aasist_model`).

Hasil evaluasi akhir (model epoch 5) dan pengaruh batas blokir ada di `datasets/aasist_publik_siap/eval_report.json` dan `eval.log` (di laptop tempat training dijalankan).

## Catatan lisensi

A17, RDF, dan SIM berlisensi **non-komersial** (CC BY-NC). Model AASIST yang dilatih dengan data ini boleh dipakai untuk riset atau tugas akhir. Model itu **tidak boleh** dipakai untuk produk berbayar tanpa izin pemilik dataset. Cantumkan sitasi di atas di laporan atau skripsi.
