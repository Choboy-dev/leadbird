<?php

declare(strict_types=1);

final class ConversationBookingStateRepository extends BaseRepository
{
    public function findByConversationId(string $conversationId): ?array
    {
        $row = $this->fetchOne(
            'SELECT * FROM conversation_booking_state WHERE conversation_id = :conversation_id LIMIT 1',
            ['conversation_id' => $conversationId]
        );

        if ($row === null) {
            return null;
        }

        if (new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable('now')) {
            $this->deleteByConversationId($conversationId);

            return null;
        }

        if (!empty($row['collected_data']) && is_string($row['collected_data'])) {
            $decoded = json_decode($row['collected_data'], true);
            if (is_array($decoded)) {
                $row['collected_data'] = $decoded;
            }
        }

        return $row;
    }

    public function upsert(string $conversationId, string $offeringId, string $step, array $collectedData, int $ttlMinutes = 120): array
    {
        $existing = $this->fetchOne(
            'SELECT id FROM conversation_booking_state WHERE conversation_id = :conversation_id LIMIT 1',
            ['conversation_id' => $conversationId]
        );

        $expiresAt = (new DateTimeImmutable('now'))->modify('+' . $ttlMinutes . ' minutes')->format('Y-m-d H:i:s');
        $json = json_encode($collectedData, JSON_THROW_ON_ERROR);

        if ($existing !== null) {
            $this->execute(
                'UPDATE conversation_booking_state SET offering_id = :offering_id, step = :step, collected_data = :collected_data, expires_at = :expires_at WHERE conversation_id = :conversation_id',
                [
                    'conversation_id' => $conversationId,
                    'offering_id' => $offeringId,
                    'step' => $step,
                    'collected_data' => $json,
                    'expires_at' => $expiresAt,
                ]
            );
        } else {
            $this->execute(
                'INSERT INTO conversation_booking_state (id, conversation_id, offering_id, step, collected_data, expires_at)
                 VALUES (:id, :conversation_id, :offering_id, :step, :collected_data, :expires_at)',
                [
                    'id' => Uuid::v4(),
                    'conversation_id' => $conversationId,
                    'offering_id' => $offeringId,
                    'step' => $step,
                    'collected_data' => $json,
                    'expires_at' => $expiresAt,
                ]
            );
        }

        return $this->findByConversationId($conversationId) ?? throw new RuntimeException('Failed to save booking state');
    }

    public function deleteByConversationId(string $conversationId): void
    {
        $this->execute(
            'DELETE FROM conversation_booking_state WHERE conversation_id = :conversation_id',
            ['conversation_id' => $conversationId]
        );
    }
}
