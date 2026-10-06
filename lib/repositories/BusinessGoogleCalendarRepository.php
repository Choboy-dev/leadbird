<?php

declare(strict_types=1);

final class BusinessGoogleCalendarRepository extends BaseRepository
{
    public function findByBusinessId(string $businessId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM business_google_calendar WHERE business_id = :business_id LIMIT 1',
            ['business_id' => $businessId]
        );
    }

    public function upsert(string $businessId, array $data): array
    {
        $existing = $this->findByBusinessId($businessId);
        if ($existing !== null) {
            $this->execute(
                'UPDATE business_google_calendar SET
                    google_email = :google_email,
                    calendar_id = :calendar_id,
                    access_token = :access_token,
                    refresh_token = COALESCE(:refresh_token, refresh_token),
                    token_expires_at = :token_expires_at,
                    timezone = :timezone,
                    updated_at = NOW()
                 WHERE business_id = :business_id',
                [
                    'business_id' => $businessId,
                    'google_email' => $data['google_email'] ?? null,
                    'calendar_id' => $data['calendar_id'] ?? 'primary',
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_expires_at' => $data['token_expires_at'] ?? null,
                    'timezone' => $data['timezone'] ?? 'Africa/Lagos',
                ]
            );

            return $this->findByBusinessId($businessId) ?? throw new RuntimeException('Failed to update calendar connection');
        }

        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO business_google_calendar (
                id, business_id, google_email, calendar_id, access_token, refresh_token, token_expires_at, timezone
            ) VALUES (
                :id, :business_id, :google_email, :calendar_id, :access_token, :refresh_token, :token_expires_at, :timezone
            )',
            [
                'id' => $id,
                'business_id' => $businessId,
                'google_email' => $data['google_email'] ?? null,
                'calendar_id' => $data['calendar_id'] ?? 'primary',
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_expires_at' => $data['token_expires_at'] ?? null,
                'timezone' => $data['timezone'] ?? 'Africa/Lagos',
            ]
        );

        return $this->findByBusinessId($businessId) ?? throw new RuntimeException('Failed to create calendar connection');
    }

    public function deleteByBusinessId(string $businessId): void
    {
        $this->execute('DELETE FROM business_google_calendar WHERE business_id = :business_id', ['business_id' => $businessId]);
    }
}
