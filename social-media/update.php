<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$service = new SocialMediaService();
$raw = file_get_contents('php://input');
$jsonPayload = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : null;
$type = trim((string) ($_POST['type'] ?? (is_array($jsonPayload) ? ($jsonPayload['type'] ?? '') : '')));

if ($type === 'live') {
    $payload = is_array($jsonPayload) ? $jsonPayload : $_POST;

    $accountId = trim((string) ($payload['account_id'] ?? ''));
    $platformPostId = trim((string) ($payload['platform_post_id'] ?? ''));
    $caption = trim((string) ($payload['caption'] ?? ''));

    if ($accountId === '' || $platformPostId === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Account and post id required.'], JSON_THROW_ON_ERROR);
        exit;
    }

    $result = $service->updateLivePost($ctx['business_id'], $accountId, $platformPostId, $caption);
    http_response_code(($result['ok'] ?? false) ? 200 : 422);
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit;
}

$postId = trim((string) ($_POST['post_id'] ?? ''));
if ($postId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Post id required.'], JSON_THROW_ON_ERROR);
    exit;
}

$caption = trim((string) ($_POST['caption'] ?? ''));
$hashtags = trim((string) ($_POST['hashtags'] ?? ''));
$accountIds = is_array($_POST['account_ids'] ?? null) ? $_POST['account_ids'] : [];
$scheduledAt = trim((string) ($_POST['scheduled_at'] ?? ''));
$publishNow = !empty($_POST['publish_now']);

$imageService = new SocialMediaImageService();
$mediaPaths = null;
$platformMediaPaths = SocialMediaService::parsePlatformMediaFromRequest($ctx['business_id'], $imageService, $publishNow);
$ephemeralUpload = $publishNow;

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

$result = $service->updateLocalPost(
    $postId,
    $ctx['business_id'],
    $caption,
    $hashtags !== '' ? $hashtags : null,
    $mediaPaths,
    $accountIdList,
    $scheduledAt !== '' ? $scheduledAt : null,
    $publishNow,
    SocialMediaService::parsePlatformContentFromRequest($_POST['platform_content'] ?? null),
    $platformMediaPaths,
    $thumbnailPath
);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
