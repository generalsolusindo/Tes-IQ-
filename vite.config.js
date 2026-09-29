import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        watch: {
            // vendor/storage hold PHP dependencies and runtime files Vite
            // never needs to hot-reload — watching them anyway is what
            // blows past the OS's inotify watch limit on this machine.
            ignored: ['**/vendor/**', '**/storage/**'],
        },
    },
});

