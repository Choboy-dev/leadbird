<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var list<array<string, mixed>> $accounts */

$composeAccountsByPlatform = [];
foreach ($accounts as $composeAccount) {
    $composePlatform = (string) ($composeAccount['platform'] ?? '');
    if ($composePlatform === '') {
        continue;
    }
    $composeAccountsByPlatform[$composePlatform][] = $composeAccount;
}
$composePlatformOrder = ['tiktok', 'instagram', 'facebook'];
$composePreviewWifiIcon = '<img src="' . e($appUrl) . '/assets/images/wifi-icon.png" alt="" class="smm-compose-preview-wifi-icon" width="14" height="10" loading="lazy" decoding="async" aria-hidden="true">';
$composePreviewNoteIcon = static function (int $size) use ($appUrl): string {
    return '<img src="' . e($appUrl) . '/assets/images/tiktok-music-note.png?v=1" alt=""'
        . ' class="smm-compose-preview-tiktok-note" width="' . $size . '" height="' . $size . '"'
        . ' loading="lazy" decoding="async" aria-hidden="true">';
};
?>
<div class="smm-compose-modal is-hidden" data-smm-compose-modal hidden aria-hidden="true">
    <div class="smm-compose-modal-backdrop" data-smm-compose-close></div>
    <div class="smm-compose-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="smm-compose-modal-title" data-smm-compose-dialog>
        <header class="smm-compose-modal-head">
            <h2 id="smm-compose-modal-title" class="smm-compose-modal-title font-sans">Create a post</h2>
            <div class="smm-compose-modal-head-actions">
                <button type="button" class="smm-compose-wisdom-btn" data-smm-wisdom-toggle aria-pressed="false" aria-controls="smm-compose-wisdom-panel">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                    </svg>
                    Ask Hannah
                </button>
                <button type="button" class="smm-compose-modal-icon-btn" data-smm-compose-close aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </header>

        <div class="smm-compose-modal-body is-wisdom-hidden">
            <div class="smm-compose-col smm-compose-col--editor">
                <form class="smm-compose-modal-form" data-smm-form enctype="multipart/form-data">
                    <div class="smm-compose-field">
                        <div class="smm-compose-field-head">
                            <label class="smm-compose-field-label font-sans" id="smm-compose-publish-label">Publish to</label>
                            <button type="button" class="smm-compose-clear-accounts is-hidden font-sans" data-smm-clear-accounts>Clear accounts</button>
                        </div>
                        <div class="smm-compose-account-picker" data-smm-account-picker>
                            <button
                                type="button"
                                class="smm-compose-account-trigger"
                                data-smm-account-trigger
                                aria-expanded="false"
                                aria-labelledby="smm-compose-publish-label"
                            >
                                <span class="smm-compose-account-trigger-content" data-smm-account-trigger-content>
                                    <span class="smm-compose-account-placeholder font-sans" data-smm-account-placeholder>Select a social account (required)</span>
                                    <span class="smm-compose-account-pills" data-smm-account-pills hidden></span>
                                </span>
                                <svg class="smm-compose-account-chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div class="smm-compose-account-menu" data-smm-account-menu hidden>
                                <?php foreach ($composePlatformOrder as $composePlatform): ?>
                                    <?php
                                    $platformAccounts = $composeAccountsByPlatform[$composePlatform] ?? [];
                                    if ($platformAccounts === []) {
                                        continue;
                                    }
                                    ?>
                                    <div class="smm-compose-account-group">
                                        <p class="smm-compose-account-group-label font-sans">
                                            <span class="smm-compose-account-group-icon" aria-hidden="true"><?= dash_platform_badge_icon($composePlatform, 16) ?></span>
                                            <?= e(dash_social_compose_group_heading($composePlatform, count($platformAccounts))) ?>
                                        </p>
                                        <?php foreach ($platformAccounts as $account): ?>
                                            <?php
                                            $platform = (string) ($account['platform'] ?? $composePlatform);
                                            $displayName = (string) ($account['display_name'] ?? $account['platform_label'] ?? 'Account');
                                            $initial = mb_strtoupper(mb_substr(trim($displayName), 0, 1));
                                            $avatarSrc = trim((string) ($account['avatar_src'] ?? ''));
                                            $accountType = (string) ($account['account_type'] ?? dash_social_compose_account_subtitle($platform));
                                            ?>
                                            <label class="smm-compose-account-option smm-compose-account-option--<?= e($platform) ?>">
                                                <input
                                                    type="checkbox"
                                                    name="account_ids[]"
                                                    value="<?= e((string) $account['id']) ?>"
                                                    class="smm-compose-account-input"
                                                    data-smm-compose-account
                                                    data-display-name="<?= e($displayName) ?>"
                                                    data-platform="<?= e($platform) ?>"
                                                    data-avatar-src="<?= e($avatarSrc) ?>"
                                                >
                                                <span class="smm-compose-account-avatar smm-compose-account-avatar--<?= e($platform) ?>" aria-hidden="true">
                                                    <?php if ($avatarSrc !== ''): ?>
                                                        <img
                                                            src="<?= e($avatarSrc) ?>"
                                                            alt=""
                                                            class="smm-compose-account-photo"
                                                            width="36"
                                                            height="36"
                                                            loading="lazy"
                                                            data-smm-compose-avatar-img
                                                            data-initial="<?= e($initial) ?>"
                                                        >
                                                        <span class="smm-compose-account-initial is-hidden" aria-hidden="true"><?= e($initial) ?></span>
                                                    <?php else: ?>
                                                        <span class="smm-compose-account-initial"><?= e($initial) ?></span>
                                                    <?php endif; ?>
                                                    <span class="smm-compose-account-badge smm-compose-account-badge--<?= e($platform) ?>">
                                                        <?= dash_platform_badge_icon($platform, 14) ?>
                                                    </span>
                                                </span>
                                                <span class="smm-compose-account-copy">
                                                    <span class="smm-compose-account-name font-sans"><?= e($displayName) ?></span>
                                                    <?php if (trim($accountType) !== ''): ?>
                                                        <span class="smm-compose-account-type font-sans"><?= e($accountType) ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="smm-compose-field smm-compose-post-field">
                        <div class="smm-compose-post-head">
                            <div class="smm-compose-post-head-left">
                                <span class="smm-compose-field-label font-sans" id="smm-compose-post-label">Your post</span>
                                <div class="smm-compose-platform-tabs is-hidden" data-smm-platform-tabs role="tablist" aria-labelledby="smm-compose-post-label" hidden></div>
                            </div>
                            <div class="smm-compose-post-type-wrap" data-smm-post-type-wrap>
                                <button type="button" class="smm-compose-post-type font-sans" data-smm-post-type-trigger aria-expanded="false" aria-haspopup="listbox">
                                    <span data-smm-post-type-label>Post</span>
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <div class="smm-compose-post-type-menu is-hidden" data-smm-post-type-menu role="listbox" aria-label="Post type" hidden>
                                    <button type="button" class="smm-compose-post-type-option font-sans is-selected" data-smm-post-type="post" role="option" aria-selected="true">Post</button>
                                    <button type="button" class="smm-compose-post-type-option font-sans" data-smm-post-type="story" role="option" aria-selected="false">Story</button>
                                    <button type="button" class="smm-compose-post-type-option font-sans" data-smm-post-type="reel" data-smm-post-type-platform="facebook" role="option" aria-selected="false">Reel</button>
                                </div>
                            </div>
                        </div>
                        <div class="smm-compose-editor-shell">
                            <div class="smm-compose-story-notice is-hidden" data-smm-story-notice="instagram" hidden>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 10v5M12 7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                <p class="font-sans">Your caption is placed on the story image when you publish.</p>
                            </div>
                            <div class="smm-compose-story-notice is-hidden" data-smm-story-notice="facebook" hidden>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 10v5M12 7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                <p class="font-sans">Your caption is placed on the story image when you publish.</p>
                            </div>
                            <div class="smm-compose-error-alerts is-hidden" data-smm-compose-error-alerts hidden aria-live="polite"></div>
                            <div class="smm-compose-editor">
                                <div class="smm-compose-caption-shell">
                                    <textarea
                                        id="smm-caption"
                                        name="caption"
                                        class="smm-compose-caption font-sans"
                                        rows="8"
                                        placeholder="Write your caption, then customize it for each social network"
                                        data-smm-caption
                                        autocomplete="off"
                                    ></textarea>
                                    <div class="smm-compose-caption-meta">
                                        <span class="smm-compose-char-count font-sans" data-smm-char-count aria-live="polite">0 / 63,206</span>
                                        <div class="smm-compose-caption-tools">
                                            <button type="button" class="smm-compose-enhance-link font-sans" data-smm-generate="caption" data-smm-ai-prompt-btn="Enhance my caption for social media engagement.">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                                </svg>
                                                <span>Enhance with Hannah</span>
                                            </button>
                                            <div class="smm-compose-caption-tool-icons" role="group" aria-label="Caption tools">
                                                <div class="smm-compose-emoji-anchor">
                                                    <button type="button" class="smm-compose-caption-tool-btn" data-smm-insert-emoji aria-label="Add emoji" aria-haspopup="dialog" aria-expanded="false">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
                                                            <path d="M8.5 10.5h.01M15.5 10.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M8.75 14.75a4.25 4.25 0 0 0 6.5 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                        </svg>
                                                    </button>
                                                    <div class="smm-compose-emoji-picker is-hidden" data-smm-emoji-picker role="dialog" aria-label="Choose an emoji" hidden>
                                                        <div class="smm-compose-emoji-picker-head">
                                                            <p class="smm-compose-emoji-picker-title font-heading">Emoji</p>
                                                            <button type="button" class="smm-compose-emoji-picker-close" data-smm-emoji-close aria-label="Close emoji picker">
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                                                            </button>
                                                        </div>
                                                        <div class="smm-compose-emoji-picker-body" data-smm-emoji-grid></div>
                                                    </div>
                                                </div>
                                                <button type="button" class="smm-compose-caption-tool-btn" data-smm-generate="hashtags" aria-label="Add hashtags">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="M5 9h14M5 15h14M10 4v16M14 4v16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="smm-compose-attach-row">
                                    <div class="smm-compose-attach-media">
                                        <button type="button" class="smm-compose-attach-btn smm-compose-attach-btn--add" data-smm-trigger-media aria-label="Upload media">
                                            <svg class="smm-compose-attach-btn-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.75"/>
                                                <circle cx="8.5" cy="10" r="1.5" fill="currentColor"/>
                                                <path d="m3 16 5-5 4 4 3-3 6 6" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        <div class="smm-compose-media-strip is-empty" data-smm-media-grid aria-live="polite"></div>
                                    </div>
                                    <input type="file" id="smm-media-input" name="media[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime" class="smm-upload-input" data-smm-media-input multiple>
                                </div>

                                <div class="smm-compose-tiktok-options is-hidden" data-smm-tiktok-options hidden>
                                    <div class="smm-compose-tiktok-privacy">
                                        <label class="smm-compose-tiktok-privacy-label font-sans" for="smm-tiktok-privacy">Who can watch this video</label>
                                        <select id="smm-tiktok-privacy" class="smm-compose-tiktok-privacy-select tiktok-privacy-select font-sans" data-smm-tiktok-privacy data-no-search>
                                            <?php // TikTok requires a manual choice here — no pre-selected audience. ?>
                                            <option value="" selected hidden>Select an audience</option>
                                            <option value="PUBLIC_TO_EVERYONE">Everyone</option>
                                            <option value="MUTUAL_FOLLOW_FRIENDS">Friends</option>
                                            <option value="FOLLOWER_OF_CREATOR">Followers</option>
                                            <option value="SELF_ONLY">Only me</option>
                                        </select>
                                    </div>
                                    <?php // TikTok requires every interaction switch to start off. ?>
                                    <div class="smm-compose-tiktok-toggles" role="group" aria-label="TikTok interaction settings">
                                        <label class="smm-compose-tiktok-toggle">
                                            <input type="checkbox" class="smm-compose-tiktok-toggle-input" data-smm-tiktok-toggle="comment">
                                            <span class="smm-compose-tiktok-toggle-track" aria-hidden="true"><span class="smm-compose-tiktok-toggle-knob"></span></span>
                                            <span class="smm-compose-tiktok-toggle-label font-sans">Allow Comments</span>
                                        </label>
                                        <label class="smm-compose-tiktok-toggle">
                                            <input type="checkbox" class="smm-compose-tiktok-toggle-input" data-smm-tiktok-toggle="duet">
                                            <span class="smm-compose-tiktok-toggle-track" aria-hidden="true"><span class="smm-compose-tiktok-toggle-knob"></span></span>
                                            <span class="smm-compose-tiktok-toggle-label font-sans">Allow Duet</span>
                                        </label>
                                        <label class="smm-compose-tiktok-toggle">
                                            <input type="checkbox" class="smm-compose-tiktok-toggle-input" data-smm-tiktok-toggle="stitch">
                                            <span class="smm-compose-tiktok-toggle-track" aria-hidden="true"><span class="smm-compose-tiktok-toggle-knob"></span></span>
                                            <span class="smm-compose-tiktok-toggle-label font-sans">Allow Stitch</span>
                                        </label>
                                    </div>
                                    <p class="smm-compose-tiktok-music-note font-sans">
                                        By posting, you confirm your video follows TikTok's
                                        <a href="https://www.tiktok.com/legal/page/global/music-usage-confirmation/en" target="_blank" rel="noopener">Music Usage Confirmation</a>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="smm-compose-platform-extras is-hidden" data-smm-platform-extras hidden>
                            <div class="smm-compose-first-comment is-hidden" data-smm-first-comment-wrap hidden>
                                <p class="smm-compose-first-comment-title font-sans">First comment</p>
                                <p class="smm-compose-first-comment-note font-sans">First comment is only available for direct publishing and for posts.</p>
                                <textarea class="smm-compose-first-comment-input font-sans" rows="3" placeholder="Write the first comment for this Instagram post" disabled aria-disabled="true"></textarea>
                            </div>

                            <div class="smm-compose-boost">
                                <p class="smm-compose-boost-title font-sans">Boost your post</p>
                                <p class="smm-compose-boost-copy font-sans">Boost your post to reach a wider audience instantly on top of your followers. As soon as you publish, Facebook or Instagram will feature it prominently in your audience's feed.</p>
                                <button type="button" class="smm-compose-boost-btn font-sans" disabled aria-disabled="true">
                                    <img
                                        src="<?= e($appUrl) ?>/assets/images/boost-post-icon.png"
                                        alt=""
                                        width="16"
                                        height="16"
                                        class="smm-compose-boost-btn-icon"
                                        loading="lazy"
                                        decoding="async"
                                        aria-hidden="true"
                                    >
                                    Boost this post
                                    <span class="smm-compose-boost-badge">Coming soon</span>
                                </button>
                            </div>
                        </div>

                        <input id="smm-hashtags" name="hashtags" type="hidden" data-smm-hashtags>
                        <textarea id="smm-ai-prompt" class="smm-upload-input" data-smm-ai-prompt hidden></textarea>
                    </div>

                    <input type="radio" name="publish_mode" value="now" data-smm-mode="now" hidden>
                    <input type="radio" name="publish_mode" value="schedule" data-smm-mode="schedule" checked hidden>
                    <input type="radio" name="publish_mode" value="draft" data-smm-mode="draft" hidden>
                    <input id="smm-scheduled-at" name="scheduled_at" type="hidden" data-smm-scheduled-at>
                </form>
            </div>

            <div class="smm-compose-col smm-compose-col--preview" aria-label="Post preview" data-smm-preview-col>
                <div class="smm-compose-preview-empty font-sans" data-smm-preview-empty>
                    Select social accounts to preview how your post will look on each platform.
                </div>

                <div class="smm-compose-preview-stack is-hidden" data-smm-preview-stack hidden>
                    <article class="smm-compose-preview-panel is-hidden" data-smm-preview-panel="tiktok" data-smm-preview-format="post" hidden aria-label="TikTok post preview">
                        <header class="smm-compose-preview-platform-head">
                            <span class="smm-compose-preview-platform-icon" aria-hidden="true"><?= brand_tiktok_icon_html('smm-compose-preview-platform-icon', 22) ?></span>
                            <h3 class="smm-compose-preview-platform-title font-sans" data-smm-preview-title="tiktok-post">TikTok Post</h3>
                        </header>
                        <div class="smm-compose-preview-phone smm-compose-preview-phone--tiktok">
                            <div class="smm-compose-preview-phone-screen">
                                <div class="smm-compose-preview-tiktok-chrome" aria-hidden="true">
                                    <div class="smm-compose-preview-phone-status">
                                        <span>2:11 PM</span>
                                        <span class="smm-compose-preview-phone-status-icons">
                                            <svg width="14" height="10" viewBox="0 0 16 12" fill="none"><rect x="0.5" y="8" width="2" height="3.5" rx="0.5" fill="currentColor"/><rect x="4" y="6" width="2" height="5.5" rx="0.5" fill="currentColor"/><rect x="7.5" y="4" width="2" height="7.5" rx="0.5" fill="currentColor"/><rect x="11" y="2" width="2" height="9.5" rx="0.5" fill="currentColor"/></svg>
                                            <?= $composePreviewWifiIcon ?>
                                            <svg width="18" height="9" viewBox="0 0 22 11" fill="none"><rect x="0.5" y="0.5" width="18" height="10" rx="2" stroke="currentColor"/><rect x="2" y="2" width="12" height="7" rx="1" fill="currentColor"/><rect x="19" y="3" width="2.5" height="5" rx="1" fill="currentColor"/></svg>
                                        </span>
                                    </div>
                                    <div class="smm-compose-preview-tiktok-tabs">
                                        <span class="smm-compose-preview-tiktok-tab font-sans">Explore</span>
                                        <span class="smm-compose-preview-tiktok-tab font-sans">Following</span>
                                        <span class="smm-compose-preview-tiktok-tab is-active font-sans">For You</span>
                                        <span class="smm-compose-preview-tiktok-search">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="smm-compose-preview-media smm-compose-preview-media--tiktok is-empty" data-smm-preview-media="tiktok">
                                    <span class="font-sans">Your video shows here</span>
                                </div>
                                <div class="smm-compose-preview-tiktok-overlay" aria-hidden="true">
                                    <div class="smm-compose-preview-tiktok-rail">
                                        <span class="smm-compose-preview-tiktok-avatar" data-smm-preview-avatar="tiktok">
                                            <span class="smm-compose-preview-tiktok-initial font-heading">Y</span>
                                        </span>
                                        <span class="smm-compose-preview-tiktok-action">
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                        </span>
                                        <span class="smm-compose-preview-tiktok-action">
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.9 3 3 6.4 3 10.6c0 2.4 1.3 4.5 3.4 5.9L5.6 21l4.2-2.3c.7.1 1.5.2 2.2.2 5.1 0 9-3.4 9-7.6S17.1 3 12 3Z"/></svg>
                                        </span>
                                        <span class="smm-compose-preview-tiktok-action">
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M13 4l8 7-8 7v-4.2c-4 0-6.8 1.2-8.8 4 .5-4.9 3.3-8.2 8.8-8.6V4Z"/></svg>
                                        </span>
                                        <span class="smm-compose-preview-tiktok-disc">
                                            <?= $composePreviewNoteIcon(15) ?>
                                        </span>
                                    </div>
                                    <div class="smm-compose-preview-tiktok-meta">
                                        <p class="smm-compose-preview-tiktok-name font-sans" data-smm-preview-account-name="tiktok">your.account</p>
                                        <p class="smm-compose-preview-tiktok-caption font-sans is-hidden" data-smm-preview-caption="tiktok" hidden></p>
                                        <p class="smm-compose-preview-tiktok-audio font-sans">
                                            <?= $composePreviewNoteIcon(11) ?>
                                            <span>Audio</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="smm-compose-preview-story-playback is-hidden" data-smm-tiktok-playback hidden>
                                <div class="smm-compose-preview-story-playback-scrubber" aria-hidden="true">
                                    <span class="smm-compose-preview-story-playback-scrubber-fill" data-smm-tiktok-scrubber-fill></span>
                                    <span class="smm-compose-preview-story-playback-scrubber-handle" data-smm-tiktok-scrubber-handle></span>
                                </div>
                                <div class="smm-compose-preview-story-playback-controls">
                                    <button type="button" class="smm-compose-preview-story-playback-btn" data-smm-tiktok-play aria-label="Play">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-tiktok-play-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-tiktok-pause-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden><path d="M7 5h3v14H7V5Zm7 0h3v14h-3V5Z" fill="currentColor"/></svg>
                                    </button>
                                    <span class="smm-compose-preview-story-playback-time font-sans" data-smm-tiktok-playback-time>0:00 / 0:05</span>
                                    <button type="button" class="smm-compose-preview-story-playback-volume" data-smm-tiktok-volume aria-label="Mute">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-tiktok-volume-on width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="M15.5 8.5a4 4 0 0 1 0 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                            <path d="M18 6a7 7 0 0 1 0 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-tiktok-volume-off width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="m16 9 5 6M21 9l-5 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?= dash_compose_preview_disclaimer_html() ?>
                    </article>

                    <article class="smm-compose-preview-panel is-hidden" data-smm-preview-panel="facebook" data-smm-preview-format="post" hidden aria-label="Facebook post preview">
                        <header class="smm-compose-preview-platform-head">
                            <span class="smm-compose-preview-platform-icon" aria-hidden="true"><?= brand_facebook_icon_html('smm-compose-preview-platform-icon', 22) ?></span>
                            <h3 class="smm-compose-preview-platform-title font-sans" data-smm-preview-title="facebook-post">Facebook Post</h3>
                        </header>
                        <div class="smm-compose-preview-fb-card">
                            <div class="smm-compose-preview-fb-top">
                                <div class="smm-compose-preview-fb-avatar" data-smm-preview-avatar="facebook" aria-hidden="true">
                                    <span class="smm-compose-preview-fb-initial font-heading">Y</span>
                                </div>
                                <div class="smm-compose-preview-fb-meta">
                                    <p class="smm-compose-preview-fb-name font-sans" data-smm-preview-account-name="facebook">Your account</p>
                                    <p class="smm-compose-preview-fb-time font-sans">
                                        Just now
                                        <span aria-hidden="true">·</span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z" stroke="currentColor" stroke-width="1.75"/></svg>
                                    </p>
                                </div>
                                <button type="button" class="smm-compose-preview-menu" tabindex="-1" aria-hidden="true">⋯</button>
                            </div>
                            <p class="smm-compose-preview-fb-caption font-sans is-hidden" data-smm-preview-caption="facebook" hidden></p>
                            <div class="smm-compose-preview-media smm-compose-preview-media--facebook is-empty" data-smm-preview-media="facebook">
                                <span class="font-sans">Your photo shows here</span>
                            </div>
                            <div class="smm-compose-preview-fb-post-playback is-hidden" data-smm-fb-post-playback hidden>
                                <div class="smm-compose-preview-story-playback-scrubber" aria-hidden="true">
                                    <span class="smm-compose-preview-story-playback-scrubber-fill" data-smm-fb-post-scrubber-fill></span>
                                    <span class="smm-compose-preview-story-playback-scrubber-handle" data-smm-fb-post-scrubber-handle></span>
                                </div>
                                <div class="smm-compose-preview-story-playback-controls">
                                    <button type="button" class="smm-compose-preview-story-playback-btn" data-smm-fb-post-play aria-label="Play">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-post-play-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-post-pause-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden><path d="M7 5h3v14H7V5Zm7 0h3v14h-3V5Z" fill="currentColor"/></svg>
                                    </button>
                                    <span class="smm-compose-preview-story-playback-time font-sans" data-smm-fb-post-playback-time>0:00 / 0:05</span>
                                    <button type="button" class="smm-compose-preview-story-playback-volume" data-smm-fb-post-volume aria-label="Unmute">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-post-volume-on width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="M15.5 8.5a4 4 0 0 1 0 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                            <path d="M18 6a7 7 0 0 1 0 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-post-volume-off width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="m16 9 5 6M21 9l-5 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="smm-compose-preview-fb-actions-img-wrap" aria-hidden="true">
                                <img
                                    src="<?= e($appUrl) ?>/assets/images/social-media/fb-post-actions-bar.png"
                                    alt=""
                                    class="smm-compose-preview-fb-actions-img"
                                    width="502"
                                    height="40"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        </div>
                        <?= dash_compose_preview_disclaimer_html() ?>
                    </article>

                    <article class="smm-compose-preview-panel is-hidden" data-smm-preview-panel="facebook" data-smm-preview-format="story" hidden aria-label="Facebook story preview">
                        <header class="smm-compose-preview-platform-head">
                            <span class="smm-compose-preview-platform-icon" aria-hidden="true"><?= brand_facebook_icon_html('smm-compose-preview-platform-icon', 22) ?></span>
                            <h3 class="smm-compose-preview-platform-title font-sans" data-smm-preview-title="facebook-story">Facebook Story</h3>
                        </header>
                        <div class="smm-compose-preview-phone smm-compose-preview-phone--story smm-compose-preview-phone--fb-story">
                            <div class="smm-compose-preview-phone-screen">
                                <div class="smm-compose-preview-story-chrome" aria-hidden="true">
                                    <div class="smm-compose-preview-phone-status">
                                        <span>2:11 PM</span>
                                        <span class="smm-compose-preview-phone-status-icons">
                                            <svg width="14" height="10" viewBox="0 0 16 12" fill="none"><rect x="0.5" y="8" width="2" height="3.5" rx="0.5" fill="currentColor"/><rect x="4" y="6" width="2" height="5.5" rx="0.5" fill="currentColor"/><rect x="7.5" y="4" width="2" height="7.5" rx="0.5" fill="currentColor"/><rect x="11" y="2" width="2" height="9.5" rx="0.5" fill="currentColor"/></svg>
                                            <?= $composePreviewWifiIcon ?>
                                            <svg width="18" height="9" viewBox="0 0 22 11" fill="none"><rect x="0.5" y="0.5" width="18" height="10" rx="2" stroke="currentColor"/><rect x="2" y="2" width="12" height="7" rx="1" fill="currentColor"/><rect x="19" y="3" width="2.5" height="5" rx="1" fill="currentColor"/></svg>
                                        </span>
                                    </div>
                                    <div class="smm-compose-preview-story-progress"><span></span></div>
                                    <div class="smm-compose-preview-story-head">
                                        <div class="smm-compose-preview-story-user">
                                            <div class="smm-compose-preview-fb-avatar smm-compose-preview-fb-avatar--sm" data-smm-preview-avatar="facebook-story" aria-hidden="true">
                                                <span class="smm-compose-preview-fb-initial font-heading">Y</span>
                                            </div>
                                            <p class="smm-compose-preview-story-name font-sans" data-smm-preview-account-name="facebook-story">Your account</p>
                                        </div>
                                        <div class="smm-compose-preview-story-actions">
                                            <button type="button" class="smm-compose-preview-story-menu" tabindex="-1" aria-hidden="true">⋯</button>
                                            <button type="button" class="smm-compose-preview-story-close" tabindex="-1" aria-hidden="true">×</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="smm-compose-preview-media smm-compose-preview-media--story is-empty" data-smm-preview-media="facebook-story">
                                    <span class="font-sans">Your photo shows here</span>
                                    <div class="smm-compose-preview-story-caption is-hidden" data-smm-preview-story-caption="facebook" hidden aria-hidden="true"></div>
                                </div>
                            </div>
                            <div class="smm-compose-preview-story-foot smm-compose-preview-story-foot--fb is-hidden" data-smm-fb-story-foot hidden aria-hidden="true">
                                <button type="button" class="smm-compose-preview-story-share font-sans" tabindex="-1">
                                    <?= dash_facebook_post_preview_action_icon('share', 16) ?>
                                    Share
                                </button>
                                <div class="smm-compose-preview-story-reactions">
                                    <span class="smm-compose-preview-story-reaction smm-compose-preview-story-reaction--love" aria-hidden="true">♥</span>
                                    <span class="smm-compose-preview-story-reaction smm-compose-preview-story-reaction--like" aria-hidden="true">👍</span>
                                    <span class="smm-compose-preview-story-reaction smm-compose-preview-story-reaction--haha" aria-hidden="true">😆</span>
                                </div>
                            </div>
                            <div class="smm-compose-preview-story-playback is-hidden" data-smm-fb-story-playback hidden>
                                <div class="smm-compose-preview-story-playback-scrubber" aria-hidden="true">
                                    <span class="smm-compose-preview-story-playback-scrubber-fill" data-smm-fb-story-scrubber-fill></span>
                                    <span class="smm-compose-preview-story-playback-scrubber-handle" data-smm-fb-story-scrubber-handle></span>
                                </div>
                                <div class="smm-compose-preview-story-playback-controls">
                                    <button type="button" class="smm-compose-preview-story-playback-btn" data-smm-fb-story-play aria-label="Play">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-story-play-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-story-pause-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden><path d="M7 5h3v14H7V5Zm7 0h3v14h-3V5Z" fill="currentColor"/></svg>
                                    </button>
                                    <span class="smm-compose-preview-story-playback-time font-sans" data-smm-fb-story-playback-time>0:00 / 0:05</span>
                                    <button type="button" class="smm-compose-preview-story-playback-volume" data-smm-fb-story-volume aria-label="Unmute">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-story-volume-on width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="M15.5 8.5a4 4 0 0 1 0 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                            <path d="M18 6a7 7 0 0 1 0 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-story-volume-off width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="m16 9 5 6M21 9l-5 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?= dash_compose_preview_disclaimer_html() ?>
                    </article>

                    <article class="smm-compose-preview-panel is-hidden" data-smm-preview-panel="facebook" data-smm-preview-format="reel" hidden aria-label="Facebook reel preview">
                        <header class="smm-compose-preview-platform-head">
                            <span class="smm-compose-preview-platform-icon" aria-hidden="true"><?= brand_facebook_icon_html('smm-compose-preview-platform-icon', 22) ?></span>
                            <h3 class="smm-compose-preview-platform-title font-sans" data-smm-preview-title="facebook-reel">Facebook Reel</h3>
                        </header>
                        <div class="smm-compose-preview-phone smm-compose-preview-phone--fb-reel">
                            <div class="smm-compose-preview-phone-screen">
                            <div class="smm-compose-preview-phone-status" aria-hidden="true">
                                <span>2:11 PM</span>
                                <span class="smm-compose-preview-phone-status-icons">
                                    <svg width="14" height="10" viewBox="0 0 16 12" fill="none"><rect x="0.5" y="8" width="2" height="3.5" rx="0.5" fill="currentColor"/><rect x="4" y="6" width="2" height="5.5" rx="0.5" fill="currentColor"/><rect x="7.5" y="4" width="2" height="7.5" rx="0.5" fill="currentColor"/><rect x="11" y="2" width="2" height="9.5" rx="0.5" fill="currentColor"/></svg>
                                    <?= $composePreviewWifiIcon ?>
                                    <svg width="18" height="9" viewBox="0 0 22 11" fill="none"><rect x="0.5" y="0.5" width="18" height="10" rx="2" stroke="currentColor"/><rect x="2" y="2" width="12" height="7" rx="1" fill="currentColor"/><rect x="19" y="3" width="2.5" height="5" rx="1" fill="currentColor"/></svg>
                                </span>
                            </div>
                            <div class="smm-compose-preview-reel-top" aria-hidden="true">
                                <button type="button" class="smm-compose-preview-reel-back" tabindex="-1">‹</button>
                                <div class="smm-compose-preview-reel-top-icons">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="1.75"/></svg>
                                    <span class="smm-compose-preview-reel-profile-dot"></span>
                                </div>
                            </div>
                            <div class="smm-compose-preview-media smm-compose-preview-media--reel is-empty" data-smm-preview-media="facebook-reel">
                                <span class="font-sans">Your video shows here</span>
                            </div>
                            <div class="smm-compose-preview-reel-overlay" aria-hidden="true">
                                <div class="smm-compose-preview-reel-meta">
                                    <div class="smm-compose-preview-fb-avatar smm-compose-preview-fb-avatar--sm" data-smm-preview-avatar="facebook-reel" aria-hidden="true">
                                        <span class="smm-compose-preview-fb-initial font-heading">Y</span>
                                    </div>
                                    <p class="smm-compose-preview-story-name font-sans" data-smm-preview-account-name="facebook-reel">Your account</p>
                                    <button type="button" class="smm-compose-preview-reel-follow font-sans" tabindex="-1">Follow</button>
                                </div>
                                <div class="smm-compose-preview-reel-actions">
                                    <div class="smm-compose-preview-reel-action-group">
                                    <img
                                        src="<?= e($appUrl) ?>/assets/images/social-media/fb-reel-like-icon.png?v=2"
                                        alt=""
                                        class="smm-compose-preview-reel-action-icon smm-compose-preview-reel-action-icon--like"
                                        width="22"
                                        height="22"
                                        loading="lazy"
                                        decoding="async"
                                        aria-hidden="true"
                                    >
                                    <img
                                        src="<?= e($appUrl) ?>/assets/images/social-media/fb-reel-comment-icon.png"
                                        alt=""
                                        class="smm-compose-preview-reel-action-icon smm-compose-preview-reel-action-icon--comment"
                                        width="22"
                                        height="22"
                                        loading="lazy"
                                        decoding="async"
                                        aria-hidden="true"
                                    >
                                    <img
                                        src="<?= e($appUrl) ?>/assets/images/social-media/fb-reel-message-icon.png"
                                        alt=""
                                        class="smm-compose-preview-reel-action-icon smm-compose-preview-reel-action-icon--share"
                                        width="22"
                                        height="22"
                                        loading="lazy"
                                        decoding="async"
                                        aria-hidden="true"
                                    >
                                    </div>
                                    <span class="smm-compose-preview-reel-action-more" aria-hidden="true">⋯</span>
                                    <span class="smm-compose-preview-reel-music"></span>
                                </div>
                            </div>
                            </div>
                            <div class="smm-compose-preview-story-playback is-hidden" data-smm-fb-reel-playback hidden>
                                <div class="smm-compose-preview-story-playback-scrubber" aria-hidden="true">
                                    <span class="smm-compose-preview-story-playback-scrubber-fill" data-smm-fb-reel-scrubber-fill></span>
                                    <span class="smm-compose-preview-story-playback-scrubber-handle" data-smm-fb-reel-scrubber-handle></span>
                                </div>
                                <div class="smm-compose-preview-story-playback-controls">
                                    <button type="button" class="smm-compose-preview-story-playback-btn" data-smm-fb-reel-play aria-label="Play">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-reel-play-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-reel-pause-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden><path d="M7 5h3v14H7V5Zm7 0h3v14h-3V5Z" fill="currentColor"/></svg>
                                    </button>
                                    <span class="smm-compose-preview-story-playback-time font-sans" data-smm-fb-reel-playback-time>0:00 / 0:05</span>
                                    <button type="button" class="smm-compose-preview-story-playback-volume" data-smm-fb-reel-volume aria-label="Mute">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-reel-volume-on width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="M15.5 8.5a4 4 0 0 1 0 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                            <path d="M18 6a7 7 0 0 1 0 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-fb-reel-volume-off width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="m16 9 5 6M21 9l-5 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?= dash_compose_preview_disclaimer_html() ?>
                    </article>

                    <article class="smm-compose-preview-panel is-hidden" data-smm-preview-panel="instagram" data-smm-preview-format="post" hidden aria-label="Instagram post preview">
                        <header class="smm-compose-preview-platform-head">
                            <span class="smm-compose-preview-platform-icon" aria-hidden="true"><?= brand_instagram_icon_html('smm-compose-preview-platform-icon', 22) ?></span>
                            <h3 class="smm-compose-preview-platform-title font-sans" data-smm-preview-title="instagram-post">Instagram Post</h3>
                        </header>
                        <div class="smm-compose-preview-ig-card">
                            <div class="smm-compose-preview-ig-top">
                                <div class="smm-compose-preview-ig-avatar" data-smm-preview-avatar="instagram" aria-hidden="true">
                                    <span class="smm-compose-preview-ig-initial font-heading">Y</span>
                                </div>
                                <p class="smm-compose-preview-ig-name font-sans" data-smm-preview-account-name="instagram">your.account</p>
                                <button type="button" class="smm-compose-preview-menu" tabindex="-1" aria-hidden="true">⋯</button>
                            </div>
                            <div class="smm-compose-preview-media smm-compose-preview-media--instagram is-empty" data-smm-preview-media="instagram">
                                <div class="smm-compose-preview-ig-carousel is-hidden" data-smm-ig-carousel hidden tabindex="0" aria-roledescription="carousel">
                                    <button type="button" class="smm-compose-preview-ig-carousel-nav smm-compose-preview-ig-carousel-nav--prev" data-smm-ig-carousel-prev aria-label="Previous slide" hidden>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <div class="smm-compose-preview-ig-carousel-viewport" data-smm-ig-carousel-viewport></div>
                                    <button type="button" class="smm-compose-preview-ig-carousel-nav smm-compose-preview-ig-carousel-nav--next" data-smm-ig-carousel-next aria-label="Next slide" hidden>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <div class="smm-compose-preview-ig-carousel-dots" data-smm-ig-carousel-dots role="tablist" aria-label="Carousel slides"></div>
                                </div>
                                <span class="smm-compose-preview-ig-empty font-sans" data-smm-ig-preview-empty>Your photo shows here</span>
                            </div>
                            <div class="smm-compose-preview-ig-actions" aria-hidden="true">
                                <div class="smm-compose-preview-ig-actions-left">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z" stroke="currentColor" stroke-width="1.75"/></svg>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.75"/></svg>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="m22 2-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.75"/></svg>
                                </div>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.75"/></svg>
                            </div>
                            <p class="smm-compose-preview-ig-username font-sans" data-smm-preview-ig-username-line>your.account</p>
                            <p class="smm-compose-preview-ig-caption-body font-sans is-hidden" data-smm-preview-ig-caption-body hidden></p>
                        </div>
                        <div class="smm-compose-ig-tag-popover is-hidden" data-smm-ig-tag-popover hidden role="dialog" aria-label="Tag someone">
                            <label class="smm-compose-ig-tag-popover-label font-sans" for="smm-ig-tag-input">Instagram username</label>
                            <input id="smm-ig-tag-input" type="text" class="smm-compose-ig-tag-popover-input font-sans" data-smm-ig-tag-input placeholder="@username" autocomplete="off" spellcheck="false">
                            <div class="smm-compose-ig-tag-popover-actions">
                                <button type="button" class="smm-compose-ig-tag-popover-btn font-sans" data-smm-ig-tag-cancel>Cancel</button>
                                <button type="button" class="smm-compose-ig-tag-popover-btn smm-compose-ig-tag-popover-btn--primary font-sans" data-smm-ig-tag-add>Add tag</button>
                            </div>
                        </div>
                        <div class="smm-compose-ig-extras">
                            <section class="smm-compose-ig-collab">
                                <button type="button" class="smm-compose-ig-collab-head font-sans" data-smm-ig-collab-toggle aria-expanded="true">
                                    <span class="smm-compose-ig-collab-title">Invite collaborators</span>
                                    <svg class="smm-compose-ig-info-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 10v5M12 7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    <svg class="smm-compose-ig-collab-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m18 15-6-6-6 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <div class="smm-compose-ig-collab-body" data-smm-ig-collab-body>
                                    <input
                                        id="smm-ig-collaborators"
                                        type="text"
                                        name="instagram_collaborators"
                                        class="smm-compose-ig-collab-input font-sans"
                                        data-smm-ig-collaborators
                                        placeholder="Invite collaborators by entering their public Instagram username here."
                                        autocomplete="off"
                                        spellcheck="false"
                                    >
                                </div>
                            </section>
                            <div class="smm-compose-ig-tag-hint font-sans" data-smm-ig-tag-hint>
                                <span>Click on image to tag people</span>
                                <svg class="smm-compose-ig-info-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 10v5M12 7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </div>
                        </div>
                        <input type="hidden" name="instagram_user_tags" data-smm-ig-user-tags value="">
                        <?= dash_compose_preview_disclaimer_html() ?>
                    </article>

                    <article class="smm-compose-preview-panel is-hidden" data-smm-preview-panel="instagram" data-smm-preview-format="reel" hidden aria-label="Instagram reel preview">
                        <header class="smm-compose-preview-platform-head">
                            <span class="smm-compose-preview-platform-icon" aria-hidden="true"><?= brand_instagram_icon_html('smm-compose-preview-platform-icon', 22) ?></span>
                            <h3 class="smm-compose-preview-platform-title font-sans" data-smm-preview-title="instagram-reel">Instagram Reel</h3>
                        </header>
                        <div class="smm-compose-preview-phone smm-compose-preview-phone--ig-reel">
                            <div class="smm-compose-preview-phone-screen">
                                <div class="smm-compose-preview-story-chrome smm-compose-preview-story-chrome--ig-reel" aria-hidden="true">
                                    <div class="smm-compose-preview-phone-status">
                                        <span>2:11 PM</span>
                                        <span class="smm-compose-preview-phone-status-icons">
                                            <svg width="14" height="10" viewBox="0 0 16 12" fill="none"><rect x="0.5" y="8" width="2" height="3.5" rx="0.5" fill="currentColor"/><rect x="4" y="6" width="2" height="5.5" rx="0.5" fill="currentColor"/><rect x="7.5" y="4" width="2" height="7.5" rx="0.5" fill="currentColor"/><rect x="11" y="2" width="2" height="9.5" rx="0.5" fill="currentColor"/></svg>
                                            <?= $composePreviewWifiIcon ?>
                                            <svg width="18" height="9" viewBox="0 0 22 11" fill="none"><rect x="0.5" y="0.5" width="18" height="10" rx="2" stroke="currentColor"/><rect x="2" y="2" width="12" height="7" rx="1" fill="currentColor"/><rect x="19" y="3" width="2.5" height="5" rx="1" fill="currentColor"/></svg>
                                        </span>
                                    </div>
                                    <button type="button" class="smm-compose-preview-reel-camera" tabindex="-1" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="1.75"/></svg>
                                    </button>
                                </div>
                                <div class="smm-compose-preview-media smm-compose-preview-media--reel is-empty" data-smm-preview-media="instagram-reel">
                                    <span class="font-sans">Your video shows here</span>
                                </div>
                                <div class="smm-compose-preview-reel-overlay smm-compose-preview-reel-overlay--ig" aria-hidden="true">
                                    <div class="smm-compose-preview-reel-bottom">
                                        <div class="smm-compose-preview-reel-meta">
                                            <div class="smm-compose-preview-ig-avatar smm-compose-preview-ig-avatar--sm" data-smm-preview-avatar="instagram-reel" aria-hidden="true">
                                                <span class="smm-compose-preview-ig-initial font-heading">Y</span>
                                            </div>
                                            <p class="smm-compose-preview-story-name font-sans" data-smm-preview-account-name="instagram-reel">your.account</p>
                                            <button type="button" class="smm-compose-preview-reel-follow font-sans" tabindex="-1">Follow</button>
                                            <button type="button" class="smm-compose-preview-story-menu" tabindex="-1" aria-hidden="true">⋯</button>
                                        </div>
                                        <div class="smm-compose-preview-reel-audio font-sans">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 18V5l12-2v13" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="1.75"/><circle cx="18" cy="16" r="3" stroke="currentColor" stroke-width="1.75"/></svg>
                                            <span>Audio</span>
                                            <span class="smm-compose-preview-reel-music"></span>
                                        </div>
                                    </div>
                                    <div class="smm-compose-preview-reel-actions smm-compose-preview-reel-actions--ig">
                                        <span aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z" stroke="currentColor" stroke-width="1.75"/></svg></span>
                                        <span aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.75"/></svg></span>
                                        <span aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="m22 2-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.75"/></svg></span>
                                    </div>
                                </div>
                            </div>
                            <div class="smm-compose-preview-story-playback is-hidden" data-smm-ig-reel-playback hidden>
                                <div class="smm-compose-preview-story-playback-scrubber" aria-hidden="true">
                                    <span class="smm-compose-preview-story-playback-scrubber-fill" data-smm-ig-reel-scrubber-fill></span>
                                    <span class="smm-compose-preview-story-playback-scrubber-handle" data-smm-ig-reel-scrubber-handle></span>
                                </div>
                                <div class="smm-compose-preview-story-playback-controls">
                                    <button type="button" class="smm-compose-preview-story-playback-btn" data-smm-ig-reel-play aria-label="Play">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-reel-play-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-reel-pause-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden><path d="M7 5h3v14H7V5Zm7 0h3v14h-3V5Z" fill="currentColor"/></svg>
                                    </button>
                                    <span class="smm-compose-preview-story-playback-time font-sans" data-smm-ig-reel-playback-time>0:00 / 0:05</span>
                                    <button type="button" class="smm-compose-preview-story-playback-volume" data-smm-ig-reel-volume aria-label="Mute">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-reel-volume-on width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="M15.5 8.5a4 4 0 0 1 0 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                            <path d="M18 6a7 7 0 0 1 0 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-reel-volume-off width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="m16 9 5 6M21 9l-5 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="smm-compose-ig-extras smm-compose-ig-extras--reel">
                            <section class="smm-compose-ig-collab">
                                <button type="button" class="smm-compose-ig-collab-head font-sans" data-smm-ig-collab-toggle aria-expanded="true">
                                    <span class="smm-compose-ig-collab-title">Invite collaborators</span>
                                    <svg class="smm-compose-ig-info-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 10v5M12 7h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    <svg class="smm-compose-ig-collab-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m18 15-6-6-6 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <div class="smm-compose-ig-collab-body" data-smm-ig-collab-body>
                                    <input
                                        type="text"
                                        class="smm-compose-ig-collab-input font-sans"
                                        placeholder="Invite collaborators by entering their public Instagram username here."
                                        autocomplete="off"
                                        spellcheck="false"
                                        disabled
                                        aria-disabled="true"
                                    >
                                </div>
                            </section>
                        </div>
                        <?= dash_compose_preview_disclaimer_html() ?>
                    </article>

                    <article class="smm-compose-preview-panel is-hidden" data-smm-preview-panel="instagram" data-smm-preview-format="story" hidden aria-label="Instagram story preview">
                        <header class="smm-compose-preview-platform-head">
                            <span class="smm-compose-preview-platform-icon" aria-hidden="true"><?= brand_instagram_icon_html('smm-compose-preview-platform-icon', 22) ?></span>
                            <h3 class="smm-compose-preview-platform-title font-sans" data-smm-preview-title="instagram-story">Instagram Story</h3>
                        </header>
                        <div class="smm-compose-preview-phone smm-compose-preview-phone--ig-story">
                            <div class="smm-compose-preview-phone-screen">
                                <div class="smm-compose-preview-story-chrome smm-compose-preview-story-chrome--ig" aria-hidden="true">
                                    <div class="smm-compose-preview-phone-status">
                                        <span>2:11 PM</span>
                                        <span class="smm-compose-preview-phone-status-icons">
                                            <svg width="14" height="10" viewBox="0 0 16 12" fill="none"><rect x="0.5" y="8" width="2" height="3.5" rx="0.5" fill="currentColor"/><rect x="4" y="6" width="2" height="5.5" rx="0.5" fill="currentColor"/><rect x="7.5" y="4" width="2" height="7.5" rx="0.5" fill="currentColor"/><rect x="11" y="2" width="2" height="9.5" rx="0.5" fill="currentColor"/></svg>
                                            <?= $composePreviewWifiIcon ?>
                                            <svg width="18" height="9" viewBox="0 0 22 11" fill="none"><rect x="0.5" y="0.5" width="18" height="10" rx="2" stroke="currentColor"/><rect x="2" y="2" width="12" height="7" rx="1" fill="currentColor"/><rect x="19" y="3" width="2.5" height="5" rx="1" fill="currentColor"/></svg>
                                        </span>
                                    </div>
                                    <div class="smm-compose-preview-story-progress"><span></span></div>
                                    <div class="smm-compose-preview-story-head">
                                        <div class="smm-compose-preview-story-user">
                                            <div class="smm-compose-preview-ig-avatar smm-compose-preview-ig-avatar--sm" data-smm-preview-avatar="instagram-story" aria-hidden="true">
                                                <span class="smm-compose-preview-ig-initial font-heading">Y</span>
                                            </div>
                                            <p class="smm-compose-preview-story-name font-sans" data-smm-preview-account-name="instagram-story">your.account</p>
                                        </div>
                                        <div class="smm-compose-preview-story-actions">
                                            <button type="button" class="smm-compose-preview-menu" tabindex="-1">⋯</button>
                                            <button type="button" class="smm-compose-preview-story-close" tabindex="-1">×</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="smm-compose-preview-media smm-compose-preview-media--story is-empty" data-smm-preview-media="instagram-story">
                                    <span class="font-sans">Your photo shows here</span>
                                    <div class="smm-compose-preview-story-caption is-hidden" data-smm-preview-story-caption="instagram" hidden aria-hidden="true"></div>
                                </div>
                                <div class="smm-compose-preview-story-foot smm-compose-preview-story-foot--ig is-hidden" data-smm-ig-story-foot hidden aria-hidden="true">
                                    <div class="smm-compose-preview-story-message font-sans">Send message</div>
                                    <div class="smm-compose-preview-story-message-icons">
                                        <svg class="smm-compose-preview-story-message-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z" stroke="currentColor" stroke-width="1.75"/></svg>
                                        <svg class="smm-compose-preview-story-message-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.75"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div class="smm-compose-preview-story-playback is-hidden" data-smm-ig-story-playback hidden>
                                <div class="smm-compose-preview-story-playback-scrubber" aria-hidden="true">
                                    <span class="smm-compose-preview-story-playback-scrubber-fill" data-smm-ig-story-scrubber-fill></span>
                                    <span class="smm-compose-preview-story-playback-scrubber-handle" data-smm-ig-story-scrubber-handle></span>
                                </div>
                                <div class="smm-compose-preview-story-playback-controls">
                                    <button type="button" class="smm-compose-preview-story-playback-btn" data-smm-ig-story-play aria-label="Play">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-story-play-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-story-pause-icon width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden><path d="M7 5h3v14H7V5Zm7 0h3v14h-3V5Z" fill="currentColor"/></svg>
                                    </button>
                                    <span class="smm-compose-preview-story-playback-time font-sans" data-smm-ig-story-playback-time>0:05 / 0:05</span>
                                    <button type="button" class="smm-compose-preview-story-playback-volume" data-smm-ig-story-volume aria-label="Unmute">
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-story-volume-on width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="M15.5 8.5a4 4 0 0 1 0 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                            <path d="M18 6a7 7 0 0 1 0 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                        <svg class="smm-compose-preview-story-playback-icon" data-smm-ig-story-volume-off width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                                            <path d="m16 9 5 6M21 9l-5 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?= dash_compose_preview_disclaimer_html() ?>
                    </article>
                </div>
            </div>

            <aside class="smm-compose-col smm-compose-col--wisdom is-hidden" id="smm-compose-wisdom-panel" data-smm-wisdom-panel aria-label="Ask Hannah" hidden>
                <div class="smm-compose-wisdom-head">
                    <span class="smm-compose-wisdom-title font-sans">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                        </svg>
                        AI Salesgirl
                    </span>
                    <div class="smm-compose-wisdom-head-actions">
                        <button type="button" class="smm-compose-wisdom-icon" data-smm-wisdom-refresh aria-label="Refresh suggestions">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-2.64-6.36" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                                <path d="M21 3v6h-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button type="button" class="smm-compose-wisdom-icon" data-smm-wisdom-close aria-label="Close AI panel">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="smm-compose-wisdom-section">
                    <p class="smm-compose-wisdom-section-label font-sans">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.74V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.26A7 7 0 0 0 12 2Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                        </svg>
                        Recommended for you
                    </p>
                    <div class="smm-compose-wisdom-list">
                        <button type="button" class="smm-compose-wisdom-item font-sans" data-smm-generate="hashtags">Add the best hashtags for reach.</button>
                        <button type="button" class="smm-compose-wisdom-item font-sans" data-smm-generate="caption" data-smm-ai-prompt-btn="Write my caption in 3 different tones of voice.">Write my caption in 3 different tones of voice.</button>
                        <button type="button" class="smm-compose-wisdom-item font-sans" data-smm-generate="caption" data-smm-ai-prompt-btn="Make my caption more engaging and impactful.">Make my caption more engaging and impactful.</button>
                        <button type="button" class="smm-compose-wisdom-item font-sans" data-smm-generate="caption" data-smm-ai-prompt-btn="Fix my caption's spelling, grammar, and clarity.">Fix my caption's spelling, grammar, and clarity.</button>
                        <button type="button" class="smm-compose-wisdom-item font-sans" data-smm-generate="caption" data-smm-ai-prompt-btn="Flag potential risks in my post before publishing.">Flag potential risks in my post (e.g., reputational, brand, or cultural considerations) before publishing.</button>
                    </div>
                </div>

                <div class="smm-compose-wisdom-ask">
                    <label class="smm-upload-input" for="smm-wisdom-ask">Ask AI Salesgirl a question</label>
                    <div class="smm-compose-wisdom-ask-wrap">
                        <textarea id="smm-wisdom-ask" class="smm-compose-wisdom-ask-input font-sans" rows="3" placeholder="Ask AI Salesgirl a question" data-smm-wisdom-ask></textarea>
                        <button type="button" class="smm-compose-wisdom-send" data-smm-wisdom-send aria-label="Send question">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <footer class="smm-compose-modal-foot">
            <div class="smm-compose-foot-actions">
                <button type="button" class="smm-compose-draft-btn smm-compose-draft-btn--desktop font-sans" data-smm-save-draft>Save as draft</button>
                <div class="smm-compose-schedule-anchor" data-smm-schedule-anchor>
                    <button
                        type="button"
                        class="smm-compose-schedule-later-btn font-sans"
                        data-smm-schedule-later
                        aria-expanded="false"
                        aria-haspopup="dialog"
                    >
                        <span data-smm-schedule-later-label>Schedule for later</span>
                        <span class="smm-compose-schedule-later-icon" data-smm-schedule-later-icon aria-hidden="true">
                            <svg class="smm-compose-schedule-later-chevron" width="16" height="16" viewBox="0 0 20 20" fill="none">
                                <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <svg class="smm-compose-schedule-later-close" width="16" height="16" viewBox="0 0 20 20" fill="none">
                                <path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="smm-schedule-modal is-hidden" data-smm-schedule-modal hidden aria-hidden="true">
                        <div class="smm-schedule-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="smm-schedule-modal-title">
                            <h3 id="smm-schedule-modal-title" class="smm-schedule-modal-title font-sans">Schedule post</h3>

                            <div class="smm-schedule-date-wrap">
                                <input type="date" id="smm-schedule-date" class="smm-schedule-date-input" data-smm-schedule-date hidden>
                                <button type="button" class="smm-schedule-date-trigger font-sans" data-smm-schedule-date-trigger>
                                    <span data-smm-schedule-date-label>Select a date</span>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="smm-schedule-time-section">
                                <p class="smm-schedule-time-heading font-sans">Manually set time</p>
                                <p class="smm-schedule-timezone font-sans" data-smm-schedule-timezone></p>
                                <div class="smm-schedule-time-fields">
                                    <div class="smm-schedule-time-field">
                                        <label class="smm-schedule-time-label font-sans" for="smm-schedule-hour">Hour</label>
                                        <select id="smm-schedule-hour" class="smm-schedule-select font-sans" data-smm-schedule-hour></select>
                                    </div>
                                    <div class="smm-schedule-time-field">
                                        <label class="smm-schedule-time-label font-sans" for="smm-schedule-minute">Minute</label>
                                        <select id="smm-schedule-minute" class="smm-schedule-select font-sans" data-smm-schedule-minute></select>
                                    </div>
                                    <div class="smm-schedule-time-field">
                                        <label class="smm-schedule-time-label font-sans" for="smm-schedule-period">Period</label>
                                        <select id="smm-schedule-period" class="smm-schedule-select font-sans" data-smm-schedule-period>
                                            <option value="AM">AM</option>
                                            <option value="PM">PM</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="button" class="smm-schedule-recommended-toggle font-sans" data-smm-schedule-recommended-toggle aria-expanded="false">
                                    See recommended times
                                </button>
                                <div class="smm-schedule-recommended-panel is-hidden" data-smm-schedule-recommended hidden></div>
                            </div>

                            <button type="button" class="smm-schedule-done font-sans" data-smm-schedule-done>Done</button>
                        </div>
                    </div>
                </div>
                <div class="smm-compose-submit-split" data-smm-submit-split>
                    <button type="button" class="smm-compose-post-primary font-sans" data-smm-submit>
                        <span data-smm-submit-label>Post now</span>
                        <svg class="smm-compose-post-primary-chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="smm-compose-submit-menu is-hidden" data-smm-submit-menu role="menu" hidden>
                        <button type="button" class="smm-compose-submit-menu-item font-sans" data-smm-submit-menu-post role="menuitem">
                            Post now
                        </button>
                        <button type="button" class="smm-compose-submit-menu-item font-sans" data-smm-save-draft role="menuitem">
                            Save as draft
                        </button>
                    </div>
                </div>
            </div>
            <p class="smm-form-note font-sans" data-smm-form-note></p>
        </footer>
    </div>
</div>

<div class="smm-video-editor-modal is-hidden" data-smm-video-editor-modal hidden aria-hidden="true">
    <div class="smm-video-editor-modal-backdrop" data-smm-video-editor-close></div>
    <div class="smm-video-editor-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="smm-video-editor-title" data-smm-video-editor-dialog>
        <header class="smm-video-editor-modal-head">
            <h2 id="smm-video-editor-title" class="smm-video-editor-modal-title font-sans" data-smm-video-editor-title>Edit video</h2>
            <button type="button" class="smm-video-editor-modal-close" data-smm-video-editor-close aria-label="Close editor">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </header>

        <div class="smm-video-editor-modal-body">
            <div class="smm-video-editor-loading" data-smm-video-editor-loading>
                <div class="smm-video-editor-loading-spinner" aria-hidden="true"></div>
                <p class="smm-video-editor-loading-title font-sans">Loading Editor</p>
                <p class="smm-video-editor-loading-copy font-sans">Just a few seconds…</p>
            </div>

            <div class="smm-video-editor-workspace is-hidden" data-smm-video-editor-workspace hidden>
                <div class="smm-video-editor-stage" data-smm-video-editor-stage>
                    <video class="smm-video-editor-preview" data-smm-video-editor-preview playsinline muted></video>
                    <div class="smm-video-editor-crop-overlay is-hidden" data-smm-video-editor-crop-overlay hidden aria-hidden="true">
                        <div class="smm-video-editor-crop-shade smm-video-editor-crop-shade--top" data-smm-video-editor-crop-shade-top></div>
                        <div class="smm-video-editor-crop-shade smm-video-editor-crop-shade--bottom" data-smm-video-editor-crop-shade-bottom></div>
                        <div class="smm-video-editor-crop-shade smm-video-editor-crop-shade--left" data-smm-video-editor-crop-shade-left></div>
                        <div class="smm-video-editor-crop-shade smm-video-editor-crop-shade--right" data-smm-video-editor-crop-shade-right></div>
                        <div class="smm-video-editor-crop-window" data-smm-video-editor-crop-window>
                            <span class="smm-video-editor-crop-grid" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>

                <div class="smm-video-editor-panel smm-video-editor-panel--trim is-hidden" data-smm-video-editor-trim-panel hidden>
                    <div class="smm-video-editor-trim-toolbar">
                        <button type="button" class="smm-video-editor-play-fab" data-smm-video-editor-trim-play aria-label="Play selection">
                            <svg class="smm-video-editor-trim-play-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                            <svg class="smm-video-editor-trim-pause-icon is-hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="0.75" fill="currentColor"/><rect x="14" y="5" width="4" height="14" rx="0.75" fill="currentColor"/></svg>
                        </button>
                        <div class="smm-video-editor-trim-timeline">
                            <div class="smm-video-editor-trim-times">
                                <span class="smm-video-editor-trim-time font-sans" data-smm-video-editor-trim-start-label>0:00</span>
                                <span class="smm-video-editor-trim-duration font-sans" data-smm-video-editor-trim-duration>0:00 – 0:00</span>
                                <span class="smm-video-editor-trim-time font-sans" data-smm-video-editor-trim-end-label>0:00</span>
                            </div>
                            <div class="smm-video-editor-trim-track-wrap">
                                <div class="smm-video-editor-trim-track" data-smm-video-editor-trim-track>
                                    <div class="smm-video-editor-trim-filmstrip" aria-hidden="true"></div>
                                    <span class="smm-video-editor-trim-mask smm-video-editor-trim-mask--left" data-smm-video-editor-trim-mask-left></span>
                                    <span class="smm-video-editor-trim-mask smm-video-editor-trim-mask--right" data-smm-video-editor-trim-mask-right></span>
                                    <span class="smm-video-editor-trim-selection" data-smm-video-editor-trim-selection></span>
                                    <span class="smm-video-editor-trim-playhead is-hidden" data-smm-video-editor-trim-playhead hidden aria-hidden="true"></span>
                                    <button type="button" class="smm-video-editor-trim-handle smm-video-editor-trim-handle--start" data-smm-video-editor-trim-start aria-label="Trim start"></button>
                                    <button type="button" class="smm-video-editor-trim-handle smm-video-editor-trim-handle--end" data-smm-video-editor-trim-end aria-label="Trim end"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="smm-video-editor-panel smm-video-editor-panel--crop is-hidden" data-smm-video-editor-crop-panel hidden>
                    <div class="smm-video-editor-crop-toolbar">
                        <div class="smm-video-editor-crop-ratios is-hidden" data-smm-video-editor-crop-ratios hidden>
                            <div class="smm-video-editor-crop-ratios-list" role="group" aria-label="Aspect ratio">
                                <button type="button" class="smm-video-editor-crop-ratio-btn font-sans" data-smm-video-editor-crop-ratio="0.5625" aria-pressed="false">9:16</button>
                                <button type="button" class="smm-video-editor-crop-ratio-btn font-sans" data-smm-video-editor-crop-ratio="0.8" aria-pressed="false">4:5</button>
                                <button type="button" class="smm-video-editor-crop-ratio-btn font-sans" data-smm-video-editor-crop-ratio="1" aria-pressed="false">1:1</button>
                                <button type="button" class="smm-video-editor-crop-ratio-btn font-sans" data-smm-video-editor-crop-ratio="1.7777777778" aria-pressed="false">16:9</button>
                            </div>
                        </div>
                        <div class="smm-video-editor-crop-playback">
                            <button type="button" class="smm-video-editor-play-fab" data-smm-video-editor-crop-play aria-label="Play video">
                                <svg class="smm-video-editor-play-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z" fill="currentColor"/></svg>
                                <svg class="smm-video-editor-pause-icon is-hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="0.75" fill="currentColor"/><rect x="14" y="5" width="4" height="14" rx="0.75" fill="currentColor"/></svg>
                            </button>
                            <span class="smm-video-editor-crop-time font-sans" data-smm-video-editor-crop-time aria-live="polite">0:00 / 0:00</span>
                        </div>
                    </div>
                    <p class="smm-video-editor-crop-hint font-sans" data-smm-video-editor-crop-note>Drag the frame to choose what stays in the shot.</p>
                </div>
            </div>

            <div class="smm-video-editor-error is-hidden" data-smm-video-editor-error hidden role="alert">
                <p class="smm-video-editor-error-title font-sans">Could not open editor</p>
                <p class="smm-video-editor-error-copy font-sans" data-smm-video-editor-error-copy></p>
            </div>
        </div>

        <footer class="smm-video-editor-modal-foot">
            <div class="smm-video-editor-foot-actions">
                <div
                    class="smm-video-editor-save-progress is-hidden"
                    data-smm-video-editor-save-progress
                    hidden
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="0"
                    aria-label="Save progress"
                >
                    <div class="smm-video-editor-save-progress-track">
                        <div class="smm-video-editor-save-progress-fill" data-smm-video-editor-save-progress-fill style="width: 0%"></div>
                    </div>
                    <span class="smm-video-editor-save-progress-label font-sans" data-smm-video-editor-save-progress-label>0%</span>
                </div>
                <button type="button" class="smm-video-editor-cancel font-sans" data-smm-video-editor-close>Cancel</button>
                <button type="button" class="smm-video-editor-save font-sans" data-smm-video-editor-save disabled aria-disabled="true">
                    <span data-smm-video-editor-save-label>Save</span>
                </button>
            </div>
        </footer>
    </div>
</div>

<div class="smm-video-edit-chooser is-hidden" data-smm-video-edit-chooser hidden aria-hidden="true">
    <div class="smm-video-edit-chooser-backdrop" data-smm-video-edit-close></div>
    <div class="smm-video-edit-chooser-dialog" role="dialog" aria-modal="true" aria-labelledby="smm-video-edit-chooser-title">
        <header class="smm-video-edit-chooser-head">
            <div class="smm-video-edit-chooser-head-copy">
                <h2 id="smm-video-edit-chooser-title" class="smm-video-edit-chooser-title font-sans">Edit video</h2>
                <p class="smm-video-edit-chooser-subtitle font-sans">Choose what to change. Edits always start from your original upload.</p>
            </div>
            <button type="button" class="smm-video-edit-chooser-close" data-smm-video-edit-close aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </header>
        <div class="smm-video-edit-chooser-body">
            <button type="button" class="smm-video-edit-chooser-action" data-smm-video-edit-action="trim">
                <span class="smm-video-edit-chooser-action-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 17h10" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><circle cx="18" cy="17" r="3" stroke="currentColor" stroke-width="1.75"/></svg>
                </span>
                <span class="smm-video-edit-chooser-action-copy">
                    <span class="smm-video-edit-chooser-action-title font-sans">Trim length</span>
                    <span class="smm-video-edit-chooser-action-note font-sans">Shorten the video to fit platform limits.</span>
                </span>
                <span class="smm-video-edit-chooser-action-badge font-sans is-hidden" data-smm-video-edit-trim-badge hidden>Needs fix</span>
            </button>
            <button type="button" class="smm-video-edit-chooser-action" data-smm-video-edit-action="crop">
                <span class="smm-video-edit-chooser-action-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M6 3v3H3M18 21v-3h3M21 6h-3V3M3 18h3v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><rect x="7" y="7" width="10" height="10" rx="1" stroke="currentColor" stroke-width="1.75"/></svg>
                </span>
                <span class="smm-video-edit-chooser-action-copy">
                    <span class="smm-video-edit-chooser-action-title font-sans">Crop aspect ratio</span>
                    <span class="smm-video-edit-chooser-action-note font-sans">Reframe between 9:16 and 16:9, or fit vertical formats.</span>
                </span>
                <span class="smm-video-edit-chooser-action-badge font-sans is-hidden" data-smm-video-edit-crop-badge hidden>Needs fix</span>
            </button>
        </div>
        <footer class="smm-video-edit-chooser-foot">
            <button type="button" class="smm-video-edit-chooser-cancel font-sans" data-smm-video-edit-close>Cancel</button>
        </footer>
    </div>
</div>

<div class="smm-publish-overlay is-hidden" data-smm-publish-overlay hidden aria-hidden="true">
    <div class="smm-publish-overlay-card" role="status" aria-live="polite">
        <span class="smm-publish-overlay-spinner" aria-hidden="true"></span>
        <p class="smm-publish-overlay-title font-heading" data-smm-publish-title>Publishing your post</p>
        <p class="smm-publish-overlay-copy font-sans" data-smm-publish-copy>Uploading your media…</p>
        <div
            class="smm-publish-overlay-track"
            data-smm-publish-track
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="0"
            aria-label="Publishing progress"
        >
            <span class="smm-publish-overlay-fill" data-smm-publish-fill style="width: 0%"></span>
        </div>
        <p class="smm-publish-overlay-pct font-sans" data-smm-publish-pct>0%</p>
    </div>
</div>
