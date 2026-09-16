import { useState, useRef, useCallback, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { useAudioRecorder } from './useAudioRecorder';
import { transcribeAudioOffline, parseVoiceTransaction } from '../api/voiceApi';

/**
 * useVoiceCommand — Callback-based architecture
 * 
 * TIDAK ada useEffect yang memantau audioBlob.
 * Processing dipicu langsung dari onStop callback MediaRecorder.
 * Ini mengeliminasi SEMUA kemungkinan loop dari React re-render.
 */

const NAVIGATION_ROUTES = [
    { path: '/dashboard', label: 'Dashboard', keywords: ['dashboard', 'dasbor', 'beranda', 'home', 'imah'] },
    { path: '/budgeting', label: 'Anggaran',  keywords: ['anggaran', 'budget', 'budgeting', 'keuangan', 'finance', 'duit', 'artos'] },
    { path: '/goals',     label: 'Target',    keywords: ['target', 'goals', 'goal', 'tujuan', 'sasaran', 'udagan'] },
    { path: '/laporan',   label: 'Laporan',   keywords: ['laporan', 'report', 'reports', 'statistik', 'statistics'] },
    { path: '/settings',  label: 'Pengaturan',keywords: ['pengaturan', 'settings', 'setting', 'setelan', 'profil', 'profile', 'pangaturan'] },
];

const NAV_PREFIXES = [
    'buka', 'ke', 'pergi ke', 'tampilkan', 'lihat',
    'open', 'go to', 'navigate to', 'show', 'take me to',
    'ka', 'tempo', 'tingali', 'lebet', 'asup',
];

function classifyIntent(transcript) {
    const text = transcript.toLowerCase().trim();
    for (const prefix of NAV_PREFIXES) {
        if (text.startsWith(prefix + ' ')) {
            const target = text.slice(prefix.length).trim();
            for (const route of NAVIGATION_ROUTES) {
                for (const keyword of route.keywords) {
                    if (target.includes(keyword)) {
                        return { type: 'navigate', path: route.path, label: route.label };
                    }
                }
            }
        }
    }
    return { type: 'transaction', text: transcript };
}

export function useVoiceCommand({ onTransactionParsed, onTransactionError } = {}) {
    const [feedback, setFeedback] = useState(null);
    const [isProcessing, setIsProcessing] = useState(false);
    const [transcript, setTranscript] = useState('');
    const feedbackTimerRef = useRef(null);
    
    // Lock ref — mencegah double-processing jika ada race condition
    const processingLockRef = useRef(false);
    
    // Simpan callback di ref agar onStop closure tidak stale
    const onTransactionParsedRef = useRef(onTransactionParsed);
    const onTransactionErrorRef = useRef(onTransactionError);
    onTransactionParsedRef.current = onTransactionParsed;
    onTransactionErrorRef.current = onTransactionError;

    const showFeedback = useCallback((type, message, duration = 3000) => {
        if (feedbackTimerRef.current) clearTimeout(feedbackTimerRef.current);
        setFeedback({ type, message });
        if (duration > 0) {
            feedbackTimerRef.current = setTimeout(() => setFeedback(null), duration);
        }
    }, []);

    const clearFeedback = useCallback(() => {
        if (feedbackTimerRef.current) clearTimeout(feedbackTimerRef.current);
        setFeedback(null);
    }, []);

    // ── Callback dipanggil LANGSUNG dari MediaRecorder.onstop ────────
    // Tidak melalui useState/useEffect — hanya sekali, deterministik.
    const handleBlobReady = useCallback(async (blob) => {
        // Guard: Jika sudah ada proses lain yang jalan, abaikan
        if (processingLockRef.current) {
            console.log('[Voice] Blob received but processing lock is active. Ignoring.');
            return;
        }
        if (!blob || blob.size === 0) {
            showFeedback('error', 'Tidak ada audio yang direkam.');
            return;
        }

        processingLockRef.current = true;
        setIsProcessing(true);
        showFeedback('processing', 'Mentranskripsi suara...', 0);

        try {
            // ── Step 1: Transcribe ──────────────────────────────────
            const transcribeRes = await transcribeAudioOffline(blob);

            if (!transcribeRes.success) {
                showFeedback('error', transcribeRes.error || 'Gagal transkripsi.');
                return;
            }

            const text = (transcribeRes.transcript || '').trim();
            if (!text) {
                showFeedback('error', 'Suara tidak terdeteksi. Coba lebih jelas.');
                return;
            }

            setTranscript(text);

            // ── Step 2: Classify & Act ──────────────────────────────
            const intent = classifyIntent(text);

            if (intent.type === 'navigate') {
                showFeedback('success', `Membuka ${intent.label}...`);
                setTimeout(() => router.visit(intent.path), 600);
                return;
            }

            // Transaction flow
            showFeedback('processing', 'Memproses transaksi...', 0);

            const parseRes = await parseVoiceTransaction(intent.text);
            if (parseRes.success) {
                showFeedback('success', 'Transaksi terdeteksi!');
                onTransactionParsedRef.current?.(parseRes.data);
            } else {
                showFeedback('error', parseRes.message || 'Gagal memproses transaksi.');
                onTransactionErrorRef.current?.(parseRes.message);
            }

        } catch (err) {
            console.error('[Voice] Error:', err);
            if (err.code === 'ERR_NETWORK') {
                showFeedback('error', 'Server AI tidak aktif. Jalankan start-fastapi.bat.');
            } else {
                showFeedback('error', 'Gagal memproses suara.');
            }
            onTransactionErrorRef.current?.(err.message);
        } finally {
            setIsProcessing(false);
            processingLockRef.current = false;
        }
    }, [showFeedback]);

    const {
        isRecording,
        audioUrl,
        analyserRef,
        startRecording,
        stopRecording,
        clearAudio,
    } = useAudioRecorder({ onStop: handleBlobReady });

    const startListening = useCallback(async () => {
        if (isRecording) {
            stopRecording();
            return;
        }
        if (processingLockRef.current) return;

        clearAudio();
        setTranscript('');
        showFeedback('listening', 'Mendengarkan...', 0);
        await startRecording();
    }, [isRecording, startRecording, stopRecording, clearAudio, showFeedback]);

    const stopListening = useCallback(() => {
        if (isRecording) stopRecording();
    }, [isRecording, stopRecording]);

    useEffect(() => {
        return () => {
            if (feedbackTimerRef.current) clearTimeout(feedbackTimerRef.current);
        };
    }, []);

    return {
        isListening: isRecording,
        isSupported: true,
        isProcessing,
        transcript,
        audioUrl,
        feedback,
        startListening,
        stopListening,
        clearFeedback,
    };
}
