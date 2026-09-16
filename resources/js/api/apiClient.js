import axios from 'axios';

/**
 * Base API Client for Voica Laravel Backend
 * All generic API calls (fetching budgets, goals, etc) should use this.
 */
const apiClient = axios.create({
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    }
});

// Anda bisa menambahkan interceptors di sini (contoh: untuk handling token kedaluwarsa dsb)
apiClient.interceptors.response.use(
    response => response,
    error => {
        // Log generic API errors or handle global 401s
        console.error('API Error:', error.response?.data || error.message);
        return Promise.reject(error);
    }
);

export default apiClient;
