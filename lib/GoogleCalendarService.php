<?php

declare(strict_types=1);

final class GoogleCalendarService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CALENDAR_API = 'https://www.googleapis.com/calendar/v3';

    /**
     * @return array{ok: true, event_id: string}|array{ok: false, error: string}
     */
    public function createConsultationEvent(
        string $businessId,
        array $offering,
        string $customerName,
        string $customerPhone,
        DateTimeImmutable $start,
        int $durationMinutes,
        ?string $customerEmail = null,
        ?string $notes = null
    ): array {
        $connection = RepositoryFactory::businessGoogleCalendar()->findByBusinessId($businessId);
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Google Calendar is not connected.'];
        }

        $accessToken = $this->ensureAccessToken($connection);
        if ($accessToken === null) {
            return ['ok' => false, 'error' => 'Could not refresh Google Calendar access.'];
        }

        $end = $start->modify('+' . max(15, $durationMinutes) . ' minutes');
        $timezone = (string) ($connection['timezone'] ?? 'Africa/Lagos');
        $calendarId = rawurlencode((string) ($connection['calendar_id'] ?? 'primary'));
        $offeringName = trim((string) ($offering['name'] ?? 'Consultation'));

        $description = "Booked via Lead Bird DM\n"
            . 'Customer: ' . $customerName . "\n"
            . 'Phone: ' . $customerPhone;
        if ($customerEmail !== null && $customerEmail !== '') {
            $description .= "\nEmail: " . $customerEmail;
        }
        if ($notes !== null && $notes !== '') {
            $description .= "\nNotes: " . $notes;
        }

        $payload = [
            'summary' => $offeringName . ' — ' . $customerName,
            'description' => $description,
            'start' => [
                'dateTime' => $start->format(DateTimeInterface::ATOM),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $end->format(DateTimeInterface::ATOM),
                'timeZone' => $timezone,
            ],
            'reminders' => [
                'useDefault' => true,
            ],
        ];

        if ($customerEmail !== null && $customerEmail !== '') {
            $payload['attendees'] = [['email' => $customerEmail]];
        }

        $response = $this->apiRequest(
            'POST',
            self::CALENDAR_API . '/calendars/' . $calendarId . '/events',
            $accessToken,
            $payload
        );

        if (!($response['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($response['error'] ?? 'Failed to create calendar event')];
        }

        $eventId = trim((string) ($response['data']['id'] ?? ''));
        if ($eventId === '') {
            return ['ok' => false, 'error' => 'Google Calendar did not return an event id.'];
        }

        return ['ok' => true, 'event_id' => $eventId];
    }

    /**
     * @return array{ok: true, busy: list<array{start: string, end: string}>}|array{ok: false, error: string}
     */
    public function getBusySlots(string $businessId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $connection = RepositoryFactory::businessGoogleCalendar()->findByBusinessId($businessId);
        if ($connection === null) {
            return ['ok' => false, 'error' => 'Google Calendar is not connected.'];
        }

        $accessToken = $this->ensureAccessToken($connection);
        if ($accessToken === null) {
            return ['ok' => false, 'error' => 'Could not refresh Google Calendar access.'];
        }

        $calendarId = (string) ($connection['calendar_id'] ?? 'primary');
        $payload = [
            'timeMin' => $from->format(DateTimeInterface::ATOM),
            'timeMax' => $to->format(DateTimeInterface::ATOM),
            'timeZone' => (string) ($connection['timezone'] ?? 'Africa/Lagos'),
            'items' => [['id' => $calendarId]],
        ];

        $response = $this->apiRequest('POST', self::CALENDAR_API . '/freeBusy', $accessToken, $payload);
        if (!($response['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($response['error'] ?? 'Could not read calendar availability')];
        }

        $busy = [];
        $calendars = is_array($response['data']['calendars'] ?? null) ? $response['data']['calendars'] : [];
        $calendarBusy = is_array($calendars[$calendarId]['busy'] ?? null) ? $calendars[$calendarId]['busy'] : [];
        foreach ($calendarBusy as $slot) {
            if (!is_array($slot)) {
                continue;
            }
            $busy[] = [
                'start' => (string) ($slot['start'] ?? ''),
                'end' => (string) ($slot['end'] ?? ''),
            ];
        }

        return ['ok' => true, 'busy' => $busy];
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function ensureAccessToken(array $connection): ?string
    {
        $accessToken = trim((string) ($connection['access_token'] ?? ''));
        $expiresAt = trim((string) ($connection['token_expires_at'] ?? ''));
        $refreshToken = trim((string) ($connection['refresh_token'] ?? ''));

        if ($expiresAt !== '') {
            $expires = new DateTimeImmutable($expiresAt);
            if ($expires > new DateTimeImmutable('+2 minutes')) {
                return $accessToken !== '' ? $accessToken : null;
            }
        }

        if ($refreshToken === '') {
            return $accessToken !== '' ? $accessToken : null;
        }

        $config = app_config()['google'];
        $body = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = $this->httpPost(self::TOKEN_URL, $body);
        if (!isset($response['access_token'])) {
            return null;
        }

        $newToken = (string) $response['access_token'];
        $expiresIn = (int) ($response['expires_in'] ?? 3600);
        $newExpires = (new DateTimeImmutable('now'))->modify('+' . $expiresIn . ' seconds')->format('Y-m-d H:i:s');

        RepositoryFactory::businessGoogleCalendar()->upsert((string) $connection['business_id'], [
            'access_token' => $newToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $newExpires,
            'calendar_id' => $connection['calendar_id'] ?? 'primary',
            'google_email' => $connection['google_email'] ?? null,
            'timezone' => $connection['timezone'] ?? 'Africa/Lagos',
        ]);

        return $newToken;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    private function apiRequest(string $method, string $url, string $accessToken, ?array $payload = null): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'Failed to initialize request'];
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload ?? [], JSON_THROW_ON_ERROR);
        }

        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded)
                ? ($decoded['error']['message'] ?? $decoded['error_description'] ?? $raw)
                : $raw;

            return ['ok' => false, 'error' => is_string($message) ? $message : 'Google Calendar API error'];
        }

        return ['ok' => true, 'data' => is_array($decoded) ? $decoded : []];
    }

    /**
     * @param array<string, string> $body
     * @return array<string, mixed>
     */
    private function httpPost(string $url, array $body): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : [];
    }
}
