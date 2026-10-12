<?php

declare(strict_types=1);

/**
 * Example values for worded template variables, used as WhatsApp review samples.
 */
final class WhatsAppStarterTemplates
{
    public const SAMPLE_VALUES = [
        'first_name' => 'Ada',
        'order_number' => 'LB1024',
        'delivery_day' => 'tomorrow',
        'amount' => '₦25,000',
        'due_date' => '15 October',
        'discount' => '20%',
        'date' => 'Friday, 17 October',
        'time' => '10:00 AM',
        'delivery_date' => 'Thursday, 15 October',
        'payment_date' => '12 October',
        'appointment_date' => 'Friday, 16 October',
        'appointment_time' => '11:00 AM',
        'reference_number' => 'REG2041',
    ];

    public static function sampleFor(string $variable): string
    {
        return self::SAMPLE_VALUES[$variable] ?? '';
    }
}
