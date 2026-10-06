<?php

declare(strict_types=1);

/** @var string $appUrl */

$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$logoSize = (string) ($logoSize ?? 'md');
$logoHref = (string) ($logoHref ?? rtrim($appUrl, '/') . '/');
$logoAriaLabel = (string) ($logoAriaLabel ?? $brandName . ' home');
$logoClass = trim('brand-logo brand-logo--' . $logoSize . ' ' . (string) ($logoClass ?? ''));
$logoSrc = rtrim($appUrl, '/') . '/assets/images/leadbird-logo.png';
$logoWrapperClass = (string) ($logoWrapperClass ?? '');
?>
<?php if ($logoWrapperClass !== ''): ?>
<div class="<?= e($logoWrapperClass) ?>">
<?php endif; ?>
<a href="<?= e($logoHref) ?>" class="<?= e($logoClass) ?>" aria-label="<?= e($logoAriaLabel) ?>">
    <img
        src="<?= e($logoSrc) ?>"
        alt="<?= e($brandName) ?>"
        width="250"
        height="150"
        decoding="async"
    >
</a>
<?php if ($logoWrapperClass !== ''): ?>
</div>
<?php endif; ?>
