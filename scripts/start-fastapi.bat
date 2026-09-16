@echo off
:: Add local FFmpeg to PATH
set PATH=%~dp0..\ffmpeg\bin;%PATH%

echo ============================================================
echo  Voica AI Engine — Starting...
echo ============================================================
echo.
echo  Server: http://127.0.0.1:8001
echo  Health: http://127.0.0.1:8001/health
echo  Docs:   http://127.0.0.1:8001/docs
echo.
echo  Models yang akan dimuat:
echo   - ECAPA-TDNN  — speaker verification
echo   - AASIST      — anti-spoofing
echo.
echo  Endpoints:
echo   POST /enroll
echo   POST /verify
echo   POST /verify-secure
echo   POST /verify-with-challenge
echo   POST /transcribe          (via file path)
echo   POST /transcribe-upload   (via audio blob dari browser)
echo.
echo  Biarkan window ini tetap terbuka selama menggunakan Voica.
echo  Tekan Ctrl+C untuk menghentikan server.
echo ============================================================
echo.

cd /d "%~dp0.."

if exist "scripts\.venv\Scripts\activate.bat" (
    call scripts\.venv\Scripts\activate.bat
) else if exist ".venv\Scripts\activate.bat" (
    call .venv\Scripts\activate.bat
)

:: Check if uvicorn is installed
python -c "import uvicorn" 2>NUL
if errorlevel 1 (
    echo [ERROR] uvicorn tidak ditemukan. Menginstall dependencies...
    pip install fastapi "uvicorn[standard]" python-multipart
    echo.
)

:: Start api_server.py (FastAPI yang sudah ada)
python -m uvicorn scripts.api_server:app --host 127.0.0.1 --port 8001 --reload

pause
