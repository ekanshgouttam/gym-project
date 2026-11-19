<?php

namespace App\Services;

use App\Services\Vision\PhotoQualityInspector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores user photos on the private "local" disk (storage/app, never web-served directly).
 * Display copies are decoded and re-encoded as JPEG, which strips EXIF/GPS metadata and
 * caps their size. Scan photos also keep the untouched original file, byte for byte.
 */
class ImageStore
{
    public function storePrivate(UploadedFile $file, string $directory, int $maxEdge = 1600, string $field = 'photo'): string
    {
        $source = $this->decode($file, $field);
        $path = $this->writeJpeg($source, $directory, $maxEdge);
        imagedestroy($source);

        return $path;
    }

    /**
     * Inspect, then store a progress photo: the original as uploaded plus a normalised display copy.
     *
     * @return array{display_path: string, original: array, quality: array}
     */
    public function storeScanPhoto(UploadedFile $file, string $directory, string $view, PhotoQualityInspector $inspector): array
    {
        $source = $this->decode($file, $view);
        $quality = $inspector->inspect($source, $view);

        if ($quality['errors']) {
            imagedestroy($source);
            throw ValidationException::withMessages([$view => ucfirst($view) . ' photo: ' . $quality['errors'][0]]);
        }

        $bytes = (string) file_get_contents($file->getRealPath());
        $mime = $file->getMimeType();
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'bin';
        $originalPath = trim($directory, '/') . '/originals/' . Str::uuid() . '.' . $ext;
        Storage::disk('local')->put($originalPath, $bytes);

        $displayPath = $this->writeJpeg($source, $directory, 1568);
        imagedestroy($source);

        return [
            'display_path' => $displayPath,
            'original' => ['path' => $originalPath, 'sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes), 'mime' => $mime],
            'quality' => $quality,
        ];
    }

    /** Gym logos: re-encoded as PNG (keeps transparency, strips metadata), max 512px. */
    public function storeLogo(UploadedFile $file, string $directory = 'gym-logos', int $maxEdge = 512): string
    {
        $source = $this->decode($file, 'logo');
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxEdge / max($width, $height));
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newW, $newH);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($source);

        ob_start();
        imagepng($canvas, null, 6);
        $png = ob_get_clean();
        imagedestroy($canvas);

        $path = trim($directory, '/') . '/' . Str::uuid() . '.png';
        Storage::disk('local')->put($path, $png);

        return $path;
    }

    /**
     * Website photo uploaded by a gym owner: re-encoded as WebP (strips metadata), max 1920px,
     * saved under site/{gymId}/. Returns the file name (served by the site.media route).
     */
    public function storeSiteImage(UploadedFile $file, int $gymId, string $field): string
    {
        $source = $this->decode($file, $field);
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 1920 / max($width, $height));
        $canvas = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, imagesx($canvas), imagesy($canvas), $width, $height);
        imagedestroy($source);

        ob_start();
        imagewebp($canvas, null, 72);
        $webp = ob_get_clean();
        imagedestroy($canvas);

        $name = Str::random(20) . '.webp';
        Storage::disk('local')->put("site/{$gymId}/{$name}", $webp);

        return $name;
    }

    public function deleteSiteFile(int $gymId, ?string $name): void
    {
        if ($name) {
            Storage::disk('local')->delete("site/{$gymId}/" . basename($name));
        }
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function decode(UploadedFile $file, string $field)
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw ValidationException::withMessages([$field => 'That image could not be read. Please upload a JPG, PNG or WebP photo.']);
        }

        return $source;
    }

    private function writeJpeg($source, string $directory, int $maxEdge): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxEdge / max($width, $height));
        $newW = (int) round($width * $scale);
        $newH = (int) round($height * $scale);

        $canvas = imagecreatetruecolor($newW, $newH);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255)); // flatten transparency
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($canvas);

        $path = trim($directory, '/') . '/' . Str::uuid() . '.jpg';
        Storage::disk('local')->put($path, $jpeg);

        return $path;
    }
}
