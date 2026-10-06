<?php

declare(strict_types=1);

/**
 * One-time setup: create Flutterwave payment plans and print .env lines.
 *
 * Usage: php scripts/setup-flutterwave-plans.php
 */

$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/config/plans.php';
require_once $root . '/config/money.php';

$secretKey = trim((string) env('FLUTTERWAVE_SECRET_KEY', ''));
if ($secretKey === '') {
    fwrite(STDERR, "FLUTTERWAVE_SECRET_KEY is not set.\n");
    exit(1);
}

$fxRate = fetchFxRate($secretKey);
$ngnPerUsd = $fxRate > 0 ? $fxRate : (float) (money_config()['fx_ngn_per_usd'] ?? 1364.0);

echo "Using FX rate: {$ngnPerUsd} NGN per USD\n\n";

$definitions = [];
foreach (plans_catalog() as $slug => $plan) {
    $name = (string) ($plan['name'] ?? ucfirst($slug));
    $monthlyUsd = (float) ($plan['price'] ?? 0);
    $annualUsd = (float) ($plan['annual_price'] ?? ($monthlyUsd * 10));

    $definitions[] = [
        'env' => (string) ($plan['flutterwave_plan_env'] ?? ''),
        'name' => "Lead Bird {$name} Monthly USD",
        'amount' => (int) round($monthlyUsd),
        'currency' => 'USD',
        'interval' => 'monthly',
    ];
    $definitions[] = [
        'env' => (string) ($plan['flutterwave_plan_env_ngn'] ?? ''),
        'name' => "Lead Bird {$name} Monthly NGN",
        'amount' => (int) round($monthlyUsd * $ngnPerUsd),
        'currency' => 'NGN',
        'interval' => 'monthly',
    ];
    $definitions[] = [
        'env' => (string) ($plan['flutterwave_annual_plan_env'] ?? ''),
        'name' => "Lead Bird {$name} Annual USD",
        'amount' => (int) round($annualUsd),
        'currency' => 'USD',
        'interval' => 'yearly',
    ];
    $definitions[] = [
        'env' => (string) ($plan['flutterwave_annual_plan_env_ngn'] ?? ''),
        'name' => "Lead Bird {$name} Annual NGN",
        'amount' => (int) round($annualUsd * $ngnPerUsd),
        'currency' => 'NGN',
        'interval' => 'yearly',
    ];
}

$envLines = ['FX_NGN_PER_USD=' . round($ngnPerUsd, 2)];

foreach ($definitions as $def) {
    if ($def['env'] === '') {
        continue;
    }

    $existing = findPlanByName($secretKey, $def['name']);
    if ($existing !== null) {
        $planId = (int) ($existing['id'] ?? 0);
        echo "Exists: {$def['name']} → {$planId}\n";
    } else {
        $created = createPlan($secretKey, $def);
        $planId = (int) ($created['id'] ?? 0);
        echo "Created: {$def['name']} ({$def['currency']} {$def['amount']}, {$def['interval']}) → {$planId}\n";
    }

    if ($planId > 0) {
        $envLines[] = $def['env'] . '=' . $planId;
    }
}

echo "\n# Add these to .env:\n";
echo implode("\n", $envLines) . "\n";

function fetchFxRate(string $secretKey): float
{
    $ch = curl_init('https://api.flutterwave.com/v3/rates?from=USD&to=NGN&amount=1');
    if ($ch === false) {
        return 0.0;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    if (!is_string($response) || $response === '') {
        return 0.0;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return 0.0;
    }

    $data = $decoded['data'] ?? null;
    if (is_array($data) && isset($data['rate'])) {
        return (float) $data['rate'];
    }

    return 0.0;
}

/**
 * @return array<string, mixed>|null
 */
function findPlanByName(string $secretKey, string $name): ?array
{
    $page = 1;
    do {
        $ch = curl_init('https://api.flutterwave.com/v3/payment-plans?page=' . $page);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        if (!is_string($response) || $response === '') {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return null;
        }

        foreach ($decoded['data'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (($row['name'] ?? '') === $name) {
                return $row;
            }
        }

        $totalPages = (int) ($decoded['meta']['page_info']['total_pages'] ?? 0);
        $page++;
    } while ($page <= max(1, $totalPages));

    return null;
}

/**
 * @param array{name: string, amount: int, currency: string, interval: string} $def
 * @return array<string, mixed>
 */
function createPlan(string $secretKey, array $def): array
{
    $payload = json_encode([
        'name' => $def['name'],
        'amount' => $def['amount'],
        'currency' => $def['currency'],
        'interval' => $def['interval'],
    ], JSON_THROW_ON_ERROR);

    $ch = curl_init('https://api.flutterwave.com/v3/payment-plans');
    if ($ch === false) {
        throw new RuntimeException('curl init failed');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 45,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response) || $response === '') {
        throw new RuntimeException('Empty Flutterwave response');
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid Flutterwave response');
    }

    if ($status < 200 || $status >= 300 || ($decoded['status'] ?? '') !== 'success') {
        $message = is_string($decoded['message'] ?? null) ? $decoded['message'] : 'Unknown error';
        throw new RuntimeException("Flutterwave error ({$status}): {$message}");
    }

    $data = $decoded['data'] ?? null;

    return is_array($data) ? $data : [];
}
