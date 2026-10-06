<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var list<array<string, mixed>> $offerings */
/** @var array<string, mixed>|null $editingOffering */
/** @var string $formAction */
/** @var array<string, mixed> $formState */
/** @var string $saveUrl */
/** @var string $cancelUrl */
/** @var string $formContext */
/** @var bool $googleCalendarConnected */
/** @var bool $openOfferModal */
/** @var string|false|null $error */
/** @var string|false|null $success */

$offersUrl = e(app_route('offers'));
$saveOfferingsUrl = e(app_route('onboarding/save-offerings'));
$isEditing = $editingOffering !== null;

$formatOfferDate = static function (?string $datetime): string {
    if ($datetime === null || trim($datetime) === '') {
        return '—';
    }

    $ts = strtotime($datetime);

    return $ts !== false ? date('M j, Y', $ts) : $datetime;
};
?>
<div
    class="catalog-page<?= $isEditing ? ' catalog-page--edit' : '' ?>"
    data-catalog-page
    <?= $isEditing ? 'data-offer-edit-page' : '' ?>
    <?= $openOfferModal ? 'data-offer-modal-autoload="true"' : '' ?>
>
    <?php if ($success): ?>
        <div class="dashboard-alert dashboard-alert--success catalog-alert" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="dashboard-alert catalog-alert catalog-alert--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($isEditing): ?>
        <header class="catalog-edit-head">
            <a href="<?= $offersUrl ?>" class="catalog-edit-back font-sans">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to catalog
            </a>
            <h2 class="catalog-edit-title font-heading">Edit offer</h2>
            <p class="catalog-edit-lead font-sans">Change any field below and save. Your Salesgirl uses the latest details in customer DMs.</p>
        </header>
        <div class="catalog-edit-body">
            <?php require dirname(__DIR__) . '/offering-form.php'; ?>
        </div>
    <?php else: ?>

    <div class="catalog-toolbar">
        <label class="catalog-search">
            <span class="sr-only">Search offers</span>
            <svg class="catalog-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/>
                <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
            <input
                type="search"
                class="catalog-search-input"
                placeholder="Search offers…"
                data-catalog-search
                autocomplete="off"
            >
        </label>
        <button type="button" class="onboarding-btn onboarding-btn--primary catalog-toolbar-btn" data-offer-modal-open>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="catalog-toolbar-btn-label">New offer</span>
        </button>
    </div>

    <?php if ($offerings === []): ?>
        <div class="catalog-empty">
            <div class="catalog-empty-icon" aria-hidden="true">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                    <path d="M4 7h16l-1.2 10.5a1 1 0 0 1-1 .5H6.2a1 1 0 0 1-1-.5L4 7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    <path d="M9 11h6M9 15h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M8 7V5.5A1.5 1.5 0 0 1 9.5 4h5A1.5 1.5 0 0 1 16 5.5V7" stroke="currentColor" stroke-width="1.5"/>
                </svg>
            </div>
            <h2 class="catalog-empty-title font-heading">No offers yet</h2>
            <p class="catalog-empty-copy">Add what you sell so your Salesgirl can quote prices and answer product questions in DMs.</p>
            <button type="button" class="onboarding-btn onboarding-btn--primary onboarding-btn--add-offer" data-offer-modal-open>New offer</button>
        </div>
    <?php else: ?>
        <div class="catalog-grid" data-catalog-grid>
            <?php foreach ($offerings as $offering): ?>
                <?php
                $id = (string) $offering['id'];
                $type = (string) ($offering['offering_type'] ?? 'product');
                $meta = dash_offering_type_meta($type);
                $name = trim((string) ($offering['name'] ?? ''));
                $description = trim((string) ($offering['description'] ?? ''));
                $imageUrl = dash_offering_image_url((string) ($offering['image_url'] ?? ''));
                $priceLabel = !empty($offering['is_free'])
                    ? 'Free'
                    : ($offering['price'] !== null
                        ? dash_format_catalog_money(
                            (float) $offering['price'],
                            (string) ($offering['currency'] ?? money_default_catalog_currency())
                        )
                        : '—');
                $searchText = strtolower(trim($name . ' ' . $meta['label'] . ' ' . $description . ' ' . $priceLabel));
                ?>
                <article
                    class="catalog-card"
                    data-catalog-card
                    data-search="<?= e($searchText) ?>"
                >
                    <a href="<?= $offersUrl ?>?edit=<?= e($id) ?>" class="catalog-card-link" aria-label="Edit <?= e($name) ?>">
                        <?php if ($imageUrl !== null): ?>
                            <div class="catalog-card-media">
                                <img src="<?= e($imageUrl) ?>" alt="" class="catalog-card-image" loading="lazy" decoding="async">
                            </div>
                        <?php else: ?>
                            <div class="catalog-card-media catalog-card-media--empty <?= e($meta['class']) ?>" aria-hidden="true">
                                <span class="catalog-card-media-letter font-heading"><?= e(strtoupper(substr($meta['label'], 0, 1))) ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                    <div class="catalog-card-body">
                        <div class="catalog-card-head">
                            <h3 class="catalog-card-name font-heading">
                                <a href="<?= $offersUrl ?>?edit=<?= e($id) ?>" class="catalog-card-name-link"><?= e($name) ?></a>
                            </h3>
                            <div class="catalog-card-menu" data-catalog-menu>
                                <button
                                    type="button"
                                    class="catalog-card-menu-btn"
                                    data-catalog-menu-trigger
                                    aria-label="Actions for <?= e($name) ?>"
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <circle cx="5" cy="12" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="19" cy="12" r="1.75"/>
                                    </svg>
                                </button>
                                <div class="catalog-card-menu-panel" data-catalog-menu-panel role="menu" hidden>
                                    <a href="<?= $offersUrl ?>?edit=<?= e($id) ?>" class="catalog-card-menu-item" role="menuitem">Edit</a>
                                    <form method="post" action="<?= $saveOfferingsUrl ?>" class="catalog-card-menu-form">
                                        <input type="hidden" name="context" value="dashboard">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="offering_id" value="<?= e($id) ?>">
                                        <button type="submit" class="catalog-card-menu-item catalog-card-menu-item--danger" data-confirm-delete role="menuitem">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <span class="catalog-card-type"><?= e($meta['label']) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="catalog-no-results is-hidden" data-catalog-no-results hidden>No offers match your search.</p>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php if (!$isEditing): ?>
<div class="lb-offer-modal" data-offer-modal hidden aria-hidden="true">
    <button type="button" class="lb-offer-modal-backdrop" data-offer-modal-close aria-label="Close"></button>
    <div class="lb-offer-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="offer-modal-title">
        <header class="lb-offer-modal-head">
            <div>
                <p class="lb-offer-modal-eyebrow">Step-by-step</p>
                <h2 id="offer-modal-title" class="lb-offer-modal-title font-heading">New offer</h2>
            </div>
            <button type="button" class="lb-offer-modal-close" data-offer-modal-close aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </header>
        <div class="lb-offer-modal-body">
            <?php require dirname(__DIR__) . '/offering-form.php'; ?>
        </div>
    </div>
</div>
<?php endif; ?>
