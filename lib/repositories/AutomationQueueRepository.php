<?php

declare(strict_types=1);

final class AutomationQueueRepository extends BaseRepository
{
    public function enqueue(string $jobType, array $payload, ?DateTimeImmutable $availableAt = null): string
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO automation_queue (id, job_type, payload, status, available_at)
             VALUES (:id, :job_type, :payload, :status, :available_at)',
            [
                'id' => $id,
                'job_type' => $jobType,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'status' => 'pending',
                'available_at' => ($availableAt ?? new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );

        return $id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function claimPending(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $rows = $this->fetchAll(
            'SELECT * FROM automation_queue
             WHERE status = :status AND available_at <= NOW()
             ORDER BY created_at ASC
             LIMIT ' . $limit,
            ['status' => 'pending']
        );

        foreach ($rows as $row) {
            $this->execute(
                'UPDATE automation_queue SET status = :status, attempts = attempts + 1 WHERE id = :id AND status = :pending',
                ['id' => $row['id'], 'status' => 'processing', 'pending' => 'pending']
            );
        }

        return $rows;
    }

    public function markCompleted(string $id): void
    {
        $this->execute(
            'UPDATE automation_queue SET status = :status, processed_at = NOW(), last_error = NULL WHERE id = :id',
            ['id' => $id, 'status' => 'completed']
        );
    }

    public function markFailed(string $id, string $error, int $retryDelaySeconds = 60): void
    {
        $this->execute(
            'UPDATE automation_queue
             SET status = CASE WHEN attempts >= 5 THEN :failed ELSE :pending END,
                 last_error = :last_error,
                 available_at = DATE_ADD(NOW(), INTERVAL :retry_seconds SECOND)
             WHERE id = :id',
            [
                'id' => $id,
                'failed' => 'failed',
                'pending' => 'pending',
                'last_error' => mb_substr($error, 0, 2000),
                'retry_seconds' => $retryDelaySeconds,
            ]
        );
    }

    public function deleteForBusiness(string $businessId): void
    {
        $this->execute(
            'DELETE FROM automation_queue WHERE payload LIKE :pattern',
            ['pattern' => '%"business_id":"' . $businessId . '"%']
        );
    }

    /** @param list<string> $conversationIds */
    public function deleteForConversations(array $conversationIds): void
    {
        foreach ($conversationIds as $conversationId) {
            $conversationId = trim($conversationId);
            if ($conversationId === '') {
                continue;
            }

            $this->execute(
                'DELETE FROM automation_queue WHERE payload LIKE :pattern',
                ['pattern' => '%"conversation_id":"' . $conversationId . '"%']
            );
        }
    }
}
