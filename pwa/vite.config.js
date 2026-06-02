import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
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
