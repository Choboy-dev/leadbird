<?php

declare(strict_types=1);

/**
 * Floating compose window, docked bottom-right like Gmail. Rendered on every mail
 * page so Compose, Reply, Forward and draft links never navigate away from the list.
 *
 * @var array $user
 */
$compose = Mail::composeContext($user, $_GET);
$signatures = Mail::listSignatures((int) $user['id']);
$scheduleOptions = Mail::scheduleOptions();
$composeError = $_SESSION['mail_compose_error'] ?? '';
unset($_SESSION['mail_compose_error']);
if ($composeError !== '') {
    $compose['open'] = true;
}
$titles = ['reply' => 'Reply', 'reply-all' => 'Reply all', 'forward' => 'Forward', 'draft' => 'Edit draft', 'new' => 'New message'];
$editorId = 'mail-compose-editor';

// Recipients start collapsed onto one line whenever there is already someone to show.
$recipientSummary = array_filter(array_map('trim', [$compose['to'], $compose['cc']]));
$bccCount = count(array_filter(array_map('trim', explode(',', $compose['bcc']))));
$collapsed = $recipientSummary !== [];
?>
<section class="mail-compose-panel<?= $compose['open'] ? ' is-open' : '' ?>" data-mail-compose aria-label="Compose message" <?= $compose['open'] ? '' : 'hidden' ?>>
    <header class="mail-compose-panel__bar" data-mail-compose-bar>
        <strong data-mail-compose-title><?= e($titles[$compose['mode']] ?? 'New message') ?></strong>
        <div class="mail-compose-panel__actions">
            <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-compose-minimize title="Minimize" aria-label="Minimize"><?= mail_icon('minimize') ?></button>
            <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-compose-expand title="Full screen" aria-label="Toggle full screen"><?= mail_icon('expand') ?></button>
            <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-compose-close title="Save &amp; close" aria-label="Save and close"><?= mail_icon('close') ?></button>
        </div>
    </header>

    <form class="mail-compose-panel__form" method="post" action="<?= e(Mail::url('compose')) ?>" enctype="multipart/form-data" data-mail-compose-form>
        <?= csrf_field() ?>
        <input type="hidden" name="draft_id" value="<?= (int) $compose['draft_id'] ?>" data-mail-draft-id>
        <input type="hidden" name="in_reply_to" value="<?= e($compose['in_reply_to']) ?>">
        <input type="hidden" name="html" value="" data-mail-body-field>
        <input type="hidden" name="scheduled_at" value="" data-mail-scheduled-field>
        <input type="hidden" name="action" value="send" data-mail-action-field>
        <input type="hidden" name="return_to" value="" data-mail-return-field>

        <?php if ($composeError !== ''): ?>
            <div class="mail-alert mail-alert--error" role="alert"><?= e((string) $composeError) ?></div>
        <?php endif; ?>

        <div class="mail-recipients" data-mail-recipients>
            <button class="mail-recipients__summary" type="button" data-mail-recipients-summary <?= $collapsed ? '' : 'hidden' ?>>
                <span class="mail-recipients__names" data-mail-recipients-names><?= e(implode(', ', $recipientSummary)) ?></span>
                <span class="mail-chip" data-mail-recipients-bcc <?= $bccCount > 0 ? '' : 'hidden' ?>><?= (int) $bccCount ?> Bcc</span>
            </button>

            <div class="mail-recipients__fields" data-mail-recipients-fields <?= $collapsed ? 'hidden' : '' ?>>
                <div class="mail-compose-row">
                    <label class="mail-compose-row__label" for="mail-compose-to">To</label>
                    <input id="mail-compose-to" class="mail-compose-row__input" name="to" value="<?= e($compose['to']) ?>" autocomplete="off" placeholder="Recipients">
                    <div class="mail-compose-row__toggles">
                        <button class="mail-inline-btn" type="button" data-mail-toggle-field="cc">Cc</button>
                        <button class="mail-inline-btn" type="button" data-mail-toggle-field="bcc">Bcc</button>
                    </div>
                </div>

                <div class="mail-compose-row" data-mail-field-row="cc"<?= trim($compose['cc']) !== '' ? '' : ' hidden' ?>>
                    <label class="mail-compose-row__label" for="mail-compose-cc">Cc</label>
                    <input id="mail-compose-cc" class="mail-compose-row__input" name="cc" value="<?= e($compose['cc']) ?>" autocomplete="off">
                </div>

                <div class="mail-compose-row" data-mail-field-row="bcc"<?= trim($compose['bcc']) !== '' ? '' : ' hidden' ?>>
                    <label class="mail-compose-row__label" for="mail-compose-bcc">Bcc</label>
                    <input id="mail-compose-bcc" class="mail-compose-row__input" name="bcc" value="<?= e($compose['bcc']) ?>" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="mail-compose-row mail-compose-row--subject">
            <label class="mail-visually-hidden" for="mail-compose-subject">Subject</label>
            <input id="mail-compose-subject" class="mail-compose-row__input" name="subject" value="<?= e($compose['subject']) ?>" placeholder="Subject" autocomplete="off">
        </div>

        <div class="mail-compose-body">
            <div id="<?= e($editorId) ?>" class="mail-editor" contenteditable="true" role="textbox" aria-multiline="true"
                 aria-label="Message body" data-mail-editor data-placeholder="Write your message"><?= $compose['body_html'] ?></div>
        </div>

        <div class="mail-attach-list" data-mail-attach-list hidden></div>
        <input class="mail-visually-hidden" id="mail-compose-files" type="file" name="attachments[]" multiple
               accept=".pdf,.txt,.csv,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
               data-mail-file-input aria-label="Attach files">

        <?php require __DIR__ . '/editor-toolbar.php'; ?>

        <footer class="mail-compose-panel__footer">
            <div class="mail-send-split">
                <button class="mail-btn mail-btn--send" type="submit" data-mail-send>Send</button>
                <div class="mail-fmt-pop" data-mail-pop>
                    <button class="mail-btn mail-btn--send mail-btn--caret" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" aria-label="More send options"><?= mail_icon('chevron-down', 'mail-fmt-caret') ?></button>
                    <div class="mail-pop-panel mail-pop-panel--list" data-mail-pop-panel hidden>
                        <button class="mail-pop-item" type="button" data-mail-schedule-open><?= mail_icon('clock') ?><span>Schedule send</span></button>
                    </div>
                </div>
            </div>

            <button class="mail-icon-btn mail-icon-btn--sm is-active" type="button" data-mail-toggle-format title="Formatting options" aria-label="Formatting options" aria-pressed="true"><?= mail_icon('font') ?></button>
            <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-attach title="Attach files" aria-label="Attach files"><?= mail_icon('attach') ?></button>

            <div class="mail-fmt-pop" data-mail-pop>
                <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="Insert link" aria-label="Insert link"><?= mail_icon('link') ?></button>
                <div class="mail-pop-panel mail-pop-panel--form" data-mail-pop-panel hidden>
                    <label for="mail-compose-link">Link URL</label>
                    <input id="mail-compose-link" type="url" placeholder="https://example.com" data-mail-link-input>
                    <div class="mail-pop-actions">
                        <button class="mail-btn mail-btn--sm mail-btn--ghost" type="button" data-mail-pop-dismiss>Cancel</button>
                        <button class="mail-btn mail-btn--sm" type="button" data-mail-link-apply>Apply</button>
                    </div>
                </div>
            </div>

            <div class="mail-fmt-pop" data-mail-pop>
                <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="Insert signature" aria-label="Insert signature"><?= mail_icon('signature') ?></button>
                <div class="mail-pop-panel mail-pop-panel--list" data-mail-pop-panel hidden>
                    <?php if ($signatures === []): ?>
                        <p class="mail-pop-empty">No signatures yet.</p>
                    <?php else: foreach ($signatures as $signature): ?>
                        <button class="mail-pop-item" type="button" data-mail-insert-signature="<?= (int) $signature['id'] ?>"><span><?= e((string) $signature['name']) ?></span><?= !empty($signature['is_default']) ? '<em>Default</em>' : '' ?></button>
                        <template data-mail-signature-html="<?= (int) $signature['id'] ?>"><?= Mail::signatureBlock($signature) ?></template>
                    <?php endforeach; endif; ?>
                    <a class="mail-pop-item" href="<?= e(Mail::url('settings')) ?>"><?= mail_icon('settings') ?><span>Manage signatures</span></a>
                </div>
            </div>

            <div class="mail-fmt-pop" data-mail-pop>
                <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="More options" aria-label="More options"><?= mail_icon('more') ?></button>
                <div class="mail-pop-panel mail-pop-panel--list" data-mail-pop-panel hidden>
                    <button class="mail-pop-item" type="button" data-mail-save-draft><?= mail_icon('drafts') ?><span>Save draft</span></button>
                </div>
            </div>

            <button class="mail-icon-btn mail-icon-btn--sm mail-compose-panel__discard" type="button" data-mail-discard title="Discard draft" aria-label="Discard draft"><?= mail_icon('trash') ?></button>
        </footer>

        <div class="mail-schedule" data-mail-schedule hidden>
            <header>
                <div>
                    <strong>Schedule send</strong>
                    <p class="mail-muted"><?= e(Mail::timezoneLabel()) ?></p>
                </div>
                <button class="mail-icon-btn mail-icon-btn--sm" type="button" data-mail-schedule-close aria-label="Close schedule send"><?= mail_icon('close') ?></button>
            </header>
            <div class="mail-schedule__presets">
                <?php foreach ($scheduleOptions as $option): ?>
                    <button class="mail-schedule__preset" type="button" data-mail-schedule-preset="<?= e($option['value']) ?>">
                        <span><?= e($option['label']) ?></span><em><?= e($option['hint']) ?></em>
                    </button>
                <?php endforeach; ?>
                <button class="mail-schedule__preset mail-schedule__preset--custom" type="button" data-mail-schedule-custom>
                    <?= mail_icon('calendar') ?><span>Pick date &amp; time</span>
                </button>
            </div>
            <div class="mail-schedule__custom" data-mail-schedule-fields hidden>
                <div class="mail-form-group">
                    <label for="mail-schedule-date">Date</label>
                    <input id="mail-schedule-date" type="date" data-mail-schedule-date>
                </div>
                <div class="mail-form-group">
                    <label for="mail-schedule-time">Time</label>
                    <input id="mail-schedule-time" type="time" data-mail-schedule-time>
                </div>
                <p class="mail-alert mail-alert--error" data-mail-schedule-error hidden role="alert"></p>
                <div class="mail-schedule__actions">
                    <button class="mail-btn mail-btn--ghost mail-btn--sm" type="button" data-mail-schedule-close>Cancel</button>
                    <button class="mail-btn mail-btn--sm" type="button" data-mail-schedule-submit>Schedule send</button>
                </div>
            </div>
        </div>
    </form>
</section>
