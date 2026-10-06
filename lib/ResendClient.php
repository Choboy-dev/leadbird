<?php

declare(strict_types=1);

final class ResendClient
{
    /**
     * @return array{ok: bool, data: mixed, error: ?string}
     */
    public function requestJson(string $method, string $path, ?array $payload = null): array
    {
        $apiKey = (string) (app_config()['resend']['api_key'] ?? '');
        if ($apiKey === '') {
            return ['ok' => false, 'data' => null, 'error' => 'RESEND_API_KEY is not configured'];
        }

        $url = str_starts_with($path, 'https://') ? $path : 'https://api.resend.com/' . ltrim($path, '/');
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'data' => null, 'error' => 'Failed to initialize cURL'];
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'User-Agent: ' . $this->userAgent(),
            ],
            CURLOPT_TIMEOUT => 30,
        ];
        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            return ['ok' => false, 'data' => null, 'error' => 'Resend request failed: ' . $error];
        }
        $decoded = json_decode($response, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) ? (string) ($decoded['message'] ?? $decoded['error'] ?? $response) : $response;
            return ['ok' => false, 'data' => $decoded, 'error' => 'Resend API error (' . $status . '): ' . $message];
        }
        return ['ok' => true, 'data' => is_array($decoded) ? $decoded : null, 'error' => null];
    }

    public function sendEmail(array $payload): array
    {
        return $this->requestJson('POST', 'emails', $payload);
    }

    /** Cancel a scheduled send before Resend delivers it. */
    public function cancelEmail(string $emailId): array
    {
        return $this->requestJson('POST', 'emails/' . rawurlencode($emailId) . '/cancel');
    }

    public function getReceivedEmail(string $emailId): array
    {
        return $this->requestJson('GET', 'emails/receiving/' . rawurlencode($emailId));
    }

    public function getReceivedAttachment(string $emailId, string $attachmentId): array
    {
        return $this->requestJson(
            'GET',
            'emails/receiving/' . rawurlencode($emailId) . '/attachments/' . rawurlencode($attachmentId)
        );
    }

    /**
     * Verify Resend's Svix signature (svix-id.timestamp.raw-body).
     *
     * @param array<string, string> $headers
     */
    public function verifyWebhook(string $rawBody, array $headers): bool
    {
        $secret = trim((string) (app_config()['mail']['webhook_secret'] ?? ''));
        if ($secret === '') {
            return (string) (app_config()['app_env'] ?? 'production') !== 'production';
        }
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = trim((string) $value);
        }
        $id = $normalized['svix-id'] ?? '';
        $timestamp = $normalized['svix-timestamp'] ?? '';
        $signatures = $normalized['svix-signature'] ?? '';
        if ($id === '' || !ctype_digit($timestamp) || $signatures === '' || abs(time() - (int) $timestamp) > 300) {
            return false;
        }
        $encodedSecret = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = base64_decode($encodedSecret, true);
        if ($key === false) {
            return false;
        }
        $expected = base64_encode(hash_hmac('sha256', $id . '.' . $timestamp . '.' . $rawBody, $key, true));
        foreach (preg_split('/\s+/', $signatures) ?: [] as $signature) {
            $candidate = str_contains($signature, ',') ? explode(',', $signature, 2)[1] : $signature;
            if ($candidate !== '' && hash_equals($expected, $candidate)) {
                return true;
            }
        }
        return false;
    }

    public function sendOtpEmail(string $to, string $code, int $ttlMinutes, string $purpose = 'signin'): void
    {
        $config = app_config()['resend'];
        $apiKey = $config['api_key'] ?? '';
        if ($apiKey === '') {
            throw new RuntimeException('RESEND_API_KEY is not configured');
        }

        $purpose = $purpose === 'signup' ? 'signup' : 'signin';
        $payload = [
            'from' => $config['from'],
            'to' => [$to],
            'subject' => OtpEmailTemplate::subject($purpose),
            'html' => OtpEmailTemplate::html($code, $ttlMinutes, $purpose),
            'text' => OtpEmailTemplate::text($code, $ttlMinutes, $purpose),
        ];

        $this->request('POST', 'https://api.resend.com/emails', $payload, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'User-Agent: ' . $this->userAgent(),
        ]);
    }

    /**
     * @param array{first_name: string, last_name: string, email: string, subject: string, message: string} $data
     */
    public function sendContactMessage(array $data): void
    {
        $config = app_config();
        $resend = $config['resend'];
        $inbox = (string) ($config['contact']['inbox_email'] ?? $config['contact']['email'] ?? '');
        $apiKey = $resend['api_key'] ?? '';

        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
        $subject = '[Contact] ' . $data['subject'];
        $text = implode("\n", [
            'New contact form submission',
            '',
            'Name: ' . $fullName,
            'Email: ' . $data['email'],
            'Subject: ' . $data['subject'],
            '',
            $data['message'],
        ]);
        $html = '<p><strong>Name:</strong> ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Email:</strong> ' . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Subject:</strong> ' . htmlspecialchars($data['subject'], ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Message:</strong></p>'
            . '<p>' . nl2br(htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8')) . '</p>';

        if ($apiKey === '') {
            throw new RuntimeException('RESEND_API_KEY is not configured');
        }

        if ($inbox === '') {
            throw new RuntimeException('CONTACT_INBOX_EMAIL is not configured');
        }

        $payload = [
            'from' => $resend['from'],
            'to' => [$inbox],
            'reply_to' => $data['email'],
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ];

        $this->request('POST', 'https://api.resend.com/emails', $payload, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'User-Agent: ' . $this->userAgent(),
        ]);
    }

    /**
     * @param array<string, mixed> $body
     * @param list<string> $headers
     */
    private function request(string $method, string $url, array $body, array $headers): void
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Resend request failed: ' . $error);
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Resend API error (' . $status . '): ' . $response);
        }
    }

    private function userAgent(): string
    {
        $host = parse_url((string) (app_config()['app_url'] ?? ''), PHP_URL_HOST);
        $suffix = is_string($host) && $host !== '' ? ' (' . $host . ')' : '';

        return 'LeadBird/1.0 PHP-cURL' . $suffix;
    }
}
