<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\NLPParserService;

class VoiceTransactionService
{
    protected NLPParserService $nlpParserService;

    public function __construct(NLPParserService $nlpParserService)
    {
        $this->nlpParserService = $nlpParserService;
    }

    /**
     * Resolves text string to parsed financial attributes including Database IDs
     */
    public function parseTransactionText(string $text, int $userId): array
    {
        if (empty($text)) {
            throw new \Exception('Teks kosong');
        }

        $parsedData = $this->nlpParserService->parse($text);

        if (!$parsedData['success']) {
            throw new \Exception($parsedData['error'] ?? 'Gagal mem-parsing teks');
        }

        $data = [
            'jenis' => $parsedData['jenis'],
            'kategori' => $parsedData['kategori'],
            'jumlah' => $parsedData['jumlah'],
            'keterangan' => $parsedData['keterangan'],
            'budget_id' => null,
            'goal_id' => null,
            'budget_name' => $parsedData['budget_allocation'],
            'goal_name' => $parsedData['goal_allocation']
        ];

        // Resolve IDs
        if ($data['budget_name']) {
            $budget = DB::table('budget')
                ->where('user_id', $userId)
                ->where('namaBudget', 'LIKE', '%' . $data['budget_name'] . '%')
                ->first();
            if ($budget) $data['budget_id'] = $budget->id;
        }

        if ($data['goal_name']) {
            $goal = DB::table('goals')
                ->where('user_id', $userId)
                ->where('namaGoal', 'LIKE', '%' . $data['goal_name'] . '%')
                ->first();
            if ($goal) $data['goal_id'] = $goal->id;
        }

        return $data;
    }

    /**
     * Transcribes audio using remote/local Python FastAPI service
     */
    public function transcribeAudio($audioFile): array
    {
        $fastapiUrl = rtrim(config('voice.api_url', 'http://127.0.0.1:8026'), '/');

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(30)
                ->attach(
                    'audio',
                    file_get_contents($audioFile->getRealPath()),
                    $audioFile->getClientOriginalName() ?: 'voice.webm'
                )
                ->post($fastapiUrl . '/transcribe-upload');

            if ($response->failed()) {
                Log::warning('[Voice Transcribe] FastAPI returned error: ' . $response->body());
                throw new \Exception('FastAPI server tidak merespons. Pastikan start-fastapi.bat sudah berjalan.');
            }

            $result = $response->json();

            if (!($result['success'] ?? false) || empty($result['transcript'])) {
                throw new \Exception($result['error'] ?? 'Suara tidak terdeteksi. Silakan coba lagi.');
            }

            Log::info('[Voice Transcribe] Transcript: ' . $result['transcript']);

            return [
                'transcript' => $result['transcript'],
                'language'   => $result['language'] ?? 'id',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[Voice Transcribe] FastAPI connection refused: ' . $e->getMessage());
            throw new \Exception('Server AI lokal tidak berjalan. Jalankan scripts/start-fastapi.bat terlebih dahulu.');
        } catch (\Exception $e) {
            Log::error('[Voice Transcribe] Unexpected error: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
