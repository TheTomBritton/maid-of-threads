import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    root: __dirname,
    build: {
        outDir: resolve(__dirname, 'site/assets/dist'),
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: resolve(__dirname, 'site/assets/src/app.js'),
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
    server: {
        origin: 'http://localhost:5173',
    },
});
