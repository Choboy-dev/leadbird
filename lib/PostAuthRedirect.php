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
            $user = RepositoryFactory::users()->findById($userId);
            $partner = (new AffiliateService())->findForUser($userId, (string) ($user['email'] ?? ''));

            return app_route($partner !== null ? 'partners/dashboard' : 'pricing');
        }

        return (new OnboardingService())->redirectUrlForUser($userId);
    }
}
