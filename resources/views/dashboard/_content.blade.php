@php
    // Data transaksi dari controller (kosong jika belum ada transaksi)
    $transactions = $transactions ?? collect([]);

    $totalIncome  = isset($totalPemasukan) ? $totalPemasukan : $transactions->where('amount', '>', 0)->sum('amount');
    $totalExpense = isset($totalPengeluaran) ? $totalPengeluaran : abs($transactions->where('amount', '<', 0)->sum('amount'));
    $saldo = isset($saldo) ? $saldo : ($totalIncome - $totalExpense);

    $total = $totalIncome + $totalExpense;
    $incomePerc  = $total ? round($totalIncome  / $total * 100) : 0;
    $expensePerc = 100 - $incomePerc;

    // Posisi label di pie chart
    $incomeAngle  = -90 + ($incomePerc * 3.6 / 2);
    $expenseAngle = -90 + ($incomePerc * 3.6) + ($expensePerc * 3.6 / 2);
@endphp

<!-- Header -->
<div class="header">
    <div class="header-text">
        <h1>Halo, {{ Auth::check() ? Auth::user()->name : 'Budi' }}!</h1>
        <p>Mari lihat pengeluaran Anda hari ini</p>
    </div>
    <button class="voice-btn voice-btn-header" onclick="startVoiceRecording()">
        <svg width="24" height="30" viewBox="0 0 38 48" fill="none">
            <path d="M38 20.8929C38 20.6571 37.7927 20.4643 37.5394 20.4643H34.0849C33.8315 20.4643 33.6242 20.6571 33.6242 20.8929C33.6242 28.4089 27.0779 34.5 19 34.5C10.9221 34.5 4.37576 28.4089 4.37576 20.8929C4.37576 20.6571 4.16849 20.4643 3.91515 20.4643H0.460606C0.207273 20.4643 0 20.6571 0 20.8929C0 29.9304 7.28909 37.3875 16.697 38.4429V43.9286H8.33121C7.54243 43.9286 6.90909 44.6946 6.90909 45.6429V47.5714C6.90909 47.8071 7.0703 48 7.26606 48H30.7339C30.9297 48 31.0909 47.8071 31.0909 47.5714V45.6429C31.0909 44.6946 30.4576 43.9286 29.6688 43.9286H21.0727V38.4696C30.59 37.5054 38 30.0054 38 20.8929ZM19 30C24.4064 30 28.7879 25.9714 28.7879 21V9C28.7879 4.02857 24.4064 0 19 0C13.5936 0 9.21212 4.02857 9.21212 9V21C9.21212 25.9714 13.5936 30 19 30Z" fill="white"/>
        </svg>
        <span class="voice-btn-text">Transaksi dengan Suara</span>
    </button>
</div>

<!-- Cards Grid -->
<div class="cards-grid">
    <div class="card balance">
        <div class="card-icon">💳</div>
        <div class="card-content">
            <h3>Saldo saat ini</h3>
            <div class="amount">Rp {{ number_format($saldo, 0, ',', '.') }}</div>
        </div>
    </div>

    @if(isset($goal) && $goal)
    <div class="card target">
        <div class="card-icon">🎯</div>
        <div class="card-content">
            <h3>{{ $goal->namaGoal }}</h3>
            <div class="amount">
                {{ number_format($goalPercentage ?? 0, 1) }}%
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ min($goalPercentage ?? 0, 100) }}%;"></div>
            </div>
        </div>
    </div>
    @endif

    <div class="card income">
        <div class="card-icon">📈</div>
        <div class="card-content">
            <h3>Pemasukan</h3>
            <div class="amount">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="card expense">
        <div class="card-icon">📉</div>
        <div class="card-content">
            <h3>Pengeluaran</h3>
            <div class="amount">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<!-- Bottom Section -->
<div class="bottom-section">
    <div class="bottom-grid">
        <!-- Chart Section -->
        <div class="chart-section">
            <h2>Chart per Bulan</h2>
            <div class="chart-container">
                <div class="pie-chart"
                     style="background: conic-gradient(
                                 #00A311 0 {{ $incomePerc }}%,
                                 #ED6363 {{ $incomePerc }}% 100%
                             );">
                    <div class="chart-label chart-label-income" style="--angle: {{ $incomeAngle }}deg;">
                        {{ $incomePerc }}%
                    </div>
                    <div class="chart-label chart-label-expense" style="--angle: {{ $expenseAngle }}deg;">
                        {{ $expensePerc }}%
                    </div>
                </div>

                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-color income"></div>
                        <span style="color: #00A311;">Pemasukan</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color expense"></div>
                        <span style="color: #ED6363;">Pengeluaran</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="transactions-section">
            <h2>Transaksi Terbaru</h2>
            <div class="transaction-list">
                @if(isset($recentTransactions) && $recentTransactions->count() > 0)
                    @foreach($recentTransactions as $transaction)
                    <div class="transaction-item">
                        <div class="transaction-name">{{ $transaction->keterangan }}</div>
                        <div class="transaction-date">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y') }}</div>
                        <div class="transaction-amount {{ $transaction->jenis == 'Pemasukan' ? 'income' : 'expense' }}">
                            {{ $transaction->jenis == 'Pemasukan' ? '+ ' : '- ' }}Rp{{ number_format($transaction->jumlah, 0, ',', '.') }}
                        </div>
                    </div>
                    @endforeach
                @else
                    <div style="text-align: center; padding: 40px 20px; color: #999;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📝</div>
                        <p style="font-size: 16px; font-weight: 600; margin-bottom: 5px;">Belum ada transaksi</p>
                        <p style="font-size: 14px;">Mulai catat transaksi Anda dengan tombol suara</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
