<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip tests if DB driver is SQLite and it has issues with DB::statement in migrations
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Skipping database tests for SQLite due to raw DB statements in migrations.');
        }

        $this->service = new TransactionService();
        $this->user = User::factory()->create();
    }

    public function test_can_store_transaction()
    {
        $data = [
            'jenis' => 'Pengeluaran',
            'kategori' => 'Makanan',
            'jumlah' => 50000,
            'keterangan' => 'Beli nasi padang',
            'budget_id' => null,
            'goal_id' => null,
        ];

        $transactionId = $this->service->store($this->user->id, $data);

        $this->assertDatabaseHas('transaction', [
            'id' => $transactionId,
            'user_id' => $this->user->id,
            'jenis' => 'Pengeluaran',
            'kategori' => 'Makanan',
            'jumlah' => 50000,
        ]);
    }

    public function test_can_update_transaction()
    {
        $transactionId = DB::table('transaction')->insertGetId([
            'user_id' => $this->user->id,
            'tanggal' => now()->format('Y-m-d'),
            'jenis' => 'Pengeluaran',
            'kategori' => 'Makanan',
            'jumlah' => 20000,
            'keterangan' => 'Beli kopi',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $updateData = [
            'tanggal' => now()->format('Y-m-d'),
            'jenis' => 'Pengeluaran',
            'kategori' => 'Transportasi',
            'jumlah' => 30000,
            'keterangan' => 'Bensin motor',
            'budget_id' => null,
            'goal_id' => null
        ];

        $result = $this->service->update($transactionId, $this->user->id, $updateData);

        $this->assertTrue($result);
        $this->assertDatabaseHas('transaction', [
            'id' => $transactionId,
            'kategori' => 'Transportasi',
            'jumlah' => 30000
        ]);
    }

    public function test_can_delete_transaction()
    {
        $transactionId = DB::table('transaction')->insertGetId([
            'user_id' => $this->user->id,
            'tanggal' => now()->format('Y-m-d'),
            'jenis' => 'Pengeluaran',
            'kategori' => 'Minuman',
            'jumlah' => 15000,
            'keterangan' => 'Es teh',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $result = $this->service->destroy($transactionId, $this->user->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('transaction', [
            'id' => $transactionId
        ]);
    }
}
