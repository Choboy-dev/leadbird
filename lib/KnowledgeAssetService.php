<?php

declare(strict_types=1);

/**
 * Public, signed URLs for trained knowledge files (documents, images).
 */
final class KnowledgeAssetService
{
    public static function shareUrl(string $businessId, string $chunkId): string
    {
        $sig = self::sign($businessId, $chunkId);

        return app_route('knowledge-share', [
            'id' => $chunkId,
            'sig' => $sig,
        ]);
    }

    public static function verify(string $chunkId, string $businessId, string $sig): bool
    {
        if ($chunkId === '' || $businessId === '' || $sig === '') {
            return false;
        }

        return hash_equals(self::sign($businessId, $chunkId), $sig);
    }

    /**
     * @return array{path: string, mime: string, file_name: string, business_id: string}|null
     */
    public static function resolveSharedFile(string $chunkId, string $sig): ?array
    {
        $chunkId = trim($chunkId);
        $sig = trim($sig);
        if ($chunkId === '' || $sig === '') {
            return null;
        }

        $row = RepositoryFactory::businessKnowledge()->findActiveDocumentById($chunkId);
        if ($row === null) {
            return null;
        }

        $businessId = (string) ($row['business_id'] ?? '');
        if (!self::verify($chunkId, $businessId, $sig)) {
            return null;
        }

        $file = (new KnowledgeTrainingService())->resolveDocumentFile($businessId, $chunkId);
        if ($file === null) {
            return null;
        }

        return [
            'path' => $file['path'],
            'mime' => $file['mime'],
            'file_name' => $file['file_name'],
            'business_id' => $businessId,
        ];
    }

    /**
     * @param array<string, mixed> $chunk
     * @return array{chunk_id: string, url: string, mime: string, file_name: string, delivery: string}|null
     */
    public static function attachmentFromChunk(array $chunk, string $businessId): ?array
    {
        $chunkId = trim((string) ($chunk['id'] ?? ''));
        if ($chunkId === '') {
            return null;
        }

        $sourceType = (string) ($chunk['source_type'] ?? '');

        if ($sourceType === 'document') {
            $meta = knowledge_chunk_metadata($chunk);
            if (trim((string) ($meta['stored_name'] ?? '')) === '') {
                return null;
            }

            $extension = strtolower((string) ($meta['extension'] ?? ''));
            $fileName = trim((string) ($meta['file_name'] ?? 'file'));
            $mime = self::mimeForExtension($extension);
            $delivery = self::deliveryTypeForMime($mime);

            return [
                'chunk_id' => $chunkId,
                'url' => self::shareUrl($businessId, $chunkId),
                'mime' => $mime,
                'file_name' => $fileName,
                'delivery' => $delivery,
            ];
        }

        $imageUrl = trim((string) ($chunk['image_url'] ?? ''));
        if ($imageUrl === '') {
            return null;
        }

        if (!str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
            $imageUrl = rtrim(app_config()['app_url'], '/') . '/' . ltrim($imageUrl, '/');
        }

        return [
            'chunk_id' => $chunkId,
            'url' => $imageUrl,
            'mime' => 'image/jpeg',
            'file_name' => trim((string) ($chunk['title'] ?? 'image')) . '.jpg',
            'delivery' => 'image',
        ];
    }

    public static function mimeForExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            default => 'application/octet-stream',
        };
    }

    public static function deliveryTypeForMime(string $mime): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        return 'document';
    }

    private static function sign(string $businessId, string $chunkId): string
    {
        $secret = (string) (app_config()['meta']['app_secret'] ?? '');
        if ($secret === '') {
            $secret = (string) (app_config()['openai']['api_key'] ?? 'leadbird-knowledge-share');
        }

        return substr(hash_hmac('sha256', $businessId . '|' . $chunkId, $secret), 0, 32);
    }
}
