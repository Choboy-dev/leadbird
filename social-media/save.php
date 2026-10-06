<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$caption = trim((string) ($_POST['caption'] ?? ''));
$hashtags = trim((string) ($_POST['hashtags'] ?? ''));
$accountIds = is_array($_POST['account_ids'] ?? null) ? $_POST['account_ids'] : [];
$scheduledAt = trim((string) ($_POST['scheduled_at'] ?? ''));
$publishNow = !empty($_POST['publish_now']);
$saveDraft = !empty($_POST['save_draft']);
$aiAssisted = !empty($_POST['ai_assisted']);

$imageService = new SocialMediaImageService();
$mediaPaths = [];
$platformMediaPaths = SocialMediaService::parsePlatformMediaFromRequest($ctx['business_id'], $imageService, $publishNow && !$saveDraft);
$ephemeralUpload = $publishNow && !$saveDraft;

try {
    if ($platformMediaPaths === []) {
        $uploaded = $imageService->storeMultipleUploads($ctx['business_id'], $_FILES['media'] ?? null, $ephemeralUpload);
        if ($uploaded !== []) {
            $mediaPaths = $uploaded;
        } else {
            $single = $imageService->storeUpload($ctx['business_id'], $_FILES['image'] ?? null, $ephemeralUpload);
            if ($single !== null) {
                $mediaPaths = [$single];
            } elseif (!empty($_POST['existing_media_url'])) {
                $existing = trim((string) $_POST['existing_media_url']);
                if ($imageService->isStoredSocialPath($existing)) {
                    $mediaPaths = [$existing];
                }
            }
        }
    }
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
    exit;
}

// Never ephemeral: the poster outlives the video so published cards keep a preview.
try {
    $thumbnailPath = $imageService->storeUpload($ctx['business_id'], $_FILES['poster'] ?? null, false);
} catch (InvalidArgumentException) {
    $thumbnailPath = null;
}

$accountIdList = [];
foreach ($accountIds as $id) {
    if (is_string($id) && $id !== '') {
        $accountIdList[] = $id;
    }
}

$result = (new SocialMediaService())->createPost(
    $ctx['business_id'],
    $ctx['user_id'],
    $caption,
    $hashtags !== '' ? $hashtags : null,
    $mediaPaths,
    $accountIdList,
    $scheduledAt !== '' ? $scheduledAt : null,
    $publishNow && !$saveDraft,
    $aiAssisted,
    SocialMediaService::parsePlatformContentFromRequest($_POST['platform_content'] ?? null),
    $platformMediaPaths,
    $thumbnailPath
);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
