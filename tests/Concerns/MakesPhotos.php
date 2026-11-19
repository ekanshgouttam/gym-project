<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

/** Generates realistic test photos (mid brightness, texture, unique bytes) that pass the vision checks. */
trait MakesPhotos
{
    private static $photoSeed = 0;

    protected function photo(string $name = 'photo.jpg', int $w = 600, int $h = 800, int $brightness = 130): UploadedFile
    {
        mt_srand(++self::$photoSeed);
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, $brightness, $brightness, $brightness));
        for ($i = 0; $i < 400; $i++) {
            $c = max(0, min(255, $brightness + mt_rand(-90, 90)));
            imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(8, 60), mt_rand(8, 60), imagecolorallocate($img, $c, $c, $c));
        }
        ob_start();
        imagejpeg($img, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    protected function scanPhotos(): array
    {
        return ['front' => $this->photo('front.jpg'), 'side' => $this->photo('side.jpg'), 'back' => $this->photo('back.jpg')];
    }
}
