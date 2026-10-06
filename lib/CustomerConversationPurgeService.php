<?php

declare(strict_types=1);

/**
 * Permanently remove a chat and all stored data for that customer.
 */
final class CustomerConversationPurgeService
{
    /**
     * @return array{conversation_ids: list<string>, customer_id: ?string}
     */
    public function purgeFromConversation(string $businessId, string $conversationId): array
    {
        $conversation = RepositoryFactory::conversations()->findById($conversationId);
        if ($conversation === null || (string) ($conversation['business_id'] ?? '') !== $businessId) {
            throw new RuntimeException('Conversation not found.');
        }

        $customerId = trim((string) ($conversation['customer_id'] ?? ''));
        $conversationIds = $customerId !== ''
            ? RepositoryFactory::conversations()->listIdsByCustomerId($businessId, $customerId)
            : [$conversationId];

        if ($conversationIds === []) {
            $conversationIds = [$conversationId];
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            RepositoryFactory::automationQueue()->deleteForConversations($conversationIds);
            RepositoryFactory::businessKnowledge()->deleteLearnedMemoryForConversations($businessId, $conversationIds);
            RepositoryFactory::ownerAlerts()->deleteForCustomerAndConversations(
                $businessId,
                $customerId !== '' ? $customerId : null,
                $conversationIds
            );

            if ($customerId !== '') {
                RepositoryFactory::handoffs()->deleteForCustomer($businessId, $customerId);
                RepositoryFactory::consultationAppointments()->deleteForCustomer($businessId, $customerId);
                RepositoryFactory::orders()->deleteForCustomer($businessId, $customerId);
                RepositoryFactory::messages()->deleteByCustomerId($businessId, $customerId);
                RepositoryFactory::conversations()->deleteByCustomerId($businessId, $customerId);
                RepositoryFactory::customers()->deleteById($customerId, $businessId);
            } else {
                RepositoryFactory::messages()->deleteByConversationIds($businessId, $conversationIds);
                foreach ($conversationIds as $id) {
                    RepositoryFactory::conversations()->deleteById($id, $businessId);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return [
            'conversation_ids' => $conversationIds,
            'customer_id' => $customerId !== '' ? $customerId : null,
        ];
    }
}
