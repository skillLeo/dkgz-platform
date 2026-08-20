import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

/** Builds the page-render smoke test; see tests/Js/render-entry.js. */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            // The real client needs a browser Inertia context; the stub gives
            // the pages the four things they actually call.
            '@inertiajs/vue3': fileURLToPath(new URL('./tests/Js/inertia-stub.js', import.meta.url)),
        },
    },
    build: {
        ssr: 'tests/Js/render-entry.js',
        outDir: 'storage/framework/testing',
        emptyOutDir: false,
        rollupOptions: { output: { entryFileNames: 'render-entry.mjs' } },
    },
})
