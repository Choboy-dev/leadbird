<?php

declare(strict_types=1);

final class OfferingImageService
{
    private const MAX_BYTES = 5_242_880; // 5 MB

    /** @var array<string, string> */
    private const MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * @param array<string, mixed>|null $file
     */
    public function storeUpload(string $businessId, ?array $file): ?string
    {
        if ($file === null || !is_array($file)) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Image upload failed. Please try again.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new InvalidArgumentException('Invalid image upload.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image must be 5 MB or smaller.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, $tmpPath) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        $extension = self::MIME_TO_EXT[$mime] ?? null;
        if ($extension === null) {
            throw new InvalidArgumentException('Use a JPG, PNG, WebP, or GIF image.');
        }

        $directory = $this->businessDirectory($businessId);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create upload folder.');
        }

        $filename = Uuid::v4() . '.' . $extension;
        $absolutePath = $directory . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            throw new RuntimeException('Could not save uploaded image.');
        }

        return '/storage/uploads/offerings/' . $businessId . '/' . $filename;
    }

    public function deleteStoredPath(?string $imageUrl): void
    {
        $absolutePath = $this->absolutePathFromUrl($imageUrl);
        if ($absolutePath !== null && is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    public function publicUrl(?string $imageUrl): ?string
    {
        $path = trim((string) $imageUrl);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(app_config()['app_url'], '/') . '/' . ltrim($path, '/');
    }

    public function absolutePathFromUrl(?string $imageUrl): ?string
    {
        $path = trim((string) $imageUrl);
        if ($path === '') {
            return null;
        }

        $prefix = '/storage/uploads/offerings/';
        if (!str_starts_with($path, $prefix)) {
            return null;
        }

        $relative = substr($path, strlen($prefix));
        $parts = explode('/', $relative, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$businessId, $filename] = $parts;
        if ($businessId === '' || $filename === '' || str_contains($filename, '/')) {
            return null;
        }

        return $this->businessDirectory($businessId) . '/' . $filename;
    }

    private function businessDirectory(string $businessId): string
    {
        return dirname(__DIR__) . '/storage/uploads/offerings/' . $businessId;
    }
}
