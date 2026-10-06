<?php

declare(strict_types=1);

final class BusinessSettingsRepository extends BaseRepository
{
    private const DEFAULT_TONE = 'Friendly and helpful';
    private const DEFAULT_GREETING = 'Hi. Thanks for reaching out. How can I help you today?';

    public function findByBusinessId(string $businessId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM business_settings WHERE business_id = :business_id LIMIT 1',
            ['business_id' => $businessId]
        );
    }

    public function create(string $businessId, array $data = []): array
    {
        $payload = $this->normalizePayload($data);
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO business_settings (
                id, business_id, ai_name, business_description, ai_tone, ai_greeting, faq, delivery_policy, return_policy,
                payment_instructions, payment_method, catalog_currency, escalation_phone, escalation_email, handoff_to_whatsapp,
                notify_owner_on_hot_lead, notify_owner_on_payment, notify_owner_on_complaint,
                negotiation_enabled, max_discount_percent, negotiation_rules, upsell_enabled, upsell_notes,
                followup_enabled, followup_delay_hours, followup_max_attempts, followup_interval_hours, followup_notes,
                receipt_enabled, receipt_message
            ) VALUES (
                :id, :business_id, :ai_name, :business_description, :ai_tone, :ai_greeting, :faq, :delivery_policy, :return_policy,
                :payment_instructions, :payment_method, :catalog_currency, :escalation_phone, :escalation_email, :handoff_to_whatsapp,
                :notify_owner_on_hot_lead, :notify_owner_on_payment, :notify_owner_on_complaint,
                :negotiation_enabled, :max_discount_percent, :negotiation_rules, :upsell_enabled, :upsell_notes,
                :followup_enabled, :followup_delay_hours, :followup_max_attempts, :followup_interval_hours, :followup_notes,
                :receipt_enabled, :receipt_message
            )',
            array_merge(['id' => $id, 'business_id' => $businessId], $payload)
        );

        return $this->fetchOne('SELECT * FROM business_settings WHERE id = :id', ['id' => $id])
            ?? throw new RuntimeException('Failed to create business settings');
    }

    public function update(string $businessId, array $data): void
    {
        $existing = $this->findByBusinessId($businessId);
        if ($existing === null) {
            return;
        }

        $payload = $this->normalizePayload(array_merge($this->payloadFromRow($existing), $data));

        $this->execute(
            'UPDATE business_settings SET
                ai_name = :ai_name, business_description = :business_description, ai_tone = :ai_tone, ai_greeting = :ai_greeting,
                faq = :faq, delivery_policy = :delivery_policy, return_policy = :return_policy,
                payment_instructions = :payment_instructions, payment_method = :payment_method,
                catalog_currency = :catalog_currency,
                escalation_phone = :escalation_phone,
                escalation_email = :escalation_email, handoff_to_whatsapp = :handoff_to_whatsapp,
                notify_owner_on_hot_lead = :notify_owner_on_hot_lead,
                notify_owner_on_payment = :notify_owner_on_payment,
                notify_owner_on_complaint = :notify_owner_on_complaint,
                negotiation_enabled = :negotiation_enabled,
                max_discount_percent = :max_discount_percent,
                negotiation_rules = :negotiation_rules,
                upsell_enabled = :upsell_enabled,
                upsell_notes = :upsell_notes,
                followup_enabled = :followup_enabled,
                followup_delay_hours = :followup_delay_hours,
                followup_max_attempts = :followup_max_attempts,
                followup_interval_hours = :followup_interval_hours,
                followup_notes = :followup_notes,
                receipt_enabled = :receipt_enabled,
                receipt_message = :receipt_message,
                updated_at = NOW()
             WHERE business_id = :business_id',
            array_merge(['business_id' => $businessId], $payload)
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function payloadFromRow(array $row): array
    {
        return [
            'ai_name' => $row['ai_name'] ?? 'Sales Girl',
            'business_description' => $row['business_description'] ?? null,
            'ai_tone' => $row['ai_tone'] ?? self::DEFAULT_TONE,
            'ai_greeting' => $row['ai_greeting'] ?? self::DEFAULT_GREETING,
            'faq' => $row['faq'] ?? null,
            'delivery_policy' => $row['delivery_policy'] ?? null,
            'return_policy' => $row['return_policy'] ?? null,
            'payment_instructions' => $row['payment_instructions'] ?? null,
            'payment_method' => $row['payment_method'] ?? null,
            'catalog_currency' => $row['catalog_currency'] ?? money_default_catalog_currency(),
            'escalation_phone' => $row['escalation_phone'] ?? null,
            'escalation_email' => $row['escalation_email'] ?? null,
            'handoff_to_whatsapp' => $row['handoff_to_whatsapp'] ?? true,
            'notify_owner_on_hot_lead' => $row['notify_owner_on_hot_lead'] ?? true,
            'notify_owner_on_payment' => $row['notify_owner_on_payment'] ?? true,
            'notify_owner_on_complaint' => $row['notify_owner_on_complaint'] ?? true,
            'negotiation_enabled' => $row['negotiation_enabled'] ?? true,
            'max_discount_percent' => $row['max_discount_percent'] ?? 10,
            'negotiation_rules' => $row['negotiation_rules'] ?? null,
            'upsell_enabled' => $row['upsell_enabled'] ?? true,
            'upsell_notes' => $row['upsell_notes'] ?? null,
            'followup_enabled' => $row['followup_enabled'] ?? true,
            'followup_delay_hours' => $row['followup_delay_hours'] ?? 6,
            'followup_max_attempts' => $row['followup_max_attempts'] ?? 2,
            'followup_interval_hours' => $row['followup_interval_hours'] ?? 12,
            'followup_notes' => $row['followup_notes'] ?? null,
            'receipt_enabled' => $row['receipt_enabled'] ?? true,
            'receipt_message' => $row['receipt_message'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        $aiName = trim((string) ($data['ai_name'] ?? 'Sales Girl'));
        $rules = trim((string) ($data['negotiation_rules'] ?? ''));
        $upsellNotes = trim((string) ($data['upsell_notes'] ?? ''));
        $followupNotes = trim((string) ($data['followup_notes'] ?? ''));
        $receiptMessage = trim((string) ($data['receipt_message'] ?? ''));

        return [
            'ai_name' => $aiName !== '' ? $aiName : 'Sales Girl',
            'business_description' => ($data['business_description'] ?? null) !== null && $data['business_description'] !== ''
                ? (string) $data['business_description']
                : null,
            'ai_tone' => trim((string) ($data['ai_tone'] ?? self::DEFAULT_TONE)) ?: self::DEFAULT_TONE,
            'ai_greeting' => trim((string) ($data['ai_greeting'] ?? self::DEFAULT_GREETING)) ?: self::DEFAULT_GREETING,
            'faq' => $data['faq'] ?? null,
            'delivery_policy' => $data['delivery_policy'] ?? null,
            'return_policy' => $data['return_policy'] ?? null,
            'payment_instructions' => $data['payment_instructions'] ?? null,
            'payment_method' => self::normalizePaymentMethod($data['payment_method'] ?? null),
            'catalog_currency' => money_normalize_currency(
                isset($data['catalog_currency']) ? (string) $data['catalog_currency'] : null
            ) ?? money_default_catalog_currency(),
            'escalation_phone' => $data['escalation_phone'] ?? null,
            'escalation_email' => $data['escalation_email'] ?? null,
            'handoff_to_whatsapp' => (int) (bool) ($data['handoff_to_whatsapp'] ?? true),
            'notify_owner_on_hot_lead' => (int) (bool) ($data['notify_owner_on_hot_lead'] ?? true),
            'notify_owner_on_payment' => (int) (bool) ($data['notify_owner_on_payment'] ?? true),
            'notify_owner_on_complaint' => (int) (bool) ($data['notify_owner_on_complaint'] ?? true),
            'negotiation_enabled' => (int) (bool) ($data['negotiation_enabled'] ?? true),
            'max_discount_percent' => max(0, min(90, round((float) ($data['max_discount_percent'] ?? 10), 2))),
            'negotiation_rules' => $rules !== '' ? $rules : null,
            'upsell_enabled' => (int) (bool) ($data['upsell_enabled'] ?? true),
            'upsell_notes' => $upsellNotes !== '' ? $upsellNotes : null,
            'followup_enabled' => (int) (bool) ($data['followup_enabled'] ?? true),
            'followup_delay_hours' => max(1, min(72, (int) ($data['followup_delay_hours'] ?? 6))),
            'followup_max_attempts' => max(1, min(5, (int) ($data['followup_max_attempts'] ?? 2))),
            'followup_interval_hours' => max(1, min(72, (int) ($data['followup_interval_hours'] ?? 12))),
            'followup_notes' => $followupNotes !== '' ? $followupNotes : null,
            'receipt_enabled' => (int) (bool) ($data['receipt_enabled'] ?? true),
            'receipt_message' => $receiptMessage !== '' ? $receiptMessage : null,
        ];
    }

    private static function normalizePaymentMethod(mixed $method): ?string
    {
        $method = trim((string) ($method ?? ''));
        if ($method === '') {
            return null;
        }

        return PaymentSetupService::isValidMethod($method) ? $method : null;
    }
}
