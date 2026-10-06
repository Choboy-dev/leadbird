<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/help/helpers.php';

$pageTitle = 'Help — ' . $brandName;
$dashboardNav = 'help';
$pageHeading = 'Help';
$extraStylesheets = ['dashboard-help.css?v=7'];

$topicSlug = trim((string) ($_GET['topic'] ?? ''));
$articleSlug = trim((string) ($_GET['article'] ?? ''));

$helpViewMode = 'home';
$helpActiveTopic = null;
$helpMainPartial = __DIR__ . '/includes/help/views/home.php';

if ($topicSlug !== '' && $articleSlug !== '') {
    $helpArticle = help_find_article($topicSlug, $articleSlug);
    if ($helpArticle === null) {
        http_response_code(404);
        $pageTitle = 'Help article not found — ' . $brandName;
        $helpMainPartial = __DIR__ . '/includes/help/views/not-found.php';
    } else {
        $helpViewMode = 'article';
        $helpActiveTopic = $topicSlug;
        $pageTitle = $helpArticle['title'] . ' — Help — ' . $brandName;
        $helpMainPartial = __DIR__ . '/includes/help/views/article.php';
    }
} elseif ($topicSlug !== '') {
    $helpTopic = help_find_topic($topicSlug);
    if ($helpTopic === null) {
        http_response_code(404);
        $pageTitle = 'Help topic not found — ' . $brandName;
        $helpMainPartial = __DIR__ . '/includes/help/views/not-found.php';
    } else {
        $helpViewMode = 'topic';
        $helpActiveTopic = $topicSlug;
        $pageTitle = $helpTopic['label'] . ' — Help — ' . $brandName;
        $helpMainPartial = __DIR__ . '/includes/help/views/topic.php';
    }
}

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/help/shell.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
