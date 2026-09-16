<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Voica - Voice Finance')</title>
    
    <!-- External CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('css/voica-app.css') }}">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="logo">
            <img src="{{ asset('images/voica-logo.png') }}" alt="VOICA Logo" style="width: 100%; height: auto; object-fit: contain;">
        </div>

        <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <div class="menu-item-icon">🏠</div>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('budgeting') }}" class="menu-item {{ request()->routeIs('budgeting') ? 'active' : '' }}">
            <div class="menu-item-icon">💰</div>
            <span>Anggaran</span>
        </a>

        <a href="{{ route('goals') }}" class="menu-item {{ request()->routeIs('goals') ? 'active' : '' }}">
            <div class="menu-item-icon">🎯</div>
            <span>Target</span>
        </a>

        <a href="{{ route('laporan') }}" class="menu-item {{ request()->routeIs('laporan') ? 'active' : '' }}">
            <div class="menu-item-icon">📊</div>
            <span>Laporan</span>
        </a>

        <a href="{{ route('settings') }}" class="menu-item {{ request()->routeIs('settings') ? 'active' : '' }}">
            <div class="menu-item-icon">⚙️</div>
            <span>Pengaturan</span>
        </a>

        <a href="{{ asset('Manual Book VOICA.pdf') }}" target="_blank" download="Manual Book VOICA.pdf" class="menu-item">
            <div class="menu-item-icon">📖</div>
            <span>Buku Panduan</span>
        </a>
    </div>

    <div class="main-content" id="main-content">
        @yield('main-content')
    </div>
</div>

<!-- Bottom Navigation Bar (Mobile Only) -->
<nav class="bottom-nav">
    <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="bottom-nav-icon">🏠</span>
        <span class="bottom-nav-label">Home</span>
    </a>
    <a href="{{ route('budgeting') }}" class="bottom-nav-item {{ request()->routeIs('budgeting') ? 'active' : '' }}">
        <span class="bottom-nav-icon">💰</span>
        <span class="bottom-nav-label">Anggaran</span>
    </a>
    <a href="{{ route('goals') }}" class="bottom-nav-item {{ request()->routeIs('goals') ? 'active' : '' }}">
        <span class="bottom-nav-icon">🎯</span>
        <span class="bottom-nav-label">Target</span>
    </a>
    <a href="{{ route('laporan') }}" class="bottom-nav-item {{ request()->routeIs('laporan') ? 'active' : '' }}">
        <span class="bottom-nav-icon">📊</span>
        <span class="bottom-nav-label">Laporan</span>
    </a>
    <a href="{{ route('settings') }}" class="bottom-nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
        <span class="bottom-nav-icon">⚙️</span>
        <span class="bottom-nav-label">Akun</span>
    </a>
</nav>

<!-- External JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/voica-app.js') }}"></script>

<!-- Voice Feature Component (on dashboard, budgeting, and goals pages) -->
@if(request()->routeIs('dashboard') || request()->routeIs('budgeting') || request()->routeIs('goals'))
    @include('components.voice-feature')
@endif

</body>
</html>
