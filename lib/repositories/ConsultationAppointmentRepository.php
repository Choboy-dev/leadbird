<?php

declare(strict_types=1);

final class ConsultationAppointmentRepository extends BaseRepository
{
    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO consultation_appointments (
                id, business_id, offering_id, conversation_id, customer_id,
                customer_name, customer_phone, customer_email,
                scheduled_start, scheduled_end, status, google_event_id, notes
            ) VALUES (
                :id, :business_id, :offering_id, :conversation_id, :customer_id,
                :customer_name, :customer_phone, :customer_email,
                :scheduled_start, :scheduled_end, :status, :google_event_id, :notes
            )',
            [
                'id' => $id,
                'business_id' => $businessId,
                'offering_id' => $data['offering_id'],
                'conversation_id' => $data['conversation_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'scheduled_start' => $data['scheduled_start'],
                'scheduled_end' => $data['scheduled_end'],
                'status' => $data['status'] ?? 'confirmed',
                'google_event_id' => $data['google_event_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create appointment');
    }

    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM consultation_appointments WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM consultation_appointments WHERE business_id = :business_id ORDER BY scheduled_start DESC LIMIT ' . max(1, min(200, $limit)),
            ['business_id' => $businessId]
        );
    }

    public function updateGoogleEventId(string $id, ?string $googleEventId): void
    {
        $this->execute(
            'UPDATE consultation_appointments SET google_event_id = :google_event_id WHERE id = :id',
            ['id' => $id, 'google_event_id' => $googleEventId]
        );
    }

    public function deleteForCustomer(string $businessId, string $customerId): void
    {
        $this->execute(
            'DELETE FROM consultation_appointments WHERE business_id = :business_id AND customer_id = :customer_id',
            ['business_id' => $businessId, 'customer_id' => $customerId]
        );
    }
}
