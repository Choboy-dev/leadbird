<?php

declare(strict_types=1);

/**
 * Fire the Graph API calls Meta App Review api_precheck looks for.
 *
 * Covers:
 * - pages_manage_posts          → POST /{page-id}/feed + update + delete
 * - instagram_content_publish   → create container + media_publish
 * - instagram_manage_contents   → DELETE /{media-id}
 * - instagram_manage_insights   → GET /{ig-user-id}/insights (user token)
 *
 * Usage (production):
 *   php scripts/ping-app-review-permissions.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$ok = [];
$fail = [];

function ping_log(string $label, bool $success, string $detail = ''): void
{
    global $ok, $fail;
    if ($success) {
        $ok[] = $label;
        echo "[OK]   {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    } else {
        $fail[] = $label;
        echo "[FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL;
    }
}

function account_error(array $response): string
{
    if (isset($response['error']) && is_array($response['error'])) {
        return (string) ($response['error']['message'] ?? json_encode($response['error']));
    }

    return 'unknown error';
}

// Prefer the App Review demo business; fall back to any active Facebook/Instagram rows.
$reviewBusinessId = (string) ($pdo->query(
    "SELECT b.id FROM businesses b
     JOIN users u ON u.id = b.user_id
     WHERE u.email = 'metareview@leadbird.app'
     LIMIT 1"
)->fetchColumn() ?: '');

$facebook = null;
$instagram = null;

if ($reviewBusinessId !== '') {
    $facebook = $pdo->prepare(
        "SELECT * FROM connected_accounts
         WHERE business_id = ? AND platform = 'facebook' AND is_active = 1
         ORDER BY created_at DESC LIMIT 1"
    );
    $facebook->execute([$reviewBusinessId]);
    $facebook = $facebook->fetch(PDO::FETCH_ASSOC) ?: null;

    // Instagram may have been deactivated after disconnect UI; still try newest token.
    $instagram = $pdo->prepare(
        "SELECT * FROM connected_accounts
         WHERE business_id = ? AND platform = 'instagram'
           AND platform_account_id IS NOT NULL AND platform_account_id != ''
         ORDER BY is_active DESC, created_at DESC LIMIT 1"
    );
    $instagram->execute([$reviewBusinessId]);
    $instagram = $instagram->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($facebook === null) {
    $facebook = $pdo->query(
        "SELECT * FROM connected_accounts
         WHERE platform = 'facebook' AND is_active = 1
           AND access_token IS NOT NULL AND access_token != ''
         ORDER BY created_at DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($instagram === null) {
    $instagram = $pdo->query(
        "SELECT * FROM connected_accounts
         WHERE platform = 'instagram'
           AND platform_account_id IS NOT NULL AND platform_account_id != ''
           AND access_token IS NOT NULL AND access_token != ''
         ORDER BY is_active DESC, created_at DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC) ?: null;
}

echo "Review business: " . ($reviewBusinessId !== '' ? $reviewBusinessId : '(none)') . PHP_EOL;
echo 'Facebook: ' . ($facebook['display_name'] ?? 'none') . PHP_EOL;
echo 'Instagram: ' . ($instagram['display_name'] ?? 'none') . PHP_EOL;
echo PHP_EOL;

/* ------------------------------------------------------------------ pages_manage_posts */
if ($facebook === null) {
    ping_log('pages_manage_posts', false, 'No Facebook Page account with a token.');
} else {
    $pageId = trim((string) ($facebook['platform_account_id'] ?? ''));
    $pageToken = trim((string) ($facebook['access_token'] ?? ''));

    $create = meta_graph_post_json($pageId . '/feed', $pageToken, [
        'message' => 'Lead Bird App Review precheck ' . gmdate('Y-m-d H:i:s') . ' UTC — safe to ignore.',
    ]);

    $postId = (string) ($create['id'] ?? '');
    if ($postId === '') {
        ping_log('pages_manage_posts (create)', false, account_error($create));
    } else {
        ping_log('pages_manage_posts (create)', true, $postId);

        $update = meta_graph_post_json($postId, $pageToken, [
            'message' => 'Lead Bird App Review precheck (edited) ' . gmdate('Y-m-d H:i:s') . ' UTC',
        ]);
        ping_log(
            'pages_manage_posts (edit)',
            !isset($update['error']),
            isset($update['error']) ? account_error($update) : 'caption updated'
        );

        $delete = meta_graph_delete($postId, $pageToken);
        $deleted = !isset($delete['error']) || ($delete['success'] ?? false) === true;
        ping_log(
            'pages_manage_posts (delete)',
            $deleted,
            $deleted ? 'post removed' : account_error($delete)
        );
    }
}

/* -------------------------------------------------------------- Instagram permissions */
if ($instagram === null) {
    ping_log('instagram_*', false, 'No Instagram account with a token. Reconnect IG on the review account.');
} else {
    $igId = trim((string) ($instagram['platform_account_id'] ?? ''));
    $igToken = trim((string) ($instagram['access_token'] ?? ''));
    $userToken = meta_user_token_from_account($instagram);

    // insights — User token required for App Review checklist
    if ($userToken === '') {
        ping_log('instagram_manage_insights', false, 'No Facebook user token in refresh_token — reconnect Instagram.');
    } else {
        $insights = meta_ping_instagram_account_insights($igId, $userToken);
        ping_log(
            'instagram_manage_insights',
            (bool) ($insights['ok'] ?? false),
            (string) (($insights['ok'] ?? false) ? ('metric=' . ($insights['metric'] ?? '')) : ($insights['error'] ?? ''))
        );
    }

    // content_publish — create + publish a tiny public image, then delete (manage_contents)
    $imageUrl = rtrim((string) app_config()['app_url'], '/') . '/assets/images/leadbird-favicon.png?v=appreview';
    $container = meta_graph_post_json($igId . '/media', $igToken, [
        'image_url' => $imageUrl,
        'caption' => 'Lead Bird App Review precheck ' . gmdate('Y-m-d H:i:s') . ' UTC — safe to ignore.',
    ]);
    $containerId = (string) ($container['id'] ?? '');
    if ($containerId === '') {
        ping_log('instagram_content_publish (container)', false, account_error($container));
    } else {
        // Wait briefly for container FINISHED status
        $ready = false;
        for ($i = 0; $i < 12; $i++) {
            $status = meta_graph_get($containerId, $igToken, ['fields' => 'status_code']);
            $code = (string) ($status['status_code'] ?? '');
            if ($code === 'FINISHED') {
                $ready = true;
                break;
            }
            if ($code === 'ERROR') {
                ping_log('instagram_content_publish (container)', false, account_error($status) ?: 'container ERROR');
                break;
            }
            sleep(2);
        }

        if ($ready) {
            $published = meta_graph_post_json($igId . '/media_publish', $igToken, [
                'creation_id' => $containerId,
            ]);
            $mediaId = (string) ($published['id'] ?? '');
            if ($mediaId === '') {
                ping_log('instagram_content_publish (publish)', false, account_error($published));
            } else {
                ping_log('instagram_content_publish (publish)', true, $mediaId);

                // manage_contents — delete the media we just published
                $del = meta_graph_delete($mediaId, $igToken);
                $deleted = !isset($del['error']) || ($del['success'] ?? false) === true;
                ping_log(
                    'instagram_manage_contents (delete)',
                    $deleted,
                    $deleted ? 'media removed' : account_error($del)
                );
            }
        } elseif ($containerId !== '' && !in_array('instagram_content_publish (container)', $fail, true)
            && !in_array('instagram_content_publish (container)', $ok, true)
        ) {
            ping_log('instagram_content_publish (container)', false, 'Timed out waiting for FINISHED');
        }
    }
}

echo PHP_EOL;
echo 'Succeeded: ' . count($ok) . ' | Failed: ' . count($fail) . PHP_EOL;
echo "Meta can take up to 24 hours to flip the App Review 'API call(s) required' checkbox.\n";
echo "Hard-refresh the submission page later today.\n";

exit($fail === [] ? 0 : 2);
