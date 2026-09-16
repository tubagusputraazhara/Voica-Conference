<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #00456A;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #00456A;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header .subtitle {
            color: #666;
            font-size: 14px;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
        }

        .info-label {
            width: 120px;
            font-weight: bold;
            color: #00456A;
        }

        .info-value {
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #00456A;
            color: white;
        }

        th {
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #E3F5FF;
            font-size: 11px;
        }

        tbody tr:nth-child(even) {
            background: #F8FCFF;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .nominal-pemasukan {
            color: #2ecc71;
            font-weight: bold;
        }

        .nominal-pengeluaran {
            color: #e74c3c;
            font-weight: bold;
        }

        .summary-table {
            width: 50%;
            margin-left: auto;
            margin-top: 20px;
        }

        .summary-table td {
            padding: 8px 10px;
            border: none;
        }

        .summary-row {
            background: #E3F5FF;
            font-weight: bold;
        }

        .summary-total {
            background: #00456A;
            color: white;
            font-weight: bold;
            font-size: 13px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #E3F5FF;
            padding-top: 10px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KEUANGAN</h1>
        <div class="subtitle">{{ $user->name }}</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Periode:</div>
            <div class="info-value">{{ $period['start'] }} - {{ $period['end'] }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal Cetak:</div>
            <div class="info-value">{{ date('d F Y, H:i') }} WIB</div>
        </div>
    </div>

    @if(count($transactions) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 15%;">Kategori</th>
                    <th style="width: 20%;">Nominal</th>
                    <th style="width: 20%;">Saldo</th>
                    <th style="width: 30%;">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction['tanggal'] }}</td>
                        <td>{{ $transaction['kategori'] }}</td>
                        <td class="{{ $transaction['jenis'] === 'Pemasukan' ? 'nominal-pemasukan' : 'nominal-pengeluaran' }}">
                            {{ $transaction['jenis'] === 'Pemasukan' ? '+' : '-' }} {{ $transaction['jumlah_formatted'] }}
                        </td>
                        <td>{{ $transaction['saldo_formatted'] }}</td>
                        <td>{{ $transaction['keterangan'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-table">
            <tr class="summary-row">
                <td>Total Pemasukan:</td>
                <td class="text-right nominal-pemasukan">{{ $summary['total_pemasukan'] }}</td>
            </tr>
            <tr class="summary-row">
                <td>Total Pengeluaran:</td>
                <td class="text-right nominal-pengeluaran">{{ $summary['total_pengeluaran'] }}</td>
            </tr>
            <tr class="summary-total">
                <td>Saldo Akhir:</td>
                <td class="text-right">{{ $summary['saldo_akhir'] }}</td>
            </tr>
        </table>
    @else
        <div class="no-data">
            Tidak ada data transaksi untuk periode ini
        </div>
    @endif

    <div class="footer">
        Laporan ini digenerate otomatis oleh sistem VOICA - Voice-Powered Financial Tracker
    </div>
</body>
</html>
