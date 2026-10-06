<?php

declare(strict_types=1);

/**
 * Publish due scheduled social posts.
 *
 * Cron: * * * * * php /path/to/scripts/process-scheduled-posts.php
 */
require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_cli();

$processed = (new SocialMediaService())->processDuePosts(25);
$cleanup = (new SocialMediaService())->cleanupStaleMedia();

if (PHP_SAPI === 'cli') {
    echo '[' . date('c') . '] social_posts=' . $processed
        . ' cleanup_posts=' . $cleanup['posts_purged']
        . ' cleanup_orphans=' . $cleanup['orphan_files_deleted'] . PHP_EOL;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'social_posts=' . $processed
        . ' cleanup_posts=' . $cleanup['posts_purged']
        . ' cleanup_orphans=' . $cleanup['orphan_files_deleted'];
}
