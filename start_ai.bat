@echo off
TITLE Voica AI Microservice

:: Add local FFmpeg to PATH
set PATH=%~dp0ffmpeg\bin;%PATH%

echo =======================================================
echo          VOICA AI FASTAPI MICROSERVICE
echo =======================================================
echo.
echo Mengaktifkan Python Virtual Environment...

if exist "scripts\.venv\Scripts\activate.bat" (
    call scripts\.venv\Scripts\activate.bat
) else if exist ".venv\Scripts\activate.bat" (
    call .venv\Scripts\activate.bat
) else (
    echo [WARNING] Folder .venv tidak ditemukan di scripts\.venv maupun .venv. Pastikan Python environment Anda sudah diaktifkan secara manual.
)

echo.
echo Menginstall / memastikan FastAPI + Uvicorn tersedia...
python -m pip install --upgrade pip setuptools wheel -q
pip install -r requirements.txt

echo.
echo Menjalankan AI Server di port 8001...
echo Jangan tutup terminal ini selama aplikasi berjalan!
echo.

python -m uvicorn scripts.api_server:app --host 127.0.0.1 --port 8001 --reload

pause
