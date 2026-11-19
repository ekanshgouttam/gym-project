const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    // Grid classes the gym website builds from data counts.
    safelist: ['xl:grid-cols-2', 'xl:grid-cols-3', 'xl:grid-cols-4', 'lg:col-span-3', 'lg:col-span-4', 'lg:col-span-7'],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
                // Gym website (resources/views/site): condensed, poster-style headlines.
                brand: ['Anton', 'Impact', '"Arial Narrow"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Gym website palette: deep black / charcoal with an electric orange-red accent.
                coal: {
                    950: '#050505',
                    900: '#0B0B0C',
                    850: '#111113',
                    800: '#18181B',
                    700: '#242428',
                    600: '#34343A',
                    500: '#5B5B63',
                },
                blaze: {
                    300: '#FF8E61',
                    400: '#FF6B2E',
                    500: '#FF4D12',
                    600: '#D63B05',
                },
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
