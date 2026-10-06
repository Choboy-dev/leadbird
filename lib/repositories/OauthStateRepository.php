<?php

declare(strict_types=1);

final class OauthStateRepository extends BaseRepository
{
    public function create(string $state, string $codeVerifier, string $expiresAt, ?array $context = null): void
    {
        $this->execute(
            'INSERT INTO oauth_states (id, state, code_verifier, context, expires_at)
             VALUES (:id, :state, :code_verifier, :context, :expires_at)',
            [
                'id' => Uuid::v4(),
                'state' => $state,
                'code_verifier' => $codeVerifier,
                'context' => $context !== null ? json_encode($context, JSON_THROW_ON_ERROR) : null,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function consume(string $state): ?string
    {
        $row = $this->consumeRow($state);
        if ($row === null) {
            return null;
        }

        return (string) ($row['code_verifier'] ?? '');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function consumeRow(string $state): ?array
    {
        $row = $this->fetchOne(
            'SELECT id, code_verifier, context, expires_at FROM oauth_states WHERE state = :state LIMIT 1',
            ['state' => $state]
        );

        if ($row === null) {
            return null;
        }

        $this->execute('DELETE FROM oauth_states WHERE id = :id', ['id' => $row['id']]);

        if (new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable('now')) {
            return null;
        }

        if (!empty($row['context']) && is_string($row['context'])) {
            $decoded = json_decode($row['context'], true);
            if (is_array($decoded)) {
                $row['context'] = $decoded;
            }
        }

        return $row;
    }

    public function purgeExpired(): void
    {
        $this->execute('DELETE FROM oauth_states WHERE expires_at < NOW()');
    }

    /**
     * Read OAuth state context without consuming it (for error redirects).
     *
     * @return array<string, mixed>|null
     */
    public function findRowByState(string $state): ?array
    {
        $row = $this->fetchOne(
            'SELECT id, code_verifier, context, expires_at FROM oauth_states WHERE state = :state LIMIT 1',
            ['state' => $state]
        );

        if ($row === null) {
            return null;
        }

        if (new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable('now')) {
            return null;
        }

        if (!empty($row['context']) && is_string($row['context'])) {
            $decoded = json_decode($row['context'], true);
            if (is_array($decoded)) {
                $row['context'] = $decoded;
            }
        }

        return $row;
    }
}
