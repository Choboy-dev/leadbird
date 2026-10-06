<?php

declare(strict_types=1);

/**
 * Formatting toolbar for a rich text editor. Stays on one row: anything that does
 * not fit scrolls horizontally instead of wrapping.
 *
 * @var string $editorId id of the contenteditable element this toolbar drives
 */
$editorId = (string) ($editorId ?? 'mail-editor');

$fonts = [
    'Sans Serif' => 'Arial, Helvetica, sans-serif',
    'Serif' => 'Georgia, "Times New Roman", serif',
    'Fixed Width' => '"Courier New", Courier, monospace',
    'Wide' => 'Verdana, Geneva, sans-serif',
    'Narrow' => '"Arial Narrow", Arial, sans-serif',
    'Trebuchet MS' => '"Trebuchet MS", Helvetica, sans-serif',
    'Tahoma' => 'Tahoma, Geneva, sans-serif',
];

$sizes = ['Small' => '2', 'Normal' => '3', 'Large' => '5', 'Huge' => '6'];

$textColors = [
    'Black' => '#000000', 'Dark grey' => '#5F6368', 'Brand blue' => '#0038FF', 'Deep blue' => '#001A99',
    'Red' => '#FF3B30', 'Green' => '#137333', 'Purple' => '#6B2FA0', 'Orange' => '#C05621',
];

$highlightColors = [
    'None' => 'transparent', 'Lime' => '#CCFF00', 'Soft blue' => '#E3E9FF',
    'Yellow' => '#FFF3B0', 'Grey' => '#EDEFF3', 'Pink' => '#FFE0E0',
];

$alignments = [
    ['justifyLeft', 'align-left', 'Align left'],
    ['justifyCenter', 'align-center', 'Center'],
    ['justifyRight', 'align-right', 'Align right'],
    ['justifyFull', 'align-justify', 'Justify'],
];
?>
<div class="mail-fmt-bar" role="toolbar" aria-label="Text formatting" data-mail-toolbar="<?= e($editorId) ?>">
    <button class="mail-fmt" type="button" data-mail-cmd="undo" title="Undo" aria-label="Undo"><?= mail_icon('undo') ?></button>
    <button class="mail-fmt" type="button" data-mail-cmd="redo" title="Redo" aria-label="Redo"><?= mail_icon('redo') ?></button>
    <span class="mail-fmt-divider" aria-hidden="true"></span>

    <div class="mail-fmt-pop" data-mail-pop>
        <button class="mail-fmt mail-fmt--label" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="Font">
            <span data-mail-fmt-label="fontName">Sans Serif</span><?= mail_icon('chevron-down', 'mail-fmt-caret') ?>
        </button>
        <div class="mail-pop-panel mail-pop-panel--list" data-mail-pop-panel hidden>
            <?php foreach ($fonts as $label => $stack): ?>
                <button class="mail-pop-item" type="button" data-mail-cmd="fontName" data-mail-value="<?= e($stack) ?>" data-mail-label="<?= e($label) ?>" style="font-family:<?= e($stack) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mail-fmt-pop" data-mail-pop>
        <button class="mail-fmt" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="Font size" aria-label="Font size">
            <?= mail_icon('font-size') ?><?= mail_icon('chevron-down', 'mail-fmt-caret') ?>
        </button>
        <div class="mail-pop-panel mail-pop-panel--list" data-mail-pop-panel hidden>
            <?php foreach ($sizes as $label => $value): ?>
                <button class="mail-pop-item" type="button" data-mail-cmd="fontSize" data-mail-value="<?= e($value) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <span class="mail-fmt-divider" aria-hidden="true"></span>

    <button class="mail-fmt" type="button" data-mail-cmd="bold" data-mail-state="bold" title="Bold (Ctrl+B)" aria-label="Bold"><?= mail_icon('bold') ?></button>
    <button class="mail-fmt" type="button" data-mail-cmd="italic" data-mail-state="italic" title="Italic (Ctrl+I)" aria-label="Italic"><?= mail_icon('italic') ?></button>
    <button class="mail-fmt" type="button" data-mail-cmd="underline" data-mail-state="underline" title="Underline (Ctrl+U)" aria-label="Underline"><?= mail_icon('underline') ?></button>

    <div class="mail-fmt-pop" data-mail-pop>
        <button class="mail-fmt" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="Text colour" aria-label="Text colour">
            <?= mail_icon('text-color') ?><?= mail_icon('chevron-down', 'mail-fmt-caret') ?>
        </button>
        <div class="mail-pop-panel" data-mail-pop-panel hidden>
            <p class="mail-pop-heading">Text colour</p>
            <div class="mail-swatches">
                <?php foreach ($textColors as $label => $hex): ?>
                    <button class="mail-swatch" type="button" data-mail-cmd="foreColor" data-mail-value="<?= e($hex) ?>" style="background:<?= e($hex) ?>" title="<?= e($label) ?>" aria-label="Text colour <?= e($label) ?>"></button>
                <?php endforeach; ?>
            </div>
            <p class="mail-pop-heading">Highlight</p>
            <div class="mail-swatches">
                <?php foreach ($highlightColors as $label => $hex): ?>
                    <button class="mail-swatch<?= $hex === 'transparent' ? ' mail-swatch--none' : '' ?>" type="button" data-mail-cmd="hiliteColor" data-mail-value="<?= e($hex) ?>" style="background:<?= e($hex) ?>" title="<?= e($label) ?>" aria-label="Highlight <?= e($label) ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="mail-fmt-pop" data-mail-pop>
        <button class="mail-fmt" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="Alignment" aria-label="Alignment">
            <?= mail_icon('align-left') ?><?= mail_icon('chevron-down', 'mail-fmt-caret') ?>
        </button>
        <div class="mail-pop-panel mail-pop-panel--row" data-mail-pop-panel hidden>
            <?php foreach ($alignments as [$command, $icon, $label]): ?>
                <button class="mail-fmt" type="button" data-mail-cmd="<?= e($command) ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>"><?= mail_icon($icon) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <button class="mail-fmt" type="button" data-mail-cmd="insertOrderedList" data-mail-state="insertOrderedList" title="Numbered list" aria-label="Numbered list"><?= mail_icon('list-ol') ?></button>
    <button class="mail-fmt" type="button" data-mail-cmd="insertUnorderedList" data-mail-state="insertUnorderedList" title="Bulleted list" aria-label="Bulleted list"><?= mail_icon('list-ul') ?></button>
    <button class="mail-fmt" type="button" data-mail-cmd="outdent" title="Decrease indent" aria-label="Decrease indent"><?= mail_icon('outdent') ?></button>
    <button class="mail-fmt" type="button" data-mail-cmd="indent" title="Increase indent" aria-label="Increase indent"><?= mail_icon('indent') ?></button>

    <button class="mail-fmt" type="button" data-mail-insert-image title="Insert image" aria-label="Insert image"><?= mail_icon('image') ?></button>
    <input class="mail-visually-hidden" type="file" accept="image/png,image/jpeg,image/gif,image/webp" multiple data-mail-image-input aria-label="Image to insert">

    <div class="mail-fmt-pop" data-mail-pop>
        <button class="mail-fmt" type="button" data-mail-pop-toggle aria-expanded="false" aria-haspopup="true" title="More formatting" aria-label="More formatting"><?= mail_icon('chevron-down', 'mail-fmt-caret') ?></button>
        <div class="mail-pop-panel mail-pop-panel--list" data-mail-pop-panel hidden>
            <button class="mail-pop-item" type="button" data-mail-cmd="strikeThrough"><?= mail_icon('strike') ?><span>Strikethrough</span></button>
            <button class="mail-pop-item" type="button" data-mail-cmd="formatBlock" data-mail-value="blockquote"><?= mail_icon('quote') ?><span>Quote</span></button>
            <button class="mail-pop-item" type="button" data-mail-cmd="unlink"><?= mail_icon('link') ?><span>Remove link</span></button>
            <button class="mail-pop-item" type="button" data-mail-cmd="removeFormat"><?= mail_icon('clear-format') ?><span>Clear formatting</span></button>
        </div>
    </div>
</div>
