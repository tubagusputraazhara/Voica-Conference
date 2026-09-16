import { useState, useRef, useCallback } from 'react';

/**
 * useAudioRecorder — Callback-based (no useEffect polling)
 * 
 * Menerima `onStop` callback yang dipanggil LANGSUNG dari MediaRecorder.onstop
 * event — satu kali, deterministik, tidak ada React re-render yang bisa memicunya lagi.
 */
export const useAudioRecorder = ({ onStop } = {}) => {
    const [isRecording, setIsRecording] = useState(false);
    const [audioUrl, setAudioUrl] = useState(null);
    const mediaRecorderRef = useRef(null);
    const chunksRef = useRef([]);
    const analyserRef = useRef(null);
    const audioContextRef = useRef(null);
    const onStopRef = useRef(onStop);
    // Keep ref up to date without triggering re-render loops
    onStopRef.current = onStop;

    const startRecording = useCallback(async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const source = audioContext.createMediaStreamSource(stream);
            const analyser = audioContext.createAnalyser();
            analyser.fftSize = 256;
            analyser.smoothingTimeConstant = 0.7;
            source.connect(analyser);
            audioContextRef.current = audioContext;
            analyserRef.current = analyser;

            const mediaRecorder = new MediaRecorder(stream);
            mediaRecorderRef.current = mediaRecorder;
            chunksRef.current = [];

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) chunksRef.current.push(e.data);
            };

            mediaRecorder.onstop = () => {
                const blob = new Blob(chunksRef.current, { 
                    type: mediaRecorder.mimeType || 'audio/webm' 
                });
                
                const url = URL.createObjectURL(blob);
                setAudioUrl(url);
                
                stream.getTracks().forEach(track => track.stop());
                if (audioContextRef.current) {
                    audioContextRef.current.close();
                    audioContextRef.current = null;
                }
                analyserRef.current = null;

                // ✅ LANGSUNG panggil callback dengan blob — tidak perlu useState/useEffect
                if (onStopRef.current) {
                    onStopRef.current(blob);
                }
            };

            mediaRecorder.start();
            setIsRecording(true);
        } catch (err) {
            console.error('Error accessing microphone:', err);
            alert('Gagal mengakses mikrofon. Pastikan Anda memberikan izin.');
        }
    }, []);

    const stopRecording = useCallback(() => {
        if (mediaRecorderRef.current && isRecording) {
            mediaRecorderRef.current.stop();
            setIsRecording(false);
        }
    }, [isRecording]);

    const clearAudio = useCallback(() => {
        if (audioUrl) {
            URL.revokeObjectURL(audioUrl);
            setAudioUrl(null);
        }
    }, [audioUrl]);

    return {
        isRecording,
        audioUrl,
        analyserRef,
        startRecording,
        stopRecording,
        clearAudio
    };
};
