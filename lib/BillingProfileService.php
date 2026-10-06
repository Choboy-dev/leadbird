<?php

declare(strict_types=1);

final class BillingProfileService
{
    public function findForUser(string $userId): ?array
    {
        return RepositoryFactory::billingProfiles()->findByUserId($userId);
    }

    /**
     * @param array<string, mixed>|null $business
     * @param array<string, mixed>|null $user
     * @return array{
     *     address_line1: string,
     *     city: string,
     *     state: string,
     *     country: string,
     *     postal_code: string,
     *     phone: string
     * }
     */
    public function defaultsForUser(string $userId, ?array $business = null, ?array $user = null): array
    {
        $profile = $this->findForUser($userId);
        if ($profile !== null) {
            return $this->normalizeStored($profile);
        }

        return [
            'address_line1' => '',
            'city' => '',
            'state' => '',
            'country' => 'NG',
            'postal_code' => '',
            'phone' => is_array($business) ? trim((string) ($business['owner_phone'] ?? '')) : '',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: true, billing: array<string, string>}|array{ok: false, message: string}
     */
    public function validateInput(array $input): array
    {
        $address = trim((string) ($input['address_line1'] ?? ''));
        $city = trim((string) ($input['city'] ?? ''));
        $state = trim((string) ($input['state'] ?? ''));
        $country = strtoupper(trim((string) ($input['country'] ?? 'NG')));
        $postal = trim((string) ($input['postal_code'] ?? ''));
        $phone = preg_replace('/\s+/', '', trim((string) ($input['phone'] ?? ''))) ?? '';

        if ($address === '' || $city === '' || $state === '' || $postal === '' || $phone === '') {
            return ['ok' => false, 'message' => 'Please complete all billing details.'];
        }

        if (strlen($country) !== 2) {
            return ['ok' => false, 'message' => 'Please choose a valid country.'];
        }

        if (strlen(preg_replace('/\D+/', '', $phone) ?? '') < 10) {
            return ['ok' => false, 'message' => 'Enter a valid phone number for billing.'];
        }

        return [
            'ok' => true,
            'billing' => [
                'address_line1' => $address,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'postal_code' => $postal,
                'phone' => $phone,
            ],
        ];
    }

    /**
     * @param array<string, string> $billing
     */
    public function saveForUser(string $userId, array $billing): array
    {
        return RepositoryFactory::billingProfiles()->upsert($userId, $billing);
    }

    /**
     * @param array<string, mixed>|null $profile
     * @return array<string, mixed>
     */
    public function flutterwaveTokenizedFields(?array $profile): array
    {
        if ($profile === null) {
            return [];
        }

        $normalized = $this->normalizeStored($profile);

        return [
            'country' => $normalized['country'],
            'phone_number' => $normalized['phone'],
            'billing_address' => [
                'city' => $normalized['city'],
                'country' => $normalized['country'],
                'line1' => $normalized['address_line1'],
                'postal_code' => $normalized['postal_code'],
                'state' => $normalized['state'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $profile
     * @return array<string, string>
     */
    private function normalizeStored(array $profile): array
    {
        return [
            'address_line1' => (string) ($profile['address_line1'] ?? ''),
            'city' => (string) ($profile['city'] ?? ''),
            'state' => (string) ($profile['state'] ?? ''),
            'country' => strtoupper((string) ($profile['country'] ?? 'NG')),
            'postal_code' => (string) ($profile['postal_code'] ?? ''),
            'phone' => (string) ($profile['phone'] ?? ''),
        ];
    }
}
