<?php

declare(strict_types=1);

final class BroadcastRepository extends BaseRepository
{
    // —— Templates ——

    /** @param array<string, mixed> $data */
    public function createTemplate(array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO whatsapp_templates (id, business_id, connected_account_id, waba_id, meta_template_id, name, title, language, category, body, footer, uses_name, status, rejected_reason)
             VALUES (:id, :business_id, :connected_account_id, :waba_id, :meta_template_id, :name, :title, :language, :category, :body, :footer, :uses_name, :status, :rejected_reason)',
            [
                'id' => $id,
                'business_id' => $data['business_id'],
                'connected_account_id' => $data['connected_account_id'],
                'waba_id' => $data['waba_id'],
                'meta_template_id' => $data['meta_template_id'] ?? null,
                'name' => $data['name'],
                'title' => $data['title'],
                'language' => $data['language'] ?? 'en',
                'category' => $data['category'],
                'body' => $data['body'],
                'footer' => $data['footer'] ?? null,
                'uses_name' => !empty($data['uses_name']) ? 1 : 0,
                'status' => $data['status'] ?? 'PENDING',
                'rejected_reason' => $data['rejected_reason'] ?? null,
            ]
        );

        return $this->findTemplate($id, (string) $data['business_id']) ?? throw new RuntimeException('Could not save template.');
    }

    public function findTemplate(string $id, string $businessId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM whatsapp_templates WHERE id = :id AND business_id = :business_id LIMIT 1',
            ['id' => $id, 'business_id' => $businessId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listTemplates(string $businessId): array
    {
        return $this->fetchAll(
            'SELECT * FROM whatsapp_templates WHERE business_id = :business_id AND deleted_at IS NULL ORDER BY updated_at DESC LIMIT 2000',
            ['business_id' => $businessId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listListPickers(string $businessId): array
    {
        return $this->fetchAll(
            "SELECT id, name, body, draft_spec FROM whatsapp_templates
             WHERE business_id = :business_id AND content_type = 'list_picker' AND deleted_at IS NULL
             ORDER BY updated_at DESC LIMIT 100",
            ['business_id' => $businessId]
        );
    }

    /**
     * Insert a template found on Meta, or refresh the Meta-owned fields of one we already have.
     *
     * @param array<string, mixed> $data
     */
    public function upsertSyncedTemplate(array $data): void
    {
        $this->execute(
            'INSERT INTO whatsapp_templates (id, business_id, connected_account_id, waba_id, meta_template_id, name, title, language, category, content_type, body, footer, components, uses_name, status, quality, source, rejected_reason)
             VALUES (:id, :business_id, :connected_account_id, :waba_id, :meta_template_id, :name, :title, :language, :category, :content_type, :body, :footer, :components, 0, :status, :quality, \'meta\', :rejected_reason)
             ON DUPLICATE KEY UPDATE
                meta_template_id = VALUES(meta_template_id),
                category = VALUES(category),
                content_type = IF(source = \'leadbird\' AND draft_spec IS NOT NULL, content_type, VALUES(content_type)),
                components = VALUES(components),
                status = VALUES(status),
                quality = VALUES(quality),
                rejected_reason = VALUES(rejected_reason),
                draft_spec = IF(source = \'leadbird\', draft_spec, NULL),
                deleted_at = NULL',
            [
                'id' => Uuid::v4(),
                'business_id' => $data['business_id'],
                'connected_account_id' => $data['connected_account_id'],
                'waba_id' => $data['waba_id'],
                'meta_template_id' => $data['meta_template_id'],
                'name' => $data['name'],
                'title' => mb_substr((string) $data['name'], 0, 120),
                'language' => $data['language'],
                'category' => $data['category'],
                'content_type' => $data['content_type'],
                'body' => $data['body'],
                'footer' => $data['footer'],
                'components' => $data['components'],
                'status' => $data['status'],
                'quality' => $data['quality'],
                'rejected_reason' => $data['rejected_reason'],
            ]
        );
    }

    /**
     * Templates that are no longer on Meta stay in the table so past broadcasts keep their names.
     *
     * @param list<string> $liveKeys name|language pairs Meta still returns
     */
    public function markMissingTemplatesDeleted(string $businessId, string $wabaId, array $liveKeys): int
    {
        $live = array_flip($liveKeys);
        $rows = $this->fetchAll(
            "SELECT id, name, language FROM whatsapp_templates WHERE business_id = :business_id AND waba_id = :waba_id AND deleted_at IS NULL AND status <> 'DRAFT'",
            ['business_id' => $businessId, 'waba_id' => $wabaId]
        );
        $removed = 0;
        foreach ($rows as $row) {
            if (!isset($live[$row['name'] . '|' . $row['language']])) {
                $this->softDeleteTemplate((string) $row['id'], $businessId);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Store a template the seller just submitted, replacing any earlier copy with the same name.
     *
     * @param array<string, mixed> $data
     */
    public function saveSubmittedTemplate(array $data): string
    {
        $this->execute(
            'INSERT INTO whatsapp_templates (id, business_id, connected_account_id, waba_id, meta_template_id, name, title, language, category, content_type, body, footer, components, header_media_url, draft_spec, uses_name, status, source)
             VALUES (:id, :business_id, :connected_account_id, :waba_id, :meta_template_id, :name, :title, :language, :category, :content_type, :body, :footer, :components, :header_media_url, :draft_spec, 0, :status, \'leadbird\')
             ON DUPLICATE KEY UPDATE
                business_id = VALUES(business_id),
                connected_account_id = VALUES(connected_account_id),
                meta_template_id = VALUES(meta_template_id),
                category = VALUES(category),
                content_type = VALUES(content_type),
                body = VALUES(body),
                footer = VALUES(footer),
                components = VALUES(components),
                header_media_url = VALUES(header_media_url),
                uses_name = 0,
                status = VALUES(status),
                quality = NULL,
                rejected_reason = NULL,
                draft_spec = VALUES(draft_spec),
                deleted_at = NULL',
            [
                'id' => Uuid::v4(),
                'business_id' => $data['business_id'],
                'connected_account_id' => $data['connected_account_id'],
                'waba_id' => $data['waba_id'],
                'meta_template_id' => $data['meta_template_id'],
                'name' => $data['name'],
                'title' => mb_substr((string) $data['name'], 0, 120),
                'language' => $data['language'],
                'category' => $data['category'],
                'content_type' => $data['content_type'],
                'body' => $data['body'],
                'footer' => $data['footer'],
                'components' => $data['components'],
                'header_media_url' => $data['header_media_url'] ?? null,
                'draft_spec' => $data['draft_spec'] ?? null,
                'status' => $data['status'],
            ]
        );

        $row = $this->fetchOne(
            'SELECT id FROM whatsapp_templates WHERE waba_id = :waba_id AND name = :name AND language = :language LIMIT 1',
            ['waba_id' => $data['waba_id'], 'name' => $data['name'], 'language' => $data['language']]
        );

        return (string) ($row['id'] ?? '');
    }

    public function findTemplateByKey(string $wabaId, string $name, string $language): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM whatsapp_templates WHERE waba_id = :waba_id AND name = :name AND language = :language LIMIT 1',
            ['waba_id' => $wabaId, 'name' => $name, 'language' => $language]
        );
    }

    /**
     * Create or overwrite a template the seller saved without sending it to WhatsApp.
     *
     * @param array<string, mixed> $data
     */
    public function saveDraftTemplate(array $data): string
    {
        $id = (string) ($data['id'] ?? '');
        $fields = [
            'business_id' => $data['business_id'],
            'connected_account_id' => $data['connected_account_id'],
            'waba_id' => $data['waba_id'],
            'name' => $data['name'],
            'title' => mb_substr((string) $data['name'], 0, 120),
            'language' => $data['language'],
            'category' => $data['category'],
            'content_type' => $data['content_type'],
            'body' => $data['body'],
            'footer' => $data['footer'],
            'header_media_url' => $data['header_media_url'],
            'draft_spec' => $data['draft_spec'],
        ];

        if ($id !== '') {
            $this->execute(
                "UPDATE whatsapp_templates
                 SET business_id = :business_id, connected_account_id = :connected_account_id, waba_id = :waba_id,
                     name = :name, title = :title, language = :language, category = :category, content_type = :content_type,
                     body = :body, footer = :footer, components = NULL, header_media_url = :header_media_url, draft_spec = :draft_spec,
                     meta_template_id = NULL, uses_name = 0, status = 'DRAFT', quality = NULL, source = 'leadbird',
                     rejected_reason = NULL, deleted_at = NULL
                 WHERE id = :id",
                $fields + ['id' => $id]
            );

            return $id;
        }

        $id = Uuid::v4();
        $this->execute(
            "INSERT INTO whatsapp_templates (id, business_id, connected_account_id, waba_id, meta_template_id, name, title, language, category, content_type, body, footer, header_media_url, draft_spec, uses_name, status, source)
             VALUES (:id, :business_id, :connected_account_id, :waba_id, NULL, :name, :title, :language, :category, :content_type, :body, :footer, :header_media_url, :draft_spec, 0, 'DRAFT', 'leadbird')",
            $fields + ['id' => $id]
        );

        return $id;
    }

    public function softDeleteTemplate(string $id, string $businessId): void
    {
        $this->execute(
            "UPDATE whatsapp_templates SET status = 'DELETED', deleted_at = NOW() WHERE id = :id AND business_id = :business_id",
            ['id' => $id, 'business_id' => $businessId]
        );
    }

    public function updateTemplateStatus(string $id, string $status, ?string $reason, ?string $metaTemplateId = null): void
    {
        $this->execute(
            'UPDATE whatsapp_templates
             SET status = :status, rejected_reason = :reason, meta_template_id = COALESCE(:meta_id, meta_template_id)
             WHERE id = :id',
            ['id' => $id, 'status' => $status, 'reason' => $reason, 'meta_id' => $metaTemplateId]
        );
    }

    // —— Contacts ——

    /**
     * Insert new contacts and re-subscribe nobody: people who replied STOP stay unsubscribed.
     *
     * @param list<array{phone: string, name: ?string}> $rows
     */
    public function addContacts(string $businessId, array $rows, string $source): int
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO broadcast_contacts (id, business_id, phone, name, source)
             VALUES (:id, :business_id, :phone, :name, :source)'
        );
        $added = 0;
        foreach ($rows as $row) {
            $stmt->execute([
                'id' => Uuid::v4(),
                'business_id' => $businessId,
                'phone' => $row['phone'],
                'name' => $row['name'],
                'source' => $source,
            ]);
            $added += $stmt->rowCount();
        }

        return $added;
    }

    /** @return list<array<string, mixed>> */
    public function listContacts(string $businessId, int $limit = 300): array
    {
        return $this->fetchAll(
            'SELECT * FROM broadcast_contacts WHERE business_id = :business_id ORDER BY created_at DESC LIMIT '
            . max(1, min(1000, $limit)),
            ['business_id' => $businessId]
        );
    }

    public function countContacts(string $businessId, bool $subscribedOnly = false): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM broadcast_contacts WHERE business_id = :business_id'
            . ($subscribedOnly ? ' AND unsubscribed_at IS NULL' : ''),
            ['business_id' => $businessId]
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array{total: int, nigeria: int}
     */
    public function subscribedContactCounts(string $businessId): array
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total, COALESCE(SUM(phone LIKE '234%'), 0) AS nigeria
             FROM broadcast_contacts
             WHERE business_id = :business_id AND unsubscribed_at IS NULL",
            ['business_id' => $businessId]
        );

        return ['total' => (int) ($row['total'] ?? 0), 'nigeria' => (int) ($row['nigeria'] ?? 0)];
    }

    /** @return list<array<string, mixed>> */
    public function listSubscribedContacts(string $businessId): array
    {
        return $this->fetchAll(
            'SELECT id, phone, name FROM broadcast_contacts
             WHERE business_id = :business_id AND unsubscribed_at IS NULL
             ORDER BY created_at ASC',
            ['business_id' => $businessId]
        );
    }

    public function deleteContact(string $id, string $businessId): void
    {
        $this->execute(
            'DELETE FROM broadcast_contacts WHERE id = :id AND business_id = :business_id',
            ['id' => $id, 'business_id' => $businessId]
        );
    }

    public function unsubscribe(string $businessId, string $phone): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE broadcast_contacts SET unsubscribed_at = NOW()
             WHERE business_id = :business_id AND phone = :phone AND unsubscribed_at IS NULL'
        );
        $stmt->execute(['business_id' => $businessId, 'phone' => $phone]);

        return $stmt->rowCount() > 0;
    }

    /** @return list<array{phone: string, name: ?string}> */
    public function whatsappInboxCustomers(string $businessId): array
    {
        $rows = $this->fetchAll(
            'SELECT platform_user_id, phone, customer_name FROM customers
             WHERE business_id = :business_id AND platform = \'whatsapp\'',
            ['business_id' => $businessId]
        );

        $out = [];
        foreach ($rows as $row) {
            $phone = trim((string) ($row['platform_user_id'] ?? '')) ?: trim((string) ($row['phone'] ?? ''));
            if ($phone === '') {
                continue;
            }
            $name = trim((string) ($row['customer_name'] ?? ''));
            $out[] = ['phone' => $phone, 'name' => $name !== '' ? $name : null];
        }

        return $out;
    }

    // —— Broadcasts ——

    /** @param array<string, mixed> $data */
    public function createBroadcast(array $data): string
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO broadcasts (id, business_id, connected_account_id, template_id, name, name_fallback, status, total_recipients, created_by, started_at)
             VALUES (:id, :business_id, :connected_account_id, :template_id, :name, :name_fallback, \'sending\', :total, :created_by, NOW())',
            [
                'id' => $id,
                'business_id' => $data['business_id'],
                'connected_account_id' => $data['connected_account_id'],
                'template_id' => $data['template_id'],
                'name' => $data['name'],
                'name_fallback' => $data['name_fallback'],
                'total' => (int) $data['total_recipients'],
                'created_by' => $data['created_by'] ?? null,
            ]
        );

        return $id;
    }

    /** @param list<array<string, mixed>> $contacts */
    public function addRecipients(string $broadcastId, array $contacts): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO broadcast_recipients (id, broadcast_id, contact_id, phone, name)
             VALUES (:id, :broadcast_id, :contact_id, :phone, :name)'
        );
        foreach ($contacts as $contact) {
            $stmt->execute([
                'id' => Uuid::v4(),
                'broadcast_id' => $broadcastId,
                'contact_id' => $contact['id'] ?? null,
                'phone' => $contact['phone'],
                'name' => $contact['name'] ?? null,
            ]);
        }
    }

    public function findBroadcast(string $id, ?string $businessId = null): ?array
    {
        if ($businessId === null) {
            return $this->fetchOne('SELECT * FROM broadcasts WHERE id = :id LIMIT 1', ['id' => $id]);
        }

        return $this->fetchOne(
            'SELECT * FROM broadcasts WHERE id = :id AND business_id = :business_id LIMIT 1',
            ['id' => $id, 'business_id' => $businessId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listBroadcasts(string $businessId): array
    {
        return $this->fetchAll(
            'SELECT b.*, t.title AS template_title,
                    SUM(r.status IN (\'sent\', \'delivered\', \'read\')) AS sent_count,
                    SUM(r.status IN (\'delivered\', \'read\')) AS delivered_count,
                    SUM(r.status = \'read\') AS read_count,
                    SUM(r.status = \'failed\') AS failed_count,
                    SUM(r.status = \'queued\') AS queued_count
             FROM broadcasts b
             LEFT JOIN whatsapp_templates t ON t.id = b.template_id
             LEFT JOIN broadcast_recipients r ON r.broadcast_id = b.id
             WHERE b.business_id = :business_id
             GROUP BY b.id
             ORDER BY b.created_at DESC
             LIMIT 50',
            ['business_id' => $businessId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listSendingBroadcasts(int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT * FROM broadcasts WHERE status = \'sending\' ORDER BY started_at ASC LIMIT ' . max(1, min(50, $limit))
        );
    }

    public function setBroadcastStatus(string $id, string $status, ?string $pauseReason = null): void
    {
        $this->execute(
            'UPDATE broadcasts
             SET status = :status, pause_reason = :reason,
                 completed_at = IF(:status_done IN (\'completed\', \'cancelled\'), NOW(), completed_at)
             WHERE id = :id',
            ['id' => $id, 'status' => $status, 'status_done' => $status, 'reason' => $pauseReason]
        );
    }

    public function addTokensCharged(string $id, int $tokens): void
    {
        $this->execute(
            'UPDATE broadcasts SET tokens_charged = tokens_charged + :tokens WHERE id = :id',
            ['id' => $id, 'tokens' => $tokens]
        );
    }

    /** @return list<array<string, mixed>> */
    public function nextQueuedRecipients(string $broadcastId, int $limit): array
    {
        return $this->fetchAll(
            'SELECT * FROM broadcast_recipients WHERE broadcast_id = :id AND status = \'queued\' ORDER BY id LIMIT '
            . max(1, min(200, $limit)),
            ['id' => $broadcastId]
        );
    }

    public function markRecipientSent(string $id, string $waMessageId): void
    {
        $this->execute(
            'UPDATE broadcast_recipients SET status = \'sent\', wa_message_id = :wamid, sent_at = NOW() WHERE id = :id',
            ['id' => $id, 'wamid' => $waMessageId]
        );
    }

    public function markRecipientFailed(string $id, string $code, string $message): void
    {
        $this->execute(
            'UPDATE broadcast_recipients SET status = \'failed\', error_code = :code, error_message = :message WHERE id = :id',
            ['id' => $id, 'code' => mb_substr($code, 0, 16), 'message' => mb_substr($message, 0, 255)]
        );
    }

    public function cancelQueuedRecipients(string $broadcastId): void
    {
        $this->execute(
            'UPDATE broadcast_recipients SET status = \'cancelled\' WHERE broadcast_id = :id AND status = \'queued\'',
            ['id' => $broadcastId]
        );
    }

    /**
     * Apply a delivery status from Meta. Statuses only move forward (sent → delivered → read).
     */
    public function applyDeliveryStatus(string $waMessageId, string $status, ?string $code, ?string $message): void
    {
        if ($status === 'failed') {
            $this->execute(
                'UPDATE broadcast_recipients
                 SET status = \'failed\', error_code = :code, error_message = :message
                 WHERE wa_message_id = :wamid AND status IN (\'sent\', \'delivered\')',
                [
                    'wamid' => $waMessageId,
                    'code' => $code !== null ? mb_substr($code, 0, 16) : null,
                    'message' => $message !== null ? mb_substr($message, 0, 255) : null,
                ]
            );

            return;
        }

        $allowedFrom = match ($status) {
            'delivered' => ['sent'],
            'read' => ['sent', 'delivered'],
            default => [],
        };
        if ($allowedFrom === []) {
            return;
        }

        $this->execute(
            'UPDATE broadcast_recipients SET status = :status
             WHERE wa_message_id = :wamid AND status IN (\'' . implode('\', \'', $allowedFrom) . '\')',
            ['wamid' => $waMessageId, 'status' => $status]
        );
    }

    public function acquireLock(string $name): bool
    {
        $row = $this->fetchOne('SELECT GET_LOCK(:name, 0) AS got', ['name' => $name]);

        return (int) ($row['got'] ?? 0) === 1;
    }

    public function releaseLock(string $name): void
    {
        $this->fetchOne('SELECT RELEASE_LOCK(:name) AS released', ['name' => $name]);
    }

    /**
     * Unique people this WhatsApp number started conversations with in the last 24 hours.
     */
    public function uniqueRecipientsLast24h(string $connectedAccountId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(DISTINCT r.phone) AS total
             FROM broadcast_recipients r
             INNER JOIN broadcasts b ON b.id = r.broadcast_id
             WHERE b.connected_account_id = :account
               AND r.sent_at >= (NOW() - INTERVAL 24 HOUR)',
            ['account' => $connectedAccountId]
        );

        return (int) ($row['total'] ?? 0);
    }
}
