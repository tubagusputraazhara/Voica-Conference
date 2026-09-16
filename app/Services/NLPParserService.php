<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NLPParserService
{
    /**
     * Parse voice text into transaction data
     */
    public function parse(string $text): array
    {
        if (empty(trim($text))) {
            return ['success' => false, 'error' => 'Teks kosong'];
        }

        $lowerText = strtolower($text);

        $data = [
            'success' => true,
            'jenis' => 'Pengeluaran', // Default
            'kategori' => 'Lainnya',
            'jumlah' => 0,
            'keterangan' => ucfirst($text),
            'budget_allocation' => null,
            'goal_allocation' => null
        ];

        $data['jumlah'] = $this->detectJumlah($lowerText);
        $data['jenis'] = $this->detectJenis($lowerText);
        $data['kategori'] = $this->detectKategori($lowerText);

        $this->applySmartDetection($lowerText, $data);

        $data['keterangan'] = $this->cleanDescription($text, $lowerText);

        return $data;
    }

    protected function detectJenis(string $lowerText): string
    {
        $pemasukanKeywords = [
            // Indonesian
            'dapat',
            'terima',
            'gaji',
            'masuk',
            'jual',
            'income',
            'tunjangan',
            'bonus',
            'thr',
            // Sunda Priangan
            'meunang',
            'narima',
            'nampa',
            'gajih',
            'asup',
            'panghasilan',
            'honor',
            'katarima',
            'dibayar',
            'honorarium',
            'hasil'
        ];
        foreach ($pemasukanKeywords as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return 'Pemasukan';
            }
        }
        return 'Pengeluaran';
    }

    protected function detectKategori(string $lowerText): string
    {
        $kategoriMap = [
            'Makanan' => [
                // Indonesian
                'makan',
                'minum',
                'nasi',
                'kopi',
                'snack',
                'jajan',
                'warteg',
                'restoran',
                'cafe',
                'roti',
                'mie',
                'bakso',
                'soto',
                'ayam',
                'sate',
                'nasdang',
                'naspad',
                'nasi padang',
                'mcd',
                'kfc',
                'mekdi',
                'ricebowl',
                'geprek',
                'ngopi',
                'nongkrong',
                // Sunda Priangan
                'dahar',
                'nginum',
                'sangu',
                'kueh',
                'jajanan',
                'tuang',
                'neda',
                'lauk',
                'sayur',
                'baso',
                'peuyeum',
                'surabi',
                'lotek',
                'kupat tahu',
                'ngadahar',
                'emihan',
                'bubur',
                'cilor',
                'batagor',
                'siomay',
                'combro',
                'misro',
                'cireng',
                'gehu',
                'cilok',
                'seblak',
                'kadaharan'
            ],
            'Transportasi' => [
                // Indonesian
                'bensin',
                'parkir',
                'grab',
                'gojek',
                'tol',
                'angkot',
                'kereta',
                'bus',
                'ojek',
                'taksi',
                'uber',
                'bengkel',
                'transport',
                'ojol',
                'goceng parkir',
                'gocar',
                'grabcar',
                'grabbike',
                // Sunda Priangan
                'ojeg',
                'ongkos',
                'kareta',
                'elf',
                'angkutan',
                'naek',
                'ngongkos',
                'tutumpakan'
            ],
            'Belanja' => [
                // Indonesian
                'beli',
                'belanja',
                'supermarket',
                'indomaret',
                'alfamart',
                'pasar',
                'mall',
                'shopee',
                'tokopedia',
                'baju',
                'celana',
                'skincare',
                'kosmetik',
                'tokped',
                'lazada',
                'olshop',
                'online shop',
                'beli baju',
                'shopping',
                // Sunda Priangan
                'meuli',
                'balanja',
                'pameuli',
                'meser',
                'ngaborong',
                'acuk',
                'pakean',
                'barang'
            ],
            'Hiburan' => [
                // Indonesian
                'nonton',
                'bioskop',
                'game',
                'main',
                'spotify',
                'netflix',
                'youtube premium',
                'konser',
                'netflik',
                'ngefilm',
                'ngegame',
                'mlbb',
                'mobile legend',
                'pubg',
                'steam',
                // Sunda Priangan
                'lalajo',
                'ulin',
                'maen',
                'hiburan',
                'nongton',
                'ngalalajokeun',
                'heureuy',
                'senang-senang',
                'cacandakan'
            ],
            'Jalan-Jalan' => [
                // Indonesian
                'liburan',
                'wisata',
                'jalan-jalan',
                'traveling',
                'trip',
                'vacation',
                'hotel',
                'penginapan',
                'jalan',
                'jalan jalan',
                'piknik',
                'refreshing',
                'staycation',
                // Sunda Priangan
                'pelesir',
                'pakanci',
                'laleumpang',
                'arulin',
                'jalan-jalan',
                'ngadon ulin',
                'plesiran',
                'pesiar'
            ],
            'Kebutuhan' => [
                // Indonesian
                'kos',
                'sewa',
                'kontrak',
                'galon',
                'gas',
                // Sunda Priangan
                'sewa',
                'kontrakan',
                'kabutuhan',
                'kaperluan',
                'pangabutuh'
            ],
            'Tagihan' => [
                // Indonesian
                'listrik',
                'air',
                'internet',
                'wifi',
                'pulsa',
                'paket data',
                'pln',
                'pdam',
                'bpjs',
                'asuransi',
                'cicilan',
                'kredit',
                'pinjaman',
                'bayar listrik',
                'token listrik',
                'beli pulsa',
                'isi pulsa',
                'kuota',
                // Sunda Priangan
                'cai',
                'mayar',
                'cicilan',
                'hutang',
                'tagihan',
                'ngabayar',
                'duit bulanan',
                'iuran'
            ],
            'Kesehatan' => [
                // Indonesian
                'obat',
                'dokter',
                'rumah sakit',
                'klinik',
                'vitamin',
                'masker',
                'medical',
                'checkup',
                'lab',
                'ke dokter',
                'berobat',
                'beli obat',
                'apotek',
                // Sunda Priangan
                'ubar',
                'tatamba',
                'ka dokter',
                'gering',
                'udur',
                'teu damang',
                'puskesmas'
            ],
            'Pendidikan' => [
                // Indonesian
                'buku',
                'sekolah',
                'kursus',
                'kuliah',
                'spp',
                'les',
                'seminar',
                'workshop',
                'training',
                'bayar spp',
                'beli buku',
                'kursus online',
                'udemy',
                'coursera',
                // Sunda Priangan
                'sakola',
                'diajar',
                'pangajaran',
                'buku',
                'atikan',
                'dialajar',
                'ngajar',
                'kursus'
            ],
            'Sedekah' => [
                // Indonesian
                'sedekah',
                'infaq',
                'zakat',
                'donasi',
                'sumbangan',
                'amal',
                'charity',
                'nyumbang',
                'derma',
                'bantuan',
                // Sunda Priangan
                'sodakoh',
                'sidekah',
                'nyumbang',
                'infak',
                'mere',
                'masihan',
                'nyumbangkeun',
                'amal'
            ],
            'Tabungan' => [
                // Indonesian
                'nabung',
                'tabung',
                'saving',
                'simpan',
                'investasi',
                // Sunda Priangan
                'nyimpen',
                'nabung',
                'simpen',
                'sisihkeun',
                'neundeun',
                'duit simpen'
            ],
            'Gaji' => [
                // Indonesian
                'gaji',
                'salary',
                'upah',
                'honor',
                // Sunda Priangan
                'gajih',
                'buruhan',
                'panghasilan',
                'bayaran'
            ],
            'Bonus' => ['bonus', 'thr', 'insentif', 'komisi'],
            'Penjualan' => [
                // Indonesian
                'jual',
                'penjualan',
                'sales',
                'omzet',
                // Sunda Priangan
                'ngajual',
                'jualan',
                'dagangan',
                'ngadagangkeun'
            ]
        ];

        foreach ($kategoriMap as $kategori => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($lowerText, $keyword) !== false) {
                    return $kategori;
                }
            }
        }
        return 'Lainnya';
    }

    protected function applySmartDetection(string $lowerText, array &$data): void
    {
        // Try getting budget and goal names from text
        $patterns = [
            // Indonesian
            '/(?:untuk|ke|masuk(?:kan)?)\s+budget\s+(\w+)/i',
            '/(?:untuk|ke|masuk(?:kan)?)\s+anggaran\s+(\w+)/i',
            '/budget\s+(\w+)/i',
            // Sunda Priangan
            '/(?:pikeun|ka|asup(?:keun)?)\s+budget\s+(\w+)/i',
            '/(?:pikeun|ka|asup(?:keun)?)\s+anggaran\s+(\w+)/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $lowerText, $matches)) {
                $data['budget_allocation'] = $matches[1];
                $data['jenis'] = 'Pengeluaran';
                break;
            }
        }

        $goalPatterns = [
            // Indonesian
            '/(?:untuk|ke|masuk(?:kan)?)\s+tabungan\s+(\w+)/i',
            '/(?:untuk|ke|masuk(?:kan)?)\s+nabung\s+(\w+)/i',
            '/(?:untuk|ke|masuk(?:kan)?)\s+goal\s+(\w+)/i',
            '/(?:untuk|ke|masuk(?:kan)?)\s+tujuan\s+(\w+)/i',
            '/tabungan\s+(\w+)/i',
            // Sunda Priangan
            '/(?:pikeun|ka|asup(?:keun)?)\s+tabungan\s+(\w+)/i',
            '/(?:pikeun|ka|asup(?:keun)?)\s+nyimpen\s+(\w+)/i',
            '/(?:pikeun|ka|asup(?:keun)?)\s+neundeun\s+(\w+)/i',
            '/(?:pikeun|ka)\s+udagan\s+(\w+)/i',
        ];
        foreach ($goalPatterns as $pattern) {
            if (preg_match($pattern, $lowerText, $matches)) {
                $data['goal_allocation'] = $matches[1];
                $data['jenis'] = 'Pengeluaran';
                $data['kategori'] = 'Tabungan';
                break;
            }
        }

        // Fixup for Pemasukan with Lainnya category
        if ($data['jenis'] === 'Pemasukan' && $data['kategori'] === 'Lainnya') {
            // Indonesian
            if (strpos($lowerText, 'gaji') !== false) $data['kategori'] = 'Gaji';
            elseif (strpos($lowerText, 'bonus') !== false) $data['kategori'] = 'Bonus';
            elseif (strpos($lowerText, 'jual') !== false) $data['kategori'] = 'Penjualan';
            // Sunda Priangan
            elseif (strpos($lowerText, 'gajih') !== false) $data['kategori'] = 'Gaji';
            elseif (strpos($lowerText, 'buruhan') !== false) $data['kategori'] = 'Gaji';
            elseif (strpos($lowerText, 'ngajual') !== false) $data['kategori'] = 'Penjualan';
        }
    }

    protected function detectJumlah(string $text): float
    {
        $jumlah = 0;
        if (preg_match('/(\d+(?:[\.,]\d+)*)\s*(ribu|rebu|juta|rb|jt|k|m|rp|rupiah)?/i', $text, $matches)) {
            $modifier = strtolower($matches[2] ?? '');
            $cleanNumStr = str_replace(',', '.', $matches[1]);
            $baseNumber = floatval(preg_replace('/[.,]/', '', $matches[1]));

            if (in_array($modifier, ['ribu', 'rebu', 'rb', 'k'])) {
                $baseNumber *= 1000;
            } elseif (in_array($modifier, ['juta', 'jt', 'm'])) {
                $baseNumber *= 1000000;
            }
            $jumlah = $baseNumber;
        }

        if ($jumlah == 0) {
            $jumlah = $this->terbilangKeAngka($text);
        }
        return $jumlah;
    }

    protected function terbilangKeAngka(string $text): float
    {
        $angkaMap = [
            // Indonesian
            'nol' => 0,
            'satu' => 1,
            'dua' => 2,
            'tiga' => 3,
            'empat' => 4,
            'lima' => 5,
            'enam' => 6,
            'tujuh' => 7,
            'delapan' => 8,
            'sembilan' => 9,
            'sepuluh' => 10,
            'sebelas' => 11,
            'belas' => 10,
            'puluh' => 10,
            'ratus' => 100,
            'ribu' => 1000,
            'juta' => 1000000,
            'miliar' => 1000000000,
            'triliun' => 1000000000000,
            // Sunda Priangan
            'hiji' => 1,
            'tilu' => 3,
            'opat' => 4,
            'genep' => 6,
            'dalapan' => 8,
            'salapan' => 9,
            'sapuluh' => 10,
            'sabelas' => 11,
            'rebu' => 1000,
        ];

        $total = 0;
        $currentSegment = 0;
        $currentVal = 0;

        $text = str_replace(
            ['seribu', 'seratus', 'sepuluh', 'sebelas', 'sarebu', 'saratus', 'sapuluh', 'sabelas'],
            ['satu ribu', 'satu ratus', 'satu puluh', 'satu belas', 'hiji rebu', 'hiji ratus', 'hiji puluh', 'hiji belas'],
            strtolower($text)
        );
        $words = explode(' ', $text);

        foreach ($words as $word) {
            if (!isset($angkaMap[$word])) continue;
            $nilai = $angkaMap[$word];

            if ($nilai >= 1000) {
                $total += ($currentSegment + $currentVal) * $nilai;
                $currentSegment = 0;
                $currentVal = 0;
            } elseif ($nilai >= 100) {
                $currentSegment += ($currentVal !== 0 ? $currentVal : 1) * $nilai;
                $currentVal = 0;
            } elseif ($word === 'puluh') {
                $currentSegment += ($currentVal !== 0 ? $currentVal : 1) * 10;
                $currentVal = 0;
            } elseif ($word === 'belas') {
                $currentSegment += $currentVal + 10;
                $currentVal = 0;
            } else {
                $currentVal += $nilai;
            }
        }
        return $total + $currentSegment + $currentVal;
    }

    protected function cleanDescription(string $originalText, string $lowerText): string
    {
        $wordsToRemove = [
            // Indonesian
            'rp',
            'rupiah',
            'idr',
            'pengeluaran',
            'keluar',
            'biaya',
            'bayar',
            'beli',
            'pemasukan',
            'masuk',
            'pendapatan',
            'dapat',
            'terima',
            'catat',
            'tolong',
            'untuk',
            'sebesar',
            'ribu',
            'juta',
            'miliar',
            'rb',
            'jt',
            'k',
            'm',
            // Sunda Priangan
            'meuli',
            'mayar',
            'kaluar',
            'asup',
            'narima',
            'nampa',
            'meunang',
            'pikeun',
            'saharga',
            'rebu',
            'catetkeun',
            'mangga',
            'tulung',
            'catet'
        ];
        $angkaWords = [
            // Indonesian
            'nol',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
            'belas',
            'puluh',
            'ratus',
            'ribu',
            'juta',
            'miliar',
            'triliun',
            'seribu',
            'seratus',
            // Sunda Priangan
            'hiji',
            'tilu',
            'opat',
            'genep',
            'dalapan',
            'salapan',
            'sapuluh',
            'sabelas',
            'rebu',
            'sarebu',
            'saratus'
        ];

        $words = explode(' ', $lowerText);
        $originalWords = explode(' ', $originalText);
        $cleanWords = [];

        for ($i = 0; $i < count($words); $i++) {
            $word = $words[$i];
            if (preg_match('/\d/', $word)) continue;
            if (in_array($word, $angkaWords)) continue;
            if (in_array($word, $wordsToRemove)) continue;
            $cleanWords[] = $originalWords[$i];
        }

        $cleanDescription = trim(implode(' ', $cleanWords));
        return empty($cleanDescription) ? ucfirst($originalText) : ucfirst($cleanDescription);
    }
}
