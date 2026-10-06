<?php

declare(strict_types=1);

/**
 * Defines offering types, form fields, and AI chat behavior per type.
 */
final class OfferingTypeRegistry
{
    /** @return list<string> */
    public static function allTypes(): array
    {
        return ['product', 'service', 'membership', 'consultation', 'course', 'event'];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::allTypes(), true);
    }

    /**
     * @return array{label: string, icon: string, class: string, tagline: string, ai_role: string}
     */
    public static function meta(string $type): array
    {
        return match ($type) {
            'service' => [
                'label' => 'Service',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/994/994928.png',
                'class' => 'offers-type--service',
                'tagline' => 'One-off or ongoing work you deliver for clients.',
                'ai_role' => 'Quote scope, turnaround, and price. Collect requirements before confirming.',
            ],
            'membership' => [
                'label' => 'Membership',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/1585/1585316.png',
                'class' => 'offers-type--membership',
                'tagline' => 'Community, club, or subscription access.',
                'ai_role' => 'Explain benefits and billing. When they want to join, send the registration link.',
            ],
            'consultation' => [
                'label' => 'Consultation',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/7139/7139856.png',
                'class' => 'offers-type--consultation',
                'tagline' => 'Bookable sessions, coaching, advice, discovery calls.',
                'ai_role' => 'Explain session format and price. Collect name, phone, and preferred time to book.',
            ],
            'course' => [
                'label' => 'Course / Program',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/5965/5965835.png',
                'class' => 'offers-type--course',
                'tagline' => 'Training, classes, or digital programs.',
                'ai_role' => 'Explain curriculum and access. Send enrollment link when they are ready.',
            ],
            'event' => [
                'label' => 'Event / Ticket',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/1456/1456687.png',
                'class' => 'offers-type--event',
                'tagline' => 'Workshops, launches, or ticketed experiences.',
                'ai_role' => 'Share date, location, and price. Send ticket link when they want to register.',
            ],
            default => [
                'label' => 'Product',
                'icon' => 'https://cdn-icons-png.flaticon.com/512/962/962669.png',
                'class' => 'offers-type--product',
                'tagline' => 'Physical or digital items you sell.',
                'ai_role' => 'Quote price, variants, and delivery. Help them order or pay.',
            ],
        };
    }

    /**
     * Billing options allowed per type.
     *
     * @return list<string>
     */
    public static function billingOptions(string $type): array
    {
        return match ($type) {
            'membership', 'course' => ['monthly', 'one_time'],
            'consultation' => ['per_session', 'one_time'],
            'event' => ['one_time'],
            default => ['one_time', 'monthly', 'per_session'],
        };
    }

    public static function defaultBilling(string $type): string
    {
        $options = self::billingOptions($type);

        return match ($type) {
            'membership', 'course' => 'monthly',
            'consultation' => 'per_session',
            default => $options[0] ?? 'one_time',
        };
    }

    /**
     * @return list<string>
     */
    public static function aiCollectFields(string $type): array
    {
        return match ($type) {
            'product' => ['size_or_variant', 'delivery_address', 'phone'],
            'service' => ['requirements', 'deadline', 'phone'],
            'membership' => ['email', 'name'],
            'consultation' => ['name', 'phone', 'preferred_datetime', 'email'],
            'course' => ['name', 'email'],
            'event' => ['name', 'phone', 'ticket_quantity'],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function aiProvideFields(string $type): array
    {
        return match ($type) {
            'product' => ['price', 'variants', 'delivery_time', 'photo', 'purchase_link'],
            'service' => ['price', 'scope', 'turnaround', 'service_area'],
            'membership' => ['price', 'benefits', 'registration_link'],
            'consultation' => ['price', 'duration', 'session_format', 'availability'],
            'course' => ['price', 'modules', 'access_type', 'enrollment_link'],
            'event' => ['price', 'date', 'location', 'ticket_link'],
            default => ['price', 'description'],
        };
    }

    /**
     * Default empty type_config keys per type.
     *
     * @return array<string, mixed>
     */
    public static function defaultTypeConfig(string $type): array
    {
        return match ($type) {
            'product' => [
                'variants' => '',
                'delivery_notes' => '',
                'purchase_url' => '',
            ],
            'service' => [
                'scope' => '',
                'turnaround' => '',
                'service_area' => '',
            ],
            'membership' => [
                'benefits' => '',
                'registration_url' => '',
            ],
            'consultation' => [
                'duration_minutes' => 60,
                'session_format' => 'video',
                'booking_mode' => 'leadbird_calendar',
                'availability_notes' => '',
                'external_booking_url' => '',
            ],
            'course' => [
                'modules' => '',
                'access_type' => 'lifetime',
                'enrollment_url' => '',
            ],
            'event' => [
                'event_date' => '',
                'location' => '',
                'ticket_url' => '',
            ],
            default => [],
        };
    }

    /**
     * @param array<string, mixed>|null $offering
     * @return array<string, mixed>
     */
    public static function typeConfigFromOffering(?array $offering): array
    {
        if ($offering === null) {
            return [];
        }

        $type = (string) ($offering['offering_type'] ?? 'product');
        $defaults = self::defaultTypeConfig($type);
        $raw = $offering['type_config'] ?? null;

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $stored = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $stored = $raw;
        } else {
            $stored = [];
        }

        return array_merge($defaults, $stored);
    }

    public static function formatPrice(?array $offering): string
    {
        if ($offering === null) {
            return 'Price on request';
        }

        if (!empty($offering['is_free'])) {
            return 'Free';
        }

        if ($offering['price'] !== null && (float) $offering['price'] > 0) {
            $currency = money_normalize_currency((string) ($offering['currency'] ?? ''))
                ?? money_default_catalog_currency();

            return money_format_catalog((float) $offering['price'], $currency);
        }

        return 'Price on request';
    }

    public static function billingLabel(string $billingType): string
    {
        return match ($billingType) {
            'monthly' => 'Monthly',
            'per_session' => 'Per session',
            default => 'One-time',
        };
    }

    /**
     * Primary link the AI should send for this offering.
     */
    public static function actionUrl(?array $offering): ?string
    {
        if ($offering === null) {
            return null;
        }

        $direct = trim((string) ($offering['action_url'] ?? ''));
        if ($direct !== '') {
            return $direct;
        }

        $config = self::typeConfigFromOffering($offering);
        $type = (string) ($offering['offering_type'] ?? '');

        $fallback = match ($type) {
            'membership' => trim((string) ($config['registration_url'] ?? '')),
            'consultation' => trim((string) ($config['external_booking_url'] ?? '')),
            'course' => trim((string) ($config['enrollment_url'] ?? '')),
            'event' => trim((string) ($config['ticket_url'] ?? '')),
            'product' => trim((string) ($config['purchase_url'] ?? '')),
            default => '',
        };

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * Build rich knowledge text for AI sync.
     *
     * @param array<string, mixed> $offering
     */
    public static function knowledgeContent(array $offering): string
    {
        $type = (string) ($offering['offering_type'] ?? 'product');
        $meta = self::meta($type);
        $name = trim((string) ($offering['name'] ?? ''));
        $price = self::formatPrice($offering);
        $billing = self::billingLabel((string) ($offering['billing_type'] ?? 'one_time'));
        $description = trim((string) ($offering['description'] ?? ''));
        $config = self::typeConfigFromOffering($offering);
        $actionUrl = self::actionUrl($offering);

        $parts = [
            $name . ' (' . $meta['label'] . ')',
            'Price: ' . $price,
            'Billing: ' . $billing,
        ];

        if ($description !== '') {
            $parts[] = 'Description: ' . $description;
        }

        foreach (self::buildTypeDetailLines($type, $config) as $line) {
            $parts[] = $line;
        }

        if ($actionUrl !== null) {
            $parts[] = 'Link to share with customer: ' . $actionUrl;
        }

        $parts[] = 'AI should collect: ' . implode(', ', self::aiCollectFields($type));
        $parts[] = 'AI should provide: ' . implode(', ', self::aiProvideFields($type));
        $parts[] = 'Role: ' . $meta['ai_role'];

        return implode('. ', $parts);
    }

    /**
     * @param array<string, mixed> $config
     * @return list<string>
     */
    private static function buildTypeDetailLines(string $type, array $config): array
    {
        $lines = [];

        switch ($type) {
            case 'product':
                if (trim((string) ($config['variants'] ?? '')) !== '') {
                    $lines[] = 'Variants: ' . trim((string) $config['variants']);
                }
                if (trim((string) ($config['delivery_notes'] ?? '')) !== '') {
                    $lines[] = 'Delivery: ' . trim((string) $config['delivery_notes']);
                }
                break;
            case 'service':
                if (trim((string) ($config['scope'] ?? '')) !== '') {
                    $lines[] = 'Scope: ' . trim((string) $config['scope']);
                }
                if (trim((string) ($config['turnaround'] ?? '')) !== '') {
                    $lines[] = 'Turnaround: ' . trim((string) $config['turnaround']);
                }
                if (trim((string) ($config['service_area'] ?? '')) !== '') {
                    $lines[] = 'Service area: ' . trim((string) $config['service_area']);
                }
                break;
            case 'membership':
                if (trim((string) ($config['benefits'] ?? '')) !== '') {
                    $lines[] = 'Benefits: ' . trim((string) $config['benefits']);
                }
                break;
            case 'consultation':
                $duration = (int) ($config['duration_minutes'] ?? 60);
                $lines[] = 'Session length: ' . $duration . ' minutes';
                $format = trim((string) ($config['session_format'] ?? 'video'));
                $lines[] = 'Format: ' . $format;
                if (trim((string) ($config['availability_notes'] ?? '')) !== '') {
                    $lines[] = 'Availability: ' . trim((string) $config['availability_notes']);
                }
                $bookingMode = (string) ($config['booking_mode'] ?? 'leadbird_calendar');
                if ($bookingMode === 'leadbird_calendar') {
                    $lines[] = 'Booking: Lead Bird books directly on Google Calendar when customer provides name, phone, and preferred time';
                }
                break;
            case 'course':
                if (trim((string) ($config['modules'] ?? '')) !== '') {
                    $lines[] = 'Modules: ' . trim((string) $config['modules']);
                }
                $lines[] = 'Access: ' . trim((string) ($config['access_type'] ?? 'lifetime'));
                break;
            case 'event':
                if (trim((string) ($config['event_date'] ?? '')) !== '') {
                    $lines[] = 'Date: ' . trim((string) $config['event_date']);
                }
                if (trim((string) ($config['location'] ?? '')) !== '') {
                    $lines[] = 'Location: ' . trim((string) $config['location']);
                }
                break;
        }

        return $lines;
    }
}
