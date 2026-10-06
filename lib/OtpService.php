<?php

declare(strict_types=1);

final class OtpService
{
    public function __construct(
        private readonly ResendClient $resend = new ResendClient(),
    ) {
    }

    public function send(string $email, string $purpose = 'signin'): void
    {
        $email = normalize_email($email);
        $config = app_config()['otp'];

        $this->assertResendCooldown($email, $config['resend_cooldown_seconds']);

        $length = max(4, min(8, $config['length']));
        $code = $this->generateNumericCode($length);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = (new DateTimeImmutable('now'))
            ->modify('+' . $config['ttl_minutes'] . ' minutes')
            ->format('Y-m-d H:i:s');

        RepositoryFactory::emailOtps()->create($email, $hash, $expiresAt);
        $purpose = $purpose === 'signup' ? 'signup' : 'signin';
        $this->resend->sendOtpEmail($email, $code, (int) $config['ttl_minutes'], $purpose);
    }

    public function verify(string $email, string $code): bool
    {
        $email = normalize_email($email);
        $config = app_config()['otp'];
        $row = RepositoryFactory::emailOtps()->findLatestForEmail($email);

        if ($row === null) {
            return false;
        }

        if ($row['used_at'] !== null) {
            return false;
        }

        if ((int) $row['attempts'] >= $config['max_attempts']) {
            return false;
        }

        if (new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable('now')) {
            return false;
        }

        $valid = password_verify($code, $row['otp_hash']);
        $attempts = (int) $row['attempts'] + 1;

        if ($valid) {
            RepositoryFactory::emailOtps()->markUsed($row['id'], $attempts);
            return true;
        }

        RepositoryFactory::emailOtps()->incrementAttempts($row['id'], $attempts);
        return false;
    }

    private function assertResendCooldown(string $email, int $cooldownSeconds): void
    {
        $row = RepositoryFactory::emailOtps()->findLatestForEmail($email);
        if ($row === null) {
            return;
        }

        $created = new DateTimeImmutable($row['created_at']);
        $elapsed = (new DateTimeImmutable('now'))->getTimestamp() - $created->getTimestamp();
        if ($elapsed < $cooldownSeconds) {
            $wait = $cooldownSeconds - $elapsed;
            throw new RuntimeException('Please wait ' . $wait . ' seconds before requesting another code.');
        }
    }

    private function generateNumericCode(int $length): string
    {
        $max = (10 ** $length) - 1;
        $number = random_int(0, $max);
        return str_pad((string) $number, $length, '0', STR_PAD_LEFT);
    }
}
