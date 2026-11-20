<?php

namespace App\Support;

use App\Models\Gym;

/**
 * A gym's brand: primary colour, accent colour, light/dark theme (logo lives on gyms.logo_path).
 * Stored in gyms.settings['branding']. The panels read colours from CSS variables
 * (see tailwind.config.js), so applying a brand just means overriding those variables.
 */
class Branding
{
    public const DEFAULTS = ['primary' => '#FFD400', 'accent' => '#DF6826', 'theme' => 'dark'];
    public const THEMES = ['dark' => 'Dark', 'light' => 'Light'];

    /** Page backgrounds the brand colours must stay readable on. */
    private const BACKGROUNDS = ['dark' => '#080B11', 'light' => '#FFFFFF'];
    private const DARK_TEXT = '#080B11';
    private const LIGHT_TEXT = '#FFFFFF';
    private const MIN_CONTRAST = 3.0;

    public static function for(?Gym $gym): array
    {
        $saved = $gym ? (array) $gym->setting('branding') : [];

        return array_merge(self::DEFAULTS, array_intersect_key($saved, self::DEFAULTS));
    }

    /** Inline style for <html>: overrides the default primary (volt) and accent (ember) palettes. */
    public static function cssVariables(array $brand): string
    {
        $vars = [];
        foreach (['volt' => $brand['primary'], 'ember' => $brand['accent']] as $name => $hex) {
            $base = self::readable($hex, self::BACKGROUNDS[$brand['theme']]);
            $shades = [
                300 => self::mix($base, '#FFFFFF', 0.25),
                400 => $base,
                500 => self::mix($base, '#000000', 0.12),
                600 => self::mix($base, '#000000', 0.3),
            ];
            foreach ($shades as $shade => $color) {
                $vars["--c-{$name}-{$shade}"] = self::channels($color);
            }
            $vars["--c-on-{$name}"] = self::channels(self::textOn($base));
        }

        return collect($vars)->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ');
    }

    /** Brand for the mobile app: the colours it should use as-is, plus readable text colours for them. */
    public static function forApi(Gym $gym): array
    {
        $brand = self::for($gym);
        $primary = self::readable($brand['primary'], self::BACKGROUNDS[$brand['theme']]);
        $accent = self::readable($brand['accent'], self::BACKGROUNDS[$brand['theme']]);

        return [
            'theme' => $brand['theme'],
            'primary' => $primary,
            'on_primary' => self::textOn($primary),
            'accent' => $accent,
            'on_accent' => self::textOn($accent),
            'logo_url' => $gym->logoUrl(),
        ];
    }

    /** Darken (light theme) or lighten (dark theme) a colour until it stands out from the background. */
    public static function readable(string $hex, string $background): string
    {
        $towards = self::luminance($background) > 0.5 ? '#000000' : '#FFFFFF';
        $color = strtoupper($hex);
        for ($i = 0; $i < 20 && self::contrast($color, $background) < self::MIN_CONTRAST; $i++) {
            $color = self::mix($color, $towards, 0.1);
        }

        return $color;
    }

    public static function textOn(string $hex): string
    {
        return self::contrast($hex, self::DARK_TEXT) >= self::contrast($hex, self::LIGHT_TEXT) ? self::DARK_TEXT : self::LIGHT_TEXT;
    }

    public static function contrast(string $a, string $b): float
    {
        [$hi, $lo] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $c = array_map(function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private static function mix(string $hex, string $with, float $amount): string
    {
        $a = self::rgb($hex);
        $b = self::rgb($with);

        return sprintf('#%02X%02X%02X', ...array_map(fn ($i) => (int) round($a[$i] + ($b[$i] - $a[$i]) * $amount), [0, 1, 2]));
    }

    private static function rgb(string $hex): array
    {
        return array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    }

    private static function channels(string $hex): string
    {
        return implode(' ', self::rgb($hex));
    }
}
