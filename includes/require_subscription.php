<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_auth.php';

$userId = (new SessionAuth())->userId();
if ($userId === null || !(new SubscriptionService())->hasActiveSubscription($userId)) {
    redirect(app_route('pricing'));
}
