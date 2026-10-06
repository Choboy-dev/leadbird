<?php

declare(strict_types=1);

/**
 * USD/NGN rates from Flutterwave — always prefers a live quote.
 *
 * Cache is only used when the live API is temporarily unavailable (last known good rate).
 * FX_NGN_PER_USD in .env is an emergency fallback only.
 */
final class FxRateService
{
    private const CACHE_FILE = 'fx_usd_ngn.json';
    /** How long a cached rate is considered "fresh" for monitoring / cron warmup. */
    private const CACHE_TTL_SECONDS = 900;

    public function ngnPerUsd(): float
    {
        return $this->resolveRate()['rate'];
    }

    public function usdToNgn(float $usd): int
    {
        return (int) round(max(0.0, $usd) * $this->ngnPerUsd());
    }

    public function ngnToUsd(float $ngn): float
    {
        $rate = $this->ngnPerUsd();

        return $rate > 0 ? round($ngn / $rate, 2) : 0.0;
    }

    /**
     * Force a fresh quote (used at payout time).
     *
     * @return array{rate: float, source: string, fetched_at: int|null}
     */
    public function quoteForPayout(): array
    {
        $quote = $this->resolveRate();

        return [
            'rate' => $quote['rate'],
            'source' => $quote['source'],
            'fetched_at' => $quote['fetched_at'],
        ];
    }

    /**
     * Warm the cache from Flutterwave (for cron). Returns null when live fetch fails.
     */
    public function refreshLiveRate(): ?float
    {
        $live = $this->fetchLiveNgnPerUsd();
        if ($live === null || $live <= 0) {
            return null;
        }

        $this->writeCache($live);

        return $live;
    }

    /**
     * @return array{rate: float, source: 'live'|'cache'|'config', fetched_at: int|null}
     */
    private function resolveRate(): array
    {
        $live = $this->fetchLiveNgnPerUsd();
        if ($live !== null && $live > 0) {
            $this->writeCache($live);

            return ['rate' => $live, 'source' => 'live', 'fetched_at' => time()];
        }

        $cached = $this->readCache(allowStale: true);
        if ($cached !== null) {
            error_log('[FxRate] live quote unavailable; using last cached rate from '
                . date('Y-m-d H:i:s', $cached['fetched_at']));

            return [
                'rate' => $cached['rate'],
                'source' => 'cache',
                'fetched_at' => $cached['fetched_at'],
            ];
        }

        $fallback = max(1.0, (float) (money_config()['fx_ngn_per_usd'] ?? 1364.0));
        error_log('[FxRate] live quote unavailable and no cache; using emergency config fallback');

        return ['rate' => $fallback, 'source' => 'config', 'fetched_at' => null];
    }

    private function fetchLiveNgnPerUsd(): ?float
    {
        try {
            return (new FlutterwaveService())->fetchUsdToNgnRate();
        } catch (Throwable $e) {
            error_log('[FxRate] live quote failed: ' . $e->getMessage());
        }

        return null;
    }

    private function cachePath(): string
    {
        return dirname(__DIR__) . '/storage/cache/' . self::CACHE_FILE;
    }

    /**
     * @return array{rate: float, fetched_at: int}|null
     */
    private function readCache(bool $allowStale = false): ?array
    {
        $path = $this->cachePath();
        if (!is_readable($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $rate = (float) ($decoded['rate'] ?? 0);
        $fetchedAt = (int) ($decoded['fetched_at'] ?? 0);
        if ($rate <= 0 || $fetchedAt <= 0) {
            return null;
        }

        if (!$allowStale && (time() - $fetchedAt) > self::CACHE_TTL_SECONDS) {
            return null;
        }

        return ['rate' => $rate, 'fetched_at' => $fetchedAt];
    }

    private function writeCache(float $rate): void
    {
        $dir = dirname($this->cachePath());
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        $payload = json_encode([
            'rate' => $rate,
            'fetched_at' => time(),
        ], JSON_THROW_ON_ERROR);

        file_put_contents($this->cachePath(), $payload, LOCK_EX);
    }
}
