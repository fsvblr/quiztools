import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    build: {
        emptyOutDir: false,
        rollupOptions: {
            // https://rollupjs.org/configuration-options/
            input: {
                'css/admin.certificate.css': 'src/css/admin.certificate.css',
                'css/admin.results.css': 'src/css/admin.results.css',
                'css/quizzes.css': 'src/css/quizzes.css',
                'css/quiz.css': 'src/css/quiz.css',
                'css/results.css': 'src/css/results.css',
                'css/result.css': 'src/css/result.css',

                'js/admin.certificate.min.js': 'src/js/admin.certificate.js',
                'js/admin.results.min.js': 'src/js/admin.results.js',
                //'js/quizzes.min.js': 'src/js/quizzes.js',
                'js/quiz.min.js': 'src/js/quiz.js',
                'js/results.min.js': 'src/js/results.js',
            },
            output: {
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
                entryFileNames: '[name]',
            },
        },
        //manifest: true,
    },
})
