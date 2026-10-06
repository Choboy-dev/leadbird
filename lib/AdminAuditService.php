<?php

declare(strict_types=1);

final class AdminAuditService
{
    /**
     * @param array<string, mixed>|null $details
     */
    public function log(
        string $adminUserId,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $details = null
    ): void {
        RepositoryFactory::adminAuditLog()->create(
            $adminUserId,
            $action,
            $targetType,
            $targetId,
            $details,
            client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
    }
}
