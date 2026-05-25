import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Roboto', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Brand palette — ámbar como color primario de marca
                'accent-blue':    '#f59e0b',   // ámbar (ex cyan #00C4FF)
                'accent-amber':   '#f59e0b',   // alias semántico
                'accent-green':   '#10b981',   // emerald
                'accent-gray':    '#374151',
                'primary-dark':   '#111318',
                'secondary-dark': '#1c1f26',
                'text-primary':   '#FFFFFF',
                'text-secondary': '#94a3b8',
                'background':     '#0b0c10',
            },
        },
    },

    plugins: [forms],
};
