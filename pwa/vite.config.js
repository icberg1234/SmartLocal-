import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
    // Dev: proxy API calls to the local Laravel backend (php artisan serve :8000)
    // so the PWA can use relative /api/v1 with no CORS.
    server: {
        proxy: {
            '/api': { target: 'http://127.0.0.1:8088', changeOrigin: true },
        },
    },
    plugins: [
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            manifest: {
                name: 'SmartLocal',
                short_name: 'SmartLocal',
                lang: 'fa',
                dir: 'rtl',
                theme_color: '#1565C0',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/',
                icons: [],
            },
        }),
    ],
    test: {
        environment: 'happy-dom',
    },
})
