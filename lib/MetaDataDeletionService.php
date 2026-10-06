<?php

declare(strict_types=1);

/**
 * Handles Meta's Deauthorize and Data Deletion callbacks.
 *
 * Meta identifies the person by their app-scoped Facebook user ID. We map that
 * to the businesses that connected through them and remove everything we hold
 * that originated from Meta. The Lead Bird account itself survives, because a
 * user can sign in with Google or email and revoking Facebook access is not the
 * same as closing the account. Full account deletion stays available in
 * Settings and in the instructions page.
 */
final class MetaDataDeletionService
{
    /**
     * Revoke tokens and unsubscribe webhooks without deleting stored history.
     * Used by the Deauthorize callback (user removed the app from Facebook).
     */
    public function deauthorize(string $metaUserId): int
    {
        $businessIds = $this->businessIdsFor($metaUserId);

        foreach ($businessIds as $businessId) {
            try {
                (new MetaConnectService())->revokeAllChannelsForBusiness($businessId);
            } catch (Throwable $e) {
                error_log('[Meta] deauthorize revoke failed for ' . $businessId . ': ' . $e->getMessage());
            }

            RepositoryFactory::businessMetaConnections()->deleteByBusinessId($businessId);
        }

        return count($businessIds);
    }

    /**
     * Purge all Meta-sourced data and return the confirmation code Meta expects.
     *
     * @return array{code: string, status_url: string}
     */
    public function requestDeletion(string $metaUserId, string $source = 'callback'): array
    {
        $code = $this->generateConfirmationCode();
        $repo = RepositoryFactory::metaDeletionRequests();
        $requestId = $repo->create($metaUserId, $code, $source);

        try {
            $businessIds = $this->businessIdsFor($metaUserId);

            foreach ($businessIds as $businessId) {
                try {
                    (new MetaConnectService())->revokeAllChannelsForBusiness($businessId);
                } catch (Throwable $e) {
                    error_log('[Meta] deletion revoke failed for ' . $businessId . ': ' . $e->getMessage());
                }

                $repo->purgeMetaDataForBusiness($businessId);
            }

            $repo->markCompleted($requestId, count($businessIds));
        } catch (Throwable $e) {
            $repo->markFailed($requestId, $e->getMessage());
            error_log('[Meta] data deletion failed for ' . $metaUserId . ': ' . $e->getMessage());
        }

        return [
            'code' => $code,
            'status_url' => app_route('data-deletion', ['code' => $code]),
        ];
    }

    public function findStatus(string $confirmationCode): ?array
    {
        return RepositoryFactory::metaDeletionRequests()->findByConfirmationCode($confirmationCode);
    }

    /** @return list<string> */
    private function businessIdsFor(string $metaUserId): array
    {
        $rows = RepositoryFactory::metaDeletionRequests()->listBusinessIdsForMetaUser($metaUserId);

        $ids = [];
        foreach ($rows as $row) {
            $businessId = trim((string) ($row['business_id'] ?? ''));
            if ($businessId !== '') {
                $ids[] = $businessId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function generateConfirmationCode(): string
    {
        return 'LB' . strtoupper(bin2hex(random_bytes(8)));
    }
}
