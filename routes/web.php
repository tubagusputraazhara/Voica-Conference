<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalsController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return inertia('Welcome');
});

// Guest routes (auth pages)
Route::middleware('guest')->group(function () {
    // Show login form
    Route::get('/login', [AuthController::class, 'showAuthForm'])
        ->name('login');

    // Show register form
    Route::get('/register', function () {
        return app(AuthController::class)->showAuthForm('register');
    })->name('register');

    // Handle traditional login (password)
    Route::post('/login', [AuthController::class, 'login']);

    // Handle voice login
    Route::post('/voice-login', [AuthController::class, 'voiceLogin'])
        ->name('voice.login');

    // Handle registration
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard dengan data real
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
        ->name('dashboard');

    // Account Settings
    Route::get('/settings', [App\Http\Controllers\AccountSettingsController::class, 'index'])
        ->name('settings');
    Route::post('/settings/update', [App\Http\Controllers\AccountSettingsController::class, 'update'])
        ->name('settings.update');

    // Budgeting
    Route::get('/budgeting', [App\Http\Controllers\BudgetController::class, 'index'])
        ->name('budgeting');
    Route::post('/api/budget', [App\Http\Controllers\BudgetController::class, 'store'])
        ->name('budget.store');
    Route::put('/api/budget/{id}', [App\Http\Controllers\BudgetController::class, 'update'])
        ->name('budget.update');
    Route::delete('/api/budget/{id}', [App\Http\Controllers\BudgetController::class, 'destroy'])
        ->name('budget.destroy');
    Route::get('/api/budget/{id}/transactions', [App\Http\Controllers\BudgetController::class, 'getTransactions'])
        ->name('budget.transactions');

    // Laporan
    Route::get('/laporan', [App\Http\Controllers\LaporanController::class, 'index'])
        ->name('laporan');
    Route::get('/api/laporan/transactions', [App\Http\Controllers\LaporanController::class, 'getTransactions'])
        ->name('laporan.transactions');
    Route::get('/api/laporan/export-pdf', [App\Http\Controllers\LaporanController::class, 'exportPdf'])
        ->name('laporan.export.pdf');
    // Voice Transaction API
    Route::post('/api/voice-transaction', [App\Http\Controllers\VoiceTransactionController::class, 'store'])
        ->name('voice.transaction.store');

    // Voice processing (audio or text)
    Route::post('/api/voice-process', [App\Http\Controllers\VoiceTransactionController::class, 'process'])
        ->name('voice.process');

    // NEW: Parse voice text (NLP parser)
    Route::post('/api/parse-voice-text', [App\Http\Controllers\VoiceTransactionController::class, 'parseVoiceText'])
        ->name('voice.parse.text');

    // NEW: Transcribe audio via FastAPI Faster Whisper (offline STT, menggantikan Google Web Speech API)
    Route::post('/api/voice/transcribe', [App\Http\Controllers\VoiceTransactionController::class, 'transcribeAudio'])
        ->name('voice.transcribe');

    Route::get('/api/budgets', [App\Http\Controllers\VoiceTransactionController::class, 'getBudgets'])
        ->name('api.budgets');

    Route::get('/api/goals', [App\Http\Controllers\VoiceTransactionController::class, 'getGoals'])
        ->name('api.goals');

    // Transaction Management (Edit & Delete)
    Route::put('/api/transactions/{id}', [App\Http\Controllers\VoiceTransactionController::class, 'update'])
        ->name('transactions.update');
    Route::delete('/api/transactions/{id}', [App\Http\Controllers\VoiceTransactionController::class, 'destroy'])
        ->name('transactions.destroy');

    // Goals
    Route::get('/goals', [GoalsController::class, 'index'])->name('goals');
    Route::post('/goals', [GoalsController::class, 'store'])->name('goals.store');
    Route::put('/goals/{id}', [GoalsController::class, 'update'])->name('goals.update');
    Route::delete('/goals/{id}', [GoalsController::class, 'destroy'])->name('goals.destroy');
    Route::get('/api/goals/{id}/transactions', [GoalsController::class, 'getTransactions'])->name('goals.transactions');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // ================================================
    // VOICE-LOCK ROUTES
    // ================================================
    // Halaman verifikasi suara untuk aksi sensitif
    Route::get('/voice-lock/verify', [App\Http\Controllers\VoiceLockController::class, 'show'])
        ->name('voice-lock.verify');
    Route::post('/voice-lock/verify', [App\Http\Controllers\VoiceLockController::class, 'verify'])
        ->name('voice-lock.process')
        ->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute

    // API: Get new challenge (for AJAX refresh)
    Route::get('/voice-lock/challenge', [App\Http\Controllers\VoiceLockController::class, 'getChallenge'])
        ->name('voice-lock.challenge');
});

// ================================================
// PROTECTED ROUTES (Memerlukan Voice Verification)
// ================================================
// Route di bawah ini memerlukan verifikasi suara tambahan
// Voice-Lock akan aktif selama 5 menit setelah verifikasi berhasil
Route::middleware(['auth', 'voice-lock'])->group(function () {

    // 🔐 Simpan Transaksi Baru - Aksi sensitif
    Route::post('/api/voice-transaction-secure', [App\Http\Controllers\VoiceTransactionController::class, 'store'])
        ->name('voice.transaction.store.secure');

    // 🔐 Hapus Transaksi - Aksi sensitif
    Route::delete('/api/transactions/{id}/voice-locked', [App\Http\Controllers\VoiceTransactionController::class, 'destroy'])
        ->name('transactions.voice-locked.destroy');

    // 🔐 Export Laporan PDF - Data sensitif
    Route::get('/api/laporan/export-pdf-secure', [App\Http\Controllers\LaporanController::class, 'exportPdf'])
        ->name('laporan.export.pdf.secure');

    // 🔐 Update Password - Aksi sensitif
    Route::post('/settings/update-secure', [App\Http\Controllers\AccountSettingsController::class, 'update'])
        ->name('settings.update.secure');
});
