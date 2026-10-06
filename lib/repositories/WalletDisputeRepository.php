<?php

declare(strict_types=1);

final class WalletDisputeRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM wallet_disputes WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listFiltered(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM wallet_disputes WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT * FROM wallet_disputes WHERE ' . $whereSql . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    public function updateStatus(string $id, string $status, ?string $adminNotes = null): void
    {
        $this->execute(
            'UPDATE wallet_disputes SET status = :status, admin_notes = COALESCE(:admin_notes, admin_notes), updated_at = NOW() WHERE id = :id',
            ['id' => $id, 'status' => $status, 'admin_notes' => $adminNotes]
        );
    }
}
