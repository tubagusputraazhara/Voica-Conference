import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: '10.190.219.183',
        },
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: [
                '**/datasets/**',
                '**/pretrained_models/**',
                '**/storage/**',
                '**/vendor/**',
                '**/node_modules/**',
                '**/ffmpeg/**',
                '**/.git/**',
                '**/.venv/**',
            ],
        },
    },
});
