<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string|false|null $error */
/** @var string|false|null $success */

require_once dirname(__DIR__) . '/help/helpers.php';

$contact = app_config()['contact'];
$submitUrl = e(app_route('contact/submit'));
$helpData = help_center_data();
$user = (new SessionAuth())->user();
$helpHomeUrl = $user !== null ? help_home_url() : app_route('help');
?>
<div class="hc-page hc-page--public">
    <div class="hc-wrap">
        <header class="hc-hero">
            <p class="hc-kicker">Help Center</p>
            <h1 class="hc-hero-title font-heading">Contact us</h1>
            <p class="hc-hero-lead">Send a message and our team will get back to you.</p>
            <form class="hc-search" action="<?= e(help_home_url()) ?>" method="get" role="search">
                <label class="sr-only" for="hc-contact-search">Search help</label>
                <svg class="hc-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M16 16l4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    id="hc-contact-search"
                    name="q"
                    class="hc-search-input"
                    placeholder="Search questions, keywords, or topics"
                    autocomplete="off"
                >
            </form>
        </header>

        <nav class="hc-topics" aria-label="Help topics">
            <a href="<?= e(help_home_url()) ?>" class="hc-topic-pill">Home</a>
            <?php foreach ($helpData['topics'] as $topicSlug => $topic): ?>
                <a href="<?= e(help_topic_url($topicSlug)) ?>" class="hc-topic-pill"><?= e($topic['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="hc-panel">
            <main class="hc-content">
                <nav class="hc-breadcrumbs" aria-label="Breadcrumb">
                    <a href="<?= e(help_home_url()) ?>">Home</a>
                    <span class="hc-breadcrumb-sep" aria-hidden="true">/</span>
                    <span>Contact us</span>
                </nav>

                <div class="hc-contact-layout">
                    <div class="hc-contact-intro">
                        <header class="hc-section-head">
                            <h2 class="hc-section-title font-heading">Send us a message</h2>
                            <p class="hc-contact-lead">
                                We are available for questions, feedback, or collaboration opportunities.
                            </p>
                        </header>
                        <ul class="hc-contact-details">
                            <li>
                                <strong>Phone:</strong>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', (string) $contact['phone']) ?? '') ?>">
                                    <?= e((string) $contact['phone']) ?>
                                </a>
                            </li>
                            <li>
                                <strong>Email:</strong>
                                <a href="mailto:<?= e((string) $contact['email']) ?>">
                                    <?= e((string) $contact['email']) ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="hc-contact-form-card">
                        <?php if ($error): ?>
                            <div class="hc-contact-alert hc-contact-alert--error" role="alert"><?= e($error) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="hc-contact-alert hc-contact-alert--success" role="status"><?= e($success) ?></div>
                        <?php endif; ?>

                        <form class="hc-contact-form" method="post" action="<?= $submitUrl ?>" novalidate>
                            <div class="hc-contact-row">
                                <div class="hc-contact-field">
                                    <label class="hc-contact-label" for="firstname">First name</label>
                                    <input class="hc-contact-input" type="text" id="firstname" name="first_name" placeholder="First name" required autocomplete="given-name">
                                </div>
                                <div class="hc-contact-field">
                                    <label class="hc-contact-label" for="lastname">Last name</label>
                                    <input class="hc-contact-input" type="text" id="lastname" name="last_name" placeholder="Last name" required autocomplete="family-name">
                                </div>
                            </div>

                            <div class="hc-contact-field">
                                <label class="hc-contact-label" for="email">Email</label>
                                <input class="hc-contact-input" type="email" id="email" name="email" placeholder="Email" required autocomplete="email">
                            </div>

                            <div class="hc-contact-field">
                                <label class="hc-contact-label" for="subject">Subject</label>
                                <input class="hc-contact-input" type="text" id="subject" name="subject" placeholder="Subject" required>
                            </div>

                            <div class="hc-contact-field">
                                <label class="hc-contact-label" for="message">Message</label>
                                <textarea class="hc-contact-textarea" id="message" name="message" placeholder="Type your message here." rows="4" required></textarea>
                            </div>

                            <button type="submit" class="hc-contact-submit">Send message</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
