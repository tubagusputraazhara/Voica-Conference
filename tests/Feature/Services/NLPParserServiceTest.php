<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Services\NLPParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NLPParserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NLPParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NLPParserService();
    }

    public function test_can_parse_simple_pengeluaran()
    {
        $text = "Beli nasi goreng 25 ribu";

        $result = $this->service->parse($text);

        $this->assertTrue($result['success'] ?? true);
        $this->assertEquals('Pengeluaran', $result['jenis']);
        $this->assertEquals('Makanan', $result['kategori']);
        $this->assertEquals(25000, $result['jumlah']);
        $this->assertStringContainsString('Nasi', $result['keterangan'] ?? $text);
    }

    public function test_can_parse_pemasukan()
    {
        $text = "Dapat gaji 5 juta";

        $result = $this->service->parse($text);

        $this->assertTrue($result['success'] ?? true);
        $this->assertEquals('Pemasukan', $result['jenis']);
        $this->assertEquals('Gaji', $result['kategori']);
        $this->assertEquals(5000000, $result['jumlah']);
    }

    public function test_can_parse_mixed_number_formats()
    {
        $text = "Bayar kos seratus lima puluh ribu";

        $result = $this->service->parse($text);

        $this->assertTrue($result['success'] ?? true);
        $this->assertEquals('Pengeluaran', $result['jenis']);
        $this->assertEquals('Kebutuhan', $result['kategori']);
        $this->assertEquals(150000, $result['jumlah']);
    }

    // ─── Sundanese Priangan Tests ─────────────────────────────────────

    public function test_can_parse_sundanese_pengeluaran()
    {
        $text = "meuli sangu 25 rebu";

        $result = $this->service->parse($text);

        $this->assertTrue($result['success'] ?? true);
        $this->assertEquals('Pengeluaran', $result['jenis']);
        $this->assertEquals('Makanan', $result['kategori']);
        $this->assertEquals(25000, $result['jumlah']);
    }

    public function test_can_parse_sundanese_pemasukan()
    {
        $text = "meunang gajih 5 juta";

        $result = $this->service->parse($text);

        $this->assertTrue($result['success'] ?? true);
        $this->assertEquals('Pemasukan', $result['jenis']);
        $this->assertEquals('Gaji', $result['kategori']);
        $this->assertEquals(5000000, $result['jumlah']);
    }

    public function test_can_parse_sundanese_number_words()
    {
        $text = "mayar kos sarebu";

        $result = $this->service->parse($text);

        $this->assertTrue($result['success'] ?? true);
        $this->assertEquals('Pengeluaran', $result['jenis']);
        $this->assertEquals(1000, $result['jumlah']);
    }
}
