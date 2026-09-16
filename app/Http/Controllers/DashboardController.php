<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show dashboard with real data
     */
    public function index()
    {
        $userId = Auth::id();

        // Get current month and last month dates
        $now = \Carbon\Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Get current month stats
        $currentMonthPemasukan = DB::table('transaction')
            ->where('user_id', $userId)
            ->where('jenis', 'Pemasukan')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('jumlah');

        $currentMonthPengeluaran = DB::table('transaction')
            ->where('user_id', $userId)
            ->where('jenis', 'Pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('jumlah');

        // Get last month stats
        $lastMonthPemasukan = DB::table('transaction')
            ->where('user_id', $userId)
            ->where('jenis', 'Pemasukan')
            ->whereBetween('tanggal', [$startOfLastMonth, $endOfLastMonth])
            ->sum('jumlah');

        $lastMonthPengeluaran = DB::table('transaction')
            ->where('user_id', $userId)
            ->where('jenis', 'Pengeluaran')
            ->whereBetween('tanggal', [$startOfLastMonth, $endOfLastMonth])
            ->sum('jumlah');

        // Calculate trends (percentage change)
        $pemasukanTrend = $lastMonthPemasukan > 0
            ? round((($currentMonthPemasukan - $lastMonthPemasukan) / $lastMonthPemasukan) * 100)
            : 0;

        $pengeluaranTrend = $lastMonthPengeluaran > 0
            ? round((($currentMonthPengeluaran - $lastMonthPengeluaran) / $lastMonthPengeluaran) * 100)
            : 0;

        // Calculate Smart Analysis (Comparing expenses)
        // If current month expenditure is LOWER than last month, it's a saving.
        $savingsPercentage = 0;
        $analysisMessage = "Mulai catat transaksi Anda untuk mendapatkan analisis cerdas!";

        if ($lastMonthPengeluaran > 0) {
            if ($currentMonthPengeluaran < $lastMonthPengeluaran) {
                $savingsPercentage = round((($lastMonthPengeluaran - $currentMonthPengeluaran) / $lastMonthPengeluaran) * 100);
                $analysisMessage = "Anda hemat {$savingsPercentage}% lebih banyak dibanding bulan lalu!";
            } else {
                $increasePercentage = round((($currentMonthPengeluaran - $lastMonthPengeluaran) / $lastMonthPengeluaran) * 100);
                $analysisMessage = "Pengeluaran Anda naik {$increasePercentage}% dibanding bulan lalu. Tetap pantau budget Anda!";
            }
        } elseif ($currentMonthPengeluaran > 0) {
            $analysisMessage = "Analisis akan lebih akurat setelah data bulan depan tersedia.";
        }

        // Get total stats (all time)
        $totalPemasukan = DB::table('transaction')
            ->where('user_id', $userId)
            ->where('jenis', 'Pemasukan')
            ->sum('jumlah');

        $totalPengeluaran = DB::table('transaction')
            ->where('user_id', $userId)
            ->where('jenis', 'Pengeluaran')
            ->sum('jumlah');

        // Calculate saldo
        $saldo = $totalPemasukan - $totalPengeluaran;

        // Get all goals and calculate their progress
        // FIX: Replaced N+1 loop (1 query per goal) with a single GROUP BY aggregation query.
        $allGoals = DB::table('goals')
            ->where('user_id', $userId)
            ->get();

        $goal = null;
        $goalPercentage = 0;

        if ($allGoals->isNotEmpty()) {
            $goalIds = $allGoals->pluck('id');

            // Single aggregation query — replaces the N+1 loop
            $goalSums = DB::table('transaction')
                ->where('user_id', $userId)
                ->whereIn('goal_id', $goalIds)
                ->groupBy('goal_id')
                ->select('goal_id', DB::raw('SUM(jumlah) as total'))
                ->pluck('total', 'goal_id');

            $goalsWithProgress = $allGoals->map(function ($g) use ($goalSums) {
                $nominalBerjalan = $goalSums->get($g->id, 0);
                $g->nominalBerjalan = $nominalBerjalan;
                $g->percentage = $g->targetNominal > 0 ? ($nominalBerjalan / $g->targetNominal) * 100 : 0;
                return $g;
            });

            // Get goal with highest percentage (closest to completion)
            $goal = $goalsWithProgress->sortByDesc('percentage')->first();
            $goalPercentage = $goal ? $goal->percentage : 0;
        }

        // Get recent transactions (5 latest)
        $recentTransactions = DB::table('transaction')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get all budgets for dropdown
        $allBudgets = DB::table('budget')
            ->where('user_id', $userId)
            ->get();

        // Get all goals for dropdown
        $goals = DB::table('goals')
            ->where('user_id', $userId)
            ->get();

        return inertia('Dashboard', [
            'stats' => [
                'saldo' => $saldo,
                'totalPemasukan' => $currentMonthPemasukan, // Use current month for the cards
                'totalPengeluaran' => $currentMonthPengeluaran,
                'pemasukanTrend' => $pemasukanTrend,
                'pengeluaranTrend' => $pengeluaranTrend,
            ],
            'analysis' => [
                'title' => $lastMonthPengeluaran > 0 ? "Analisis Cerdas Sudah Siap." : "Halo! Mulailah Mencatat.",
                'percentage' => $savingsPercentage,
                'message' => $analysisMessage,
            ],
            'goal' => $goal,
            'goalPercentage' => $goalPercentage,
            'recentTransactions' => $recentTransactions,
            'budgets' => $allBudgets,
            'goals' => $goals,
        ]);
    }
}
