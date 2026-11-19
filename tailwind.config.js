const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: {
                    950: '#080B11',
                    900: '#151A23',
                    850: '#10141C',
                    800: '#1B212C',
                    700: '#262E3B',
                    600: '#374152',
                },
                ember: {
                    300: '#F0915A',
                    400: '#E57A3C',
                    500: '#DF6826',
                    600: '#B9521A',
                },
                volt: {
                    300: '#FFE14D',
                    400: '#FFD400',
                    500: '#E6BF00',
                    600: '#B39500',
                },
            },
            boxShadow: {
                glow: '0 0 0 1px rgba(255,212,0,.15), 0 10px 40px -10px rgba(255,212,0,.35)',
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
