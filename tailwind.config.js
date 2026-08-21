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
            // Los valores viven en variables CSS (resources/css/app.css) en vez de estar
            // acá: así una pantalla puede redefinir la paleta con un :root local sin
            // recompilar ni afectar al resto del panel.
            //
            // El formato "rgb(var(--x) / <alpha-value>)" no es capricho: hay 344 usos con
            // opacidad (accent-blue/20, accent-gray/50…) que se romperían con un var()
            // plano, porque Tailwind necesita los componentes sueltos para inyectar el alfa.
            colors: {
                'accent-blue':    'rgb(var(--c-accent) / <alpha-value>)',
                'accent-amber':   'rgb(var(--c-accent) / <alpha-value>)',   // alias semántico
                'accent-green':   'rgb(var(--c-ok) / <alpha-value>)',
                'accent-gray':    'rgb(var(--c-line) / <alpha-value>)',
                'primary-dark':   'rgb(var(--c-elev-1) / <alpha-value>)',
                'secondary-dark': 'rgb(var(--c-elev-2) / <alpha-value>)',
                'text-primary':   'rgb(var(--c-ink) / <alpha-value>)',
                'text-secondary': 'rgb(var(--c-ink-soft) / <alpha-value>)',
                'background':     'rgb(var(--c-bg) / <alpha-value>)',

                // Semánticos: NO son de marca. Existen para que un aviso se lea como
                // aviso aunque cambie la identidad, y para no volver a tocar 119 clases
                // sueltas la próxima vez que se ajuste un color.
                'warning':        'rgb(var(--c-warn) / <alpha-value>)',
                'danger':         'rgb(var(--c-danger) / <alpha-value>)',
            },
        },
    },

    plugins: [forms],
};
