# Voica (Proter) 🎙️💸

Voica adalah aplikasi Asisten Keuangan Berbasis Suara yang memungkinkan pengguna untuk mencatat transaksi, menetapkan anggaran, dan mengawasi target keuangan — semuanya cukup dengan suara, menggunakan AI yang berjalan **100% Offline**.

## 🚀 Fitur Utama
- **🎙️ Voice Command Offline:** Terjemahkan ucapan Anda menjadi catatan pengeluaran/pemasukan tanpa perlu koneksi internet (Mendukung Bahasa Indonesia dan logat Sunda Priangan melalui adaptasi AI Faster Whisper).
- **🔒 Anti-Spoofing & Speaker Verification:** (*Dalam Pengembangan*) Mengamankan data pelaporan menggunakan identifikasi pola suara (ECAPA-TDNN) dan pendeteksi suara palsu (AASIST).
- **⚡ Single Page Application:** Antarmuka modern yang secepat kilat dibangun menggunakan React & Inertia.js.

---

## 🛠️ Tech Stack
Voica dibangun menggunakan struktur **Microservices** lokal untuk memastikan performa AI yang konstan tanpa membebani web server utama.

### 1. Frontend (UI & Tampilan)
- **React.js** (dikompilasi dengan Vite)
- **Tailwind CSS** (animasi halus & desain responsif)
- **Inertia.js** (Jembatan navigasi antara React dan Laravel tanpa API publik)

### 2. Backend (Logika Bisnis & Database)
- **Laravel (PHP 8+)** (Menyediakan arsitektur MVC, ORM, dan Session management)
- **SQLite** (Database default yang ringan)

### 3. AI Engine (Microservice)
- **Python (FastAPI)** (Server AI asinkron yang berjalan pada *port* 8000)
- **Faster Whisper** (Mesin transkripsi offline yang luar biasa cepat)
- **FFmpeg** (Sistem pengubah format audio pada Host OS)

---

## 📂 Struktur Folder Utama
Aplikasi baru saja di-refactor secara mendalam (*Clean Architecture*). Berikut adalah navigasinya:

- `/app/Services/` → Otak dari backend Laravel. Menangani logika parsing NLP dan transaksi keuangan.
- `/resources/js/api/` → Otak dari frontend React. Memusatkan pemanggilan asinkron (Axios) ke Laravel API maupun FastAPI.
- `/resources/js/Components/` → Potongan antarmuka (Sidebar, MobileNav, Feedback Layar) yang bisa dipakai ulang.
- `/scripts/` → Kumpulan AI engine Python dan skrip penggerak microservice `start-fastapi.bat`.

---

## 💻 Cara Menjalankan Aplikasi Secara Lokal

Karena Voica menggunakan AI, Anda memerlukan instalasi Python dan FFmpeg di laptop/server lokal Anda.

### 1. Persiapan Awal
Pastikan Anda telah meng-install:
- PHP 8.2+
- Node.js 18+ & NPM
- Python 3.10+
- FFmpeg (Sudah terdaftar di Path Environment Windows/Linux)

### 2. Install Dependencies Web
Buka terminal di root proyek, lalu:
```bash
composer install
npm install
npm run build
```

### 3. Install Dependencies AI (Satu Kali Saja)
```bash
cd scripts
python -m venv .venv
.venv\Scripts\activate
pip install -r ../requirements.txt
pip install faster-whisper
```
> *Catatan: Model AI (Faster Whisper tipe 'small') berukuran ratusan Megabyte akan otomatis diunduh saat pertama kali server AI berjalan.*

### 4. Nyalakan Service Secara Bersamaan
Anda butuh **DUA** Terminal menyala.

**Terminal 1 (Laravel Web Server):**
```bash
php artisan serve --port=8080
```

**Terminal 2 (Python AI Server):**
```bash
# Jalankan skrip ini (di Windows)
scripts\start-fastapi.bat
```

> **Akses Aplikasi:** Bukalah browser dan kunjungi `http://127.0.0.1:8080`

