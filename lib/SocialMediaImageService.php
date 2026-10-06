<?php

declare(strict_types=1);

final class SocialMediaImageService
{
    private const MAX_IMAGE_BYTES = 8_388_608; // 8 MB
    private const MAX_VIDEO_BYTES = 52_428_800; // 50 MB
    private const EPHEMERAL_SEGMENT = 'ephemeral';
    private const ORPHAN_EPHEMERAL_MAX_AGE_SECONDS = 7200; // 2 hours

    /** @var array<string, string> */
    private const IMAGE_MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** @var array<string, string> */
    private const VIDEO_MIME_TO_EXT = [
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
    ];

    /**
     * @param array<string, mixed>|null $file
     */
    public function storeUpload(string $businessId, ?array $file, bool $ephemeral = false): ?string
    {
        if ($file === null || !is_array($file)) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Media upload failed. Please try again.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new InvalidArgumentException('Invalid media upload.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new InvalidArgumentException('Upload is empty.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, $tmpPath) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        $extension = self::IMAGE_MIME_TO_EXT[$mime] ?? self::VIDEO_MIME_TO_EXT[$mime] ?? null;
        if ($extension === null) {
            throw new InvalidArgumentException('Use JPG, PNG, WebP, MP4, or MOV.');
        }

        $maxBytes = isset(self::VIDEO_MIME_TO_EXT[$mime]) ? self::MAX_VIDEO_BYTES : self::MAX_IMAGE_BYTES;
        if ($size > $maxBytes) {
            $limit = isset(self::VIDEO_MIME_TO_EXT[$mime]) ? '50 MB' : '8 MB';
            throw new InvalidArgumentException('File must be ' . $limit . ' or smaller.');
        }

        $directory = $ephemeral
            ? $this->ephemeralDirectory($businessId)
            : $this->persistentDirectory($businessId);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create upload folder.');
        }

        $filename = Uuid::v4() . '.' . $extension;
        $absolutePath = $directory . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            throw new RuntimeException('Could not save uploaded media.');
        }

        return $ephemeral
            ? '/storage/uploads/social/' . $businessId . '/' . self::EPHEMERAL_SEGMENT . '/' . $filename
            : '/storage/uploads/social/' . $businessId . '/' . $filename;
    }

    /**
     * Download a remote media URL (e.g. Instagram CDN) into social storage.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function storeFromRemoteUrl(string $businessId, string $remoteUrl, bool $ephemeral = false): string
    {
        $remoteUrl = trim($remoteUrl);
        if ($remoteUrl === '' || !preg_match('#^https?://#i', $remoteUrl)) {
            throw new InvalidArgumentException('This post has no downloadable media.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'lbmedia_');
        if ($tmpPath === false) {
            throw new RuntimeException('Could not prepare a temporary file.');
        }

        $ch = curl_init($remoteUrl);
        if ($ch === false) {
            @unlink($tmpPath);
            throw new RuntimeException('Could not download media.');
        }

        $fp = fopen($tmpPath, 'wb');
        if ($fp === false) {
            curl_close($ch);
            @unlink($tmpPath);
            throw new RuntimeException('Could not write temporary media.');
        }

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => 'LeadBird/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        curl_close($ch);
        fclose($fp);

        if ($ok === false || $httpCode >= 400 || !is_file($tmpPath) || filesize($tmpPath) <= 0) {
            @unlink($tmpPath);
            throw new InvalidArgumentException('Could not download this post’s media. Try again in a moment.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, $tmpPath) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if ($mime === '' || $mime === 'application/octet-stream') {
            $mime = strtok($contentType, ';') ?: $mime;
        }

        $extension = self::IMAGE_MIME_TO_EXT[$mime] ?? self::VIDEO_MIME_TO_EXT[$mime] ?? null;
        if ($extension === null) {
            $pathExt = strtolower(pathinfo((string) (parse_url($remoteUrl, PHP_URL_PATH) ?? ''), PATHINFO_EXTENSION));
            if (in_array($pathExt, ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'], true)) {
                $extension = $pathExt === 'jpeg' ? 'jpg' : $pathExt;
                $mime = in_array($extension, ['mp4', 'mov'], true)
                    ? ($extension === 'mov' ? 'video/quicktime' : 'video/mp4')
                    : 'image/jpeg';
            }
        }

        if ($extension === null) {
            @unlink($tmpPath);
            throw new InvalidArgumentException('Unsupported media type for this post.');
        }

        $size = (int) filesize($tmpPath);
        $maxBytes = in_array($extension, ['mp4', 'mov'], true) ? self::MAX_VIDEO_BYTES : self::MAX_IMAGE_BYTES;
        if ($size > $maxBytes) {
            @unlink($tmpPath);
            $limit = in_array($extension, ['mp4', 'mov'], true) ? '50 MB' : '8 MB';
            throw new InvalidArgumentException('File must be ' . $limit . ' or smaller.');
        }

        $directory = $ephemeral
            ? $this->ephemeralDirectory($businessId)
            : $this->persistentDirectory($businessId);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            @unlink($tmpPath);
            throw new RuntimeException('Could not create upload folder.');
        }

        $filename = Uuid::v4() . '.' . $extension;
        $absolutePath = $directory . '/' . $filename;
        if (!@rename($tmpPath, $absolutePath)) {
            if (!@copy($tmpPath, $absolutePath)) {
                @unlink($tmpPath);
                throw new RuntimeException('Could not save downloaded media.');
            }
            @unlink($tmpPath);
        }

        return $ephemeral
            ? '/storage/uploads/social/' . $businessId . '/' . self::EPHEMERAL_SEGMENT . '/' . $filename
            : '/storage/uploads/social/' . $businessId . '/' . $filename;
    }

    /**
     * @param array<string, mixed>|null $files $_FILES field with multiple uploads
     * @return list<string>
     */
    public function storeMultipleUploads(string $businessId, ?array $files, bool $ephemeral = false): array
    {
        if ($files === null || !is_array($files)) {
            return [];
        }

        $names = $files['name'] ?? null;
        if (!is_array($names)) {
            $single = $this->storeUpload($businessId, $files, $ephemeral);
            return $single !== null ? [$single] : [];
        }

        $paths = [];
        $count = count($names);
        for ($index = 0; $index < $count; $index++) {
            $file = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];

            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $stored = $this->storeUpload($businessId, $file, $ephemeral);
            if ($stored !== null) {
                $paths[] = $stored;
            }
        }

        return $paths;
    }

    public function isEphemeralPath(?string $storedPath): bool
    {
        $path = trim((string) $storedPath);
        if ($path === '') {
            return false;
        }

        return str_contains($path, '/' . self::EPHEMERAL_SEGMENT . '/');
    }

    public function isStoredSocialPath(?string $storedPath): bool
    {
        $path = trim((string) $storedPath);

        return $path !== '' && str_starts_with($path, '/storage/uploads/social/');
    }

    public function mediaTypeFromStoredPath(?string $storedPath): ?string
    {
        $path = trim((string) $storedPath);
        if ($path === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['mp4', 'mov'], true)) {
            return 'video';
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return 'image';
        }

        return null;
    }

    public function deleteStoredPath(?string $mediaUrl): void
    {
        $absolutePath = $this->absolutePathFromUrl($mediaUrl);
        if ($absolutePath !== null && is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    public function publicUrl(?string $mediaUrl): ?string
    {
        $path = trim((string) $mediaUrl);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(app_config()['app_url'], '/') . '/' . ltrim($path, '/');
    }

    public function absolutePathFromUrl(?string $mediaUrl): ?string
    {
        $path = trim((string) $mediaUrl);
        if ($path === '') {
            return null;
        }

        $prefix = '/storage/uploads/social/';
        if (!str_starts_with($path, $prefix)) {
            return null;
        }

        $relative = ltrim(substr($path, strlen($prefix)), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $segments = explode('/', $relative);
        $businessId = array_shift($segments);
        if ($businessId === '' || $segments === []) {
            return null;
        }

        $subPath = implode('/', $segments);
        if ($subPath === '' || str_contains($subPath, '..')) {
            return null;
        }

        $base = realpath(dirname(__DIR__) . '/storage/uploads/social');
        if ($base === false) {
            return null;
        }

        $absolute = $base . '/' . $businessId . '/' . $subPath;
        $resolved = realpath($absolute);
        if ($resolved === false || !str_starts_with($resolved, $base . DIRECTORY_SEPARATOR . $businessId)) {
            return is_file($absolute) ? $absolute : null;
        }

        return $resolved;
    }

    /**
     * Remove ephemeral files that are not linked to any post and older than the grace window.
     *
     * @param list<string> $referencedPaths
     */
    public function cleanupOrphanedEphemeralFiles(array $referencedPaths): int
    {
        $referenced = [];
        foreach ($referencedPaths as $path) {
            $path = trim($path);
            if ($path !== '') {
                $referenced[$path] = true;
            }
        }

        $root = dirname(__DIR__) . '/storage/uploads/social';
        if (!is_dir($root)) {
            return 0;
        }

        $deleted = 0;
        $cutoff = time() - self::ORPHAN_EPHEMERAL_MAX_AGE_SECONDS;

        foreach (scandir($root) ?: [] as $businessId) {
            if ($businessId === '.' || $businessId === '..') {
                continue;
            }

            $ephemeralDir = $root . '/' . $businessId . '/' . self::EPHEMERAL_SEGMENT;
            if (!is_dir($ephemeralDir)) {
                continue;
            }

            foreach (scandir($ephemeralDir) ?: [] as $filename) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                $storedPath = '/storage/uploads/social/' . $businessId . '/' . self::EPHEMERAL_SEGMENT . '/' . $filename;
                if (isset($referenced[$storedPath])) {
                    continue;
                }

                $absolute = $ephemeralDir . '/' . $filename;
                if (!is_file($absolute)) {
                    continue;
                }

                if (filemtime($absolute) > $cutoff) {
                    continue;
                }

                if (@unlink($absolute)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    private function persistentDirectory(string $businessId): string
    {
        return $this->businessDirectory($businessId);
    }

    private function ephemeralDirectory(string $businessId): string
    {
        return $this->businessDirectory($businessId) . '/' . self::EPHEMERAL_SEGMENT;
    }

    private function businessDirectory(string $businessId): string
    {
        return dirname(__DIR__) . '/storage/uploads/social/' . $businessId;
    }

    /**
     * Bake story caption text onto an image (Meta stories do not accept caption fields via API).
     */
    public function applyStoryCaptionToPublicUrl(string $publicUrl, string $caption): ?string
    {
        $caption = trim($caption);
        if ($caption === '') {
            return $publicUrl;
        }

        $storedPath = $this->storedPathFromPublicUrl($publicUrl);
        if ($storedPath === null) {
            return null;
        }

        $overlaidPath = $this->applyStoryCaptionOverlay($storedPath, $caption);

        return $overlaidPath !== null ? $this->publicUrl($overlaidPath) : null;
    }

    public function applyStoryCaptionOverlay(string $storedPath, string $caption): ?string
    {
        $caption = trim($caption);
        if ($caption === '' || $this->mediaTypeFromStoredPath($storedPath) !== 'image') {
            return $storedPath;
        }

        $absolute = $this->absolutePathFromUrl($storedPath);
        if ($absolute === null || !is_file($absolute)) {
            return null;
        }

        $image = $this->loadGdImage($absolute);
        if ($image === null) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);

            return null;
        }

        $padding = (int) max(24, round($width * 0.05));
        $barHeight = (int) max(round($height * 0.24), 120);
        $barTop = $height - $barHeight;

        $overlay = imagecolorallocatealpha($image, 0, 0, 0, 55);
        if ($overlay !== false) {
            imagefilledrectangle($image, 0, $barTop, $width, $height, $overlay);
        }

        $text = mb_substr(preg_replace('/\s+/u', ' ', $caption) ?? $caption, 0, 220);
        $fontSize = max(16, (int) round($width / 26));
        $maxWidth = $width - ($padding * 2);
        $fontPath = $this->resolveStoryFontPath();
        $white = imagecolorallocate($image, 255, 255, 255);

        if ($fontPath !== null && $white !== false) {
            $lines = $this->wrapTextForTtf($text, $fontPath, $fontSize, $maxWidth);
            $lineHeight = (int) round($fontSize * 1.35);
            $textBlockHeight = count($lines) * $lineHeight;
            $y = $barTop + (int) max($padding, ($barHeight - $textBlockHeight) / 2) + $fontSize;

            foreach ($lines as $line) {
                imagettftext($image, $fontSize, 0, $padding, $y, $white, $fontPath, $line);
                $y += $lineHeight;
            }
        } elseif ($white !== false) {
            $lines = $this->wrapTextForBuiltinFont($text, $maxWidth);
            $y = $barTop + $padding;
            foreach ($lines as $line) {
                imagestring($image, 5, $padding, $y, $line, $white);
                $y += 16;
            }
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $outputPath = $this->uniqueSiblingPath($absolute, '-story');
        $saved = match ($extension) {
            'png' => imagepng($image, $outputPath),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $outputPath, 90) : imagejpeg($image, $outputPath, 92),
            default => imagejpeg($image, $outputPath, 92),
        };

        imagedestroy($image);

        if ($saved !== true) {
            return null;
        }

        return $this->storedPathFromAbsolute($outputPath);
    }

    public function storedPathFromPublicUrl(string $publicUrl): ?string
    {
        $publicUrl = trim($publicUrl);
        if ($publicUrl === '') {
            return null;
        }

        if (str_starts_with($publicUrl, '/storage/')) {
            return $publicUrl;
        }

        $appUrl = rtrim((string) (app_config()['app_url'] ?? ''), '/');
        if ($appUrl !== '' && str_starts_with($publicUrl, $appUrl . '/storage/')) {
            return substr($publicUrl, strlen($appUrl));
        }

        $path = parse_url($publicUrl, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/storage/uploads/social/')) {
            return $path;
        }

        return null;
    }

  /**
     * @return list<string>
     */
    private function wrapTextForTtf(string $text, string $fontPath, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            $box = imagettfbbox($fontSize, 0, $fontPath, $candidate);
            $lineWidth = is_array($box) ? abs($box[2] - $box[0]) : 0;

            if ($lineWidth > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [$text] : $lines;
    }

    /**
     * @return list<string>
     */
    private function wrapTextForBuiltinFont(string $text, int $maxWidth): array
    {
        $charLimit = max(12, (int) floor($maxWidth / 8));
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($candidate) > $charLimit && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [$text] : $lines;
    }

    private function resolveStoryFontPath(): ?string
    {
        $candidates = [
            dirname(__DIR__) . '/assets/fonts/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function loadGdImage(string $absolutePath): ?\GdImage
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => @imagecreatefrompng($absolutePath) ?: null,
            'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($absolutePath) ?: null) : null,
            default => @imagecreatefromjpeg($absolutePath) ?: null,
        };
    }

    private function uniqueSiblingPath(string $absolutePath, string $suffix): string
    {
        $directory = dirname($absolutePath);
        $filename = pathinfo($absolutePath, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return $directory . '/' . $filename . $suffix . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    }

    private function storedPathFromAbsolute(string $absolutePath): ?string
    {
        $base = realpath(dirname(__DIR__) . '/storage/uploads/social');
        $resolved = realpath($absolutePath);
        if ($base === false || $resolved === false || !str_starts_with($resolved, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $relative = ltrim(substr($resolved, strlen($base)), '/\\');

        return '/storage/uploads/social/' . str_replace('\\', '/', $relative);
    }
}
