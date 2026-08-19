import defaultTheme from 'tailwindcss/defaultTheme';
import preset from './vendor/filament/filament/tailwind.config.preset'


/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],

    input: [
        'resources/css/app.css',
        'resources/css/filament/admin/theme.css',
        'resources/css/filament/admin/ar-invoice.css',
        'resources/js/app.js',
    ],
    plugins: [],
};
