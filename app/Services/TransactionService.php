<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Store a new transaction
     */
    public function store(int $userId, array $data): int
    {
        DB::beginTransaction();
        try {
            $transactionId = DB::table('transaction')->insertGetId([
                'user_id' => $userId,
                'tanggal' => now()->format('Y-m-d'),
                'jenis' => $data['jenis'],
                'kategori' => $data['kategori'],
                'jumlah' => $data['jumlah'],
                'keterangan' => $data['keterangan'],
                'budget_id' => $data['budget_id'] ?? null,
                'goal_id' => $data['goal_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->applyBudgetGoal($data['jumlah'], $data['budget_id'] ?? null, $data['goal_id'] ?? null);

            DB::commit();
            return $transactionId;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TransactionService store error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing transaction
     */
    public function update(int $id, int $userId, array $data): bool
    {
        $transaction = DB::table('transaction')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$transaction) {
            throw new \Exception('Transaksi tidak ditemukan');
        }

        DB::beginTransaction();
        try {
            // Revert old amounts
            $this->applyBudgetGoal(-$transaction->jumlah, $transaction->budget_id, $transaction->goal_id);

            // Update transaction
            DB::table('transaction')
                ->where('id', $id)
                ->update([
                    'tanggal' => $data['tanggal'],
                    'jenis' => $data['jenis'],
                    'kategori' => $data['kategori'],
                    'jumlah' => $data['jumlah'],
                    'keterangan' => $data['keterangan'],
                    'updated_at' => now()
                ]);

            // Apply new amounts
            $this->applyBudgetGoal($data['jumlah'], $transaction->budget_id, $transaction->goal_id);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TransactionService update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a transaction
     */
    public function destroy(int $id, int $userId): bool
    {
        $transaction = DB::table('transaction')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$transaction) {
            throw new \Exception('Transaksi tidak ditemukan');
        }

        DB::beginTransaction();
        try {
            // Revert amounts
            $this->applyBudgetGoal(-$transaction->jumlah, $transaction->budget_id, $transaction->goal_id);

            // Delete transaction
            DB::table('transaction')->where('id', $id)->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TransactionService destroy error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Apply or revert budget and goal amounts
     */
    protected function applyBudgetGoal(float $jumlah, ?int $budgetId, ?int $goalId): void
    {
        if ($budgetId) {
            $budget = DB::table('budget')->where('id', $budgetId)->first();
            if ($budget) {
                DB::table('budget')
                    ->where('id', $budgetId)
                    ->update([
                        'jumlahBerjalan' => $budget->jumlahBerjalan + $jumlah,
                        'updated_at' => now()
                    ]);
            }
        }

        if ($goalId) {
            $goal = DB::table('goals')->where('id', $goalId)->first();
            if ($goal) {
                DB::table('goals')
                    ->where('id', $goalId)
                    ->update([
                        'nominalBerjalan' => $goal->nominalBerjalan + $jumlah,
                        'updated_at' => now()
                    ]);
            }
        }
    }
}
