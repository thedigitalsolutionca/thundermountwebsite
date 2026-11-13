<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use RuntimeException;

/**
 * Validated file uploads for product photos and dealer documents.
 * Files live under public/uploads and are served directly by the web server.
 */
final class UploadService
{
    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const VIDEO_TYPES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];

    private const IMAGE_MAX_BYTES = 10 * 1024 * 1024;
    private const DOCUMENT_MAX_BYTES = 10 * 1024 * 1024;
    private const VIDEO_MAX_BYTES = 50 * 1024 * 1024;

    public function image(?array $file, string $folder = 'products', int $maxWidth = 1800): array
    {
        $check = $this->inspect($file, self::IMAGE_MAX_BYTES);
        if (!$check['ok']) {
            return $check;
        }
        $mime = $this->mime($check['tmp']);
        if (!isset(self::IMAGE_TYPES[$mime])) {
            return ['ok' => false, 'error' => 'Use a JPG, PNG or WebP image.', 'path' => null];
        }
        $size = @getimagesize($check['tmp']);
        if (!$size || $size[0] < 200 || $size[1] < 200) {
            return ['ok' => false, 'error' => 'The image must be at least 200 × 200 pixels.', 'path' => null];
        }

        $relativeDir = trim($folder, '/') . '/' . date('Y/m');
        $targetDir = rtrim((string) Config::get('uploads.path'), '/') . '/' . $relativeDir;
        $this->ensureDir($targetDir);

        $name = $this->randomName() . '.' . self::IMAGE_TYPES[$mime];
        $target = $targetDir . '/' . $name;
        $resized = $this->resize($check['tmp'], $target, $mime, $maxWidth);
        if (!$resized) {
            if (!@move_uploaded_file($check['tmp'], $target)) {
                @unlink($check['tmp']);
                return ['ok' => false, 'error' => 'The image could not be saved. Check folder permissions.', 'path' => null];
            }
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                @unlink($target);
                return ['ok' => false, 'error' => 'Unsupported image.', 'path' => null];
            }
        }
        return [
            'ok' => true,
            'error' => null,
            'path' => '/uploads/' . $relativeDir . '/' . $name,
        ];
    }

    public function document(?array $file, string $folder = 'dealers'): array
    {
        $check = $this->inspect($file, self::DOCUMENT_MAX_BYTES);
        if (!$check['ok']) {
            return $check;
        }
        $mime = $this->mime($check['tmp']);
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return ['ok' => false, 'error' => 'Upload a PDF, JPG or PNG file.', 'path' => null];
        }
        $extension = $mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg');
        $relativeDir = trim($folder, '/') . '/' . date('Y/m');
        $targetDir = rtrim((string) Config::get('uploads.path'), '/') . '/' . $relativeDir;
        $this->ensureDir($targetDir);
        $name = $this->randomName() . '.' . $extension;
        if (!@move_uploaded_file($check['tmp'], $targetDir . '/' . $name)) {
            @unlink($check['tmp']);
            return ['ok' => false, 'error' => 'The file could not be saved. Check folder permissions.', 'path' => null];
        }
        return ['ok' => true, 'error' => null, 'path' => '/uploads/' . $relativeDir . '/' . $name];
    }

    public function video(?array $file, string $folder = 'gallery'): array
    {
        $check = $this->inspect($file, self::VIDEO_MAX_BYTES);
        if (!$check['ok']) {
            return $check;
        }
        $mime = $this->mime($check['tmp']);
        $clientExt = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $ext = self::VIDEO_TYPES[$mime] ?? null;
        if ($ext === null && $mime === 'video/x-matroska' && $clientExt === 'webm') {
            $ext = 'webm';
        }
        if ($ext === null) {
            return ['ok' => false, 'error' => 'Upload an MP4 or WebM video file (up to 50 MB).', 'path' => null];
        }

        $relativeDir = trim($folder, '/') . '/' . date('Y/m');
        $targetDir = rtrim((string) Config::get('uploads.path'), '/') . '/' . $relativeDir;
        $this->ensureDir($targetDir);
        $name = $this->randomName() . '.' . $ext;
        $target = $targetDir . '/' . $name;
        if (!@move_uploaded_file($check['tmp'], $target)) {
            @unlink($check['tmp']);
            return ['ok' => false, 'error' => 'The video could not be saved. Check folder permissions.', 'path' => null];
        }
        return [
            'ok' => true,
            'error' => null,
            'path' => '/uploads/' . $relativeDir . '/' . $name,
        ];
    }

    public function delete(?string $path): void
    {
        if (!$path || !str_starts_with($path, '/uploads/')) {
            return;
        }
        $base = realpath((string) Config::get('uploads.path'));
        $file = realpath(rtrim((string) Config::get('uploads.path'), '/') . '/' . ltrim(substr($path, strlen('/uploads/')), '/'));
        if ($base && $file && str_starts_with($file, $base) && is_file($file)) {
            @unlink($file);
        }
    }

    private function inspect(?array $file, int $maxBytes): array
    {
        if (!$file || !isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Choose a file to upload.', 'path' => null];
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => $this->uploadError((int) $file['error']), 'path' => null];
        }
        if (!is_uploaded_file((string) $file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Invalid upload.', 'path' => null];
        }
        if ((int) $file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'The file is too large (maximum ' . round($maxBytes / 1024 / 1024) . ' MB).', 'path' => null];
        }
        return ['ok' => true, 'error' => null, 'tmp' => (string) $file['tmp_name'], 'path' => null];
    }

    private function mime(string $path): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            return (string) $finfo->file($path);
        }
        return (string) (mime_content_type($path) ?: '');
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Upload directory could not be created: ' . $dir);
        }
    }

    private function randomName(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function resize(string $source, string $target, string $mime, int $maxWidth): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            default => false,
        };
        if (!$image) {
            return false;
        }
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round($height * ($maxWidth / $width));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        }
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($canvas, $target, 84),
            'image/png' => imagepng($canvas, $target, 7),
            'image/webp' => imagewebp($canvas, $target, 84),
            default => false,
        };
        imagedestroy($image);
        imagedestroy($canvas);
        if ($saved) {
            @unlink($source);
        }
        return (bool) $saved;
    }

    private function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
            UPLOAD_ERR_PARTIAL => 'The upload did not finish. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server has no upload folder configured.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the upload.',
            UPLOAD_ERR_EXTENSION => 'The upload was blocked by the server.',
            default => 'The upload failed. Please try again.',
        };
    }
}
