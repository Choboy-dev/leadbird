<?php

declare(strict_types=1);

/**
 * Single entry point for where authenticated users should land after sign-in.
 */
final class PostAuthRedirect
{
    public static function urlForUser(string $userId): string
    {
        $appUrl = app_config()['app_url'];

        if (!(new SubscriptionService())->hasActiveSubscription($userId)) {
            return app_route('pricing');
        }

        return (new OnboardingService())->redirectUrlForUser($userId);
    }
}
