import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

// No `fonts:` entry and no bunny()/google font helper: IBM Plex is self-hosted
// through @fontsource and imported from resources/css/app.css. Any CDN font
// request would transmit visitor IPs to a third party (GDPR / German case law).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    build: {
        // Assets are built locally and uploaded; public/build is committed.
        // Pages are split automatically by the import.meta.glob in app.js;
        // this pins the shared runtime into one long-cached vendor chunk.
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (/[\\/]node_modules[\\/](vue|@vue|@inertiajs|pinia)[\\/]/.test(id)) {
                            return 'vendor';
                        }
                        if (id.includes('@fontsource')) return 'fonts';
                    }
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
