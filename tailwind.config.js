const defaultTheme = require('tailwindcss/defaultTheme');
const twColors = require('tailwindcss/colors');
const plugin = require('tailwindcss/plugin');

// Panel colours are CSS variables ("r g b") so a gym's branding can override them at runtime
// (App\Support\Branding sets --c-volt-* / --c-ember-* on <html>; .theme-light swaps the neutrals).
const ink = { 950: '#080B11', 900: '#151A23', 850: '#10141C', 800: '#1B212C', 700: '#262E3B', 600: '#374152' };
const ember = { 300: '#F0915A', 400: '#E57A3C', 500: '#DF6826', 600: '#B9521A' };
const volt = { 300: '#FFE14D', 400: '#FFD400', 500: '#E6BF00', 600: '#B39500' };
const neutrals = ['slate', 'zinc'];
const statuses = ['emerald', 'rose', 'amber', 'sky', 'fuchsia'];
const scale = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

const channels = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(' ');
const themed = (name, shades) => Object.fromEntries(shades.map((s) => [s, `rgb(var(--c-${name}-${s}) / <alpha-value>)`]));
const vars = (name, palette) => Object.fromEntries(Object.entries(palette).map(([s, hex]) => [`--c-${name}-${s}`, channels(hex)]));

const defaults = {
    '--c-white': channels('#FFFFFF'),
    '--c-on-volt': channels(ink[950]),
    '--c-on-ember': channels(ink[950]),
    ...vars('ink', ink), ...vars('volt', volt), ...vars('ember', ember),
    ...Object.assign({}, ...[...neutrals, ...statuses].map((n) => vars(n, twColors[n]))),
};

// Light theme: light surfaces, dark text. Neutral greys are mirrored (slate-200 ↔ slate-800 …) and
// the pale status shades used for text on dark backgrounds become their darker counterparts.
const light = {
    'color-scheme': 'light',
    '--c-white': channels('#0B1220'),
    ...vars('ink', { 950: '#F3F5F8', 900: '#FFFFFF', 850: '#F8FAFC', 800: '#EDF0F4', 700: '#E1E5EB', 600: '#C9D0DA' }),
    ...Object.assign({}, ...neutrals.map((n) => vars(n, Object.fromEntries(scale.map((s, i) => [s, twColors[n][scale[scale.length - 1 - i]]]))))),
    ...Object.assign({}, ...statuses.map((n) => vars(n, { 100: twColors[n][900], 200: twColors[n][800], 300: twColors[n][700], 400: twColors[n][600] }))),
};

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
                white: 'rgb(var(--c-white) / <alpha-value>)',
                ...Object.fromEntries([...neutrals, ...statuses].map((n) => [n, themed(n, scale)])),
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
                // Panels: ink = surfaces, volt = brand primary, ember = brand accent.
                ink: themed('ink', Object.keys(ink)),
                ember: themed('ember', Object.keys(ember)),
                volt: themed('volt', Object.keys(volt)),
                'on-volt': 'rgb(var(--c-on-volt) / <alpha-value>)',
                'on-ember': 'rgb(var(--c-on-ember) / <alpha-value>)',
            },
            boxShadow: {
                glow: '0 0 0 1px rgb(var(--c-volt-400) / .15), 0 10px 40px -10px rgb(var(--c-volt-400) / .35)',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        plugin(({ addBase }) => addBase({ ':root': defaults, '.theme-light': light })),
    ],
};
