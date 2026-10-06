<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $aiName */
/** @var array<string, mixed>|null $knowledgeTraining */
?>
<div class="kb-training" data-kb-training>
    <?php if ($knowledgeTraining === null): ?>
        <p class="kb-training-empty">Set up a business first to train your AI.</p>
    <?php else: ?>
        <div class="kb-training-layout" data-kb-layout>
            <div class="kb-training-main">
                <div class="kb-training-screen is-active" data-kb-screen="hub">
                    <header class="kb-training-hero">
                        <p class="kb-training-kicker font-heading">Knowledge base</p>
                        <h2 class="kb-training-title font-heading">Train <?= e($aiName) ?></h2>
                        <p class="kb-training-lead">Add an information source so your salesgirl answers like your best staff member.</p>
                    </header>

                    <?php
                    $knowledgeCount = (int) ($knowledgeTraining['knowledge_count'] ?? 0);
                    $knowledgeCountLabel = KnowledgeTrainingService::formatEntryCountLabel($knowledgeCount);
                    $documentCount = (int) ($knowledgeTraining['document_count'] ?? 0);
                    $documentCountLabel = KnowledgeTrainingService::formatFileCountLabel($documentCount);
                    $linkCount = (int) ($knowledgeTraining['link_count'] ?? 0);
                    $linkSubpageCount = (int) ($knowledgeTraining['link_subpage_count'] ?? 0);
                    $linkCountLabel = (string) ($knowledgeTraining['link_count_label'] ?? KnowledgeTrainingService::formatLinkCountLabel($linkCount, $linkSubpageCount));
                    $faqCount = (int) ($knowledgeTraining['faq_count'] ?? 0);
                    $faqCountLabel = (string) ($knowledgeTraining['faq_count_label'] ?? KnowledgeTrainingService::formatFaqCountLabel($faqCount));
                    $documentUsageLimit = KnowledgeTrainingService::DOCUMENT_USAGE_CHAR_LIMIT;
                    ?>
                    <div class="kb-source-grid" role="list">
                        <button type="button" class="kb-source-card kb-source-card--knowledge" data-kb-open="knowledge-list" role="listitem">
                            <span class="kb-source-accent" aria-hidden="true"></span>
                            <span class="kb-source-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
                            </span>
                            <span class="kb-source-copy">
                                <span class="kb-source-name font-heading">Knowledge</span>
                                <span class="kb-source-desc<?= $knowledgeCount > 0 ? ' kb-source-desc--count' : '' ?>" data-kb-hub-count="knowledge"><?= e($knowledgeCountLabel) ?></span>
                            </span>
                            <span class="kb-source-chevron" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                            </span>
                        </button>

                        <button type="button" class="kb-source-card kb-source-card--link" data-kb-open="link-list" role="listitem">
                            <span class="kb-source-accent" aria-hidden="true"></span>
                            <span class="kb-source-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </span>
                            <span class="kb-source-copy">
                                <span class="kb-source-name font-heading">Link</span>
                                <span class="kb-source-desc<?= $linkCount > 0 ? ' kb-source-desc--count' : '' ?>" data-kb-hub-count="link"><?= e($linkCountLabel) ?></span>
                            </span>
                            <span class="kb-source-chevron" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                            </span>
                        </button>

                        <button type="button" class="kb-source-card kb-source-card--document" data-kb-open="document-list" role="listitem">
                            <span class="kb-source-accent" aria-hidden="true"></span>
                            <span class="kb-source-icon" aria-hidden="true">
                                <?= brand_upload_icon_html('kb-source-icon-img', 22, '', 'white') ?>
                            </span>
                            <span class="kb-source-copy">
                                <span class="kb-source-name font-heading">File</span>
                                <span class="kb-source-desc<?= $documentCount > 0 ? ' kb-source-desc--count' : '' ?>" data-kb-hub-count="document"><?= e($documentCountLabel) ?></span>
                            </span>
                            <span class="kb-source-chevron" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                            </span>
                        </button>

                        <button type="button" class="kb-source-card kb-source-card--qa" data-kb-open="qa-list" role="listitem">
                            <span class="kb-source-accent" aria-hidden="true"></span>
                            <span class="kb-source-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                            </span>
                            <span class="kb-source-copy">
                                <span class="kb-source-name font-heading">Questions &amp; answer</span>
                                <span class="kb-source-desc<?= $faqCount > 0 ? ' kb-source-desc--count' : '' ?>" data-kb-hub-count="qa"><?= e($faqCountLabel) ?></span>
                            </span>
                            <span class="kb-source-chevron" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--knowledge-list" data-kb-screen="knowledge-list" hidden>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="hub" aria-label="Back to training options">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--knowledge" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    <path d="M12 7v.01"/>
                                    <path d="M11 11h2"/>
                                </svg>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">KNOWLEDGE</h2>
                                <p class="kb-entry-subtitle">Add text-based information to train your agent.</p>
                            </div>
                        </div>
                    </header>

                    <button type="button" class="kb-knowledge-add-btn font-heading" data-kb-open="knowledge">+ Add new knowledge</button>

                    <div class="kb-knowledge-list<?= ($knowledgeTraining['knowledge_entries'] ?? []) === [] ? ' is-empty' : '' ?>" data-kb-knowledge-list>
                        <ul class="kb-knowledge-items" data-kb-knowledge-items>
                            <?php foreach (($knowledgeTraining['knowledge_entries'] ?? []) as $entry): ?>
                                <li class="kb-knowledge-card" data-kb-knowledge-id="<?= e((string) $entry['id']) ?>">
                                    <div class="kb-knowledge-card-head">
                                        <span class="kb-knowledge-card-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        </span>
                                        <h3 class="kb-knowledge-card-title font-heading"><?= e((string) $entry['title']) ?></h3>
                                        <span class="kb-knowledge-card-meta"><?= (int) $entry['char_count'] ?> characters</span>
                                    </div>
                                    <p class="kb-knowledge-card-preview"><?= e((string) $entry['content']) ?></p>
                                    <div class="kb-knowledge-card-foot">
                                        <span class="kb-knowledge-card-learned">Agent learned on <?= e((string) $entry['learned_at']) ?></span>
                                        <div class="kb-knowledge-card-actions">
                                            <button
                                                type="button"
                                                class="kb-knowledge-card-edit"
                                                data-kb-edit-knowledge
                                                data-knowledge-id="<?= e((string) $entry['id']) ?>"
                                                data-knowledge-title="<?= e((string) $entry['title']) ?>"
                                                data-knowledge-content="<?= e((string) $entry['content']) ?>"
                                            >Edit</button>
                                            <button
                                                type="button"
                                                class="kb-knowledge-card-remove"
                                                data-kb-delete="knowledge"
                                                data-chunk-id="<?= e((string) $entry['id']) ?>"
                                                aria-label="Remove knowledge"
                                            >Remove</button>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="kb-knowledge-empty" data-kb-knowledge-empty<?= ($knowledgeTraining['knowledge_entries'] ?? []) === [] ? '' : ' hidden' ?>>No knowledge yet. Add your first entry above.</p>
                    </div>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--knowledge" data-kb-screen="knowledge" hidden>
                    <?php
                    $knowledgeCharLimit = KnowledgeTrainingService::KNOWLEDGE_CHAR_LIMIT;
                    ?>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="knowledge-list" aria-label="Back to knowledge list">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--knowledge" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    <path d="M12 7v.01"/>
                                    <path d="M11 11h2"/>
                                </svg>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">KNOWLEDGE</h2>
                                <p class="kb-entry-subtitle">Add information to train your AI.</p>
                            </div>
                        </div>
                    </header>

                    <form class="kb-entry-form" data-kb-form="knowledge" novalidate>
                        <input type="hidden" name="knowledge_id" value="" data-kb-knowledge-id>

                        <div class="kb-entry-card">
                            <h3 class="kb-entry-card-title font-heading">Knowledge for Your AI</h3>
                            <p class="kb-entry-card-lead">Write an info that the AI can use to provide accurate answers</p>

                            <div class="kb-entry-textarea-wrap">
                                <label class="ai-sr-only" for="kb-knowledge-content">Knowledge for your AI</label>
                                <textarea
                                    id="kb-knowledge-content"
                                    class="kb-entry-textarea"
                                    name="knowledge_content"
                                    rows="12"
                                    maxlength="<?= (int) $knowledgeCharLimit ?>"
                                    placeholder="Company overview, product features, customer FAQs, service guidelines..."
                                    data-kb-char-input
                                ></textarea>
                                <span class="kb-entry-counter" data-kb-char-counter>0/<?= (int) $knowledgeCharLimit ?></span>
                            </div>
                        </div>

                        <p class="kb-entry-status" data-kb-status="knowledge" aria-live="polite"></p>

                        <div class="kb-entry-actions">
                            <button type="submit" class="kb-entry-save font-heading" disabled>Save</button>
                        </div>
                    </form>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--link-list" data-kb-screen="link-list" hidden>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="hub" aria-label="Back to training options">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--link" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                </svg>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">LINK</h2>
                                <p class="kb-entry-subtitle">Add website URLs to train your agent with dynamic information.</p>
                            </div>
                        </div>
                    </header>

                    <button type="button" class="kb-knowledge-add-btn font-heading" data-kb-open="link">+ Add new knowledge</button>

                    <div class="kb-link-list<?= ($knowledgeTraining['links'] ?? []) === [] ? ' is-empty' : '' ?>" data-kb-link-list>
                        <ul class="kb-link-items" data-kb-link-items>
                            <?php foreach (($knowledgeTraining['links'] ?? []) as $link): ?>
                                <li
                                    class="kb-link-card<?= ($link['status'] ?? '') === 'processing' ? ' is-processing' : '' ?>"
                                    data-kb-link-id="<?= e((string) $link['id']) ?>"
                                    data-kb-link-status="<?= e((string) ($link['status'] ?? 'ready')) ?>"
                                >
                                    <div class="kb-link-card-head">
                                        <span class="kb-link-card-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                        </span>
                                        <div class="kb-link-card-copy">
                                            <h3 class="kb-link-card-title font-heading"><?= e((string) $link['url']) ?></h3>
                                            <ul class="kb-link-card-subpages">
                                                <?php foreach (array_slice((array) ($link['subpages'] ?? []), 0, 4) as $subpage): ?>
                                                    <li><?= e((string) ($subpage['url'] ?? '')) ?></li>
                                                <?php endforeach; ?>
                                                <?php if ((int) ($link['subpage_count'] ?? 0) > 4): ?>
                                                    <li class="kb-link-card-more"><?= (int) ($link['subpage_count'] ?? 0) - 4 ?>+ subpages more</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <button
                                            type="button"
                                            class="kb-link-card-edit"
                                            data-kb-edit-link
                                            data-link-id="<?= e((string) $link['id']) ?>"
                                            data-link-url="<?= e((string) $link['url']) ?>"
                                            data-link-selected-urls="<?= e(json_encode(array_values((array) ($link['selected_urls'] ?? [])), JSON_THROW_ON_ERROR)) ?>"
                                            data-recrawl-enabled="<?= !empty($link['recrawl_enabled']) ? '1' : '0' ?>"
                                            data-recrawl-frequency="<?= e((string) ($link['recrawl_frequency'] ?? 'monthly')) ?>"
                                            aria-label="Edit link"
                                        >
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                        </button>
                                    </div>
                                    <div class="kb-link-card-foot">
                                        <?php if (($link['status'] ?? '') === 'processing'): ?>
                                            <div class="kb-link-progress" data-kb-link-progress>
                                                <div class="kb-link-progress-bar" data-kb-link-progress-bar"></div>
                                            </div>
                                            <p class="kb-link-progress-label">Agent is gathering insights from the URL…</p>
                                        <?php elseif (($link['status'] ?? '') === 'error'): ?>
                                            <p class="kb-link-card-error"><?= e((string) ($link['error_message'] ?? 'Could not crawl this link.')) ?></p>
                                            <div class="kb-link-card-actions">
                                                <button type="button" class="kb-link-card-edit-btn" data-kb-edit-link data-link-id="<?= e((string) $link['id']) ?>" data-link-url="<?= e((string) $link['url']) ?>" data-link-selected-urls="<?= e(json_encode(array_values((array) ($link['selected_urls'] ?? [])), JSON_THROW_ON_ERROR)) ?>" data-recrawl-enabled="<?= !empty($link['recrawl_enabled']) ? '1' : '0' ?>" data-recrawl-frequency="<?= e((string) ($link['recrawl_frequency'] ?? 'monthly')) ?>">Edit</button>
                                                <button type="button" class="kb-link-card-remove" data-kb-delete="link" data-chunk-id="<?= e((string) $link['id']) ?>">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="kb-link-card-learned">Agent learned on <?= e((string) $link['learned_at']) ?></span>
                                            <div class="kb-link-card-actions">
                                                <button type="button" class="kb-link-card-edit-btn" data-kb-edit-link data-link-id="<?= e((string) $link['id']) ?>" data-link-url="<?= e((string) $link['url']) ?>" data-link-selected-urls="<?= e(json_encode(array_values((array) ($link['selected_urls'] ?? [])), JSON_THROW_ON_ERROR)) ?>" data-recrawl-enabled="<?= !empty($link['recrawl_enabled']) ? '1' : '0' ?>" data-recrawl-frequency="<?= e((string) ($link['recrawl_frequency'] ?? 'monthly')) ?>">Edit</button>
                                                <button type="button" class="kb-link-card-remove" data-kb-delete="link" data-chunk-id="<?= e((string) $link['id']) ?>">Remove</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="kb-link-empty" data-kb-link-empty<?= ($knowledgeTraining['links'] ?? []) === [] ? '' : ' hidden' ?>>No links yet. Add your first website above.</p>
                    </div>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--link" data-kb-screen="link" hidden>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="link-list" aria-label="Back to link list">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--link" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                </svg>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">LINK</h2>
                                <p class="kb-entry-subtitle" data-kb-link-subtitle>Add website URLs to train your agent with dynamic information.</p>
                            </div>
                        </div>
                    </header>

                    <form class="kb-entry-form" data-kb-form="link" novalidate>
                        <input type="hidden" name="chunk_id" value="" data-kb-link-id>

                        <div class="kb-entry-card">
                            <h3 class="kb-entry-card-title font-heading">Enter a URL</h3>
                            <p class="kb-entry-card-lead">Provide a URL for your agent to analyze</p>

                            <div class="kb-url-field">
                                <span class="kb-url-prefix" aria-hidden="true">https://</span>
                                <label class="ai-sr-only" for="kb-link-url">Website URL</label>
                                <input
                                    id="kb-link-url"
                                    class="kb-url-input"
                                    type="text"
                                    name="url_path"
                                    placeholder="example.com"
                                    inputmode="url"
                                    autocomplete="off"
                                    data-kb-url-input
                                    required
                                >
                            </div>

                            <div class="kb-link-pages-block" data-kb-link-pages-block hidden>
                                <div class="kb-link-pages-head">
                                    <div>
                                        <h4 class="kb-link-pages-title font-heading">Select pages to crawl</h4>
                                        <p class="kb-link-pages-hint" data-kb-link-pages-hint hidden>You can select up to 10 pages at a time.</p>
                                    </div>
                                    <span class="kb-link-pages-count" data-kb-link-pages-count>0/10</span>
                                </div>

                                <label class="kb-link-page-row kb-link-page-row--all">
                                    <input type="checkbox" data-kb-link-select-all>
                                    <span class="kb-link-page-check" aria-hidden="true"></span>
                                    <span class="kb-link-page-copy">
                                        <strong>Select all</strong>
                                    </span>
                                </label>

                                <div class="kb-link-pages-list" data-kb-link-pages-list></div>
                            </div>

                            <div class="kb-link-recrawl-block">
                                <div class="kb-link-recrawl-head">
                                    <div>
                                        <h4 class="kb-link-recrawl-title font-heading">Enable periodic recrawling</h4>
                                        <p class="kb-link-recrawl-lead">The agent will automatically recrawl the link at the selected frequency.</p>
                                    </div>
                                    <label class="kb-link-toggle">
                                        <input type="checkbox" name="recrawl_enabled" data-kb-link-recrawl-toggle checked>
                                        <span class="kb-link-toggle-track" aria-hidden="true"></span>
                                    </label>
                                </div>

                                <div class="kb-link-frequency" data-kb-link-frequency>
                                    <label class="kb-link-frequency-option">
                                        <input type="radio" name="recrawl_frequency" value="daily">
                                        <span>Daily</span>
                                    </label>
                                    <label class="kb-link-frequency-option">
                                        <input type="radio" name="recrawl_frequency" value="weekly">
                                        <span>Weekly</span>
                                    </label>
                                    <label class="kb-link-frequency-option">
                                        <input type="radio" name="recrawl_frequency" value="monthly" checked>
                                        <span>Monthly</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <p class="kb-entry-status" data-kb-status="link" role="status"></p>

                        <div class="kb-entry-actions">
                            <button type="button" class="kb-entry-save kb-entry-save--crawl font-heading" data-kb-link-crawl>Crawl</button>
                            <button type="submit" class="kb-entry-save font-heading" data-kb-link-save hidden disabled>Save</button>
                        </div>
                    </form>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--document-list" data-kb-screen="document-list" hidden>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="hub" aria-label="Back to training options">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--document" aria-hidden="true">
                                <?= brand_upload_icon_html('kb-entry-icon-img', 20, '', 'white') ?>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">FILE</h2>
                                <p class="kb-entry-subtitle">Upload files to train your agent.</p>
                            </div>
                        </div>
                    </header>

                    <button type="button" class="kb-knowledge-add-btn font-heading" data-kb-open="document">+ Add new file</button>

                    <div class="kb-file-list<?= ($knowledgeTraining['documents'] ?? []) === [] ? ' is-empty' : '' ?>" data-kb-file-list>
                        <ul class="kb-file-items" data-kb-file-items>
                            <?php foreach (($knowledgeTraining['documents'] ?? []) as $doc): ?>
                                <li class="kb-file-card" data-kb-file-id="<?= e((string) $doc['id']) ?>">
                                    <?php if (!empty($doc['is_image']) && !empty($doc['preview_url'])): ?>
                                        <div class="kb-file-card-preview">
                                            <img src="<?= e((string) $doc['preview_url']) ?>" alt="" loading="lazy" decoding="async">
                                        </div>
                                    <?php endif; ?>
                                    <div class="kb-file-card-body">
                                        <div class="kb-file-card-head">
                                            <span class="kb-file-card-icon" aria-hidden="true">
                                                <?= brand_upload_icon_html('', 16) ?>
                                            </span>
                                            <div class="kb-file-card-copy">
                                                <h3 class="kb-file-card-title font-heading"><?= e((string) $doc['title']) ?></h3>
                                                <p class="kb-file-card-subtitle"><?= e((string) $doc['subheading']) ?></p>
                                            </div>
                                        </div>
                                        <div class="kb-file-card-foot">
                                            <span class="kb-file-card-learned">Agent learned on <?= e((string) $doc['learned_at']) ?></span>
                                            <button type="button" class="kb-file-card-remove" data-kb-delete="document" data-chunk-id="<?= e((string) $doc['id']) ?>" aria-label="Remove file">Remove</button>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="kb-file-empty" data-kb-file-empty<?= ($knowledgeTraining['documents'] ?? []) === [] ? '' : ' hidden' ?>>No files yet. Add your first file above.</p>
                    </div>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--document" data-kb-screen="document" hidden>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="document-list" aria-label="Back to file list">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--document" aria-hidden="true">
                                <?= brand_upload_icon_html('kb-entry-icon-img', 20, '', 'white') ?>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">FILE</h2>
                                <p class="kb-entry-subtitle">Upload files to train your agent.</p>
                            </div>
                        </div>
                    </header>

                    <form class="kb-entry-form" data-kb-form="document" enctype="multipart/form-data" novalidate>
                        <div class="kb-entry-card">
                            <h3 class="kb-entry-card-title font-heading">Upload Files</h3>
                            <p class="kb-entry-card-lead">Train your agent with uploaded content</p>

                            <div class="kb-file-chip" data-kb-file-chip hidden>
                                <span class="kb-file-chip-icon" aria-hidden="true">
                                    <?= brand_upload_icon_html('', 18) ?>
                                </span>
                                <span class="kb-file-chip-name" data-kb-file-chip-name></span>
                                <button type="button" class="kb-file-chip-remove" data-kb-file-clear aria-label="Remove selected file">&times;</button>
                            </div>

                            <div class="kb-doc-dropzone" data-kb-dropzone role="button" tabindex="0" aria-label="Upload a file">
                                <input
                                    type="file"
                                    name="document"
                                    class="kb-doc-input"
                                    accept=".txt,.md,.csv,.pdf,.jpg,.jpeg,.png,.webp,.gif,text/plain,text/markdown,text/csv,application/pdf,image/jpeg,image/png,image/webp,image/gif"
                                    data-kb-file-input
                                    hidden
                                >
                                <span class="kb-doc-drop-icon" aria-hidden="true">
                                    <?= brand_upload_icon_html('kb-doc-drop-icon-img', 36, '', 'white') ?>
                                </span>
                                <p class="kb-doc-drop-title font-heading">Upload a File</p>
                                <p class="kb-doc-drop-hint">
                                    Drag and drop your files here or
                                    <button type="button" class="kb-doc-drop-link" data-kb-file-trigger>upload a file</button>
                                </p>
                            </div>

                            <div class="kb-file-usage-block">
                                <label class="kb-file-usage-label font-heading" for="kb-file-usage">How should your agent use these files?</label>
                                <div class="kb-entry-textarea-wrap">
                                    <textarea
                                        id="kb-file-usage"
                                        class="kb-entry-textarea kb-file-usage-input"
                                        name="usage_instructions"
                                        rows="4"
                                        maxlength="<?= (int) $documentUsageLimit ?>"
                                        placeholder="The prize for this event is $20"
                                        data-kb-file-usage-input
                                    ></textarea>
                                    <span class="kb-entry-counter" data-kb-file-usage-counter>0/<?= (int) $documentUsageLimit ?></span>
                                </div>
                            </div>
                        </div>

                        <p class="kb-entry-status" data-kb-status="document" aria-live="polite"></p>

                        <div class="kb-entry-actions">
                            <button type="submit" class="kb-entry-save font-heading" disabled>Save</button>
                        </div>
                    </form>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--qa-list" data-kb-screen="qa-list" hidden>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="hub" aria-label="Back to training options">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--qa" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    <path d="M9.09 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3"/>
                                    <path d="M12 17h.01"/>
                                </svg>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">QUESTIONS &amp; ANSWER</h2>
                                <p class="kb-entry-subtitle">Provide a question-and-answer pairing your agent can use in conversations.</p>
                            </div>
                        </div>
                    </header>

                    <button type="button" class="kb-knowledge-add-btn font-heading" data-kb-open="qa">+ Add new knowledge</button>

                    <div class="kb-qa-list<?= ($knowledgeTraining['faqs'] ?? []) === [] ? ' is-empty' : '' ?>" data-kb-qa-list>
                        <ul class="kb-qa-items" data-kb-qa-items>
                            <?php foreach (($knowledgeTraining['faqs'] ?? []) as $faq): ?>
                                <li
                                    class="kb-qa-card"
                                    data-kb-qa-id="<?= e((string) ($faq['id'] ?? '')) ?>"
                                    data-kb-qa-entry="<?= e(json_encode($faq, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
                                >
                                    <div class="kb-qa-card-head">
                                        <span class="kb-qa-card-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M9.09 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                                        </span>
                                        <h3 class="kb-qa-card-title font-heading"><?= e((string) ($faq['q'] ?? '')) ?></h3>
                                        <span class="kb-qa-card-meta"><?= (int) ($faq['char_count'] ?? 0) ?> characters</span>
                                    </div>
                                    <p class="kb-qa-card-preview"><?= e((string) ($faq['a'] ?? '')) ?></p>
                                    <div class="kb-qa-card-foot">
                                        <span class="kb-qa-card-learned">Agent learned on <?= e((string) ($faq['learned_at'] ?? '')) ?></span>
                                        <div class="kb-qa-card-actions">
                                            <button type="button" class="kb-qa-card-edit" data-kb-edit-qa aria-label="Edit question">Edit</button>
                                            <button type="button" class="kb-qa-card-remove" data-kb-delete-qa data-entry-id="<?= e((string) ($faq['id'] ?? '')) ?>" aria-label="Remove question">Remove</button>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="kb-qa-empty" data-kb-qa-empty<?= ($knowledgeTraining['faqs'] ?? []) === [] ? '' : ' hidden' ?>>No questions yet. Add your first Q&amp;A above.</p>
                    </div>
                </div>

                <div class="kb-training-screen kb-training-screen--entry kb-training-screen--qa" data-kb-screen="qa" hidden>
                    <header class="kb-entry-header">
                        <button type="button" class="kb-entry-back" data-kb-back="qa-list" aria-label="Back to Q&amp;A list">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>

                        <div class="kb-entry-heading">
                            <span class="kb-entry-icon kb-entry-icon--qa" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    <path d="M9.09 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3"/>
                                    <path d="M12 17h.01"/>
                                </svg>
                            </span>
                            <div class="kb-entry-titles">
                                <h2 class="kb-entry-title font-heading">QUESTIONS &amp; ANSWER</h2>
                                <p class="kb-entry-subtitle">Provide a question-and-answer pairing your agent can use in conversations.</p>
                            </div>
                        </div>
                    </header>

                    <form class="kb-entry-form" data-kb-form="qa" novalidate>
                        <input type="hidden" name="entry_id" value="" data-kb-qa-entry-id>
                        <input type="hidden" name="payload_json" value="" data-kb-qa-payload>

                        <div class="kb-entry-card">
                            <div class="kb-qa-block">
                                <h3 class="kb-entry-card-title font-heading">Question</h3>
                                <p class="kb-entry-card-lead">Train the AI based on your questions &amp; answers</p>
                                <label class="ai-sr-only" for="kb-faq-question">Question</label>
                                <input
                                    id="kb-faq-question"
                                    class="kb-qa-input"
                                    type="text"
                                    name="faq_question"
                                    placeholder="Enter a brief, clear question that you'd like the Agent to answer."
                                    required
                                >
                            </div>

                            <div class="kb-qa-block">
                                <label class="kb-entry-card-title font-heading" for="kb-faq-answer-type">Answer Type</label>
                                <select id="kb-faq-answer-type" class="kb-qa-select" name="answer_type" data-kb-qa-answer-type>
                                    <option value="text" selected>Text</option>
                                    <option value="list">Show List of Items</option>
                                    <option value="video">Show Video</option>
                                    <option value="images">Show Images</option>
                                    <option value="checklist">Show Checklist</option>
                                    <option value="pdf">Display a PDF</option>
                                    <option value="location">Show Location</option>
                                </select>
                            </div>
                        </div>

                        <div class="kb-qa-answer-panels">
                            <div class="kb-entry-card kb-qa-panel is-active" data-kb-qa-panel="text">
                                <div class="kb-qa-rich-toolbar" data-kb-qa-toolbar>
                                    <button type="button" class="kb-qa-rich-btn" data-kb-qa-cmd="bold" aria-label="Bold"><strong>B</strong></button>
                                    <button type="button" class="kb-qa-rich-btn" data-kb-qa-cmd="italic" aria-label="Italic"><em>I</em></button>
                                    <button type="button" class="kb-qa-rich-btn" data-kb-qa-cmd="underline" aria-label="Underline"><span class="kb-qa-rich-underline">U</span></button>
                                    <button type="button" class="kb-qa-rich-btn" data-kb-qa-cmd="createLink" aria-label="Insert link">Link</button>
                                    <button type="button" class="kb-qa-rich-btn" data-kb-qa-cmd="removeFormat" aria-label="Clear formatting">Tx</button>
                                </div>
                                <div
                                    class="kb-qa-rich-editor"
                                    data-kb-qa-text-editor
                                    contenteditable="true"
                                    data-placeholder="Provide a detailed and informative answer that will help the Agent respond accurately to the question."
                                ></div>
                            </div>

                            <div class="kb-entry-card kb-qa-panel" data-kb-qa-panel="list" hidden>
                                <div class="kb-qa-panel-head">
                                    <h3 class="kb-qa-panel-title font-heading">Setup your list</h3>
                                </div>
                                <div class="kb-qa-list-items" data-kb-qa-list-items></div>
                                <button type="button" class="kb-qa-add-item" data-kb-qa-add-list-item>+ Add list item</button>
                            </div>

                            <div class="kb-entry-card kb-qa-panel" data-kb-qa-panel="video" hidden>
                                <div class="kb-qa-panel-head">
                                    <h3 class="kb-qa-panel-title font-heading">Select a Platform</h3>
                                </div>
                                <div class="kb-qa-field-row">
                                    <label class="kb-qa-field-label" for="kb-faq-video-platform">Platform</label>
                                    <select id="kb-faq-video-platform" class="kb-qa-select" data-kb-qa-video-platform>
                                        <option value="">Please select</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="vimeo">Vimeo</option>
                                        <option value="23video">23 Video</option>
                                        <option value="loom">Loom Video Embed</option>
                                        <option value="ziggeo">Ziggeo Video Player</option>
                                        <option value="animoto">Animoto</option>
                                    </select>
                                </div>
                                <div class="kb-qa-field-row">
                                    <label class="kb-qa-field-label" for="kb-faq-video-url">Video URL</label>
                                    <input id="kb-faq-video-url" class="kb-qa-input" type="url" placeholder="https://" data-kb-qa-video-url>
                                </div>
                            </div>

                            <div class="kb-entry-card kb-qa-panel" data-kb-qa-panel="images" hidden>
                                <div class="kb-qa-panel-head">
                                    <h3 class="kb-qa-panel-title font-heading">Upload Your Images</h3>
                                </div>
                                <div class="kb-qa-dropzone" data-kb-qa-image-dropzone>
                                    <input type="file" class="kb-qa-file-input" accept="image/*" multiple data-kb-qa-image-input hidden>
                                    <span class="kb-qa-dropzone-icon" aria-hidden="true">
                                        <?= brand_upload_icon_html('', 28) ?>
                                    </span>
                                    <button type="button" class="kb-qa-dropzone-btn" data-kb-qa-image-trigger>Choose Files</button>
                                    <p class="kb-qa-dropzone-hint"><button type="button" class="kb-doc-drop-link" data-kb-qa-image-trigger>Click to upload or drag &amp; drop here</button></p>
                                </div>
                                <ul class="kb-qa-image-list" data-kb-qa-image-list></ul>
                            </div>

                            <div class="kb-entry-card kb-qa-panel" data-kb-qa-panel="checklist" hidden>
                                <div class="kb-qa-panel-head">
                                    <h3 class="kb-qa-panel-title font-heading">Add Your List</h3>
                                </div>
                                <div class="kb-qa-checklist-items" data-kb-qa-checklist-items></div>
                                <button type="button" class="kb-qa-add-item" data-kb-qa-add-checklist-item>+ New checklist item</button>
                            </div>

                            <div class="kb-entry-card kb-qa-panel" data-kb-qa-panel="pdf" hidden>
                                <div class="kb-qa-panel-head">
                                    <h3 class="kb-qa-panel-title font-heading">Upload Your PDF</h3>
                                </div>
                                <div class="kb-qa-dropzone" data-kb-qa-pdf-dropzone>
                                    <input type="file" class="kb-qa-file-input" accept="application/pdf,.pdf" data-kb-qa-pdf-input hidden>
                                    <span class="kb-qa-dropzone-icon" aria-hidden="true">
                                        <?= brand_upload_icon_html('', 28) ?>
                                    </span>
                                    <button type="button" class="kb-qa-dropzone-btn" data-kb-qa-pdf-trigger>Choose File</button>
                                    <p class="kb-qa-dropzone-hint"><button type="button" class="kb-doc-drop-link" data-kb-qa-pdf-trigger>Click to upload or drag &amp; drop here</button></p>
                                </div>
                                <div class="kb-qa-pdf-chip" data-kb-qa-pdf-chip hidden>
                                    <span class="kb-qa-pdf-name" data-kb-qa-pdf-name></span>
                                    <button type="button" class="kb-qa-pdf-clear" data-kb-qa-pdf-clear aria-label="Remove PDF">&times;</button>
                                </div>
                            </div>

                            <div class="kb-entry-card kb-qa-panel" data-kb-qa-panel="location" hidden>
                                <div class="kb-qa-panel-head">
                                    <h3 class="kb-qa-panel-title font-heading">Setup Your Location</h3>
                                </div>
                                <div class="kb-qa-field-row">
                                    <label class="kb-qa-field-label" for="kb-faq-location-address">Address</label>
                                    <input id="kb-faq-location-address" class="kb-qa-input" type="text" placeholder="Please enter a location" data-kb-qa-location-address>
                                </div>
                                <div class="kb-qa-field-row">
                                    <label class="kb-qa-field-label" for="kb-faq-location-map-type">Map Type</label>
                                    <select id="kb-faq-location-map-type" class="kb-qa-select" data-kb-qa-location-map-type>
                                        <option value="">Please select</option>
                                        <option value="roadmap">Roadmap</option>
                                        <option value="satellite">Satellite</option>
                                    </select>
                                </div>
                                <div class="kb-qa-map-preview" data-kb-qa-map-preview>
                                    <iframe
                                        title="Map preview"
                                        class="kb-qa-map-frame"
                                        data-kb-qa-map-frame
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        src="about:blank"
                                    ></iframe>
                                </div>
                            </div>
                        </div>

                        <p class="kb-entry-status" data-kb-status="qa" aria-live="polite"></p>

                        <div class="kb-entry-actions">
                            <button type="submit" class="kb-entry-save font-heading" data-kb-faq-submit disabled>Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
