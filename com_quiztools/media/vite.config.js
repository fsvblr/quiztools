import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    build: {
        emptyOutDir: true,
        rollupOptions: {
            // https://rollupjs.org/configuration-options/
            input: {
                'css/admin.certificate.css': 'src/css/admin.certificate.css',
                'css/admin.results.css': 'src/css/admin.results.css',
                'css/admin.orders.css': 'src/css/admin.orders.css',
                'css/admin.order.css': 'src/css/admin.order.css',
                'css/lpaths.css': 'src/css/lpaths.css',
                'css/lpath.css': 'src/css/lpath.css',
                'css/quizzes.css': 'src/css/quizzes.css',
                'css/quiz.css': 'src/css/quiz.css',
                'css/results.css': 'src/css/results.css',
                'css/result.css': 'src/css/result.css',
                'css/orders.css': 'src/css/orders.css',

                'js/admin.certificate.min.js': 'src/js/admin.certificate.js',
                'js/admin.results.min.js': 'src/js/admin.results.js',
                'js/admin.subscription.min.js': 'src/js/admin.subscription.js',
                'js/lpath.min.js': 'src/js/lpath.js',
                'js/quiz.min.js': 'src/js/quiz.js',
                'js/results.min.js': 'src/js/results.js',
            },
            output: {
                entryFileNames: '[name]',
                assetFileNames: ({ name }) => {
                    /*if (/\.(gif|jpe?g|png|svg)$/.test(name ?? "")) {
                        return "images/[name]-[hash][extname]";
                    }
                    if (/\.(ttf|otf|fnt|woff|woff2|eot)$/.test(name ?? "")) {
                        return "fonts/[name]-[hash][extname]";
                    }*/
                    if (/\.css$/.test(name ?? "")) {
                        return "[name].min[extname]";
                    }
                    return "assets/[name]-[hash][extname]";
                },
                chunkFileNames: 'js/[name].min.js',
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor'
                    }
                    if (id.includes('src/js/components/Preloader.vue')) {
                        return 'vendor'
                    }
                },
            },
        },
        minify: 'esbuild',  // default
        cssMinify: true,    // default
        //manifest: true,
    },
})
