<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class LaporanController extends Controller
{
    /**
     * Display laporan page
     */
    public function index()
    {
        $user = Auth::user();

        // Get year range for dropdown (from first transaction to 10 years ahead)
        $firstTransaction = DB::table('transaction')
            ->where('user_id', $user->id)
            ->orderBy('tanggal', 'asc')
            ->first();

        $startYear = $firstTransaction ? Carbon::parse($firstTransaction->tanggal)->year : date('Y');
        $endYear = date('Y') + 10;

        $years = range($startYear, $endYear);

        // Get all budgets for voice modal dropdown
        $allBudgets = DB::table('budget')
            ->where('user_id', $user->id)
            ->select('id', 'namaBudget', 'kategori')
            ->get();

        // Get all goals for voice modal dropdown
        $goals = DB::table('goals')
            ->where('user_id', $user->id)
            ->select('id', 'namaGoal')
            ->get();

        return inertia('Laporan', [
            'years' => $years,
            'allBudgets' => $allBudgets,
            'goals' => $goals
        ]);
    }

    /**
     * Get filtered transactions
     */
    public function getTransactions(Request $request)
    {
        $user = Auth::user();

        $bulanDari = $request->input('bulan_dari');
        $bulanSampai = $request->input('bulan_sampai');
        $tahunDari = $request->input('tahun_dari');
        $tahunSampai = $request->input('tahun_sampai');

        // Build query
        $query = DB::table('transaction')->where('user_id', $user->id);

        // Apply date range filter
        if ($bulanDari && $tahunDari && $bulanSampai && $tahunSampai) {
            $startDate = Carbon::create($tahunDari, $bulanDari, 1)->startOfMonth();
            $endDate = Carbon::create($tahunSampai, $bulanSampai, 1)->endOfMonth();

            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        // Get transactions ordered by date
        // LIMIT: Safety cap at 500 rows to prevent PHP memory exhaustion on large datasets.
        // The running_balance calculation is now done client-side from this dataset.
        $transactions = $query->orderBy('tanggal', 'asc')->limit(500)->get();

        // Calculate running balance
        $runningBalance = 0;
        $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->jenis === 'Pemasukan') {
                $runningBalance += $transaction->jumlah;
            } else {
                $runningBalance -= $transaction->jumlah;
            }

            return [
                'id' => $transaction->id,
                'tanggal' => Carbon::parse($transaction->tanggal)->format('d F Y'),
                'jenis' => $transaction->jenis,
                'kategori' => $transaction->kategori,
                'jumlah' => $transaction->jumlah,
                'jumlah_formatted' => 'Rp ' . number_format($transaction->jumlah, 0, ',', '.'),
                'saldo' => $runningBalance,
                'saldo_formatted' => 'Rp ' . number_format($runningBalance, 0, ',', '.'),
                'keterangan' => $transaction->keterangan,
            ];
        });

        // Calculate summary
        $totalPemasukan = $transactions->where('jenis', 'Pemasukan')->sum('jumlah');
        $totalPengeluaran = $transactions->where('jenis', 'Pengeluaran')->sum('jumlah');
        $saldoAkhir = $totalPemasukan - $totalPengeluaran;

        // Calculate chart data (Group by date)
        $chartData = $transactions->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m-d');
        })->map(function ($group, $date) {
            return [
                'date' => $date,
                'pemasukan' => $group->where('jenis', 'Pemasukan')->sum('jumlah'),
                'pengeluaran' => $group->where('jenis', 'Pengeluaran')->sum('jumlah'),
            ];
        })->values();

        // Calculate category data (Group by category)
        $categoryData = $transactions->where('jenis', 'Pengeluaran')->groupBy('kategori')
            ->map(function ($group, $category) {
                return [
                    'name' => $category,
                    'value' => $group->sum('jumlah')
                ];
            })->values();

        return response()->json([
            'success' => true,
            'transactions' => $transactionsWithBalance,
            'summary' => [
                'total_pemasukan' => $totalPemasukan,
                'total_pemasukan_formatted' => 'Rp ' . number_format($totalPemasukan, 0, ',', '.'),
                'total_pengeluaran' => $totalPengeluaran,
                'total_pengeluaran_formatted' => 'Rp ' . number_format($totalPengeluaran, 0, ',', '.'),
                'saldo_akhir' => $saldoAkhir,
                'saldo_akhir_formatted' => 'Rp ' . number_format($saldoAkhir, 0, ',', '.'),
            ],
            'chart_data' => $chartData,
            'category_data' => $categoryData,
            'period' => [
                'start' => $bulanDari && $tahunDari ? $this->getMonthName($bulanDari) . ' ' . $tahunDari : null,
                'end' => $bulanSampai && $tahunSampai ? $this->getMonthName($bulanSampai) . ' ' . $tahunSampai : null,
            ]
        ]);
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        $user = Auth::user();

        $bulanDari = $request->input('bulan_dari');
        $bulanSampai = $request->input('bulan_sampai');
        $tahunDari = $request->input('tahun_dari');
        $tahunSampai = $request->input('tahun_sampai');

        // Build query
        $query = DB::table('transaction')->where('user_id', $user->id);

        // Apply date range filter
        if ($bulanDari && $tahunDari && $bulanSampai && $tahunSampai) {
            $startDate = Carbon::create($tahunDari, $bulanDari, 1)->startOfMonth();
            $endDate = Carbon::create($tahunSampai, $bulanSampai, 1)->endOfMonth();

            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        // Get transactions
        $transactions = $query->orderBy('tanggal', 'asc')->get();

        // Calculate running balance
        $runningBalance = 0;
        $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->jenis === 'Pemasukan') {
                $runningBalance += $transaction->jumlah;
            } else {
                $runningBalance -= $transaction->jumlah;
            }

            return [
                'tanggal' => Carbon::parse($transaction->tanggal)->format('d F Y'),
                'jenis' => $transaction->jenis,
                'kategori' => $transaction->kategori,
                'jumlah' => $transaction->jumlah,
                'jumlah_formatted' => 'Rp ' . number_format($transaction->jumlah, 0, ',', '.'),
                'saldo' => $runningBalance,
                'saldo_formatted' => 'Rp ' . number_format($runningBalance, 0, ',', '.'),
                'keterangan' => $transaction->keterangan,
            ];
        });

        // Calculate summary
        $totalPemasukan = $transactions->where('jenis', 'Pemasukan')->sum('jumlah');
        $totalPengeluaran = $transactions->where('jenis', 'Pengeluaran')->sum('jumlah');
        $saldoAkhir = $totalPemasukan - $totalPengeluaran;

        $summary = [
            'total_pemasukan' => 'Rp ' . number_format($totalPemasukan, 0, ',', '.'),
            'total_pengeluaran' => 'Rp ' . number_format($totalPengeluaran, 0, ',', '.'),
            'saldo_akhir' => 'Rp ' . number_format($saldoAkhir, 0, ',', '.'),
        ];

        $period = [
            'start' => $bulanDari && $tahunDari ? $this->getMonthName($bulanDari) . ' ' . $tahunDari : 'Semua',
            'end' => $bulanSampai && $tahunSampai ? $this->getMonthName($bulanSampai) . ' ' . $tahunSampai : 'Semua',
        ];

        // Generate PDF
        $pdf = Pdf::loadView('laporan-pdf', [
            'user' => $user,
            'transactions' => $transactionsWithBalance,
            'summary' => $summary,
            'period' => $period,
        ]);

        $filename = 'Laporan_' . str_replace(' ', '_', $period['start']) . '_' . str_replace(' ', '_', $period['end']) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get month name in Indonesian
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[$month] ?? '';
    }
}
