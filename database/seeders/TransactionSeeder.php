<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = DB::table('users')->first()->id ?? 1;

        // Clear existing transactions to have a clean "real" state
        DB::table('transaction')->truncate();

        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // ---------------------------------------------------------
        // LAST MONTH TRANSACTIONS (Higher Expenses)
        // ---------------------------------------------------------

        // Income last month
        DB::table('transaction')->insert([
            'user_id' => $userId,
            'jenis' => 'Pemasukan',
            'kategori' => 'Gaji',
            'jumlah' => 10000000,
            'keterangan' => 'Gaji Bulan Lalu',
            'tanggal' => $lastMonth->copy()->startOfMonth()->addDays(1),
            'created_at' => $lastMonth->copy()->startOfMonth()->addDays(1),
        ]);

        // Expenses last month (Total: 6,000,000)
        $expensesLastMonth = [
            ['Makan', 2000000, 'Belanja Bulanan'],
            ['Transportasi', 1000000, 'Bensin & Tol'],
            ['Hiburan', 2000000, 'Nonton & Jalan'],
            ['Tagihan', 1000000, 'Listrik & WiFi'],
        ];

        foreach ($expensesLastMonth as $item) {
            DB::table('transaction')->insert([
                'user_id' => $userId,
                'jenis' => 'Pengeluaran',
                'kategori' => $item[0],
                'jumlah' => $item[1],
                'keterangan' => $item[2],
                'tanggal' => $lastMonth->copy()->startOfMonth()->addDays(rand(2, 28)),
                'created_at' => $lastMonth->copy()->startOfMonth()->addDays(rand(2, 28)),
            ]);
        }

        // ---------------------------------------------------------
        // THIS MONTH TRANSACTIONS (Lower Expenses -> Savings!)
        // ---------------------------------------------------------

        // Income this month
        DB::table('transaction')->insert([
            'user_id' => $userId,
            'jenis' => 'Pemasukan',
            'kategori' => 'Gaji',
            'jumlah' => 10000000,
            'keterangan' => 'Gaji Bulan Ini',
            'tanggal' => $now->copy()->startOfMonth()->addDays(1),
            'created_at' => $now->copy()->startOfMonth()->addDays(1),
        ]);

        // Expenses this month (Total: 4,000,000 -> 33% Savings)
        $expensesThisMonth = [
            ['Makan', 1500000, 'Masak sendiri lebih hemat'],
            ['Transportasi', 800000, 'Naik MRT'],
            ['Hiburan', 700000, 'Netflix saja'],
            ['Tagihan', 1000000, 'Listrik & WiFi'],
        ];

        foreach ($expensesThisMonth as $item) {
            DB::table('transaction')->insert([
                'user_id' => $userId,
                'jenis' => 'Pengeluaran',
                'kategori' => $item[0],
                'jumlah' => $item[1],
                'keterangan' => $item[2],
                'tanggal' => $now->copy()->startOfMonth()->addDays(rand(2, 18)),
                'created_at' => $now->copy()->startOfMonth()->addDays(rand(2, 18)),
            ]);
        }
    }
}
