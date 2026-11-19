<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /** Render a URL as a standalone SVG QR code (dark modules on white — scans reliably when printed). */
    public function svg(string $content, int $size = 360): string
    {
        $style = new RendererStyle($size, 2, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(7, 9, 12)));
        $writer = new Writer(new ImageRenderer($style, new SvgImageBackEnd()));

        return $writer->writeString($content);
    }
}
