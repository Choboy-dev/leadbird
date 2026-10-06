<?php

declare(strict_types=1);

final class MetaDeletionRequestRepository extends BaseRepository
{
    public function create(string $metaUserId, string $confirmationCode, string $source = 'callback'): string
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO meta_deletion_requests (id, confirmation_code, meta_user_id, source, status)
             VALUES (:id, :confirmation_code, :meta_user_id, :source, :status)',
            [
                'id' => $id,
                'confirmation_code' => $confirmationCode,
                'meta_user_id' => $metaUserId,
                'source' => $source,
                'status' => 'pending',
            ]
        );

        return $id;
    }

    public function markCompleted(string $id, int $businessesAffected): void
    {
        $this->execute(
            'UPDATE meta_deletion_requests
             SET status = :status, businesses_affected = :businesses_affected, completed_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'status' => 'completed',
                'businesses_affected' => $businessesAffected,
            ]
        );
    }

    public function markFailed(string $id, string $errorMessage): void
    {
        $this->execute(
            'UPDATE meta_deletion_requests
             SET status = :status, error_message = :error_message
             WHERE id = :id',
            [
                'id' => $id,
                'status' => 'failed',
                'error_message' => mb_substr($errorMessage, 0, 500),
            ]
        );
    }

    public function findByConfirmationCode(string $confirmationCode): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM meta_deletion_requests WHERE confirmation_code = :confirmation_code LIMIT 1',
            ['confirmation_code' => $confirmationCode]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listBusinessIdsForMetaUser(string $metaUserId): array
    {
        return $this->fetchAll(
            'SELECT business_id FROM business_meta_connections WHERE meta_user_id = :meta_user_id',
            ['meta_user_id' => $metaUserId]
        );
    }

    /**
     * Remove every record sourced from Meta for one business: the stored user
     * token, the connected channels, and all conversation data that arrived
     * over Facebook, Instagram, or WhatsApp. Business-owned data the user typed
     * themselves (products, offers, settings) is intentionally left alone.
     */
    public function purgeMetaDataForBusiness(string $businessId): void
    {
        $params = ['business_id' => $businessId];

        $this->execute(
            'DELETE FROM messages WHERE business_id = :business_id AND platform IN (' . self::PLATFORM_LIST . ')',
            $params
        );
        $this->execute(
            'DELETE FROM conversations WHERE business_id = :business_id AND platform IN (' . self::PLATFORM_LIST . ')',
            $params
        );
        $this->execute(
            'DELETE FROM customers WHERE business_id = :business_id AND platform IN (' . self::PLATFORM_LIST . ')',
            $params
        );
        $this->execute(
            'DELETE FROM connected_accounts WHERE business_id = :business_id AND platform IN (' . self::PLATFORM_LIST . ')',
            $params
        );
        $this->execute(
            'DELETE FROM business_meta_connections WHERE business_id = :business_id',
            $params
        );
    }

    private const PLATFORM_LIST = "'facebook', 'instagram', 'whatsapp'";
}
