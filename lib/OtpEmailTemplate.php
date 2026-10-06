<?php

declare(strict_types=1);

final class OtpEmailTemplate
{
    public static function subject(string $purpose): string
    {
        $brand = self::brandName();

        return 'Your ' . $brand . ' verification code';
    }

    public static function html(string $code, int $ttlMinutes, string $purpose): string
    {
        $brand = self::brandName();
        $displayCode = e(self::formatCode($code));
        $actionLine = $purpose === 'signup'
            ? 'Enter this verification code in the window where you started creating your account.'
            : 'Enter this verification code in the window where you started signing in.';
        $securityLine = $purpose === 'signup'
            ? 'If you didn\'t try to create an account, you can safely ignore this email.'
            : 'If you didn\'t try to sign in, you can safely ignore this email.';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$brand} verification code</title>
</head>
<body style="margin:0;padding:0;background-color:#f6f9fc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#525f7f;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f9fc;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background-color:#ffffff;border:1px solid #e6ebf1;border-radius:8px;">
                    <tr>
                        <td style="padding:32px 40px 8px 40px;">
                            <p style="margin:0;font-size:16px;font-weight:600;color:#32325d;letter-spacing:-0.2px;">{$brand}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 40px 0 40px;">
                            <h1 style="margin:0;font-size:20px;line-height:28px;font-weight:600;color:#32325d;letter-spacing:-0.3px;">Your verification code</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 40px 0 40px;">
                            <p style="margin:0;font-size:15px;line-height:24px;color:#525f7f;">{$actionLine}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 40px 0 40px;" align="center">
                            <p style="margin:0;font-size:32px;line-height:40px;font-weight:600;letter-spacing:0.28em;color:#32325d;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;">{$displayCode}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 40px 0 40px;">
                            <p style="margin:0;font-size:14px;line-height:22px;color:#8898aa;">This code will expire in {$ttlMinutes} minutes.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 40px 32px 40px;">
                            <p style="margin:0;font-size:14px;line-height:22px;color:#8898aa;">{$securityLine}</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0 0;font-size:12px;line-height:18px;color:#8898aa;">&copy; {$brand}</p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    public static function text(string $code, int $ttlMinutes, string $purpose): string
    {
        $brand = self::brandName();
        $displayCode = self::formatCode($code);
        $actionLine = $purpose === 'signup'
            ? 'Enter this verification code in the window where you started creating your account.'
            : 'Enter this verification code in the window where you started signing in.';
        $securityLine = $purpose === 'signup'
            ? 'If you didn\'t try to create an account, you can safely ignore this email.'
            : 'If you didn\'t try to sign in, you can safely ignore this email.';

        return implode("\n\n", [
            $brand,
            '',
            'Your verification code',
            '',
            $actionLine,
            '',
            $displayCode,
            '',
            'This code will expire in ' . $ttlMinutes . ' minutes.',
            '',
            $securityLine,
        ]);
    }

    public static function formatCode(string $code): string
    {
        $digits = preg_replace('/\D+/', '', $code) ?? $code;
        if (strlen($digits) === 6) {
            return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3);
        }

        return trim(chunk_split($digits, 3, ' '));
    }

    private static function brandName(): string
    {
        return app_config()['brand_name'] ?? 'Lead Bird';
    }
}
