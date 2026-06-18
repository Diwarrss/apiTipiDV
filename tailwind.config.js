import defaultTheme from 'tailwindcss/defaultTheme';

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
                sans: ['system-ui', 'Segoe UI', 'Roboto', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                tipidv: {
                    orange: '#f26c20',
                    'orange-dark': '#d95a14',
                    green: '#247a2b',
                },
            },
        },
    },
    plugins: [],
};
