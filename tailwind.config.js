/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './site/templates/**/*.php',
        './site/assets/src/**/*.{js,css}',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#fdf8f6',
                    100: '#f8ede8',
                    200: '#f0d9cf',
                    300: '#e4bfad',
                    400: '#d4a089',
                    500: '#c0826a',
                    600: '#a6664e',
                    700: '#8a5040',
                    800: '#724438',
                    900: '#613c33',
                },
                surface: {
                    50: '#fafaf9',
                    100: '#f5f5f4',
                    200: '#e7e5e4',
                    300: '#d6d3d1',
                    400: '#a8a29e',
                    500: '#78716c',
                    600: '#57534e',
                    700: '#44403c',
                    800: '#292524',
                    900: '#1c1917',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                display: ['Playfair Display', 'Georgia', 'serif'],
            },
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
    ],
};
