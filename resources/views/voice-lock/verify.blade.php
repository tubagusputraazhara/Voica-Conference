<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi Suara - Voice-Lock</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #E3F5FF;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0px 20px 50px rgba(0, 0, 0, 0.15);
            padding: 40px;
            text-align: center;
        }

        .lock-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #00456A;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            color: #856404;
            font-size: 14px;
        }

        .error-box {
            background: #fff2f2;
            border: 1px solid #F53003;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .error-title {
            color: #F53003;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .error-list {
            margin: 0;
            padding-left: 20px;
            color: #F53003;
            font-size: 14px;
        }

        .voice-recorder {
            background: #f8f9fa;
            border: 2px dashed #00456A;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 25px;
            transition: all 0.3s;
        }

        .voice-recorder.recording {
            background: #ffe8e8;
            border-color: #F53003;
            animation: pulse 1.5s ease-in-out infinite;
        }

        .voice-recorder.recorded {
            background: #e8f5e9;
            border-color: #10b981;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        .voice-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .voice-status {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
        }

        .voice-timer {
            font-size: 24px;
            font-weight: 700;
            color: #00456A;
            margin-bottom: 15px;
        }

        .voice-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .voice-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .voice-btn-primary {
            background: #00456A;
            color: white;
        }

        .voice-btn-primary:hover {
            background: #003d5c;
        }

        .voice-btn-danger {
            background: #F53003;
            color: white;
        }

        .voice-btn-danger:hover {
            background: #d42800;
        }

        .voice-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .voice-btn-secondary:hover {
            background: #5a6268;
        }

        .voice-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .submit-btn {
            width: 100%;
            height: 52px;
            background: #10b981;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            background: #059669;
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .cancel-link {
            display: block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .cancel-link:hover {
            color: #00456A;
        }

        .security-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0f9ff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            color: #00456A;
        }

        /* Challenge Box Styles */
        .challenge-box {
            background: linear-gradient(135deg, #00456A 0%, #006d9e 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
            color: white;
        }

        .challenge-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .challenge-text {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            margin-bottom: 15px;
            font-family: 'Courier New', monospace;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .challenge-timer {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 10px;
        }

        .challenge-timer span {
            font-weight: 600;
            color: #ffeb3b;
        }

        .challenge-timer.expired span {
            color: #ff5252;
        }

        .refresh-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .refresh-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Security Layers */
        .security-layers {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .layer-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="lock-icon">🔐</div>
            <h1 class="title">Verifikasi Suara 3-Layer</h1>
            <p class="subtitle">
                Untuk keamanan, ucapkan kode di bawah ini dengan jelas.<br>
                Sistem akan memverifikasi suara dan kata yang Anda ucapkan.
            </p>

            <!-- Challenge Display Box -->
            <div class="challenge-box" id="challenge-box">
                <div class="challenge-label">📢 Ucapkan dengan jelas:</div>
                <div class="challenge-text" id="challenge-text">{{ $challenge['text'] ?? 'Loading...' }}</div>
                <div class="challenge-timer">
                    Berlaku: <span id="challenge-countdown">2:00</span>
                </div>
                <button type="button" class="refresh-btn" onclick="VoiceLock.refreshChallenge()">
                    🔄 Challenge Baru
                </button>
            </div>

            @if(session('warning'))
            <div class="warning-box">
                ⚠️ {{ session('warning') }}
            </div>
            @endif

            @if($errors->any())
            <div class="error-box">
                <div class="error-title">Verifikasi Gagal</div>
                <ul class="error-list">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('voice-lock.process') }}" enctype="multipart/form-data" id="voice-lock-form">
                @csrf

                <div class="voice-recorder" id="voice-recorder">
                    <div class="voice-icon">🎤</div>
                    <div class="voice-status" id="voice-status">Klik tombol untuk merekam kode di atas</div>
                    <div class="voice-timer" id="voice-timer" style="display: none;">00:00</div>
                    <div class="voice-controls">
                        <button type="button" class="voice-btn voice-btn-primary" id="start-btn" onclick="VoiceLock.startRecording()">🎤 Mulai Rekam</button>
                        <button type="button" class="voice-btn voice-btn-danger" id="stop-btn" onclick="VoiceLock.stopRecording()" style="display: none;">⏹ Berhenti</button>
                        <button type="button" class="voice-btn voice-btn-secondary" id="play-btn" onclick="VoiceLock.playRecording()" style="display: none;">▶ Putar</button>
                        <button type="button" class="voice-btn voice-btn-secondary" id="reset-btn" onclick="VoiceLock.resetRecording()" style="display: none;">🔄 Reset</button>
                    </div>
                </div>

                <input type="file" name="voice_audio" id="voice-input" style="display: none;" accept="audio/*">

                <button type="submit" class="submit-btn" id="submit-btn" disabled>
                    🔓 Verifikasi & Lanjutkan
                </button>
            </form>

            <a href="{{ route('dashboard') }}" class="cancel-link">← Kembali ke Dashboard</a>

            <div class="security-info">
                <div class="security-badge">
                    🛡️ Dilindungi oleh Voice-Lock 3-Layer Security
                </div>
                <div class="security-layers">
                    <span class="layer-badge">1️⃣ Text Challenge</span>
                    <span class="layer-badge">2️⃣ Anti-Spoofing</span>
                    <span class="layer-badge">3️⃣ Voice Match</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const VoiceLock = {
            mediaRecorder: null,
            audioChunks: [],
            audioBlob: null,
            audioUrl: null,
            recordingTimer: 0,
            recordingInterval: null,

            async startRecording() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: true
                    });
                    this.mediaRecorder = new MediaRecorder(stream, {
                        mimeType: 'audio/webm'
                    });
                    this.audioChunks = [];

                    this.mediaRecorder.ondataavailable = (e) => {
                        this.audioChunks.push(e.data);
                    };

                    this.mediaRecorder.onstop = () => {
                        this.audioBlob = new Blob(this.audioChunks, {
                            type: 'audio/webm'
                        });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);
                        this.updateUI('recorded');
                        this.createFileInput();
                    };

                    this.mediaRecorder.start();
                    this.updateUI('recording');
                    this.startTimer();
                } catch (error) {
                    alert('Tidak dapat mengakses mikrofon: ' + error.message);
                }
            },

            stopRecording() {
                if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
                    this.mediaRecorder.stop();
                    this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                    this.stopTimer();
                }
            },

            playRecording() {
                if (this.audioUrl) {
                    const audio = new Audio(this.audioUrl);
                    audio.play();
                }
            },

            resetRecording() {
                this.audioBlob = null;
                this.audioUrl = null;
                this.recordingTimer = 0;
                this.updateUI('idle');
                document.getElementById('voice-timer').textContent = '00:00';
                document.getElementById('voice-input').value = '';
                document.getElementById('submit-btn').disabled = true;
            },

            createFileInput() {
                const file = new File([this.audioBlob], 'voice_lock.webm', {
                    type: 'audio/webm'
                });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('voice-input').files = dataTransfer.files;
                document.getElementById('submit-btn').disabled = false;
            },

            updateUI(state) {
                const recorder = document.getElementById('voice-recorder');
                const status = document.getElementById('voice-status');
                const timer = document.getElementById('voice-timer');
                const startBtn = document.getElementById('start-btn');
                const stopBtn = document.getElementById('stop-btn');
                const playBtn = document.getElementById('play-btn');
                const resetBtn = document.getElementById('reset-btn');

                recorder.className = 'voice-recorder';

                if (state === 'recording') {
                    recorder.classList.add('recording');
                    status.textContent = '🔴 Merekam... Ucapkan sesuatu';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'inline-block';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                } else if (state === 'recorded') {
                    recorder.classList.add('recorded');
                    status.textContent = '✅ Rekaman selesai! Klik Verifikasi untuk melanjutkan.';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'inline-block';
                    resetBtn.style.display = 'inline-block';
                } else {
                    status.textContent = 'Klik tombol di bawah untuk merekam suara Anda';
                    timer.style.display = 'none';
                    startBtn.style.display = 'inline-block';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                }
            },

            startTimer() {
                this.recordingInterval = setInterval(() => {
                    this.recordingTimer++;
                    const mins = Math.floor(this.recordingTimer / 60).toString().padStart(2, '0');
                    const secs = (this.recordingTimer % 60).toString().padStart(2, '0');
                    document.getElementById('voice-timer').textContent = `${mins}:${secs}`;
                }, 1000);
            },

            stopTimer() {
                clearInterval(this.recordingInterval);
            },

            // Challenge countdown timer
            challengeSeconds: 120,
            challengeInterval: null,

            initChallengeCountdown() {
                this.challengeSeconds = 120;
                this.updateChallengeDisplay();

                if (this.challengeInterval) {
                    clearInterval(this.challengeInterval);
                }

                this.challengeInterval = setInterval(() => {
                    this.challengeSeconds--;
                    this.updateChallengeDisplay();

                    if (this.challengeSeconds <= 0) {
                        clearInterval(this.challengeInterval);
                        this.handleChallengeExpired();
                    }
                }, 1000);
            },

            updateChallengeDisplay() {
                const mins = Math.floor(this.challengeSeconds / 60);
                const secs = this.challengeSeconds % 60;
                const display = `${mins}:${secs.toString().padStart(2, '0')}`;
                document.getElementById('challenge-countdown').textContent = display;

                const timerDiv = document.querySelector('.challenge-timer');
                if (this.challengeSeconds <= 30) {
                    timerDiv.classList.add('expired');
                } else {
                    timerDiv.classList.remove('expired');
                }
            },

            handleChallengeExpired() {
                document.getElementById('challenge-text').textContent = 'EXPIRED';
                document.getElementById('start-btn').disabled = true;
                document.getElementById('submit-btn').disabled = true;
                alert('⏱️ Challenge expired! Silakan klik "Challenge Baru" untuk mendapatkan kode baru.');
            },

            async refreshChallenge() {
                try {
                    const response = await fetch('/voice-lock/challenge', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        document.getElementById('challenge-text').textContent = data.challenge;
                        document.getElementById('start-btn').disabled = false;
                        this.initChallengeCountdown();
                        this.resetRecording();
                    } else {
                        alert('Gagal mendapatkan challenge baru: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        };

        // Initialize challenge countdown on page load
        document.addEventListener('DOMContentLoaded', function() {
            VoiceLock.initChallengeCountdown();
        });
    </script>
</body>

</html>