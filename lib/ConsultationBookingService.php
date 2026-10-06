<?php

declare(strict_types=1);

/**
 * Handles consultation booking actions from AI DM replies.
 */
final class ConsultationBookingService
{
    /**
     * Process booking-related AI output and optionally append confirmation to reply.
     *
     * @param array<string, mixed> $ai
     * @param list<array<string, mixed>> $offerings
     * @return array{ai: array<string, mixed>, appointment_id: ?string}
     */
    public function processAiBooking(
        string $businessId,
        string $conversationId,
        ?string $customerId,
        array $ai,
        array $offerings
    ): array {
        if (empty($ai['booking_required'])) {
            return ['ai' => $ai, 'appointment_id' => null];
        }

        $offeringId = trim((string) ($ai['booking_offering_id'] ?? ''));
        $offering = $this->findOffering($offerings, $offeringId);
        if ($offering === null) {
            return ['ai' => $ai, 'appointment_id' => null];
        }

        if ((string) ($offering['offering_type'] ?? '') !== 'consultation') {
            return ['ai' => $ai, 'appointment_id' => null];
        }

        $config = OfferingTypeRegistry::typeConfigFromOffering($offering);
        $bookingMode = (string) ($config['booking_mode'] ?? 'leadbird_calendar');

        $customerName = trim((string) ($ai['booking_customer_name'] ?? ''));
        $customerPhone = trim((string) ($ai['booking_customer_phone'] ?? ''));
        $preferredTime = trim((string) ($ai['booking_preferred_datetime'] ?? ''));
        $customerEmail = trim((string) ($ai['booking_customer_email'] ?? ''));

        if ($customerName === '' || $customerPhone === '' || $preferredTime === '') {
            RepositoryFactory::conversationBookingState()->upsert(
                $conversationId,
                (string) $offering['id'],
                'collecting',
                [
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'preferred_datetime' => $preferredTime,
                    'email' => $customerEmail,
                ]
            );

            return ['ai' => $ai, 'appointment_id' => null];
        }

        if ($bookingMode === 'external_link') {
            $link = OfferingTypeRegistry::actionUrl($offering);
            if ($link !== null && !str_contains((string) ($ai['customer_reply'] ?? ''), $link)) {
                $ai['customer_reply'] = trim((string) ($ai['customer_reply'] ?? ''))
                    . "\n\nBook here: " . $link;
            }
            RepositoryFactory::conversationBookingState()->deleteByConversationId($conversationId);

            return ['ai' => $ai, 'appointment_id' => null];
        }

        $start = $this->parsePreferredDatetime($preferredTime);
        if ($start === null) {
            $ai['customer_reply'] = trim((string) ($ai['customer_reply'] ?? ''))
                . "\n\nWhat day and time works for you? For example: Tuesday 2pm.";

            return ['ai' => $ai, 'appointment_id' => null];
        }

        $duration = max(15, (int) ($config['duration_minutes'] ?? 60));
        $end = $start->modify('+' . $duration . ' minutes');

        $calendar = new GoogleCalendarService();
        $calendarResult = $calendar->createConsultationEvent(
            $businessId,
            $offering,
            $customerName,
            $customerPhone,
            $start,
            $duration,
            $customerEmail !== '' ? $customerEmail : null
        );

        $googleEventId = null;
        if ($calendarResult['ok'] ?? false) {
            $googleEventId = (string) $calendarResult['event_id'];
        }

        $appointment = RepositoryFactory::consultationAppointments()->create($businessId, [
            'offering_id' => (string) $offering['id'],
            'conversation_id' => $conversationId !== '' ? $conversationId : null,
            'customer_id' => $customerId !== null && $customerId !== '' ? $customerId : null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail !== '' ? $customerEmail : null,
            'scheduled_start' => $start->format('Y-m-d H:i:s'),
            'scheduled_end' => $end->format('Y-m-d H:i:s'),
            'status' => ($calendarResult['ok'] ?? false) ? 'confirmed' : 'pending_calendar',
            'google_event_id' => $googleEventId,
            'notes' => !($calendarResult['ok'] ?? false) ? (string) ($calendarResult['error'] ?? '') : null,
        ]);

        RepositoryFactory::conversationBookingState()->deleteByConversationId($conversationId);

        $formattedTime = $start->format('l, j M Y \a\t g:ia');
        $confirmation = "\n\nYou're booked for " . $formattedTime . '. We have your name (' . $customerName . ') and phone (' . $customerPhone . ').';
        if (!($calendarResult['ok'] ?? false)) {
            $confirmation = "\n\nI've noted your request for " . $formattedTime . '. The team will confirm your slot shortly.';
        }

        if (!str_contains((string) ($ai['customer_reply'] ?? ''), $formattedTime)) {
            $ai['customer_reply'] = trim((string) ($ai['customer_reply'] ?? '')) . $confirmation;
        }

        $ai['owner_alert_required'] = true;
        $ai['owner_alert_reason'] = 'New consultation booked: ' . (string) ($offering['name'] ?? 'Session') . ' — ' . $customerName;

        return ['ai' => $ai, 'appointment_id' => (string) ($appointment['id'] ?? '')];
    }

    /**
     * @param list<array<string, mixed>> $offerings
     */
    private function findOffering(array $offerings, string $offeringId): ?array
    {
        foreach ($offerings as $offering) {
            if ((string) ($offering['id'] ?? '') === $offeringId) {
                return $offering;
            }
        }

        return null;
    }

    private function parsePreferredDatetime(string $input): ?DateTimeImmutable
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        try {
            $dt = new DateTimeImmutable($input, new DateTimeZone('Africa/Lagos'));

            return $dt;
        } catch (Throwable) {
            // Try common patterns like "Tuesday 2pm", "tomorrow 10am"
        }

        $lower = mb_strtolower($input);
        $base = new DateTimeImmutable('now', new DateTimeZone('Africa/Lagos'));

        if (str_contains($lower, 'tomorrow')) {
            $base = $base->modify('+1 day');
        }

        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)/i', $input, $matches) === 1) {
            $hour = (int) $matches[1];
            $minute = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
            $ampm = mb_strtolower($matches[3]);
            if ($ampm === 'pm' && $hour < 12) {
                $hour += 12;
            }
            if ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }

            return $base->setTime($hour, $minute, 0);
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $input, $dateMatch) === 1) {
            try {
                return new DateTimeImmutable($dateMatch[1] . ' 10:00:00', new DateTimeZone('Africa/Lagos'));
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Build extra prompt context when a booking is in progress.
     *
     * @param array<string, mixed>|null $bookingState
     */
    public static function promptContext(?array $bookingState): ?string
    {
        if ($bookingState === null) {
            return null;
        }

        $collected = is_array($bookingState['collected_data'] ?? null) ? $bookingState['collected_data'] : [];
        $parts = ['Active consultation booking in progress.'];
        foreach (['name', 'phone', 'preferred_datetime', 'email'] as $key) {
            $value = trim((string) ($collected[$key] ?? ''));
            if ($value !== '') {
                $parts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
            }
        }
        $parts[] = 'Collect any missing fields, then set booking_required true with booking_offering_id, booking_customer_name, booking_customer_phone, booking_preferred_datetime.';

        return implode(' ', $parts);
    }
}
