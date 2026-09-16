# setup.ps1 - Environment Setup for proter
Write-Host "Setting up Python environment..." -ForegroundColor Cyan

if (-not (Test-Path ".venv")) {
    Write-Host "Creating virtual environment..."
    python -m venv .venv
}

Write-Host "Activating virtual environment..."
& .\.venv\Scripts\Activate.ps1

Write-Host "Upgrading pip, setuptools, and wheel..."
python.exe -m pip install --upgrade pip
pip install --upgrade setuptools wheel

Write-Host "Installing dependencies from requirements.txt..."
pip install -r ..\requirements.txt

Write-Host "Setup complete!" -ForegroundColor Green
