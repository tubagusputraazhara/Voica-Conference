# Voica - Voice Integrated Financial Application

Selamat datang di repositori Voica. Aplikasi ini merupakan sistem pencatatan keuangan yang terintegrasi dengan teknologi Voice Recognition (Pengenalan Suara) dan perlindungan Anti-Spoofing.

Repositori ini disiapkan khusus sebagai *Supplementary Material* untuk keperluan **Review Paper Conference**.

---

## 🛠️ Persyaratan Sistem (Prerequisites)
Sebelum menjalankan aplikasi, pastikan sistem Anda memiliki:
- **PHP** (Minimal versi 8.1) & **Composer**
- **Node.js** (Minimal versi 16.x) & **NPM**
- **XAMPP / MySQL** (Untuk database)
- **Python 3.10+** (Hanya jika ingin menjalankan server AI/Anti-Spoofing lokal)

---

## 🚀 Panduan Instalasi (Untuk Reviewer)

Ikuti langkah-langkah di bawah ini secara berurutan untuk menjalankan aplikasi di lingkungan lokal Anda.

### 1. Kloning Repositori
Buka terminal Anda dan jalankan perintah berikut:
`ash
git clone https://github.com/tubagusputraazhara/Voica-Conference.git
cd Voica-Conference
`

### 2. Instalasi Dependensi (Backend & Frontend)
Instal seluruh paket yang dibutuhkan oleh Laravel (PHP) dan React (Node.js):
`ash
composer install
npm install
`

### 3. Pengaturan Konfigurasi Lingkungan (.env)
Buat salinan file pengaturan lingkungan dari file contoh yang disediakan:
`ash
cp .env.example .env
`
Lalu, sesuaikan isi file .env pada bagian database:
`env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=voica_demo
DB_USERNAME=root
DB_PASSWORD=
`

### 4. Menyiapkan Database
Generate application key dan jalankan migrasi beserta data contoh (dummy data):
`ash
php artisan key:generate
php artisan migrate:fresh --seed
`
*(Catatan: Buat dulu database kosong di phpMyAdmin sesuai nama di file .env Anda).*

### 5. Menjalankan Server
Buka **dua terminal terpisah**.

**Terminal 1 (Untuk Frontend Vite):**
`ash
npm run dev
`

**Terminal 2 (Untuk Backend Laravel):**
`ash
php artisan serve
`

---

## 🤖 Menjalankan Server Suara (AI / Fast API) - *Opsional*

Jika ingin menguji Voice Recognition dan Anti-Spoofing:
`ash
pip install -r requirements.txt
python scripts/api_server.py
`

*Terima kasih telah meninjau proyek ini.*
