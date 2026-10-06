<?php

declare(strict_types=1);

/**
 * Purge stale social post media and orphaned publish-now temp files.
 *
 * Cron (daily): 15 3 * * * php /path/to/scripts/cleanup-social-media.php
 */
require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_cli();

$result = (new SocialMediaService())->cleanupStaleMedia();

if (PHP_SAPI === 'cli') {
    echo '[' . date('c') . '] social_media_cleanup posts_purged=' . $result['posts_purged']
        . ' orphan_files_deleted=' . $result['orphan_files_deleted'] . PHP_EOL;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'posts_purged=' . $result['posts_purged'] . ' orphan_files_deleted=' . $result['orphan_files_deleted'];
}
