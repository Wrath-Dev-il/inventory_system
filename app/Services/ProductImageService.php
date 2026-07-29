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
        $thumbnailData = $this->generateThumbnail($file->getPathname(), $file->getMimeType());

        return [
            'image_data' => $imageData,
            'thumbnail_data' => $thumbnailData,
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'checksum' => hash('sha256', $imageData),
        ];
    }

    private function generateThumbnail(string $path, string $mime): string
    {
        $src = match ($mime) {
            'image/jpeg' => @\imagecreatefromjpeg($path),
            'image/png' => @\imagecreatefrompng($path),
            'image/webp' => @\imagecreatefromwebp($path),
            default => throw new \RuntimeException('Unsupported mime type: ' . $mime),
        };

        if ($src === false) {
            throw new \RuntimeException('Failed to decode image for thumbnail generation.');
        }

        $origW = \imagesx($src);
        $origH = \imagesy($src);

        $ratio = min(self::THUMB_MAX / $origW, self::THUMB_MAX / $origH);
        $newW = (int) round($origW * $ratio);
        $newH = (int) round($origH * $ratio);

        if ($ratio >= 1) {
            $thumb = $src;
        } else {
            $thumb = \imagecreatetruecolor($newW, $newH);
            \imagealphablending($thumb, false);
            \imagesavealpha($thumb, true);
            \imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        }

        \ob_start();
        \imagewebp($thumb, null, 75);
        $data = \ob_get_clean();

        if ($thumb !== $src) {
            \imagedestroy($thumb);
        }
        \imagedestroy($src);

        return $data !== false ? $data : '';
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