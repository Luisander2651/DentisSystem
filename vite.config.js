import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/contenido/index.js', 'resources/js/pages/dashboard.js', 'resources/js/pages/usuarios/delete-user.js', 'resources/js/pages/usuarios/edit-user.js', 'resources/js/pages/usuarios/create-user.js', 'resources/js/pages/patients/index.js', 'resources/js/pages/patients/create-patient.js', 'resources/js/pages/patients/edit-patient.js', 'resources/js/pages/patients/delete-patient.js', 'resources/js/pages/contenido/promociones/index.js', 'resources/js/pages/contenido/certificaciones/index.js', 'resources/js/pages/contenido/testimonios/index.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
            usePolling: true,
        },
        host: '0.0.0.0',
        port: 5173,      
        hmr: {
            host: 'localhost',
        },
    },
});
