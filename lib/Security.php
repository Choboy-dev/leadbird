<?php

declare(strict_types=1);

/**
 * Shared security helpers: CLI guard, CSRF, return URL allowlist, IP rate limits.
 */

function require_cli(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo 'Forbidden';
    exit;
}

/**
 * Only allow same-origin app paths (blocks https://leadbird.app.evil.com tricks).
 */
function safe_return_url(?string $candidate, ?string $fallback = null): string
{
    $appUrl = rtrim((string) (app_config()['app_url'] ?? ''), '/');
    $fallback = $fallback ?? ($appUrl . '/dashboard');

    $candidate = trim((string) $candidate);
    if ($candidate === '') {
        return $fallback;
    }

    // Relative app path
    if (str_starts_with($candidate, '/') && !str_starts_with($candidate, '//')) {
        if (str_contains($candidate, '\\') || str_contains($candidate, '@')) {
            return $fallback;
        }

        return $appUrl . $candidate;
    }

    $parts = parse_url($candidate);
    $appParts = parse_url($appUrl);
    if ($parts === false || $appParts === false) {
        return $fallback;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    $appScheme = strtolower((string) ($appParts['scheme'] ?? 'https'));
    $appHost = strtolower((string) ($appParts['host'] ?? ''));

    if ($scheme === '' || $host === '' || $appHost === '') {
        return $fallback;
    }

    if ($scheme !== $appScheme || $host !== $appHost) {
        return $fallback;
    }

    $appPort = isset($appParts['port']) ? (int) $appParts['port'] : ($appScheme === 'https' ? 443 : 80);
    $candPort = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
    if ($appPort !== $candPort) {
        return $fallback;
    }

    $path = (string) ($parts['path'] ?? '/');
    if ($path === '') {
        $path = '/';
    }

    $url = $appUrl . ($path === '/' ? '/' : $path);
    if (!empty($parts['query'])) {
        $url .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $url .= '#' . $parts['fragment'];
    }

    return $url;
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token = null): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $expected = $_SESSION['_csrf_token'] ?? null;
    if (!is_string($expected) || $expected === '') {
        return false;
    }

    if ($token === null) {
        $token = (string) ($_POST['_csrf'] ?? '');
        if ($token === '') {
            $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }
    }

    return is_string($token) && $token !== '' && hash_equals($expected, $token);
}

function require_csrf(): void
{
    if (csrf_verify()) {
        return;
    }

    http_response_code(419);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Session expired. Refresh the page and try again.'], JSON_THROW_ON_ERROR);
        exit;
    }

    flash_set('error', 'Session expired. Please try again.');
    $referer = safe_return_url($_SERVER['HTTP_REFERER'] ?? null, app_route('dashboard'));
    redirect($referer);
}

function client_ip(): string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '0.0.0.0';
}

/**
 * Simple filesystem IP rate limiter. Returns true if allowed.
 */
function rate_limit_allow(string $bucket, int $maxHits, int $windowSeconds): bool
{
    $maxHits = max(1, $maxHits);
    $windowSeconds = max(1, $windowSeconds);
    $ip = client_ip();
    $dir = dirname(__DIR__) . '/storage/rate-limits';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    $file = $dir . '/' . hash('sha256', $bucket . '|' . $ip) . '.json';
    $now = time();
    $hits = [];

    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        return true;
    }

    try {
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded['hits'] ?? null)) {
                $hits = array_map('intval', $decoded['hits']);
            }
        }

        $hits = array_values(array_filter(
            $hits,
            static fn(int $ts): bool => ($now - $ts) < $windowSeconds
        ));

        if (count($hits) >= $maxHits) {
            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode(['hits' => $hits], JSON_THROW_ON_ERROR));
            return false;
        }

        $hits[] = $now;
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, json_encode(['hits' => $hits], JSON_THROW_ON_ERROR));

        return true;
    } catch (Throwable $e) {
        return true;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/**
 * Verify Flutterwave payment amount/currency against expected values.
 *
 * @param array<string, mixed> $paymentData
 */
function assert_flutterwave_payment_matches(
    array $paymentData,
    float $expectedAmount,
    string $expectedCurrency = 'NGN',
    float $tolerance = 1.0
): void {
    $paid = (float) ($paymentData['amount'] ?? 0);
    $currency = strtoupper(trim((string) ($paymentData['currency'] ?? '')));
    $expectedCurrency = strtoupper(trim($expectedCurrency));

    if ($currency === '' || $currency !== $expectedCurrency) {
        throw new RuntimeException('Payment currency mismatch.');
    }

    if ($expectedAmount <= 0 || abs($paid - $expectedAmount) > $tolerance) {
        throw new RuntimeException('Payment amount mismatch.');
    }

    $status = strtolower(trim((string) ($paymentData['status'] ?? '')));
    if ($status !== '' && $status !== 'successful') {
        throw new RuntimeException('Payment was not successful.');
    }
}
