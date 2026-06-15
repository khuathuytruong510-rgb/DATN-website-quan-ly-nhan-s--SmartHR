import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            '/': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: false,
                xfwd: true,
                secure: false,
                bypass: (req) => {
                    if (req.url.startsWith('/@vite') || req.url.startsWith('/resources') || req.url.startsWith('/node_modules') || req.url.startsWith('/favicon.ico') || req.url.startsWith('/robots.txt')) {
                        return req.url;
                    }
                },
                configure: (proxy) => {
                    proxy.on('proxyRes', (proxyRes, req, res) => {
                        const location = proxyRes.headers['location'];
                        if (location) {
                            res.setHeader('location', location.replace('http://127.0.0.1:8000', 'http://127.0.0.1:5173'));
                        }
                    });
                },
            },
        },
    },
});
