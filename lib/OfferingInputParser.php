<?php

declare(strict_types=1);

final class OfferingInputParser
{
    /**
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, error: string}
     */
    public static function parse(?string $editingId = null): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'error' => 'Offer name is required.'];
        }

        $type = (string) ($_POST['offering_type'] ?? 'product');
        if (!OfferingTypeRegistry::isValid($type)) {
            $type = 'product';
        }

        $isFree = !empty($_POST['is_free']);
        $priceRaw = trim((string) ($_POST['price'] ?? ''));
        $price = null;
        if (!$isFree && $priceRaw !== '' && is_numeric($priceRaw)) {
            $price = max(0, (float) $priceRaw);
            if ($price <= 0) {
                $price = null;
            }
        }

        $currency = money_normalize_currency((string) ($_POST['currency'] ?? ''));
        if ($currency === null) {
            $currency = money_default_catalog_currency();
        }

        $billingType = (string) ($_POST['billing_type'] ?? OfferingTypeRegistry::defaultBilling($type));
        if (!in_array($billingType, OfferingTypeRegistry::billingOptions($type), true)) {
            $billingType = OfferingTypeRegistry::defaultBilling($type);
        }

        $description = trim((string) ($_POST['description'] ?? '')) ?: null;
        $typeConfig = self::parseTypeConfig($type);
        if (!$typeConfig['ok']) {
            return ['ok' => false, 'error' => (string) $typeConfig['error']];
        }

        $actionUrl = self::resolveActionUrl($type, $typeConfig['data']);

        return [
            'ok' => true,
            'data' => [
                'offering_type' => $type,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'currency' => $currency,
                'is_free' => $isFree ? 1 : 0,
                'billing_type' => $billingType,
                'action_url' => $actionUrl,
                'type_config' => json_encode($typeConfig['data'], JSON_THROW_ON_ERROR),
            ],
        ];
    }

    /**
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, error: string}
     */
    private static function parseTypeConfig(string $type): array
    {
        $config = OfferingTypeRegistry::defaultTypeConfig($type);

        switch ($type) {
            case 'product':
                $config['variants'] = trim((string) ($_POST['variants'] ?? ''));
                $config['delivery_notes'] = trim((string) ($_POST['delivery_notes'] ?? ''));
                $config['purchase_url'] = self::normalizeUrl((string) ($_POST['purchase_url'] ?? ''));
                break;
            case 'service':
                $config['scope'] = trim((string) ($_POST['scope'] ?? ''));
                $config['turnaround'] = trim((string) ($_POST['turnaround'] ?? ''));
                $config['service_area'] = trim((string) ($_POST['service_area'] ?? ''));
                break;
            case 'membership':
                $config['benefits'] = trim((string) ($_POST['benefits'] ?? ''));
                $registrationUrl = self::normalizeUrl((string) ($_POST['registration_url'] ?? ''));
                if ($registrationUrl === '') {
                    return ['ok' => false, 'error' => 'Membership registration link is required.'];
                }
                $config['registration_url'] = $registrationUrl;
                break;
            case 'consultation':
                $duration = (int) ($_POST['duration_minutes'] ?? 60);
                $config['duration_minutes'] = max(15, min(480, $duration));
                $format = (string) ($_POST['session_format'] ?? 'video');
                $config['session_format'] = in_array($format, ['video', 'phone', 'in_person'], true) ? $format : 'video';
                $bookingMode = (string) ($_POST['booking_mode'] ?? 'leadbird_calendar');
                $config['booking_mode'] = in_array($bookingMode, ['leadbird_calendar', 'external_link'], true)
                    ? $bookingMode
                    : 'leadbird_calendar';
                $config['availability_notes'] = trim((string) ($_POST['availability_notes'] ?? ''));
                $externalUrl = self::normalizeUrl((string) ($_POST['external_booking_url'] ?? ''));
                if ($config['booking_mode'] === 'external_link' && $externalUrl === '') {
                    return ['ok' => false, 'error' => 'Add your booking link or switch to Lead Bird calendar booking.'];
                }
                $config['external_booking_url'] = $externalUrl;
                break;
            case 'course':
                $config['modules'] = trim((string) ($_POST['modules'] ?? ''));
                $access = (string) ($_POST['access_type'] ?? 'lifetime');
                $config['access_type'] = in_array($access, ['lifetime', 'subscription', 'cohort'], true) ? $access : 'lifetime';
                $enrollmentUrl = self::normalizeUrl((string) ($_POST['enrollment_url'] ?? ''));
                if ($enrollmentUrl === '') {
                    return ['ok' => false, 'error' => 'Course enrollment link is required.'];
                }
                $config['enrollment_url'] = $enrollmentUrl;
                break;
            case 'event':
                $config['event_date'] = trim((string) ($_POST['event_date'] ?? ''));
                $config['location'] = trim((string) ($_POST['location'] ?? ''));
                $ticketUrl = self::normalizeUrl((string) ($_POST['ticket_url'] ?? ''));
                if ($ticketUrl === '') {
                    return ['ok' => false, 'error' => 'Event ticket or registration link is required.'];
                }
                $config['ticket_url'] = $ticketUrl;
                break;
        }

        return ['ok' => true, 'data' => $config];
    }

    /**
     * @param array<string, mixed> $typeConfig
     */
    private static function resolveActionUrl(string $type, array $typeConfig): ?string
    {
        $url = match ($type) {
            'membership' => trim((string) ($typeConfig['registration_url'] ?? '')),
            'consultation' => (string) ($typeConfig['booking_mode'] ?? '') === 'external_link'
                ? trim((string) ($typeConfig['external_booking_url'] ?? ''))
                : '',
            'course' => trim((string) ($typeConfig['enrollment_url'] ?? '')),
            'event' => trim((string) ($typeConfig['ticket_url'] ?? '')),
            'product' => trim((string) ($typeConfig['purchase_url'] ?? '')),
            default => '',
        };

        return $url !== '' ? $url : null;
    }

    private static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    /**
     * Build form defaults for UI from an offering row or empty state.
     *
     * @param array<string, mixed>|null $offering
     * @return array<string, mixed>
     */
    public static function formState(?array $offering): array
    {
        $type = (string) ($offering['offering_type'] ?? 'product');
        $config = OfferingTypeRegistry::typeConfigFromOffering($offering);
        $isFree = !empty($offering['is_free']);
        $price = '';
        if (!$isFree && $offering !== null && $offering['price'] !== null) {
            $amount = (float) $offering['price'];
            $currency = money_normalize_currency((string) ($offering['currency'] ?? ''))
                ?? money_default_catalog_currency();
            $decimals = money_currency_decimals($currency);
            $price = $decimals === 0 || fmod($amount, 1.0) === 0.0
                ? (string) (int) round($amount)
                : rtrim(rtrim(number_format($amount, $decimals, '.', ''), '0'), '.');
        }

        return [
            'offering_type' => $type,
            'name' => (string) ($offering['name'] ?? ''),
            'description' => (string) ($offering['description'] ?? ''),
            'is_free' => $isFree,
            'price' => $price,
            'currency' => money_normalize_currency((string) ($offering['currency'] ?? ''))
                ?? money_default_catalog_currency(),
            'billing_type' => (string) ($offering['billing_type'] ?? OfferingTypeRegistry::defaultBilling($type)),
            'type_config' => $config,
            'image_url' => dash_offering_image_url(is_array($offering) ? (string) ($offering['image_url'] ?? '') : null),
        ];
    }
}
