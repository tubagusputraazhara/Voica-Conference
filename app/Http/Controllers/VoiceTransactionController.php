<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionService;
use App\Services\VoiceTransactionService;

class VoiceTransactionController extends Controller
{
    protected TransactionService $transactionService;
    protected VoiceTransactionService $voiceTransactionService;

    public function __construct(TransactionService $transactionService, VoiceTransactionService $voiceTransactionService)
    {
        $this->transactionService = $transactionService;
        $this->voiceTransactionService = $voiceTransactionService;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'jenis' => 'required|in:Pemasukan,Pengeluaran',
                'kategori' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'required|string',
                'budget_id' => 'nullable|exists:budget,id',
                'goal_id' => 'nullable|exists:goals,id'
            ]);

            $userId = Auth::id(); // Fixing linter 'Undefined method id.' by strictly using facade
            $transactionId = $this->transactionService->store($userId, $validated);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil disimpan',
                    'transaction_id' => $transactionId
                ]);
            }
            return redirect()->back()->with('success', 'Transaksi berhasil disimpan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'jenis' => 'required|in:Pemasukan,Pengeluaran',
                'kategori' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'required|string'
            ]);

            $this->transactionService->update($id, Auth::id(), $validated);

            return redirect()->back()->with('success', 'Transaksi berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->transactionService->destroy($id, Auth::id());
            return redirect()->back()->with('success', 'Transaksi berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function parseVoiceText(Request $request)
    {
        try {
            $text = $request->input('text');
            $data = $this->voiceTransactionService->parseTransactionText($text, Auth::id());

            return response()->json([
                'success' => true,
                'data' => $data,
                'raw_text' => $text
            ]);
        } catch (\Exception $e) {
            $code = $e->getMessage() === 'Teks kosong' ? 400 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $code);
        }
    }

    public function getBudgets()
    {
        try {
            $budgets = DB::table('budget')
                ->where('user_id', Auth::id())
                ->select('id', 'namaBudget', 'kategori', 'jumlah', 'jumlahBerjalan')
                ->get();
            return response()->json(['success' => true, 'data' => $budgets]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data budget'], 500);
        }
    }

    public function getGoals()
    {
        try {
            $goals = DB::table('goals')
                ->where('user_id', Auth::id())
                ->select('id', 'namaGoal', 'targetNominal', 'nominalBerjalan', 'tanggalTarget')
                ->get();
            return response()->json(['success' => true, 'data' => $goals]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data goals'], 500);
        }
    }

    public function transcribeAudio(Request $request)
    {
        try {
            $request->validate([
                'audio' => 'required|file|mimes:webm,wav,mp3,ogg,flac,m4a,mp4,aac,x-m4a|max:20480',
            ]);

            $result = $this->voiceTransactionService->transcribeAudio($request->file('audio'));

            return response()->json([
                'success'    => true,
                'transcript' => $result['transcript'],
                'language'   => $result['language'],
            ]);
        } catch (\Exception $e) {
            $code = str_contains($e->getMessage(), 'tidak merespons') ? 503 : 500;
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], $code);
        }
    }
}
