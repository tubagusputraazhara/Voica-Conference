# Developer Guide: Voica (Proter) System 🧑‍💻⚙️

Dokumen ini disusun untuk Software Engineer yang akan memelihara atau melanjutkan pengembangan Voica. Aplikasi ini baru saja mengalami **Grand Refactoring** yang beralih dari pemanggilan skrip via CLI ke arsitektur **Python FastAPI Microservices** secara penuh.

## 🏗️ Architecture Overview (Refactored)

Sistem saat ini terbagi menjadi 3 Lapisan independen yang terkoneksi melalui antarmuka HTTP/AJAX.

### 1. Frontend Layer (React + Inertia JS)
Di-hosting di dalam ekosistem Laravel melalui Vite, tetapi ditulis 100% secara modular.
*   **Logic Abstraction:** Semua panggilan *network* menggunakan library asinkron (Axios) wajib diletakkan di `resources/js/api/` (misal: `voiceApi.js` atau `apiClient.js`). Hook dan komponen dilarang melakukan pemanggilan Axios secara mentah.
*   **Component Structure:** 
    *   `resources/js/Pages/` (Hanya berisi struktur halaman).
    *   `resources/js/Components/Layout/` (Pecahan modular *Sidebar*, *MobileNav*).
    *   `resources/js/Components/Voice/` (Elemen animasi UI khusus mikrofon).

### 2. Backend Layer (Laravel PHP)
Bertindak sebagai "Controller" dan gerbang akses ke Database SQLite.
*   **Thin Controllers:** File di dalam `app/Http/Controllers` (seperti `VoiceTransactionController.php`) dijaga agar seminimal mungkin. Controller hanya bertugas menangani Request dan memberikan tanggapan HTTP (Response/Redirect).
*   **Service Pattern:** Seluruh logika bisnis (*business logic*) dan kalkulasi algoritma (termasuk resolusi ke Database ID) dialihkan ke **Service Layer** di folder `app/Services/` (contoh: `VoiceTransactionService.php`).

### 3. AI Engine Layer (FastAPI Python)
Berjalan sebagai *local server* independen di *Port* `8000`. Laravel berkomunikasi dengan engine ini secara HTTP (Cepat & Stabil tanpa *cold-boot*).
*   **Offline Transcribe:** Menggunakan **Faster Whisper** model "small" untuk mentranskripsi suara (Sunda/Indonesia) ke teks via endpoint `/transcribe-upload`.
*   **Verification:** Modul *ECAPA-TDNN* tetap berdiri di sini yang dapat dipanggil saat registrasi suara atau verifikasi.

---

## 🛠️ Developer Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer & NPM (Vite)
- Python 3.10 - 3.13
- FFmpeg (Terinstall dan terdaftar dalam **System PATH**)

### 1. PHP & JS Environment
```bash
# Terminal 1 - Background Build
composer install
npm install
npm run dev
```

### 2. Microservice Environment (FastAPI)
Buka terminal baru di root folder:
```powershell
# Buka folder script dan seting Python Virtual Environment
cd scripts
python -m venv .venv

# Aktivasi Venv
# Windows:
.venv\Scripts\activate
# Linux/Mac:
source .venv/bin/activate

# Install Semua Dependency
pip install -r ../requirements.txt
pip install faster-whisper
```

### 3. Konektivitas Service 
Setiap kali mendevelop sistem Voice, Anda **WAJIB** menyalakan API Python secara manual via skrip:
```powershell
# Windows
scripts\start-fastapi.bat

# Linux/Mac
cd scripts
../scripts/.venv/bin/uvicorn api_server:app --port 8000
```
> Biarkan layar terminal tersebut terbuka di latar belakang. Semua request STT dari Laravel akan diarahkan ke `127.0.0.1:8000/transcribe-upload`.

---

## 🐞 Error Handling / Gotchas

1. **"FFmpeg conversion failed / proc stderr"**
   - **Sebab:** Browser mengirim audio berektensi WebM/OGG. Whisper butuh format `.wav`. FastAPI mengeksekusi subprocess `ffmpeg` mengubah WebM -> Wav. 
   - **Solusi:** FFmpeg tidak terbaca di Environment Variable/PATH PC Anda. Tambahkan `C:\ffmpeg\bin` ke Path System.
2. **Memory Leak di Faster Whisper**
   - **Sebab:** Menggunakan tipe float32/fp16.
   - **Solusi:** `api_server.py` secara sengaja di-*lock* untuk menggunakan `compute_type="int8"` untuk menghemat RAM dev environment Anda (di bawah 1 GB RAM AI load). Jangan diubah ke Float32 bila PC dev Anda memorinya terbatas.
3. **Linter Error "Undefined method 'id'." di Controllers**
   - **Sebab:** Anda mungkin meniru gaya *coding* lama `auth()->id()`.
   - **Solusi:** Gunakan facade Laravel modern `Illuminate\Support\Facades\Auth;` lalu panggil `Auth::id()`.
