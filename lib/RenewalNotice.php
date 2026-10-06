<?php

declare(strict_types=1);

/**
 * Billing emails sent when an automatic renewal fails or a plan ends.
 * Email clients ignore CSS variables, so brand colors are inlined as hex here.
 */
final class RenewalNotice
{
    private const BLUE = '#0038FF';
    private const LIME = '#CCFF00';
    private const INK = '#111827';
    private const MUTED = '#4B5563';
    private const SURFACE = '#F8F9FA';

    /**
     * @param array<string, mixed> $user
     */
    public static function sendFailed(
        array $user,
        string $planName,
        bool $hasCard,
        DateTimeImmutable $nextAttempt,
        DateTimeImmutable $finalAttempt
    ): void {
        $brand = self::brandName();
        $intro = $hasCard
            ? 'We tried to charge your saved card for your ' . $planName . ' plan, but the payment did not go through.'
            : 'Your ' . $planName . ' plan is due for renewal, but there is no card saved on your account.';
        $next = 'Your Salesgirl keeps replying for now. We will try again on ' . $nextAttempt->format('l, M j')
            . '. Update your card before ' . $finalAttempt->format('M j') . ' to avoid any interruption.';

        self::send(
            $user,
            'Action needed: we could not renew your ' . $brand . ' plan',
            'We could not renew your plan',
            [$intro, $next],
            $hasCard ? 'Update payment method' : 'Add a card',
            app_route('billing')
        );
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function sendExpired(array $user, string $planName): void
    {
        $brand = self::brandName();

        self::send(
            $user,
            'Your ' . $brand . ' plan has ended',
            'Your plan has ended',
            [
                'We could not renew your ' . $planName . ' plan, so it has ended and your Salesgirl has stopped replying to customers.',
                'Your settings, products and conversations are saved. Renew any time to pick up where you left off.',
            ],
            'Renew my plan',
            app_route('pricing')
        );
    }

    /**
     * @param array<string, mixed> $user
     * @param list<string> $paragraphs
     */
    private static function send(
        array $user,
        string $subject,
        string $heading,
        array $paragraphs,
        string $ctaLabel,
        string $ctaUrl
    ): void {
        $to = trim((string) ($user['email'] ?? ''));
        if ($to === '') {
            return;
        }

        $name = trim((string) ($user['name'] ?? ''));
        $greeting = 'Hi ' . ($name !== '' ? (preg_split('/\s+/u', $name)[0] ?? $name) : 'there') . ',';

        $result = (new ResendClient())->sendEmail([
            'from' => (string) (app_config()['resend']['from'] ?? ''),
            'to' => [$to],
            'subject' => $subject,
            'html' => self::html($heading, $greeting, $paragraphs, $ctaLabel, $ctaUrl),
            'text' => implode("\n\n", array_merge([$greeting], $paragraphs, [$ctaLabel . ': ' . $ctaUrl, '— ' . self::brandName()])),
        ]);

        if (!($result['ok'] ?? false)) {
            error_log('[RenewalNotice] ' . (string) ($result['error'] ?? 'send failed') . ' to=' . $to);
        }
    }

    /**
     * @param list<string> $paragraphs
     */
    private static function html(string $heading, string $greeting, array $paragraphs, string $ctaLabel, string $ctaUrl): string
    {
        $brand = e(self::brandName());
        $heading = e($heading);
        $greeting = e($greeting);
        $ctaLabel = e($ctaLabel);
        $ctaUrl = e($ctaUrl);
        $blue = self::BLUE;
        $lime = self::LIME;
        $ink = self::INK;
        $muted = self::MUTED;
        $surface = self::SURFACE;

        $body = '';
        foreach ($paragraphs as $paragraph) {
            $body .= '<p style="margin:0 0 16px 0;font-size:15px;line-height:24px;color:' . $muted . ';">' . e($paragraph) . '</p>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$heading}</title>
</head>
<body style="margin:0;padding:0;background-color:{$surface};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:{$ink};">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:{$surface};padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background-color:#ffffff;border:1px solid rgba(0,56,255,0.12);border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background-color:{$blue};padding:20px 32px;">
                            <p style="margin:0;font-family:'Arial Black',Impact,sans-serif;font-size:18px;letter-spacing:0.02em;color:#ffffff;">LEAD <span style="color:{$lime};">BIRD</span></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 8px 32px;">
                            <h1 style="margin:0 0 20px 0;font-family:'Arial Black',Impact,sans-serif;font-size:22px;line-height:30px;font-weight:900;color:{$ink};">{$heading}</h1>
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:24px;color:{$ink};">{$greeting}</p>
                            {$body}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 32px 32px 32px;">
                            <a href="{$ctaUrl}" style="display:inline-block;padding:13px 26px;border-radius:999px;background-color:{$blue};color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">{$ctaLabel}</a>
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0 0;font-size:12px;line-height:18px;color:{$muted};">&copy; {$brand}</p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private static function brandName(): string
    {
        return (string) (app_config()['brand_name'] ?? 'Lead Bird');
    }
}
