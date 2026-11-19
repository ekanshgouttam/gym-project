const mix = require('laravel-mix');

// Admin panels, member app and FlexFit marketing pages.
mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        require('tailwindcss'),
        require('autoprefixer'),
    ])
    // Premium gym website at "/" (GSAP smooth scroll + Three.js showcase, split from the panels).
    .js('resources/js/site.js', 'public/js')
    .postCss('resources/css/site.css', 'public/css', [
        require('tailwindcss'),
        require('autoprefixer'),
    ])
    .webpackConfig({
        // Lazy chunks (Three.js scenes, join form) land next to app.js.
        output: { chunkFilename: 'js/[name].js?id=[chunkhash]' },
    })
    .version();
