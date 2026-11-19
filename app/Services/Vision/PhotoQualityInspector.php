<?php

namespace App\Services\Vision;

/**
 * Server-side computer-vision checks on a progress photo, run before anything is stored
 * or sent to the AI. Works on a 256px greyscale copy:
 *  - brightness: mean luma (0-255)
 *  - contrast:   luma standard deviation
 *  - sharpness:  variance of the Laplacian (low = blurry)
 * Hard failures reject the upload; softer issues are stored as warnings on the scan
 * and shown to the member so the next scan is more consistent.
 */
class PhotoQualityInspector
{
    public const MIN_SHORT_EDGE = 480;

    /** @param resource|\GdImage $image */
    public function inspect($image, string $view): array
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $scale = 256 / max($width, $height);
        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));
        $small = imagecreatetruecolor($w, $h);
        imagecopyresampled($small, $image, 0, 0, 0, 0, $w, $h, $width, $height);

        $luma = [];
        $sum = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $l = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
                $luma[$y][$x] = $l;
                $sum += $l;
            }
        }
        imagedestroy($small);

        $n = $w * $h;
        $mean = $sum / $n;
        $var = 0;
        foreach ($luma as $row) {
            foreach ($row as $l) {
                $var += ($l - $mean) ** 2;
            }
        }
        $contrast = sqrt($var / $n);

        // Laplacian variance (4-neighbour kernel).
        $lap = [];
        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                $lap[] = $luma[$y - 1][$x] + $luma[$y + 1][$x] + $luma[$y][$x - 1] + $luma[$y][$x + 1] - 4 * $luma[$y][$x];
            }
        }
        $lapMean = $lap ? array_sum($lap) / count($lap) : 0;
        $sharpness = 0;
        foreach ($lap as $v) {
            $sharpness += ($v - $lapMean) ** 2;
        }
        $sharpness = $lap ? $sharpness / count($lap) : 0;

        $errors = [];
        $warnings = [];

        if (min($width, $height) < self::MIN_SHORT_EDGE) {
            $errors[] = 'The photo resolution is too low. Use your phone camera directly rather than a screenshot.';
        }
        if ($mean < 35) {
            $errors[] = 'The photo is too dark to analyse. Turn on more lights or face a window.';
        } elseif ($mean > 235) {
            $errors[] = 'The photo is overexposed. Avoid pointing the camera at a bright window or flash.';
        }
        if ($contrast < 12) {
            $errors[] = 'The photo looks blank or uniform. Make sure your full body is in frame.';
        }

        if (! $errors) {
            if ($mean < 70) {
                $warnings[] = 'Lighting is a little dark.';
            }
            if ($sharpness < 25) {
                $warnings[] = 'The photo may be slightly blurry. Hold the phone steady or use a timer.';
            }
            if ($width > $height) {
                $warnings[] = 'Use portrait orientation so your whole body fills the frame.';
            }
        }

        return [
            'view' => $view,
            'width' => $width,
            'height' => $height,
            'brightness' => round($mean, 1),
            'contrast' => round($contrast, 1),
            'sharpness' => round($sharpness, 1),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
