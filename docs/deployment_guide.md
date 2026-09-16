# Panduan Deployment cPanel (Laravel + Python)

Dokumen ini menjelaskan langkah-langkah untuk mendeploy aplikasi "Proter" ke cPanel.

## 1. Persiapan Repository (GitHub)

Sebelum melakukan push, jalankan perintah ini di lokal untuk membersihkan file GSD dari index Git (file tetap aman di komputer Anda):

```bash
# Menghapus file GSD dari index agar tidak masuk GitHub
git rm -r --cached .planning/

# Aktivasi Git LFS (Hanya perlu sekali)
git lfs install
git lfs track "*.ckpt"
git lfs track "*.pth"
git add .gitattributes

# Tambahkan model AI dan push
git add pretrained_models/
git commit -m "chore: use Git LFS for AI models and exclude GSD"
git push origin feature/deployment-lfs
```

## 2. Pull Request (PR)

Karena branch `main` diproteksi:

1. Masuk ke GitHub.
2. Buat Pull Request dari branch `feature/deployment-lfs` ke `main`.
3. Merge PR tersebut ke `main`.

## 3. Struktur Folder di cPanel

Disarankan untuk meletakkan file Laravel di luar folder `public_html` demi keamanan:

```text
/home/username/
├── proter_app/         <-- (Isi folder project diletakkan di sini)
│   ├── app/
│   ├── scripts/
│   ├── pretrained_models/
│   └── ...
└── public_html/        <-- (Hanya isi dari folder 'public' project)
    ├── index.php
    └── assets/
```

## 4. Setup Python di cPanel

1. Masuk ke cPanel > **Setup Python App**.
2. Klik **Create Application**.
3. Pilih **Python Version** (disarankan 3.10+).
4. **Application root**: `proter_app` (atau folder tempat script berada).
5. **Application URL**: (Bisa diisi domain/subdomain).
6. Setelah aplikasi dibuat, masuk ke terminal cPanel dan jalankan perintah yang muncul di panel Python App (biasanya `source .../bin/activate`).
7. Update pip: `pip install --upgrade pip`
8. Install dependencies (PENTING):
   Karena cPanel biasanya tidak punya GPU (CUDA), Anda **wajib** menginstall PyTorch versi CPU agar ukurannya tidak membengkak (bisa hemat 2GB+):

    ```bash
    # Install Torch versi CPU dulu
    pip install torch torchaudio --index-url https://download.pytorch.org/whl/cpu

    # Baru install sisanya dari requirements.txt
    pip install -r requirements.txt
    ```

## 5. Build Assets (NPM)

Folder `node_modules` **tidak akan terbawa** ke GitHub (sesuai standar). Untuk aset (JS/CSS):

1. **Opsi A (Build di Server)**: Jika cPanel Anda punya akses SSH dan Node.js, jalankan:
    ```bash
    npm install
    npm run build
    ```
2. **Opsi B (Upload Manual)**: Jika tidak ada Node.js di server, jalankan `npm run build` di laptop Anda, lalu upload isi folder `public/build` ke cPanel secara manual via File Manager/FTP.

## 6. Konfigurasi .env (Laravel)

Pastikan path script Python di `.env` sudah benar. Contoh untuk Python 3.11:

```env
PYTHON_EXEC=/home/username/virtualenv/proter_app/3.11/bin/python
```

_(Ingat: Gunakan path 'source' yang ada di cPanel Setup Python App, tapi ganti 'activate' di ujungnya menjadi 'python')_

## 7. Solusi FFmpeg di cPanel (PENTING)

Aplikasi ini butuh **FFmpeg** untuk mengonversi rekaman suara dari browser. Jika cPanel Anda tidak punya FFmpeg bawaan (`ffmpeg: command not found`), ikuti cara ini:

1. Buka terminal cPanel, masuk ke folder project (`proter_app`).
2. Download file FFmpeg statis:
    ```bash
    wget https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz
    tar -xf ffmpeg-release-amd64-static.tar.xz
    ```
3. Pindahkan file `ffmpeg` ke folder project agar mudah:
    ```bash
    mv ffmpeg-*-amd64-static/ffmpeg ./ffmpeg_bin
    chmod +x ffmpeg_bin
    ```
4. Tambahkan/Ubah configurasi di file `.env` Laravel Anda:
    ```env
    FFMPEG_PATH=/home/username/proter_app/ffmpeg_bin
    ```
    _(Sesuaikan `/home/username/proter_app` dengan path asli Anda di File Manager)_

## 8. Symlink Public

Jangan lupa menghubungkan folder `public` ke `public_html`:

```bash
ln -s /home/username/proter_app/public /home/username/public_html
```

## 9. Tips: Git LFS di cPanel (PENTING)

Karena model AI (yang berukuran besar) sekarang menggunakan **Git LFS**, jika di cPanel Anda muncul error seperti:
`Error: Anti-spoofing check failed: invalid load key, 'v'.`
Itu artinya cPanel hanya mendownload "teks link" nya saja (pointer file LFS), bukan file model aslinya karena `git-lfs` tidak terinstall di cPanel Anda.

**Cara Memperbaikinya:**
Anda wajib **meng-upload manual** file model utuh dari laptop Anda (tempat LFS berfungsi) ke cPanel via File Manager. Folder yang wajib ditimpa secara manual:

1. `pretrained_models/` (Timpa seluruh isinya)
2. `scripts/aasist/models/weights/` (Penting untuk file `AASIST.pth`)

_(Saran: Zip kedua folder ini di laptop, upload zip ke cPanel, lalu ekstrak dan timpa folder aslinya)._

_(Atau arahkan Document Root subdomain ke folder public)_
