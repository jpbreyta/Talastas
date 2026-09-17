import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/gis.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        target: 'es2020',
        sourcemap: false,
        cssCodeSplit: true,
        chunkSizeWarningLimit: 750,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return undefined;
                    }

                    if (id.includes('leaflet') || id.includes('@geoman-io')) {
                        return 'vendor-leaflet';
                    }

                    if (id.includes('@turf')) {
                        return 'vendor-turf';
                    }

                    if (id.includes('axios')) {
                        return 'vendor-http';
                    }

                    return 'vendor';
                },
            },
        },
    },
});
