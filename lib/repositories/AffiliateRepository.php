<?php

declare(strict_types=1);

final class AffiliateRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM affiliates WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByCode(string $code): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM affiliates WHERE code = :code LIMIT 1',
            ['code' => strtoupper(trim($code))]
        );
    }

    public function findByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM affiliates WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM affiliates WHERE email = :email LIMIT 1',
            ['email' => normalize_email($email)]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO affiliates (
                id, user_id, code, name, email, phone, status, kyc_status
            ) VALUES (
                :id, :user_id, :code, :name, :email, :phone, :status, :kyc_status
            )',
            [
                'id' => $id,
                'user_id' => $data['user_id'] ?? null,
                'code' => strtoupper((string) $data['code']),
                'name' => $data['name'],
                'email' => normalize_email((string) $data['email']),
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? 'active',
                'kyc_status' => $data['kyc_status'] ?? 'none',
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create affiliate');
    }

    public function linkUser(string $affiliateId, string $userId): void
    {
        $this->execute(
            'UPDATE affiliates SET user_id = :user_id, updated_at = NOW() WHERE id = :id AND user_id IS NULL',
            ['id' => $affiliateId, 'user_id' => $userId]
        );
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function update(string $id, array $fields): void
    {
        $allowed = [
            'name', 'phone', 'status', 'kyc_status', 'flw_psa_id', 'flw_barter_id',
            'flw_account_reference', 'bank_code', 'bank_name', 'account_number', 'account_name',
        ];
        $sets = [];
        $params = ['id' => $id];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $fields)) {
                continue;
            }
            $sets[] = $key . ' = :' . $key;
            $params[$key] = $fields[$key];
        }
        if ($sets === []) {
            return;
        }

        $this->execute(
            'UPDATE affiliates SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id',
            $params
        );
    }

    public function recordAttribution(
        string $affiliateId,
        string $visitorKey,
        ?string $userId = null,
        ?string $landingPath = null
    ): void {
        $this->execute(
            'INSERT INTO affiliate_attributions (id, affiliate_id, visitor_key, user_id, landing_path)
             VALUES (:id, :affiliate_id, :visitor_key, :user_id, :landing_path)',
            [
                'id' => Uuid::v4(),
                'affiliate_id' => $affiliateId,
                'visitor_key' => $visitorKey,
                'user_id' => $userId,
                'landing_path' => $landingPath,
            ]
        );
    }

    public function countClicks(string $affiliateId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM affiliate_attributions WHERE affiliate_id = :id',
            ['id' => $affiliateId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countSignups(string $affiliateId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE referred_by_affiliate_id = :id',
            ['id' => $affiliateId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function listReferredUsers(string $affiliateId, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT
                u.id,
                u.name,
                u.email,
                u.created_at,
                (
                    SELECT ac.commission_amount
                    FROM affiliate_commissions ac
                    WHERE ac.referred_user_id = u.id
                      AND ac.affiliate_id = :affiliate_id_amount
                      AND ac.status != \'clawed_back\'
                    ORDER BY ac.created_at DESC
                    LIMIT 1
                ) AS commission_amount,
                (
                    SELECT ac.currency
                    FROM affiliate_commissions ac
                    WHERE ac.referred_user_id = u.id
                      AND ac.affiliate_id = :affiliate_id_currency
                      AND ac.status != \'clawed_back\'
                    ORDER BY ac.created_at DESC
                    LIMIT 1
                ) AS commission_currency,
                (
                    SELECT ac.status
                    FROM affiliate_commissions ac
                    WHERE ac.referred_user_id = u.id
                      AND ac.affiliate_id = :affiliate_id_status
                      AND ac.status != \'clawed_back\'
                    ORDER BY ac.created_at DESC
                    LIMIT 1
                ) AS commission_status
             FROM users u
             WHERE u.referred_by_affiliate_id = :affiliate_id_main
             ORDER BY u.created_at DESC
             LIMIT ' . max(1, min(50, $limit)),
            [
                'affiliate_id_amount' => $affiliateId,
                'affiliate_id_currency' => $affiliateId,
                'affiliate_id_status' => $affiliateId,
                'affiliate_id_main' => $affiliateId,
            ]
        );
    }
}
