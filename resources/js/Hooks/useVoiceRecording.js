import { useState, useEffect, useRef } from 'react';

export const useVoiceRecording = (options = {}) => {
    const { 
        onResult, 
        onError, 
        onStart, 
        onEnd,
        language = 'id-ID' 
    } = options;

    const [isRecording, setIsRecording] = useState(false);
    const [transcript, setTranscript] = useState('');
    const recognitionRef = useRef(null);

    useEffect(() => {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            const recognition = new SpeechRecognition();
            recognition.lang = language;
            recognition.continuous = false;
            recognition.interimResults = false;

            recognition.onstart = () => {
                setIsRecording(true);
                if (onStart) onStart();
            };

            recognition.onresult = (event) => {
                const text = event.results[0][0].transcript;
                setTranscript(text);
                if (onResult) onResult(text);
            };

            recognition.onerror = (event) => {
                if (onError) onError(event.error);
                setIsRecording(false);
            };

            recognition.onend = () => {
                setIsRecording(false);
                if (onEnd) onEnd();
            };

            recognitionRef.current = recognition;
        }
    }, [language]);

    const startRecording = () => {
        if (recognitionRef.current && !isRecording) {
            try {
                recognitionRef.current.start();
            } catch (e) {
                console.error('Speech recognition start error:', e);
            }
        }
    };

    const stopRecording = () => {
        if (recognitionRef.current && isRecording) {
            recognitionRef.current.stop();
        }
    };

    const toggleRecording = () => {
        if (isRecording) stopRecording();
        else startRecording();
    };

    return {
        isRecording,
        transcript,
        startRecording,
        stopRecording,
        toggleRecording,
        isSupported: !!(window.SpeechRecognition || window.webkitSpeechRecognition)
    };
};
