<?php

declare(strict_types=1);

/**
 * Permanently remove a business, revoke Meta webhooks, and clear related data.
 */
final class BusinessDeletionService
{
    public function deleteForUser(string $userId, string $businessId): void
    {
        $ctx = new BusinessContext();
        if (!$ctx->userOwnsBusiness($userId, $businessId)) {
            throw new RuntimeException('Business not found.');
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null) {
            throw new RuntimeException('Business not found.');
        }

        $businessName = (string) ($business['business_name'] ?? 'Business');
        $db = Database::connection();
        $db->beginTransaction();

        try {
            (new MetaConnectService())->revokeAllChannelsForBusiness($businessId);
            RepositoryFactory::automationQueue()->deleteForBusiness($businessId);
            RepositoryFactory::knowledgeLinks()->deleteByBusinessId($businessId);
            RepositoryFactory::businesses()->deleteById($businessId);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $this->clearSessionPointers($businessId);

        $remaining = RepositoryFactory::businesses()->listByUserId($userId);
        if ($remaining !== []) {
            $next = $this->pickNextBusiness($remaining);
            if ($next !== null) {
                $ctx->setActiveBusinessId((string) $next['id']);
            }
        }

        (new SubscriptionService())->syncBusinessSubscription($userId);

        flash_set('success', $businessName . ' and all of its data have been permanently deleted.');
    }

    private function clearSessionPointers(string $businessId): void
    {
        $ctx = new BusinessContext();

        if ($ctx->getActiveBusinessId() === $businessId) {
            unset($_SESSION['active_business_id']);
        }

        if ($ctx->getOnboardingBusinessId() === $businessId) {
            unset($_SESSION['onboarding_business_id']);
        }
    }

    /**
     * @param list<array<string, mixed>> $businesses
     */
    private function pickNextBusiness(array $businesses): ?array
    {
        foreach ($businesses as $business) {
            if (!empty($business['onboarding_completed_at'])) {
                return $business;
            }
        }

        return $businesses[0] ?? null;
    }
}
