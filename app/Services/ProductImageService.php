<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProductImageService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const THUMB_MAX = 160;

    public function validate(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'picture' => 'The file must not be larger than 5MB.',
            ]);
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'picture' => 'The file must be a JPEG, PNG, or WebP image.',
            ]);
        }

        $info = @\getimagesize($file->getPathname());
        if ($info === false) {
            throw ValidationException::withMessages([
                'picture' => 'The file is not a valid or decodable image.',
            ]);
        }

        if (!in_array($info['mime'], self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'picture' => 'The file appears to be a corrupted or invalid image.',
            ]);
        }
    }

    public function process(UploadedFile $file): array
    {
        $imageData = file_get_contents($file->getPathname());
        $thumbnailData = $this->generateThumbnail(
            $file->getPathname(),
            $file->getMimeType(),
            $imageData,
        );

        return [
            'image_data' => $imageData,
            'thumbnail_data' => $thumbnailData,
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'checksum' => hash('sha256', $imageData),
        ];
    }

    private function generateThumbnail(string $path, string $mime, string $originalData): string
    {
        $loader = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };

        $encoder = match ($mime) {
            'image/jpeg' => 'imagejpeg',
            'image/png' => 'imagepng',
            'image/webp' => 'imagewebp',
            default => null,
        };

        // If GD or the codec for this image type is unavailable, keep the
        // validated original bytes. This keeps uploads working without GD and
        // guarantees thumbnail_data always matches the stored mime_type.
        if (
            $loader === null
            || $encoder === null
            || ! function_exists($loader)
            || ! function_exists($encoder)
            || ! function_exists('imagecreatetruecolor')
        ) {
            return $originalData;
        }

        $src = @$loader($path);
        if ($src === false) {
            return $originalData;
        }

        $origW = \imagesx($src);
        $origH = \imagesy($src);
        if ($origW <= 0 || $origH <= 0) {
            \imagedestroy($src);
            return $originalData;
        }

        $ratio = min(self::THUMB_MAX / $origW, self::THUMB_MAX / $origH, 1);
        $newW = max(1, (int) round($origW * $ratio));
        $newH = max(1, (int) round($origH * $ratio));

        if ($ratio >= 1) {
            $thumb = $src;
        } else {
            $thumb = \imagecreatetruecolor($newW, $newH);

            if ($mime === 'image/png' || $mime === 'image/webp') {
                \imagealphablending($thumb, false);
                \imagesavealpha($thumb, true);
                $transparent = \imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                \imagefilledrectangle($thumb, 0, 0, $newW, $newH, $transparent);
            }

            \imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        }

        \ob_start();
        $written = match ($mime) {
            'image/jpeg' => \imagejpeg($thumb, null, 82),
            'image/png' => \imagepng($thumb, null, 6),
            'image/webp' => \imagewebp($thumb, null, 80),
            default => false,
        };
        $data = \ob_get_clean();

        if ($thumb !== $src) {
            \imagedestroy($thumb);
        }
        \imagedestroy($src);

        return $written && is_string($data) && $data !== '' ? $data : $originalData;
    }

    public function streamData(string $data, string $mimeType)
    {
        return response($data, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($data),
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'Pragma' => 'cache',
        ]);
    }
}
