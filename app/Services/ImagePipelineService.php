<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns one uploaded photo into the full set of sizes a marketplace needs —
 * exactly the pattern Peter described (Airbnb/Facebook style): the professional
 * uploads a normal photo and the system auto-generates every crop.
 *
 * Uses native GD (no extra composer dependency). Center "cover" crop by default;
 * an optional focal point (0..1, 0..1) lets the pro pick what stays in frame,
 * which is the pragmatic stand-in for smart AI cropping.
 */
class ImagePipelineService
{
    /** Target sizes [width, height] keyed by role. */
    public const SIZES = [
        'square' => [300, 300],   // avatar / thumbnail
        'hero'   => [640, 360],   // search-card hero (16:9, retina-friendly)
        'banner' => [1600, 500],  // profile cover banner
        'mobile' => [360, 220],   // mobile thumbnail
    ];

    /**
     * Process an uploaded image into all sizes under storage/app/public/$dir.
     *
     * @return array{original:string,square:string,hero:string,banner:string,mobile:string}|array{}
     */
    public function process(UploadedFile $file, string $dir, float $focalX = 0.5, float $focalY = 0.5): array
    {
        $src = $this->load($file->getRealPath(), $file->getClientMimeType());
        if (!$src) {
            return [];
        }

        Storage::disk('public')->makeDirectory($dir);
        $base = trim($dir, '/') . '/' . Str::uuid()->toString();

        $out = ['original' => $this->fit($src, $base . '_orig.jpg', 1920, 1920)];
        foreach (self::SIZES as $key => [$w, $h]) {
            $out[$key] = $this->coverCrop($src, $base . '_' . $key . '.jpg', $w, $h, $focalX, $focalY);
        }

        imagedestroy($src);
        return $out;
    }

    /**
     * Re-crop an existing item from its stored original using a new focal point
     * (the "professional chooses cover position" flow). Keeps the original,
     * replaces the derived crops.
     *
     * @return array the updated item (with new crop paths + focal_x/focal_y)
     */
    public function reprocess(array $item, string $dir, float $fx, float $fy): array
    {
        if (empty($item['original']) || ! Storage::disk('public')->exists($item['original'])) {
            return $item;
        }
        $src = @imagecreatefromjpeg(Storage::disk('public')->path($item['original']));
        if (! $src) {
            return $item;
        }

        foreach (array_keys(self::SIZES) as $k) {
            if (! empty($item[$k])) {
                Storage::disk('public')->delete($item[$k]);
            }
        }

        Storage::disk('public')->makeDirectory($dir);
        $base = trim($dir, '/') . '/' . Str::uuid()->toString();
        $out = $item;
        foreach (self::SIZES as $key => [$w, $h]) {
            $out[$key] = $this->coverCrop($src, $base . '_' . $key . '.jpg', $w, $h, $fx, $fy);
        }
        $out['focal_x'] = round($fx, 4);
        $out['focal_y'] = round($fy, 4);
        imagedestroy($src);

        return $out;
    }

    /** Delete every generated size for a portfolio item. */
    public function delete(array $item): void
    {
        foreach (['original', 'square', 'hero', 'banner', 'mobile'] as $k) {
            if (!empty($item[$k])) {
                Storage::disk('public')->delete($item[$k]);
            }
        }
    }

    private function load(string $path, ?string $mime)
    {
        return match (true) {
            str_contains((string) $mime, 'png')  => @imagecreatefrompng($path),
            str_contains((string) $mime, 'webp') => @imagecreatefromwebp($path),
            str_contains((string) $mime, 'gif')  => @imagecreatefromgif($path),
            default                              => @imagecreatefromjpeg($path),
        } ?: null;
    }

    /** Cover-crop to exactly {tw}×{th}, keeping the focal point in frame. */
    private function coverCrop($src, string $relPath, int $tw, int $th, float $fx, float $fy): string
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = max($tw / $sw, $th / $sh);
        $cropW = (int) round($tw / $scale);
        $cropH = (int) round($th / $scale);
        $srcX = (int) max(0, min($sw - $cropW, round(($sw * $fx) - ($cropW / 2))));
        $srcY = (int) max(0, min($sh - $cropH, round(($sh * $fy) - ($cropH / 2))));

        $dst = imagecreatetruecolor($tw, $th);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $tw, $th, $cropW, $cropH);
        $this->save($dst, $relPath);
        imagedestroy($dst);
        return $relPath;
    }

    /** Resize to fit within {maxW}×{maxH} (no crop). */
    private function fit($src, string $relPath, int $maxW, int $maxH): string
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = min(1, $maxW / $sw, $maxH / $sh);
        $w = (int) round($sw * $scale);
        $h = (int) round($sh * $scale);
        $dst = imagecreatetruecolor($w, $h);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
        $this->save($dst, $relPath);
        imagedestroy($dst);
        return $relPath;
    }

    private function save($img, string $relPath): void
    {
        ob_start();
        imagejpeg($img, null, 82);
        Storage::disk('public')->put($relPath, ob_get_clean());
    }
}
