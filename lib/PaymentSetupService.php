<?php

declare(strict_types=1);

/**
 * Onboarding and settings helpers for customer payment setup.
 */
final class PaymentSetupService
{
    public const METHOD_BANK = 'bank_transfer';

    /**
     * @return array{bank_name: string, account_name: string, account_number: string}
     */
    public static function parseBankInstructions(string $instructions): array
    {
        $parsed = [
            'bank_name' => '',
            'account_name' => '',
            'account_number' => '',
        ];

        foreach (preg_split('/\r\n|\r|\n/', $instructions) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^bank\s*:\s*(.+)$/i', $line, $matches) === 1) {
                $parsed['bank_name'] = trim($matches[1]);
            } elseif (preg_match('/^account name\s*:\s*(.+)$/i', $line, $matches) === 1) {
                $parsed['account_name'] = trim($matches[1]);
            } elseif (preg_match('/^account number\s*:\s*(.+)$/i', $line, $matches) === 1) {
                $parsed['account_number'] = preg_replace('/\D+/', '', $matches[1]) ?? '';
            }
        }

        return $parsed;
    }

    public static function formatBankInstructions(string $bankName, string $accountName, string $accountNumber): string
    {
        return implode("\n", [
            'Bank: ' . $bankName,
            'Account name: ' . $accountName,
            'Account number: ' . $accountNumber,
        ]);
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    public static function resolveMethod(?array $settings): string
    {
        $stored = trim((string) ($settings['payment_method'] ?? ''));
        if ($stored === self::METHOD_BANK) {
            return self::METHOD_BANK;
        }

        if (trim((string) ($settings['payment_instructions'] ?? '')) !== '') {
            return self::METHOD_BANK;
        }

        return self::METHOD_BANK;
    }

    public static function isValidMethod(string $method): bool
    {
        return $method === self::METHOD_BANK;
    }
}
