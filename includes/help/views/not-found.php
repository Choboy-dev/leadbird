<?php

declare(strict_types=1);
?>
<nav class="hc-breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= e(help_home_url()) ?>">Home</a>
    <span class="hc-breadcrumb-sep" aria-hidden="true">/</span>
    <span>Not found</span>
</nav>
<header class="hc-section-head">
    <h2 class="hc-section-title font-heading">We could not find that page</h2>
    <p class="hc-section-lead">The article or topic may have moved.</p>
</header>
<p class="hc-empty">Try searching from <a href="<?= e(help_home_url()) ?>">Help home</a> or <a href="<?= e(help_contact_url()) ?>">contact support</a>.</p>
