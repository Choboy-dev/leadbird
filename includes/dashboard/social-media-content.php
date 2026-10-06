<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string|null $businessId */
/** @var list<array<string, mixed>> $accounts */
/** @var array<string, int> $postStats */
/** @var list<array<string, mixed>> $initialPosts */
?>
<div class="smm-planner" data-smm-root>
    <aside class="smm-planner-sidebar" aria-label="Planner navigation">
        <div class="smm-planner-sidebar-head">
            <h2 class="smm-planner-brand font-heading">Planner</h2>
            <p class="smm-planner-tagline font-sans">Content creation, planning, and publishing.</p>
        </div>

        <div class="smm-planner-create-wrap">
            <button type="button" class="smm-planner-create-btn" data-smm-open-compose>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Create a post
            </button>
        </div>

        <nav class="smm-planner-nav" aria-label="Planner sections">
            <p class="smm-planner-nav-label">Plan</p>
            <button type="button" class="smm-planner-nav-item is-active" data-smm-view="calendar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                Calendar
            </button>
            <button type="button" class="smm-planner-nav-item" data-smm-view="drafts">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                Drafts
            </button>
            <button type="button" class="smm-planner-nav-item" data-smm-view="content">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/>
                    <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/>
                    <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/>
                    <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                My Posts
            </button>

            <p class="smm-planner-nav-label">Ads</p>
            <button type="button" class="smm-planner-nav-item is-disabled smm-planner-nav-item--with-badge" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m3 11 18-5v12L3 13v-2Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                    <path d="M11 13v8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                Ads
                <span class="smm-planner-nav-badge">Coming soon</span>
            </button>
            <button type="button" class="smm-planner-nav-item is-disabled" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 20V10M18 20V4M6 20v-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                Boost
            </button>

            <button type="button" class="smm-planner-nav-item is-disabled" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 3v18h18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                    <path d="M7 16l4-8 4 4 5-9" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                My reports
            </button>
        </nav>
    </aside>

    <div class="smm-planner-main">
        <?php if ($accounts === []): ?>
            <section class="smm-planner-view is-active" data-smm-panel="empty">
                <div class="smm-empty-accounts">
                    <div class="smm-empty-card">
                        <h2 class="font-heading">Connect your Page &amp; Instagram first</h2>
                        <p class="font-sans">Social posting goes to your public Facebook Page timeline and Instagram profile feed. Connect those accounts to start.</p>
                        <a href="<?= e(app_route('connected-accounts')) ?>" class="smm-btn smm-btn--primary">Add social account</a>
                    </div>
                </div>
            </section>
        <?php else: ?>

        <section class="smm-planner-view smm-planner-view--calendar is-active" data-smm-panel="calendar">
            <header class="smm-planner-page-head">
                <h1 class="smm-planner-page-title font-heading">Calendar</h1>
                <div class="smm-planner-page-actions">
                    <a href="<?= e(app_route('connected-accounts')) ?>" class="smm-planner-outline-btn">Add social account</a>
                    <button type="button" class="smm-planner-primary-btn" data-smm-open-compose>Create a post</button>
                </div>
            </header>

            <div class="smm-planner-toolbar">
                <div class="smm-planner-toolbar-left">
                    <button type="button" class="smm-planner-icon-btn" data-smm-cal-prev aria-label="Previous">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button type="button" class="smm-planner-today-btn" data-smm-cal-today>Today</button>
                    <button type="button" class="smm-planner-icon-btn" data-smm-cal-next aria-label="Next">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="smm-planner-range-wrap">
                        <button type="button" class="smm-planner-range-btn" data-smm-cal-range aria-haspopup="dialog" aria-expanded="false">
                            <span data-smm-cal-range-label>Aug 2026</span>
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="smm-month-picker is-hidden" data-smm-month-picker role="dialog" aria-label="Choose month">
                            <div class="smm-month-picker-head">
                                <button type="button" class="smm-planner-icon-btn" data-smm-month-prev aria-label="Previous year">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                </button>
                                <span class="smm-month-picker-year font-heading" data-smm-month-picker-year>2026</span>
                                <button type="button" class="smm-planner-icon-btn" data-smm-month-next aria-label="Next year">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                </button>
                            </div>
                            <div class="smm-month-picker-grid" data-smm-month-picker-grid></div>
                        </div>
                    </div>
                </div>
                <div class="smm-planner-toolbar-right">
                    <div class="smm-planner-view-toggle" role="group" aria-label="Calendar view">
                        <button type="button" class="smm-planner-view-btn" data-smm-cal-view="list" aria-pressed="false" aria-label="List view" title="View calendar as list">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                            </svg>
                        </button>
                        <button type="button" class="smm-planner-view-btn" data-smm-cal-view="week" aria-pressed="false" aria-label="Week view" title="View calendar as week">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3" y="4" width="5" height="16" rx="1" stroke="currentColor" stroke-width="1.75"/>
                                <rect x="10" y="4" width="5" height="16" rx="1" stroke="currentColor" stroke-width="1.75"/>
                                <rect x="17" y="4" width="4" height="16" rx="1" stroke="currentColor" stroke-width="1.75"/>
                            </svg>
                        </button>
                        <button type="button" class="smm-planner-view-btn is-active" data-smm-cal-view="month" aria-pressed="true" aria-label="Month view" title="View calendar as month">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/>
                                <path d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                    <div class="smm-planner-filters-wrap">
                        <button
                            type="button"
                            class="smm-planner-outline-btn smm-planner-outline-btn--sm"
                            data-smm-cal-filters
                            aria-haspopup="dialog"
                            aria-expanded="false"
                        >
                            Filters
                            <span class="smm-planner-filters-count font-sans is-hidden" data-smm-cal-filters-count hidden></span>
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="smm-planner-filters-panel is-hidden" data-smm-cal-filters-panel role="dialog" aria-label="Filter calendar" hidden>
                            <div class="smm-planner-filters-head">
                                <span class="smm-planner-filters-title font-heading">Filters</span>
                                <button type="button" class="smm-planner-filters-clear font-sans" data-smm-cal-filters-clear>Clear all</button>
                            </div>
                            <div class="smm-planner-filters-body" data-smm-cal-filters-body></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="smm-planner-volume is-hidden" data-smm-volume>
                <div class="smm-planner-volume-head">
                    <span class="smm-planner-volume-label font-heading">Your post volume</span>
                    <span class="smm-planner-volume-month font-sans" data-smm-volume-month>August 2026</span>
                    <button type="button" class="smm-planner-volume-toggle" data-smm-volume-toggle aria-expanded="true" aria-label="Collapse post volume">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M5 12.5 10 7.5 15 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <div class="smm-planner-volume-track" data-smm-volume-track></div>
            </div>

            <div class="smm-planner-calendar-layout" data-smm-cal-layout>
                <div class="smm-planner-calendar-main">
                    <div class="smm-planner-calendar-wrap">
                        <div class="smm-planner-calendar" data-smm-calendar aria-label="Content calendar"></div>
                    </div>
                </div>

                <aside class="smm-planner-day-panel is-hidden" data-smm-day-panel aria-label="Day details">
                    <header class="smm-day-panel-head">
                        <h2 class="smm-day-panel-date font-heading" data-smm-day-panel-date>Fri Aug 7</h2>
                        <button type="button" class="smm-day-panel-close" data-smm-day-panel-close aria-label="Close day panel">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </header>
                    <div class="smm-day-panel-body">
                        <button type="button" class="smm-day-panel-add" data-smm-day-add aria-label="Add content">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                        <div class="smm-day-panel-menu is-hidden" data-smm-day-menu>
                            <button type="button" class="smm-day-panel-menu-item" data-smm-day-create-post>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" stroke="currentColor" stroke-width="1.75"/></svg>
                                Post
                            </button>
                        </div>
                        <div class="smm-day-panel-slots" data-smm-day-slots></div>
                    </div>
                </aside>
            </div>
        </section>

        <?php require __DIR__ . '/social-media-compose-modal.php'; ?>

        <section class="smm-planner-view" data-smm-panel="drafts" hidden>
            <header class="smm-planner-page-head">
                <h1 class="smm-planner-page-title font-heading">Drafts</h1>
                <div class="smm-planner-page-actions">
                    <button type="button" class="smm-planner-primary-btn" data-smm-open-compose>Create a post</button>
                </div>
            </header>
            <div class="smm-posts-toolbar" data-reveal>
                <div class="smm-toolbar-controls">
                    <div class="smm-toolbar-field">
                        <label class="smm-toolbar-field-label font-sans" for="smm-filter-account-drafts">Account</label>
                        <select id="smm-filter-account-drafts" class="smm-toolbar-select font-sans" data-smm-filter-account-select aria-label="Filter by account">
                            <option value="">All accounts</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= e((string) $account['id']) ?>"><?= e((string) ($account['display_name'] ?? $account['platform_label'] ?? 'Account')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="smm-posts-grid" data-smm-posts-list data-smm-posts-context="drafts"></div>
        </section>

        <section class="smm-planner-view" data-smm-panel="content" hidden>
            <header class="smm-planner-page-head">
                <h1 class="smm-planner-page-title font-heading">My Posts</h1>
                <div class="smm-planner-page-actions">
                    <button type="button" class="smm-planner-primary-btn" data-smm-open-compose>Create a post</button>
                </div>
            </header>
            <div class="smm-posts-toolbar" data-reveal>
                <div class="smm-toolbar-controls">
                    <div class="smm-toolbar-field">
                        <label class="smm-toolbar-field-label font-sans" for="smm-filter-account">Account</label>
                        <select id="smm-filter-account" class="smm-toolbar-select font-sans" data-smm-filter-account-select aria-label="Filter by account">
                            <option value="">All accounts</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= e((string) $account['id']) ?>"><?= e((string) ($account['display_name'] ?? $account['platform_label'] ?? 'Account')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="smm-toolbar-field">
                        <span class="smm-toolbar-field-label font-sans" id="smm-filter-view-label">View</span>
                        <div class="smm-segment" role="tablist" aria-labelledby="smm-filter-view-label">
                            <button type="button" class="smm-segment-btn is-active" role="tab" aria-selected="true" data-smm-source="all">All</button>
                            <button type="button" class="smm-segment-btn" role="tab" aria-selected="false" data-smm-source="live">Live</button>
                            <button type="button" class="smm-segment-btn" role="tab" aria-selected="false" data-smm-source="leadbird">Queue</button>
                        </div>
                    </div>
                    <div class="smm-toolbar-field" data-smm-status-wrap>
                        <label class="smm-toolbar-field-label font-sans" for="smm-filter-status">Queue status</label>
                        <select id="smm-filter-status" class="smm-toolbar-select font-sans" data-smm-filter-select aria-label="Filter by queue status">
                            <option value="all">All statuses</option>
                            <option value="draft">Drafts</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="published">Published</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="smm-posts-grid" data-smm-posts-list data-smm-posts-context="content"></div>
        </section>

        <div class="smm-modal is-hidden" data-smm-modal hidden aria-hidden="true">
            <div class="smm-modal-backdrop" data-smm-modal-close></div>
            <div class="smm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="smm-modal-title">
                <header class="smm-modal-head">
                    <h2 class="font-heading" id="smm-modal-title" data-smm-modal-title>Edit post</h2>
                    <button type="button" class="smm-modal-close" data-smm-modal-close aria-label="Close">&times;</button>
                </header>
                <form class="smm-modal-form" data-smm-edit-form>
                    <input type="hidden" name="post_id" data-smm-edit-id>
                    <input type="hidden" name="edit_source" data-smm-edit-source>
                    <input type="hidden" name="account_id" data-smm-edit-account-id>
                    <input type="hidden" name="platform_post_id" data-smm-edit-platform-id>
                    <label class="smm-label" for="smm-edit-caption">Caption</label>
                    <textarea id="smm-edit-caption" name="caption" class="smm-textarea" rows="5" data-smm-edit-caption></textarea>
                    <div data-smm-edit-hashtags-wrap>
                        <label class="smm-label" for="smm-edit-hashtags">Hashtags</label>
                        <input id="smm-edit-hashtags" name="hashtags" type="text" class="smm-input" data-smm-edit-hashtags>
                    </div>
                    <p class="smm-modal-note font-sans" data-smm-edit-note></p>
                    <div class="smm-modal-actions">
                        <button type="button" class="smm-btn smm-btn--ghost" data-smm-modal-close>Cancel</button>
                        <button type="submit" class="smm-btn smm-btn--primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="smm-modal is-hidden" data-smm-insights-modal hidden aria-hidden="true">
            <div class="smm-modal-backdrop" data-smm-insights-close></div>
            <div class="smm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="smm-insights-title">
                <header class="smm-modal-head">
                    <h2 class="font-heading" id="smm-insights-title" data-smm-insights-title>Post insights</h2>
                    <button type="button" class="smm-modal-close" data-smm-insights-close aria-label="Close">&times;</button>
                </header>
                <div class="smm-insights-modal-body" data-smm-insights-body>
                    <p class="smm-insights-loading font-sans">Loading insights…</p>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<script>
    window.LeadBirdSocialMedia = {
        appUrl: <?= json_encode($appUrl, JSON_THROW_ON_ERROR) ?>,
        accounts: <?= json_encode($accounts, JSON_THROW_ON_ERROR) ?>,
        platformIcons: <?= json_encode([
            'instagram' => dash_platform_badge_icon('instagram', 20),
            'facebook' => dash_platform_badge_icon('facebook', 20),
            'tiktok' => dash_platform_badge_icon('tiktok', 20),
        ], JSON_THROW_ON_ERROR) ?>,
        tiktokEnabled: <?= tiktok_is_enabled() ? 'true' : 'false' ?>,
        endpoints: {
            posts: <?= json_encode(app_route('social-media/posts'), JSON_THROW_ON_ERROR) ?>,
            feed: <?= json_encode(app_route('social-media/feed'), JSON_THROW_ON_ERROR) ?>,
            save: <?= json_encode(app_route('social-media/save'), JSON_THROW_ON_ERROR) ?>,
            update: <?= json_encode(app_route('social-media/update'), JSON_THROW_ON_ERROR) ?>,
            generate: <?= json_encode(app_route('social-media/generate'), JSON_THROW_ON_ERROR) ?>,
            delete: <?= json_encode(app_route('social-media/delete'), JSON_THROW_ON_ERROR) ?>,
            insights: <?= json_encode(app_route('social-media/insights'), JSON_THROW_ON_ERROR) ?>,
            tiktokCreatorInfo: <?= json_encode(app_route('social-media/tiktok-creator-info'), JSON_THROW_ON_ERROR) ?>,
            insightsPage: <?= json_encode(app_route('social-media-insights'), JSON_THROW_ON_ERROR) ?>,
        },
        initialPosts: <?= json_encode($initialPosts, JSON_THROW_ON_ERROR) ?>,
        stats: <?= json_encode($postStats, JSON_THROW_ON_ERROR) ?>,
    };
</script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-social-media-video-editor.js?v=8"></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-social-media.js?v=85"></script>
