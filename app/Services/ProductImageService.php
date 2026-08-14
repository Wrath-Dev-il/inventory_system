<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProductImageService
{
    private const ENCODED_PREFIX = 'base64:';
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
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'picture' => 'The file must be a JPEG, PNG, or WebP image.',
            ]);
        }

        $info = @getimagesize($file->getPathname());
        if ($info === false) {
            throw ValidationException::withMessages([
                'picture' => 'The file is not a valid or decodable image.',
            ]);
        }

        if (! in_array($info['mime'], self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'picture' => 'The file appears to be a corrupted or invalid image.',
            ]);
        }
    }

    public function process(UploadedFile $file): array
    {
        $imageData = file_get_contents($file->getPathname());
        if ($imageData === false) {
            throw ValidationException::withMessages([
                'picture' => 'The uploaded image could not be read.',
            ]);
        }

        $mime = $this->detectMime($imageData) ?? (string) $file->getMimeType();
        $thumbnailData = $this->generateThumbnail($file->getPathname(), $mime, $imageData);

        return [
            'image_data' => $this->encodeStoredData($imageData),
            'thumbnail_data' => $this->encodeStoredData($thumbnailData),
            'mime_type' => $mime,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'checksum' => hash('sha256', $imageData),
        ];
    }

    private function generateThumbnail(string $path, string $mime, string $fallbackData): string
    {
        // Some hosting environments do not have GD (or a specific image codec)
        // enabled. The original, already validated image is a safe fallback.
        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            return $fallbackData;
        }

        $decoder = match ($mime) {
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

        if ($decoder === null || $encoder === null || ! function_exists($decoder) || ! function_exists($encoder)) {
            return $fallbackData;
        }

        $src = @$decoder($path);
        if ($src === false) {
            return $fallbackData;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($src);
            return $fallbackData;
        }

        $ratio = min(self::THUMB_MAX / $origW, self::THUMB_MAX / $origH, 1);
        if ($ratio >= 1) {
            imagedestroy($src);
            return $fallbackData;
        }

        $newW = max(1, (int) round($origW * $ratio));
        $newH = max(1, (int) round($origH * $ratio));
        $thumb = imagecreatetruecolor($newW, $newH);

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefilledrectangle($thumb, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        ob_start();
        $written = match ($mime) {
            'image/jpeg' => imagejpeg($thumb, null, 82),
            'image/png' => imagepng($thumb, null, 6),
            'image/webp' => imagewebp($thumb, null, 78),
            default => false,
        };
        $data = ob_get_clean();

        imagedestroy($thumb);
        imagedestroy($src);

        return $written && is_string($data) && $data !== '' ? $data : $fallbackData;
    }

    public function streamData(string $data, string $mimeType)
    {
        $decoded = $this->decodeStoredData($data);
        $detectedMime = $this->detectMime($decoded) ?? $mimeType;

        return response($decoded, 200, [
            'Content-Type' => $detectedMime,
            'Content-Length' => strlen($decoded),
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'Pragma' => 'cache',
        ]);
    }

    public function isDisplayable(string $data): bool
    {
        return $this->detectMime($this->decodeStoredData($data)) !== null;
    }

    public function decodeStoredData(string $data): string
    {
        if (str_starts_with($data, self::ENCODED_PREFIX)) {
            $decoded = base64_decode(substr($data, strlen(self::ENCODED_PREFIX)), true);

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $data;
    }

    private function encodeStoredData(string $data): string
    {
        return self::ENCODED_PREFIX.base64_encode($data);
    }

    private function detectMime(string $data): ?string
    {
        $info = @getimagesizefromstring($data);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;

        return in_array($mime, self::ALLOWED_MIMES, true) ? $mime : null;
    }
}
