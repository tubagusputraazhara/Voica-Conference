import axios from 'axios';
import apiClient from './apiClient';

/**
 * Dedicated API module for Voice features.
 */

// ── 1. FastAPI (Offline STT engine)
const fastApiUrl = '/api/voice/transcribe'; // Currently proxied via Laravel in web.php or direct via NGINX/Apache if configured

/**
 * Transcribes audio blob using Faster Whisper running on FastAPI
 * @param {Blob} audioBlob 
 * @returns {Promise<Object>} e.g. { success: true, transcript: "text" }
 */
export const transcribeAudioOffline = async (audioBlob) => {
    const formData = new FormData();
    formData.append('audio', audioBlob, 'voice.webm');

    const response = await axios.post(fastApiUrl, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    
    return response.data;
};

// ── 2. Laravel Backend (NLP Parser & Executions)

/**
 * Sends text to backend to be parsed into a structured financial transaction.
 * @param {string} text 
 * @returns {Promise<Object>}
 */
export const parseVoiceTransaction = async (text) => {
    const response = await apiClient.post('/api/parse-voice-text', { text });
    return response.data;
};

/**
 * Saves the finalized voice transaction to the database.
 * @param {Object} transactionData 
 * @returns {Promise<Object>}
 */
export const submitVoiceTransaction = async (transactionData) => {
    const response = await apiClient.post('/api/voice-transaction', transactionData);
    return response.data;
};

/**
 * Generic data fetcher for Modal drop-downs
 */
export const fetchTransactionModalDependencies = async () => {
    const [budgetsRes, goalsRes] = await Promise.all([
        apiClient.get('/api/budgets'),
        apiClient.get('/api/goals'),
    ]);
    
    return {
        budgets: budgetsRes.data?.data || budgetsRes.data || [],
        goals: goalsRes.data?.data || goalsRes.data || [],
    };
};
