<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mode === 'register' ? 'Daftar' : 'Login' }} - Proyek Terapan</title>
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

        .auth-container {
            width: 100%;
            max-width: 1440px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 40px 20px;
        }

        .logo {
            width: 100px;
            height: 94px;
            margin-bottom: 16px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .title {
            width: 100%;
            max-width: 681px;
            text-align: center;
            color: black;
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 40px;
            padding: 0 20px;
            line-height: 1.4;
        }

        .form-wrapper {
            position: relative;
            width: 100%;
            max-width: 761px;
        }

        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0px 20px 50px rgba(0, 0, 0, 0.15);
            padding: 60px 50px 50px;
            width: 100%;
            max-width: 545px;
            margin: 0 auto;
            position: relative;
        }

        .tab-container {
            width: 100%;
            height: 59px;
            background: #E5E5E5;
            border-radius: 10px;
            display: flex;
            margin-bottom: 35px;
            position: relative;
            padding: 8px;
        }

        .tab-slider {
            position: absolute;
            height: 44px;
            background: white;
            border-radius: 5px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            left: 8px;
            top: 8px;
            z-index: 1;
            width: calc(50% - 12px);
        }

        .tab-slider.register-mode {
            left: calc(50% + 4px);
        }

        .tab-button {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: black;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            font-family: 'Inter', sans-serif;
            padding: 0;
        }

        .tab-button:hover {
            color: #00456A;
        }

        .tab-button.active {
            color: black;
        }

        .form-content {
            position: relative;
            overflow: hidden;
        }

        .form-panel {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .form-panel.hidden {
            display: none;
        }

        .input-group {
            margin-bottom: 25px;
        }

        .input-label {
            display: block;
            color: black;
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .input-wrapper {
            position: relative;
            width: 100%;
            height: 42px;
            background: #E5E5E5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            padding: 0 16px;
            transition: background 0.2s;
        }

        .input-wrapper:focus-within {
            background: #f0f0f0;
        }

        .input-field {
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            outline: none;
            color: rgba(0, 0, 0, 0.7);
            font-size: 16px;
            font-weight: 400;
            font-family: 'Inter', sans-serif;
            flex: 1;
            padding-right: 8px;
        }

        .input-field::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }

        .eye-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            margin-left: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
            transition: opacity 0.2s;
            flex-shrink: 0;
        }

        .eye-toggle:hover {
            opacity: 1;
        }

        .voice-recorder {
            background: #f8f9fa;
            border: 2px dashed #00456A;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
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
            margin-bottom: 10px;
        }

        .voice-status {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
        }

        .voice-timer {
            font-size: 20px;
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
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
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

        .remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            width: 100%;
            flex-wrap: wrap;
            gap: 10px;
        }

        .remember-wrapper {
            display: flex;
            align-items: center;
        }

        .remember-checkbox {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            accent-color: rgba(0, 0, 0, 0.7);
            cursor: pointer;
        }

        .remember-label {
            color: rgba(0, 0, 0, 0.78);
            font-size: 16px;
            font-weight: 400;
            cursor: pointer;
            user-select: none;
        }

        .forgot-link {
            color: #00456A;
            font-size: 16px;
            font-weight: 400;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #003d5c;
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            height: 52px;
            background: #00456A;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 24px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            box-shadow: 0px 4px 8px rgba(0, 69, 106, 0.2);
        }

        .submit-btn:hover {
            background: #003d5c;
            box-shadow: 0px 6px 12px rgba(0, 69, 106, 0.3);
            transform: translateY(-1px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .voice-login-btn {
            width: 100%;
            height: 52px;
            background: #5B9E9D;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            box-shadow: 0px 4px 8px rgba(91, 158, 157, 0.2);
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .voice-login-btn:hover {
            background: #4a8786;
            box-shadow: 0px 6px 12px rgba(91, 158, 157, 0.3);
            transform: translateY(-1px);
        }

        .error-box {
            margin-bottom: 20px;
            padding: 15px 18px;
            background: #fff2f2;
            border: 1px solid #F53003;
            border-radius: 10px;
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
            line-height: 1.5;
        }

        .success-box {
            margin-bottom: 20px;
            padding: 15px 18px;
            background: #d1fae5;
            border: 1px solid #10b981;
            border-radius: 10px;
        }

        .success-text {
            margin: 0;
            color: #065f46;
            font-size: 14px;
            line-height: 1.5;
        }

        .error-message {
            margin-top: 6px;
            color: #F53003;
            font-size: 13px;
            line-height: 1.4;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #00456A;
            margin: 0 0 10px 0;
        }

        .modal-subtitle {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 28px;
            color: #666;
            cursor: pointer;
            line-height: 1;
            padding: 5px 10px;
        }

        .modal-close:hover {
            color: #F53003;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .auth-container {
                padding: 20px 10px;
            }

            .logo {
                width: 70px;
                height: 66px;
                margin-bottom: 12px;
            }

            .title {
                font-size: 16px;
                margin-bottom: 24px;
                padding: 0 10px;
            }

            .form-card {
                padding: 30px 20px;
                border-radius: 10px;
            }

            .tab-container {
                height: 50px;
                margin-bottom: 25px;
            }

            .tab-slider {
                height: 36px;
            }

            .tab-button {
                font-size: 18px;
            }

            .input-label {
                font-size: 16px;
                margin-bottom: 6px;
            }

            .input-wrapper {
                height: 44px;
                padding: 0 12px;
            }

            .input-field {
                font-size: 16px;
                /* Prevent zoom on iOS */
            }

            .input-group {
                margin-bottom: 20px;
            }

            .voice-recorder {
                padding: 15px;
                margin-bottom: 20px;
            }

            .voice-icon {
                font-size: 36px;
            }

            .voice-status {
                font-size: 14px;
            }

            .voice-timer {
                font-size: 18px;
            }

            .voice-btn {
                padding: 8px 16px;
                font-size: 14px;
            }

            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                margin-bottom: 20px;
            }

            .remember-label,
            .forgot-link {
                font-size: 14px;
            }

            .submit-btn {
                height: 48px;
                font-size: 20px;
            }

            .voice-login-btn {
                height: 48px;
                font-size: 18px;
                margin-top: 12px;
            }

            .error-box,
            .success-box {
                padding: 12px 15px;
            }

            .error-title,
            .error-list,
            .success-text {
                font-size: 13px;
            }

            .modal-content {
                padding: 25px 20px;
                width: 95%;
            }

            .modal-title {
                font-size: 20px;
            }

            .modal-subtitle {
                font-size: 13px;
            }
        }

        /* Extra small devices */
        @media (max-width: 480px) {
            .form-card {
                padding: 25px 15px;
            }

            .tab-button {
                font-size: 16px;
            }

            .input-label {
                font-size: 15px;
            }

            .submit-btn {
                font-size: 18px;
            }

            .voice-login-btn {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="logo">
            <img src="{{ asset('images/voica-logo.png') }}" alt="Voica Logo">
        </div>
        <div class="title">Kelola keuangan Anda dengan mudah!</div>

        <div class="form-wrapper">
            <div class="form-card">
                <div class="tab-container">
                    <div class="tab-slider {{ $mode === 'register' ? 'register-mode' : '' }}"></div>
                    <button type="button" class="tab-button {{ $mode === 'login' ? 'active' : '' }}" onclick="switchTab('login')">Masuk</button>
                    <button type="button" class="tab-button {{ $mode === 'register' ? 'active' : '' }}" onclick="switchTab('register')">Daftar</button>
                </div>

                @if ($errors->any())
                <div class="error-box">
                    <div class="error-title">Terjadi Kesalahan</div>
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if (session('status'))
                <div class="success-box">
                    <p class="success-text">{{ session('status') }}</p>
                </div>
                @endif

                <div class="form-content">
                    <!-- LOGIN PANEL -->
                    <div id="login-panel" class="form-panel {{ $mode === 'register' ? 'hidden' : '' }}">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="input-group">
                                <label class="input-label" for="login-phone">Nama Pengguna atau Nomor Telepon</label>
                                <div class="input-wrapper">
                                    <input type="text" id="login-phone" name="phone" value="{{ old('phone') }}" required autofocus autocomplete="username" placeholder="Masukkan nama atau nomor telepon" class="input-field">
                                </div>
                                @error('phone')
                                <p class="error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="input-group">
                                <label class="input-label" for="login-password">Kata Sandi</label>
                                <div class="input-wrapper">
                                    <input type="password" id="login-password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi anda" class="input-field">
                                    <button type="button" onclick="togglePassword('login-password')" class="eye-toggle">
                                        <svg id="eye-icon-login-password" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M12 14C12.5304 14 13.0391 13.7893 13.4142 13.4142C13.7893 13.0391 14 12.5304 14 12C14 11.4696 13.7893 10.9609 13.4142 10.5858C13.0391 10.2107 12.5304 10 12 10C11.4696 10 10.9609 10.2107 10.5858 10.5858C10.2107 10.9609 10 11.4696 10 12C10 12.5304 10.2107 13.0391 10.5858 13.4142C10.9609 13.7893 11.4696 14 12 14Z" fill="black" fill-opacity="0.7" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M21 12C21 14.761 16.97 17 12 17C7.03 17 3 14.761 3 12C3 9.239 7.03 7 12 7C16.97 7 21 9.239 21 12ZM16 12C16 13.0609 15.5786 14.0783 14.8284 14.8284C14.0783 15.5786 13.0609 16 12 16C10.9391 16 9.92172 15.5786 9.17157 14.8284C8.42143 14.0783 8 13.0609 8 12C8 10.9391 8.42143 9.92172 9.17157 9.17157C9.92172 8.42143 10.9391 8 12 8C13.0609 8 14.0783 8.42143 14.8284 9.17157C15.5786 9.92172 16 10.9391 16 12Z" fill="black" fill-opacity="0.7" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                <p class="error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="remember-forgot">
                                <div class="remember-wrapper">
                                    <input type="checkbox" id="remember" name="remember" class="remember-checkbox">
                                    <label for="remember" class="remember-label">Ingat saya</label>
                                </div>
                                @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="forgot-link">Lupa kata sandi?</a>
                                @endif
                            </div>

                            <button type="submit" class="submit-btn">Masuk</button>
                            <button type="button" class="voice-login-btn" onclick="openVoiceLoginModal()">
                                <span>🎤</span><span>Login dengan Suara</span>
                            </button>
                        </form>
                    </div>

                    <!-- REGISTER PANEL -->
                    <div id="register-panel" class="form-panel {{ $mode === 'login' ? 'hidden' : '' }}">
                        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" id="register-form">
                            @csrf
                            <div class="input-group">
                                <label class="input-label" for="register-name">Nama Pengguna</label>
                                <div class="input-wrapper">
                                    <input type="text" id="register-name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Masukkan Nama Anda" class="input-field">
                                </div>
                                @error('name')
                                <p class="error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="input-group">
                                <label class="input-label" for="register-phone">No. Telepon</label>
                                <div class="input-wrapper">
                                    <input type="tel" id="register-phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="Masukkan nomor telepon anda" class="input-field">
                                </div>
                                @error('phone')
                                <p class="error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="input-group">
                                <label class="input-label" for="register-password">Kata Sandi</label>
                                <div class="input-wrapper">
                                    <input type="password" id="register-password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="input-field" minlength="8">
                                    <button type="button" onclick="togglePassword('register-password')" class="eye-toggle">
                                        <svg id="eye-icon-register-password" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M12 14C12.5304 14 13.0391 13.7893 13.4142 13.4142C13.7893 13.0391 14 12.5304 14 12C14 11.4696 13.7893 10.9609 13.4142 10.5858C13.0391 10.2107 12.5304 10 12 10C11.4696 10 10.9609 10.2107 10.5858 10.5858C10.2107 10.9609 10 11.4696 10 12C10 12.5304 10.2107 13.0391 10.5858 13.4142C10.9609 13.7893 11.4696 14 12 14Z" fill="black" fill-opacity="0.7" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M21 12C21 14.761 16.97 17 12 17C7.03 17 3 14.761 3 12C3 9.239 7.03 7 12 7C16.97 7 21 9.239 21 12ZM16 12C16 13.0609 15.5786 14.0783 14.8284 14.8284C14.0783 15.5786 13.0609 16 12 16C10.9391 16 9.92172 15.5786 9.17157 14.8284C8.42143 14.0783 8 13.0609 8 12C8 10.9391 8.42143 9.92172 9.17157 9.17157C9.92172 8.42143 10.9391 8 12 8C13.0609 8 14.0783 8.42143 14.8284 9.17157C15.5786 9.92172 16 10.9391 16 12Z" fill="black" fill-opacity="0.7" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                <p class="error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="input-group">
                                <label class="input-label" for="register-password-confirmation">Konfirmasi Kata Sandi</label>
                                <div class="input-wrapper">
                                    <input type="password" id="register-password-confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="input-field" minlength="8">
                                    <button type="button" onclick="togglePassword('register-password-confirmation')" class="eye-toggle">
                                        <svg id="eye-icon-register-password-confirmation" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M12 14C12.5304 14 13.0391 13.7893 13.4142 13.4142C13.7893 13.0391 14 12.5304 14 12C14 11.4696 13.7893 10.9609 13.4142 10.5858C13.0391 10.2107 12.5304 10 12 10C11.4696 10 10.9609 10.2107 10.5858 10.5858C10.2107 10.9609 10 11.4696 10 12C10 12.5304 10.2107 13.0391 10.5858 13.4142C10.9609 13.7893 11.4696 14 12 14Z" fill="black" fill-opacity="0.7" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M21 12C21 14.761 16.97 17 12 17C7.03 17 3 14.761 3 12C3 9.239 7.03 7 12 7C16.97 7 21 9.239 21 12ZM16 12C16 13.0609 15.5786 14.0783 14.8284 14.8284C14.0783 15.5786 13.0609 16 12 16C10.9391 16 9.92172 15.5786 9.17157 14.8284C8.42143 14.0783 8 13.0609 8 12C8 10.9391 8.42143 9.92172 9.17157 9.17157C9.92172 8.42143 10.9391 8 12 8C13.0609 8 14.0783 8.42143 14.8284 9.17157C15.5786 9.92172 16 10.9391 16 12Z" fill="black" fill-opacity="0.7" />
                                        </svg>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                <p class="error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="input-group">
                                <label class="input-label">🎤 Rekam Suara Anda (Wajib)</label>
                                <div class="voice-recorder" id="register-voice-recorder">
                                    <div class="voice-icon">🎤</div>
                                    <div class="voice-status" id="register-voice-status">Rekam suara Anda selama 3-5 detik untuk autentikasi</div>
                                    <div class="voice-timer" id="register-voice-timer" style="display: none;">00:00</div>
                                    <div class="voice-controls">
                                        <button type="button" class="voice-btn voice-btn-primary" id="register-start-btn" onclick="RegisterVoice.startRecording()">Mulai Rekam</button>
                                        <button type="button" class="voice-btn voice-btn-danger" id="register-stop-btn" onclick="RegisterVoice.stopRecording()" style="display: none;">Berhenti</button>
                                        <button type="button" class="voice-btn voice-btn-secondary" id="register-play-btn" onclick="RegisterVoice.playRecording()" style="display: none;">▶ Putar</button>
                                        <button type="button" class="voice-btn voice-btn-secondary" id="register-reset-btn" onclick="RegisterVoice.resetRecording()" style="display: none;">🔄 Reset</button>
                                    </div>
                                </div>
                                <input type="hidden" name="voice_audio_base64" id="register-voice-input" required>
                                @error('voice_audio')
                                <p class="error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="submit-btn" id="register-submit-btn" disabled>Daftar Sekarang</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VOICE LOGIN MODAL -->
    <div id="voice-login-modal" class="modal">
        <div class="modal-content" style="position: relative;">
            <button class="modal-close" onclick="closeVoiceLoginModal()">&times;</button>
            <div class="modal-header">
                <h2 class="modal-title">🎤 Login dengan Suara</h2>
                <p class="modal-subtitle">Rekam suara Anda untuk masuk ke sistem</p>
            </div>
            <form method="POST" action="{{ route('voice.login') }}" enctype="multipart/form-data" id="voice-login-form">
                @csrf
                <div class="input-group">
                    <label class="input-label" for="voice-login-phone">Nama Pengguna atau Nomor Telepon</label>
                    <div class="input-wrapper">
                        <input type="text" id="voice-login-phone" name="phone" required placeholder="Masukkan nama atau nomor telepon" class="input-field">
                    </div>
                </div>
                <div class="voice-recorder" id="voice-login-recorder">
                    <div class="voice-icon">🎤</div>
                    <div class="voice-status" id="voice-login-status">Klik tombol untuk merekam suara Anda</div>
                    <div class="voice-timer" id="voice-login-timer" style="display: none;">00:00</div>
                    <div class="voice-controls">
                        <button type="button" class="voice-btn voice-btn-primary" id="voice-login-start-btn" onclick="VoiceLogin.startRecording()">Mulai Rekam</button>
                        <button type="button" class="voice-btn voice-btn-danger" id="voice-login-stop-btn" onclick="VoiceLogin.stopRecording()" style="display: none;">Berhenti</button>
                        <button type="button" class="voice-btn voice-btn-secondary" id="voice-login-play-btn" onclick="VoiceLogin.playRecording()" style="display: none;">▶ Putar</button>
                        <button type="button" class="voice-btn voice-btn-secondary" id="voice-login-reset-btn" onclick="VoiceLogin.resetRecording()" style="display: none;">🔄 Reset</button>
                    </div>
                </div>
                <input type="hidden" name="voice_audio_base64" id="voice-login-input">
                <button type="submit" class="submit-btn" id="voice-login-submit-btn" disabled>Login dengan Suara</button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = document.getElementById('eye-icon-' + fieldId);
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88" stroke="black" stroke-width="2" stroke-opacity="0.7" stroke-linecap="round" stroke-linejoin="round"/>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path d="M12 14C12.5304 14 13.0391 13.7893 13.4142 13.4142C13.7893 13.0391 14 12.5304 14 12C14 11.4696 13.7893 10.9609 13.4142 10.5858C13.0391 10.2107 12.5304 10 12 10C11.4696 10 10.9609 10.2107 10.5858 10.5858C10.2107 10.9609 10 11.4696 10 12C10 12.5304 10.2107 13.0391 10.5858 13.4142C10.9609 13.7893 11.4696 14 12 14Z" fill="black" fill-opacity="0.7"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21 12C21 14.761 16.97 17 12 17C7.03 17 3 14.761 3 12C3 9.239 7.03 7 12 7C16.97 7 21 9.239 21 12ZM16 12C16 13.0609 15.5786 14.0783 14.8284 14.8284C14.0783 15.5786 13.0609 16 12 16C10.9391 16 9.92172 15.5786 9.17157 14.8284C8.42143 14.0783 8 13.0609 8 12C8 10.9391 8.42143 9.92172 9.17157 9.17157C9.92172 8.42143 10.9391 8 12 8C13.0609 8 14.0783 8.42143 14.8284 9.17157C15.5786 9.92172 16 10.9391 16 12Z" fill="black" fill-opacity="0.7"/>`;
            }
        }

        function switchTab(mode) {
            const slider = document.querySelector('.tab-slider');
            const loginPanel = document.getElementById('login-panel');
            const registerPanel = document.getElementById('register-panel');
            const loginBtn = document.querySelector('.tab-button:first-of-type');
            const registerBtn = document.querySelector('.tab-button:last-of-type');
            if (mode === 'register') {
                slider.classList.add('register-mode');
                loginPanel.classList.add('hidden');
                registerPanel.classList.remove('hidden');
                loginBtn.classList.remove('active');
                registerBtn.classList.add('active');
                window.history.pushState({}, '', '/register');
            } else {
                slider.classList.remove('register-mode');
                registerPanel.classList.add('hidden');
                loginPanel.classList.remove('hidden');
                registerBtn.classList.remove('active');
                loginBtn.classList.add('active');
                window.history.pushState({}, '', '/login');
            }
        }

        window.addEventListener('popstate', function() {
            const path = window.location.pathname;
            if (path.includes('register')) {
                switchTab('register');
            } else {
                switchTab('login');
            }
        });

        function openVoiceLoginModal() {
            document.getElementById('voice-login-modal').classList.add('active');
        }

        function closeVoiceLoginModal() {
            document.getElementById('voice-login-modal').classList.remove('active');
            VoiceLogin.resetRecording();
        }

        document.getElementById('voice-login-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVoiceLoginModal();
            }
        });

        const RegisterVoice = {
            mediaRecorder: null,
            audioChunks: [],
            audioBlob: null,
            audioUrl: null,
            wavBlob: null,
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
                    this.mediaRecorder.ondataavailable = (event) => {
                        this.audioChunks.push(event.data);
                    };
                    this.mediaRecorder.onstop = async () => {
                        this.audioBlob = new Blob(this.audioChunks, {
                            type: 'audio/webm'
                        });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);

                        // Convert to WAV format
                        await this.convertToWav();

                        stream.getTracks().forEach(track => track.stop());
                    };
                    this.mediaRecorder.start();
                    this.updateUI('recording');
                    this.recordingTimer = 0;
                    this.recordingInterval = setInterval(() => {
                        this.recordingTimer++;
                        const minutes = Math.floor(this.recordingTimer / 60);
                        const seconds = this.recordingTimer % 60;
                        document.getElementById('register-voice-timer').textContent =
                            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }, 1000);
                } catch (error) {
                    alert('Gagal mengakses mikrofon: ' + error.message);
                }
            },

            async convertToWav() {
                try {
                    const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                    const arrayBuffer = await this.audioBlob.arrayBuffer();
                    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

                    // Resample to 16kHz mono
                    const targetSampleRate = 16000;
                    const offlineContext = new OfflineAudioContext(
                        1, // mono
                        audioBuffer.duration * targetSampleRate,
                        targetSampleRate
                    );

                    const source = offlineContext.createBufferSource();
                    source.buffer = audioBuffer;
                    source.connect(offlineContext.destination);
                    source.start();

                    const resampledBuffer = await offlineContext.startRendering();

                    // Convert to WAV
                    const wavData = this.audioBufferToWav(resampledBuffer);
                    this.wavBlob = new Blob([wavData], {
                        type: 'audio/wav'
                    });

                    // Convert to base64
                    const reader = new FileReader();
                    reader.readAsDataURL(this.wavBlob);
                    reader.onloadend = () => {
                        document.getElementById('register-voice-input').value = reader.result;
                        document.getElementById('register-submit-btn').disabled = false;
                        this.updateUI('recorded');
                    };
                } catch (error) {
                    console.error('Error converting to WAV:', error);
                    alert('Gagal mengkonversi audio: ' + error.message);
                }
            },

            audioBufferToWav(buffer) {
                const numChannels = buffer.numberOfChannels;
                const sampleRate = buffer.sampleRate;
                const format = 1; // PCM
                const bitDepth = 16;

                const bytesPerSample = bitDepth / 8;
                const blockAlign = numChannels * bytesPerSample;

                const data = buffer.getChannelData(0);
                const dataLength = data.length * bytesPerSample;
                const buffer_size = 44 + dataLength;

                const arrayBuffer = new ArrayBuffer(buffer_size);
                const view = new DataView(arrayBuffer);

                // WAV header
                const writeString = (offset, string) => {
                    for (let i = 0; i < string.length; i++) {
                        view.setUint8(offset + i, string.charCodeAt(i));
                    }
                };

                writeString(0, 'RIFF');
                view.setUint32(4, 36 + dataLength, true);
                writeString(8, 'WAVE');
                writeString(12, 'fmt ');
                view.setUint32(16, 16, true);
                view.setUint16(20, format, true);
                view.setUint16(22, numChannels, true);
                view.setUint32(24, sampleRate, true);
                view.setUint32(28, sampleRate * blockAlign, true);
                view.setUint16(32, blockAlign, true);
                view.setUint16(34, bitDepth, true);
                writeString(36, 'data');
                view.setUint32(40, dataLength, true);

                // Write PCM samples
                let offset = 44;
                for (let i = 0; i < data.length; i++) {
                    const sample = Math.max(-1, Math.min(1, data[i]));
                    view.setInt16(offset, sample < 0 ? sample * 0x8000 : sample * 0x7FFF, true);
                    offset += 2;
                }

                return arrayBuffer;
            },

            stopRecording() {
                if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                    this.mediaRecorder.stop();
                    clearInterval(this.recordingInterval);
                }
            },

            playRecording() {
                if (this.audioUrl) {
                    const audio = new Audio(this.audioUrl);
                    audio.play().then(() => {
                        console.log('✅ Playing audio');
                    }).catch(err => {
                        console.error('❌ Play failed:', err);
                        alert('Gagal memutar: ' + err.message);
                    });
                }
            },

            resetRecording() {
                if (this.audioUrl) {
                    URL.revokeObjectURL(this.audioUrl);
                }
                this.audioChunks = [];
                this.audioBlob = null;
                this.wavBlob = null;
                this.audioUrl = null;
                this.recordingTimer = 0;
                document.getElementById('register-voice-input').value = '';
                document.getElementById('register-submit-btn').disabled = true;
                this.updateUI('idle');
            },

            updateUI(state) {
                const recorder = document.getElementById('register-voice-recorder');
                const status = document.getElementById('register-voice-status');
                const timer = document.getElementById('register-voice-timer');
                const startBtn = document.getElementById('register-start-btn');
                const stopBtn = document.getElementById('register-stop-btn');
                const playBtn = document.getElementById('register-play-btn');
                const resetBtn = document.getElementById('register-reset-btn');
                recorder.classList.remove('recording', 'recorded', 'converting');
                if (state === 'recording') {
                    recorder.classList.add('recording');
                    status.textContent = '🔴 Sedang merekam... Berbicara dengan jelas';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'inline-block';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                } else if (state === 'converting') {
                    recorder.classList.add('converting');
                    status.textContent = '⏳ Mengkonversi audio...';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                } else if (state === 'recorded') {
                    recorder.classList.add('recorded');
                    status.textContent = '✅ Rekaman berhasil! Anda dapat memutar atau merekam ulang';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'inline-block';
                    resetBtn.style.display = 'inline-block';
                } else {
                    status.textContent = 'Rekam suara Anda selama 3-5 detik untuk autentikasi';
                    timer.style.display = 'none';
                    timer.textContent = '00:00';
                    startBtn.style.display = 'inline-block';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                }
            }
        };

        const VoiceLogin = {
            mediaRecorder: null,
            audioChunks: [],
            audioBlob: null,
            audioUrl: null,
            wavBlob: null,
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
                    this.mediaRecorder.ondataavailable = (event) => {
                        this.audioChunks.push(event.data);
                    };
                    this.mediaRecorder.onstop = async () => {
                        this.audioBlob = new Blob(this.audioChunks, {
                            type: 'audio/webm'
                        });
                        this.audioUrl = URL.createObjectURL(this.audioBlob);

                        // Convert to WAV format
                        await this.convertToWav();

                        stream.getTracks().forEach(track => track.stop());
                    };
                    this.mediaRecorder.start();
                    this.updateUI('recording');
                    this.recordingTimer = 0;
                    this.recordingInterval = setInterval(() => {
                        this.recordingTimer++;
                        const minutes = Math.floor(this.recordingTimer / 60);
                        const seconds = this.recordingTimer % 60;
                        document.getElementById('voice-login-timer').textContent =
                            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }, 1000);
                } catch (error) {
                    alert('Gagal mengakses mikrofon: ' + error.message);
                }
            },

            async convertToWav() {
                try {
                    this.updateUI('converting');
                    const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                    const arrayBuffer = await this.audioBlob.arrayBuffer();
                    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

                    // Resample to 16kHz mono
                    const targetSampleRate = 16000;
                    const offlineContext = new OfflineAudioContext(
                        1, // mono
                        audioBuffer.duration * targetSampleRate,
                        targetSampleRate
                    );

                    const source = offlineContext.createBufferSource();
                    source.buffer = audioBuffer;
                    source.connect(offlineContext.destination);
                    source.start();

                    const resampledBuffer = await offlineContext.startRendering();

                    // Convert to WAV
                    const wavData = this.audioBufferToWav(resampledBuffer);
                    this.wavBlob = new Blob([wavData], {
                        type: 'audio/wav'
                    });

                    // Convert to base64
                    const reader = new FileReader();
                    reader.readAsDataURL(this.wavBlob);
                    reader.onloadend = () => {
                        document.getElementById('voice-login-input').value = reader.result;
                        document.getElementById('voice-login-submit-btn').disabled = false;
                        this.updateUI('recorded');
                    };
                } catch (error) {
                    console.error('Error converting to WAV:', error);
                    alert('Gagal mengkonversi audio: ' + error.message);
                }
            },

            audioBufferToWav(buffer) {
                const numChannels = buffer.numberOfChannels;
                const sampleRate = buffer.sampleRate;
                const format = 1; // PCM
                const bitDepth = 16;

                const bytesPerSample = bitDepth / 8;
                const blockAlign = numChannels * bytesPerSample;

                const data = buffer.getChannelData(0);
                const dataLength = data.length * bytesPerSample;
                const buffer_size = 44 + dataLength;

                const arrayBuffer = new ArrayBuffer(buffer_size);
                const view = new DataView(arrayBuffer);

                // WAV header
                const writeString = (offset, string) => {
                    for (let i = 0; i < string.length; i++) {
                        view.setUint8(offset + i, string.charCodeAt(i));
                    }
                };

                writeString(0, 'RIFF');
                view.setUint32(4, 36 + dataLength, true);
                writeString(8, 'WAVE');
                writeString(12, 'fmt ');
                view.setUint32(16, 16, true);
                view.setUint16(20, format, true);
                view.setUint16(22, numChannels, true);
                view.setUint32(24, sampleRate, true);
                view.setUint32(28, sampleRate * blockAlign, true);
                view.setUint16(32, blockAlign, true);
                view.setUint16(34, bitDepth, true);
                writeString(36, 'data');
                view.setUint32(40, dataLength, true);

                // Write PCM samples
                let offset = 44;
                for (let i = 0; i < data.length; i++) {
                    const sample = Math.max(-1, Math.min(1, data[i]));
                    view.setInt16(offset, sample < 0 ? sample * 0x8000 : sample * 0x7FFF, true);
                    offset += 2;
                }

                return arrayBuffer;
            },

            stopRecording() {
                if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                    this.mediaRecorder.stop();
                    clearInterval(this.recordingInterval);
                }
            },

            playRecording() {
                if (this.audioUrl) {
                    const audio = new Audio(this.audioUrl);
                    audio.play();
                }
            },

            resetRecording() {
                if (this.audioUrl) {
                    URL.revokeObjectURL(this.audioUrl);
                }
                this.audioChunks = [];
                this.audioBlob = null;
                this.wavBlob = null;
                this.audioUrl = null;
                this.recordingTimer = 0;
                document.getElementById('voice-login-input').value = '';
                document.getElementById('voice-login-submit-btn').disabled = true;
                this.updateUI('idle');
            },

            updateUI(state) {
                const recorder = document.getElementById('voice-login-recorder');
                const status = document.getElementById('voice-login-status');
                const timer = document.getElementById('voice-login-timer');
                const startBtn = document.getElementById('voice-login-start-btn');
                const stopBtn = document.getElementById('voice-login-stop-btn');
                const playBtn = document.getElementById('voice-login-play-btn');
                const resetBtn = document.getElementById('voice-login-reset-btn');
                recorder.classList.remove('recording', 'recorded', 'converting');
                if (state === 'recording') {
                    recorder.classList.add('recording');
                    status.textContent = '🔴 Sedang merekam... Berbicara dengan jelas';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'inline-block';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                } else if (state === 'converting') {
                    recorder.classList.add('converting');
                    status.textContent = '⏳ Mengkonversi audio...';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                } else if (state === 'recorded') {
                    recorder.classList.add('recorded');
                    status.textContent = '✅ Rekaman berhasil! Anda dapat memutar atau merekam ulang';
                    timer.style.display = 'block';
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'inline-block';
                    resetBtn.style.display = 'inline-block';
                } else {
                    status.textContent = 'Klik tombol untuk merekam suara Anda';
                    timer.style.display = 'none';
                    timer.textContent = '00:00';
                    startBtn.style.display = 'inline-block';
                    stopBtn.style.display = 'none';
                    playBtn.style.display = 'none';
                    resetBtn.style.display = 'none';
                }
            }
        };

        document.getElementById('register-form').addEventListener('submit', function(e) {
            const voiceInput = document.getElementById('register-voice-input');
            if (!voiceInput.value) {
                e.preventDefault();
                alert('Harap rekam suara Anda terlebih dahulu!');
                return false;
            }
        });

        document.getElementById('voice-login-form').addEventListener('submit', function(e) {
            const voiceInput = document.getElementById('voice-login-input');
            if (!voiceInput.value) {
                e.preventDefault();
                alert('Harap rekam suara Anda terlebih dahulu!');
                return false;
            }
        });
    </script>

    <!-- Audio Processing Library -->
    <script src="https://cdn.jsdelivr.net/npm/lamejs@1.2.1/lame.min.js"></script>
</body>

</html>