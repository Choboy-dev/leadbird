<?php

declare(strict_types=1);

final class Mail
{
    public const FOLDERS = ['inbox', 'starred', 'sent', 'drafts', 'scheduled', 'archive', 'spam', 'trash'];
    public const CATEGORIES = ['primary', 'promotions', 'social', 'updates'];
    private const SAFE_EXTENSIONS = ['pdf','txt','csv','jpg','jpeg','png','gif','webp','doc','docx','xls','xlsx','ppt','pptx','zip'];
    /** Only mail actually sent by these platforms belongs in the Social tab. */
    private const SOCIAL_DOMAINS = [
        'facebook.com', 'facebookmail.com', 'instagram.com', 'linkedin.com', 'linkedinmail.com',
        'tiktok.com', 'tiktokglobalshop.com', 'youtube.com', 'twitter.com', 'x.com', 'pinterest.com',
        'reddit.com', 'redditmail.com', 'threads.net', 'snapchat.com', 'discord.com', 'tumblr.com',
        'quora.com', 'meetup.com', 'nextdoor.com', 'bsky.app', 'mastodon.social', 'vk.com', 'weibo.com',
    ];
    /** Inline (in-body) images, hosted publicly so email clients can load them. */
    private const INLINE_IMAGE_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    public const INLINE_IMAGE_MAX_BYTES = 5242880;
    public const AVATAR_MAX_BYTES = 3145728;
    private const AVATAR_SIZE = 256;
    private const SAFE_MIMES = [
        'application/pdf','text/plain','text/csv','image/jpeg','image/png','image/gif','image/webp',
        'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip','application/octet-stream',
    ];

    /** @var array<string, string> */
    private static array $avatarCache = [];

    private static function one(string $sql, array $params = []): ?array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private static function all(string $sql, array $params = []): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function ensureStorage(): void
    {
        foreach (['inbound', 'outbound', 'drafts'] as $bucket) {
            $path = dirname(__DIR__) . '/storage/mail/' . $bucket;
            if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
                throw new RuntimeException('Could not create private mail storage.');
            }
        }
    }

    public static function domain(): string
    {
        return strtolower(trim((string) app_config()['mail']['domain']));
    }

    public static function url(string $path = '', array $query = []): string
    {
        $base = trim((string) app_config()['mail']['path'], '/');
        $path = preg_replace('/\.php(?=$|\?)/', '', ltrim($path, '/')) ?? $path;
        return app_route($base . ($path !== '' ? '/' . $path : ''), $query);
    }

    /** Only allow post-submit redirects back into the mail app itself. */
    public static function safeReturnUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || !str_starts_with($value, '/') || str_starts_with($value, '//') || preg_match('/[\r\n]/', $value)) {
            return null;
        }
        $base = '/' . trim((string) app_config()['mail']['path'], '/');

        return str_starts_with($value, $base) ? $value : null;
    }

    public static function findUserByEmail(string $email, bool $activeOnly = true): ?array
    {
        return self::one(
            'SELECT * FROM mail_users WHERE email = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' LIMIT 1',
            [normalize_email($email)]
        );
    }

    public static function findUserById(int $id, bool $activeOnly = true): ?array
    {
        return self::one('SELECT * FROM mail_users WHERE id = ?' . ($activeOnly ? ' AND is_active = 1' : ''), [$id]);
    }

    public static function authenticate(string $email, string $password): ?array
    {
        $user = self::findUserByEmail($email);
        return $user !== null && password_verify($password, (string) $user['password_hash']) ? $user : null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['mail_user_id'] = (int) $user['id'];
        $_SESSION['mail_user_email'] = (string) $user['email'];
    }

    public static function logout(): void
    {
        unset($_SESSION['mail_user_id'], $_SESSION['mail_user_email']);
        session_regenerate_id(true);
    }

    public static function currentUser(): ?array
    {
        $id = (int) ($_SESSION['mail_user_id'] ?? 0);
        return $id > 0 ? self::findUserById($id) : null;
    }

    public static function requireUser(bool $allowPasswordChange = false): array
    {
        $user = self::currentUser();
        if ($user === null) {
            redirect(self::url('login'));
        }
        if (!$allowPasswordChange && !empty($user['must_change_password'])) {
            redirect(self::url('change-password'));
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireUser();
        if (($user['role'] ?? '') !== 'admin') {
            redirect(self::url());
        }
        return $user;
    }

    public static function changePassword(int $userId, string $password): array
    {
        if (strlen($password) < 12) {
            return ['ok' => false, 'error' => 'Use at least 12 characters.'];
        }
        self::run('UPDATE mail_users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [
            password_hash($password, PASSWORD_DEFAULT), $userId,
        ]);
        return ['ok' => true];
    }

    public static function userInitials(array $user): string
    {
        $parts = preg_split('/\s+/', trim((string) ($user['display_name'] ?? ''))) ?: [];
        $value = implode('', array_map(static fn(string $part): string => strtoupper(substr($part, 0, 1)), array_slice($parts, 0, 2)));
        return $value !== '' ? $value : strtoupper(substr((string) ($user['email'] ?? 'M'), 0, 1));
    }

    public static function folderCounts(int $userId): array
    {
        $counts = array_fill_keys(self::FOLDERS, ['total' => 0, 'unread' => 0]);
        foreach (self::all('SELECT folder, COUNT(*) total, SUM(is_read = 0) unread FROM mail_messages WHERE user_id = ? GROUP BY folder', [$userId]) as $row) {
            $counts[(string) $row['folder']] = ['total' => (int) $row['total'], 'unread' => (int) $row['unread']];
        }
        $starred = self::one('SELECT COUNT(*) total, SUM(is_read = 0) unread FROM mail_messages WHERE user_id = ? AND is_starred = 1 AND folder <> "trash"', [$userId]);
        $counts['starred'] = ['total' => (int) ($starred['total'] ?? 0), 'unread' => (int) ($starred['unread'] ?? 0)];
        return $counts;
    }

    /** Cheap snapshot the open tabs poll to notice mail that arrived since load. */
    public static function inboxState(int $userId): array
    {
        $row = self::one(
            'SELECT COALESCE(MAX(id), 0) AS latest_id, COALESCE(SUM(is_read = 0), 0) AS unread
             FROM mail_messages WHERE user_id = ? AND direction = "inbound" AND folder = "inbox"',
            [$userId]
        );

        return ['latest_id' => (int) ($row['latest_id'] ?? 0), 'unread' => (int) ($row['unread'] ?? 0)];
    }

    public static function listMessages(int $userId, string $folder, string $category, string $search, int $page, int $perPage = 50): array
    {
        $page = max(1, $page);
        $where = ['user_id = ?'];
        $params = [$userId];
        if ($folder === 'starred') {
            $where[] = 'is_starred = 1';
            $where[] = 'folder <> "trash"';
        } else {
            $where[] = 'folder = ?';
            $params[] = in_array($folder, self::FOLDERS, true) ? $folder : 'inbox';
        }
        if ($folder === 'inbox' && $category !== 'all') {
            $where[] = 'category = ?';
            $params[] = in_array($category, self::CATEGORIES, true) ? $category : 'primary';
        }
        if ($search !== '') {
            $where[] = '(subject LIKE ? OR snippet LIKE ? OR from_email LIKE ? OR from_name LIKE ? OR body_text LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $sql = implode(' AND ', $where);
        $total = (int) (self::one("SELECT COUNT(*) total FROM mail_messages WHERE {$sql}", $params)['total'] ?? 0);
        $offset = ($page - 1) * $perPage;
        $order = $folder === 'scheduled' ? 'scheduled_at ASC' : 'COALESCE(received_at, sent_at, created_at) DESC';
        $messages = self::all(
            "SELECT * FROM mail_messages WHERE {$sql} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['messages' => $messages, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public static function getMessage(int $userId, int $messageId): ?array
    {
        return self::one('SELECT * FROM mail_messages WHERE id = ? AND user_id = ?', [$messageId, $userId]);
    }

    public static function getAttachments(int $messageId): array
    {
        return self::all('SELECT * FROM mail_attachments WHERE message_id = ? ORDER BY id', [$messageId]);
    }

    public static function getAttachment(int $userId, int $attachmentId): ?array
    {
        return self::one(
            'SELECT a.* FROM mail_attachments a INNER JOIN mail_messages m ON m.id = a.message_id WHERE a.id = ? AND m.user_id = ?',
            [$attachmentId, $userId]
        );
    }

    public static function markRead(int $userId, int $id, bool $read): void
    {
        self::run('UPDATE mail_messages SET is_read = ? WHERE id = ? AND user_id = ?', [$read ? 1 : 0, $id, $userId]);
    }

    public static function toggleStar(int $userId, int $id): void
    {
        self::run('UPDATE mail_messages SET is_starred = 1 - is_starred WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function moveMessages(int $userId, array $ids, string $folder): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0));
        if ($ids === [] || !in_array($folder, self::FOLDERS, true)) return;
        $marks = implode(',', array_fill(0, count($ids), '?'));
        self::run("UPDATE mail_messages SET folder = ? WHERE id IN ({$marks}) AND user_id = ?", array_merge([$folder], $ids, [$userId]));
    }

    public static function deleteMessages(int $userId, array $ids, bool $permanent): void
    {
        if (!$permanent) {
            self::moveMessages($userId, $ids, 'trash');
            return;
        }
        foreach ($ids as $id) {
            $message = self::getMessage($userId, (int) $id);
            if ($message === null) continue;
            foreach (self::getAttachments((int) $id) as $attachment) {
                $path = dirname(__DIR__) . '/' . ltrim((string) $attachment['storage_path'], '/');
                if (is_file($path)) @unlink($path);
            }
            self::run('DELETE FROM mail_messages WHERE id = ? AND user_id = ?', [(int) $id, $userId]);
        }
    }

    public static function decodeJsonList(?string $json): array
    {
        $items = json_decode((string) $json, true);
        return is_array($items) ? $items : [];
    }

    private static function encodeJsonList(array $items): string
    {
        return json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function recipients(array|string $input): array
    {
        $input = is_string($input) ? (preg_split('/[,;]+/', $input) ?: []) : $input;
        $valid = [];
        foreach ($input as $item) {
            $item = trim((string) $item);
            if (preg_match('/<([^>]+)>/', $item, $match)) $item = $match[1];
            $item = normalize_email($item);
            if (is_valid_email($item)) $valid[] = $item;
        }
        return array_values(array_unique($valid));
    }

    public static function validateUploads(array $files): array
    {
        $result = [];
        $names = is_array($files['name'] ?? null) ? $files['name'] : [];
        if (count($names) > (int) app_config()['mail']['max_attachments']) {
            throw new RuntimeException('You can attach up to 10 files.');
        }
        $total = 0;
        foreach ($names as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('An attachment upload failed.');
            $tmp = (string) ($files['tmp_name'][$i] ?? '');
            $size = (int) ($files['size'][$i] ?? 0);
            $total += $size;
            if (!is_uploaded_file($tmp) || $total > (int) app_config()['mail']['max_attachment_bytes']) {
                throw new RuntimeException('Attachments must total 10 MB or less.');
            }
            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream';
            if (!in_array($extension, self::SAFE_EXTENSIONS, true) || !in_array($mime, self::SAFE_MIMES, true)) {
                throw new RuntimeException('That attachment type is not allowed.');
            }
            $result[] = ['path' => $tmp, 'filename' => (string) $name, 'content_type' => $mime];
        }
        return $result;
    }

    public static function sendMessage(array $user, array $payload): array
    {
        $to = self::recipients($payload['to'] ?? []);
        $cc = self::recipients($payload['cc'] ?? []);
        $bcc = self::recipients($payload['bcc'] ?? []);
        if ($to === []) return ['ok' => false, 'error' => 'Add at least one valid recipient.'];
        $html = self::bodyHtml($payload, (int) $user['id']);
        $text = MailHtml::toText($html);
        if (trim($text) === '' && $html === '') return ['ok' => false, 'error' => 'Write a message before sending.'];
        $subject = trim((string) ($payload['subject'] ?? ''));
        $fromName = trim((string) ($user['display_name'] ?? ''));
        $scheduledAt = self::normalizeScheduledAt((string) ($payload['scheduled_at'] ?? ''));
        $api = ['from' => ($fromName !== '' ? $fromName . ' <' . $user['email'] . '>' : $user['email']), 'to' => $to,
            'subject' => $subject !== '' ? $subject : '(no subject)', 'text' => $text !== '' ? $text : ' ', 'html' => $html !== '' ? $html : '<div></div>'];
        if ($cc !== []) $api['cc'] = $cc;
        if ($bcc !== []) $api['bcc'] = $bcc;
        if ($scheduledAt !== null) $api['scheduled_at'] = $scheduledAt->format('Y-m-d\TH:i:s\Z');
        if (!empty($payload['in_reply_to'])) $api['headers'] = ['In-Reply-To' => (string) $payload['in_reply_to']];
        foreach ((array) ($payload['attachments'] ?? []) as $file) {
            $content = file_get_contents((string) $file['path']);
            if ($content !== false) $api['attachments'][] = ['filename' => basename((string) $file['filename']), 'content' => base64_encode($content)];
        }
        $response = (new ResendClient())->sendEmail($api);
        if (!$response['ok']) return ['ok' => false, 'error' => $response['error'] ?? 'Could not send email.'];
        $resendId = (string) ($response['data']['id'] ?? '');
        self::run(
            'INSERT INTO mail_messages (user_id,folder,category,direction,from_email,from_name,to_emails,cc_emails,bcc_emails,subject,body_html,body_text,snippet,is_read,message_id,in_reply_to,resend_email_id,sent_at,scheduled_at)
             VALUES (?,?,"primary","outbound",?,?,?,?,?,?,?,?,?,1,?,?,?,?,?)',
            [(int) $user['id'], $scheduledAt !== null ? 'scheduled' : 'sent', $user['email'], $fromName,
             self::encodeJsonList($to), self::encodeJsonList($cc), self::encodeJsonList($bcc),
             $subject, $html, $text, self::makeSnippet($text), $resendId, $payload['in_reply_to'] ?: null, $resendId ?: null,
             $scheduledAt !== null ? null : gmdate('Y-m-d H:i:s'), $scheduledAt?->format('Y-m-d H:i:s')]
        );
        $id = (int) Database::connection()->lastInsertId();
        foreach ((array) ($payload['attachments'] ?? []) as $file) {
            $stored = self::storeAttachmentFile($id, (string) $file['path'], (string) $file['filename'], 'outbound', (string) ($file['content_type'] ?? ''));
            self::run('INSERT INTO mail_attachments (message_id,filename,content_type,size_bytes,storage_path) VALUES (?,?,?,?,?)',
                [$id, $stored['filename'], $stored['content_type'], $stored['size_bytes'], $stored['storage_path']]);
        }
        if ((int) ($payload['draft_id'] ?? 0) > 0) self::run('DELETE FROM mail_messages WHERE id = ? AND user_id = ? AND folder = "drafts"', [(int) $payload['draft_id'], (int) $user['id']]);
        return ['ok' => true, 'message_id' => $id, 'scheduled_at' => $scheduledAt?->format('Y-m-d H:i:s')];
    }

    public static function saveDraft(array $user, array $payload): array
    {
        $html = self::bodyHtml($payload, (int) $user['id']);
        $text = MailHtml::toText($html);
        $values = [self::encodeJsonList(self::recipients($payload['to'] ?? [])), self::encodeJsonList(self::recipients($payload['cc'] ?? [])),
            self::encodeJsonList(self::recipients($payload['bcc'] ?? [])), trim((string) ($payload['subject'] ?? '')), $html, $text, self::makeSnippet($text)];
        $id = (int) ($payload['draft_id'] ?? 0);
        if ($id > 0) {
            self::run('UPDATE mail_messages SET to_emails=?,cc_emails=?,bcc_emails=?,subject=?,body_html=?,body_text=?,snippet=? WHERE id=? AND user_id=? AND folder="drafts"', array_merge($values, [$id, (int) $user['id']]));
        } else {
            self::run('INSERT INTO mail_messages (user_id,folder,category,direction,from_email,from_name,to_emails,cc_emails,bcc_emails,subject,body_html,body_text,snippet,is_read) VALUES (?,"drafts","primary","outbound",?,?,?,?,?,?,?,?,?,1)',
                array_merge([(int) $user['id'], $user['email'], $user['display_name']], $values));
            $id = (int) Database::connection()->lastInsertId();
        }
        return ['ok' => true, 'draft_id' => $id];
    }

    /** Compose posts rich text; older callers may still post plain text. */
    private static function bodyHtml(array $payload, int $userId = 0): string
    {
        $html = MailHtml::sanitize((string) ($payload['html'] ?? ''));
        if ($html !== '') return $userId > 0 ? self::materializeInlineImages($html, $userId) : $html;
        return MailHtml::fromText((string) ($payload['text'] ?? $payload['body'] ?? ''));
    }

    /**
     * Resend accepts a UTC instant for scheduled delivery; the composer sends the
     * user's local wall-clock time, so convert it here and reject unusable times.
     */
    private static function normalizeScheduledAt(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') return null;
        try {
            $local = new DateTimeImmutable($value, app_timezone());
        } catch (Throwable) {
            throw new RuntimeException('That send time is not a valid date and time.');
        }
        $utc = $local->setTimezone(new DateTimeZone('UTC'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if ($utc < $now->modify('+1 minute')) throw new RuntimeException('Choose a send time at least a minute from now.');
        if ($utc > $now->modify('+90 days')) throw new RuntimeException('Scheduled sends must be within the next 90 days.');
        return $utc;
    }

    /** Gmail-style quick picks for the schedule popover, in the user's timezone. */
    public static function scheduleOptions(): array
    {
        $now = new DateTimeImmutable('now', app_timezone());
        $tomorrow = $now->modify('+1 day');
        $candidates = [
            ['label' => 'Tomorrow morning', 'at' => $tomorrow->setTime(8, 0)],
            ['label' => 'Tomorrow afternoon', 'at' => $tomorrow->setTime(13, 0)],
            ['label' => 'Monday morning', 'at' => $now->modify('monday next week')->setTime(8, 0)],
        ];
        $options = [];
        foreach ($candidates as $candidate) {
            /** @var DateTimeImmutable $at */
            $at = $candidate['at'];
            if ($at <= $now) continue;
            $options[] = ['label' => $candidate['label'], 'hint' => $at->format('M j, g:i A'), 'value' => $at->format('Y-m-d H:i')];
        }
        return $options;
    }

    public static function timezoneLabel(): string
    {
        $zone = app_timezone();
        if (class_exists('IntlTimeZone')) {
            $name = IntlTimeZone::createTimeZone($zone->getName())->getDisplayName();
            if (is_string($name) && $name !== '') return $name;
        }
        return str_replace('_', ' ', $zone->getName());
    }

    public static function formatScheduledAt(?string $utcDatetime): string
    {
        $utc = app_utc_datetime($utcDatetime);
        return $utc === null ? '' : $utc->setTimezone(app_timezone())->format('M j, Y \a\t g:i A');
    }

    /** Pull a scheduled send back before Resend hands it off, and keep it as a draft. */
    public static function cancelScheduled(int $userId, int $messageId): array
    {
        $message = self::getMessage($userId, $messageId);
        if ($message === null || (string) $message['folder'] !== 'scheduled') {
            return ['ok' => false, 'error' => 'That scheduled message is no longer available.'];
        }
        $resendId = (string) ($message['resend_email_id'] ?? '');
        if ($resendId !== '') {
            $response = (new ResendClient())->cancelEmail($resendId);
            if (!$response['ok']) return ['ok' => false, 'error' => $response['error'] ?? 'Resend could not cancel this send.'];
        }
        self::run('UPDATE mail_messages SET folder="drafts", scheduled_at=NULL, resend_email_id=NULL WHERE id=? AND user_id=?', [$messageId, $userId]);
        return ['ok' => true, 'draft_id' => $messageId];
    }

    public static function processInboundWebhook(array $event): array
    {
        if (($event['type'] ?? '') !== 'email.received') return ['ok' => true, 'skipped' => 'unsupported_event'];
        $emailId = (string) ($event['data']['email_id'] ?? '');
        if ($emailId === '') return ['ok' => false, 'error' => 'missing_email_id'];
        if (self::one('SELECT id FROM mail_messages WHERE resend_email_id = ?', [$emailId])) return ['ok' => true, 'skipped' => 'duplicate'];
        $response = (new ResendClient())->getReceivedEmail($emailId);
        if (!$response['ok'] || !is_array($response['data'])) return ['ok' => false, 'error' => 'could_not_fetch_email'];
        $email = $response['data'];
        $user = null;
        foreach (array_merge((array) ($email['to'] ?? []), (array) ($email['cc'] ?? []), (array) ($email['bcc'] ?? [])) as $address) {
            if (preg_match('/<([^>]+)>/', (string) $address, $match)) $address = $match[1];
            $user = self::findUserByEmail((string) $address);
            if ($user) break;
        }
        if ($user === null) {
            $catchAll = normalize_email((string) app_config()['mail']['catch_all_email']);
            if ($catchAll === '') {
                error_log('Rejected inbound mail for unknown recipient; Resend ID ' . $emailId);
                return ['ok' => false, 'error' => 'no_matching_user'];
            }
            $user = self::findUserByEmail($catchAll);
            if ($user === null) return ['ok' => false, 'error' => 'invalid_catch_all'];
        }
        $fromRaw = (string) ($email['from'] ?? '');
        $fromName = '';
        $fromEmail = $fromRaw;
        if (preg_match('/^(.*?)<([^>]+)>$/', $fromRaw, $match)) {
            $fromName = trim($match[1], " \t\"'");
            $fromEmail = $match[2];
        }
        $html = (string) ($email['html'] ?? '');
        $text = (string) ($email['text'] ?? strip_tags($html));
        self::run(
            'INSERT INTO mail_messages (user_id,folder,category,direction,from_email,from_name,to_emails,cc_emails,bcc_emails,subject,body_html,body_text,snippet,is_read,thread_id,message_id,resend_email_id,received_at)
             VALUES (?,"inbox",?,"inbound",?,?,?,?,?,?,?,?,?,0,?,?,?,?)',
            [(int) $user['id'], self::categorizeInbound($fromEmail, (string) ($email['subject'] ?? ''), (array) ($email['headers'] ?? [])), normalize_email($fromEmail), $fromName,
             self::encodeJsonList((array) ($email['to'] ?? [])), self::encodeJsonList((array) ($email['cc'] ?? [])), self::encodeJsonList((array) ($email['bcc'] ?? [])),
             (string) ($email['subject'] ?? ''), $html, $text, self::makeSnippet($text), (string) ($email['message_id'] ?? $emailId),
             $email['message_id'] ?? null, $emailId, gmdate('Y-m-d H:i:s', strtotime((string) ($email['created_at'] ?? 'now')) ?: time())]
        );
        $messageId = (int) Database::connection()->lastInsertId();
        self::storeInboundAttachments($messageId, $emailId, (array) ($email['attachments'] ?? []));
        return ['ok' => true, 'message_id' => $messageId];
    }

    private static function storeInboundAttachments(int $messageId, string $emailId, array $attachments): void
    {
        $attachments = array_slice($attachments, 0, (int) app_config()['mail']['max_attachments']);
        $total = 0;
        foreach ($attachments as $meta) {
            $attachmentId = (string) ($meta['id'] ?? '');
            $response = $attachmentId !== '' ? (new ResendClient())->getReceivedAttachment($emailId, $attachmentId) : ['ok' => false];
            $url = (string) ($response['data']['download_url'] ?? '');
            if (!$response['ok'] || !str_starts_with($url, 'https://')) continue;
            $binary = @file_get_contents($url);
            if (!is_string($binary)) continue;
            $total += strlen($binary);
            if ($total > (int) app_config()['mail']['max_attachment_bytes']) break;
            $filename = (string) ($response['data']['filename'] ?? $meta['filename'] ?? 'attachment.bin');
            $tmp = tempnam(sys_get_temp_dir(), 'lbmail_');
            if ($tmp === false) continue;
            file_put_contents($tmp, $binary, LOCK_EX);
            try {
                $stored = self::storeAttachmentFile($messageId, $tmp, $filename, 'inbound', (string) ($response['data']['content_type'] ?? ''));
                self::run('INSERT INTO mail_attachments (message_id,filename,content_type,size_bytes,storage_path,resend_attachment_id) VALUES (?,?,?,?,?,?)',
                    [$messageId, $stored['filename'], $stored['content_type'], $stored['size_bytes'], $stored['storage_path'], $attachmentId]);
            } catch (Throwable $e) {
                error_log('Inbound attachment rejected: ' . $e->getMessage());
            } finally {
                @unlink($tmp);
            }
        }
    }

    public static function storeAttachmentFile(int $messageId, string $source, string $filename, string $bucket, string $contentType = ''): array
    {
        if (!in_array($bucket, ['inbound','outbound','drafts'], true) || !is_file($source)) throw new RuntimeException('Invalid attachment.');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = $contentType !== '' ? strtolower(trim(explode(';', $contentType)[0])) : ((new finfo(FILEINFO_MIME_TYPE))->file($source) ?: 'application/octet-stream');
        if (!in_array($extension, self::SAFE_EXTENSIONS, true) || !in_array($mime, self::SAFE_MIMES, true)) throw new RuntimeException('Attachment type is not allowed.');
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', basename($filename)) ?: 'attachment.bin';
        $storedName = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        $dir = dirname(__DIR__) . '/storage/mail/' . $bucket . '/' . $messageId;
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) throw new RuntimeException('Could not store attachment.');
        $target = $dir . '/' . $storedName;
        if (!copy($source, $target)) throw new RuntimeException('Could not store attachment.');
        chmod($target, 0640);
        return ['filename' => $safe, 'content_type' => $mime, 'size_bytes' => (int) filesize($target),
            'storage_path' => 'storage/mail/' . $bucket . '/' . $messageId . '/' . $storedName];
    }

    /**
     * Inline images have to live at a public https URL: email clients strip
     * data: URIs, so a pasted image only renders inside our own editor.
     */
    public static function storeInlineImage(int $userId, string $source, string $contentType = ''): string
    {
        if (!is_file($source)) throw new RuntimeException('Could not read that image.');
        $size = (int) filesize($source);
        if ($size <= 0) throw new RuntimeException('That image is empty.');
        if ($size > self::INLINE_IMAGE_MAX_BYTES) throw new RuntimeException('Images must be 5 MB or smaller.');
        $detected = (new finfo(FILEINFO_MIME_TYPE))->file($source) ?: $contentType;
        $mime = strtolower(trim(explode(';', $detected)[0]));
        $extension = self::INLINE_IMAGE_TYPES[$mime] ?? null;
        if ($extension === null) throw new RuntimeException('Use a PNG, JPEG, GIF or WebP image.');
        $relative = 'storage/uploads/mail-inline/' . $userId;
        $dir = dirname(__DIR__) . '/' . $relative;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Could not store the image.');
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!copy($source, $dir . '/' . $name)) throw new RuntimeException('Could not store the image.');
        chmod($dir . '/' . $name, 0644);

        return rtrim((string) app_config()['app_url'], '/') . '/' . $relative . '/' . $name;
    }

    /**
     * Rewrite any data: image left in editor output to a hosted URL, and drop
     * images we cannot host so recipients never see a broken placeholder.
     */
    public static function materializeInlineImages(string $html, int $userId): string
    {
        if ($html === '' || stripos($html, 'data:image/') === false) return $html;

        return preg_replace_callback('#<img\b[^>]*>#i', static function (array $match) use ($userId): string {
            $tag = $match[0];
            if (!preg_match('#\ssrc="(data:image/[a-z+.-]+;base64,[^"]+)"#i', $tag, $src)) return $tag;
            $payload = base64_decode((string) preg_replace('#^data:image/[a-z+.-]+;base64,#i', '', $src[1]), true);
            if ($payload === false || $payload === '') return '';
            $tmp = tempnam(sys_get_temp_dir(), 'lbinline_');
            if ($tmp === false) return '';
            try {
                file_put_contents($tmp, $payload, LOCK_EX);
                $url = self::storeInlineImage($userId, $tmp);
                return str_replace($src[0], ' src="' . e($url) . '"', $tag);
            } catch (Throwable $exception) {
                error_log('Inline image dropped: ' . $exception->getMessage());
                return '';
            } finally {
                @unlink($tmp);
            }
        }, $html) ?? $html;
    }

    /**
     * Profile pictures are squared and downscaled on upload, so a mailbox photo
     * stays cheap enough to render beside every message.
     */
    public static function storeAvatar(int $userId, string $source, string $contentType = ''): string
    {
        if (!is_file($source)) throw new RuntimeException('Could not read that image.');
        $size = (int) filesize($source);
        if ($size <= 0) throw new RuntimeException('That image is empty.');
        if ($size > self::AVATAR_MAX_BYTES) throw new RuntimeException('Profile pictures must be 3 MB or smaller.');
        $detected = (new finfo(FILEINFO_MIME_TYPE))->file($source) ?: $contentType;
        $mime = strtolower(trim(explode(';', $detected)[0]));
        $extension = self::INLINE_IMAGE_TYPES[$mime] ?? null;
        if ($extension === null) throw new RuntimeException('Use a PNG, JPEG, GIF or WebP image.');

        $relativeDir = 'storage/uploads/mail-avatars/' . $userId;
        $dir = dirname(__DIR__) . '/' . $relativeDir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Could not store the picture.');
        $squared = self::squareImage($source);
        $name = bin2hex(random_bytes(16)) . '.' . ($squared !== null ? 'png' : $extension);
        $target = $dir . '/' . $name;
        $stored = $squared !== null ? file_put_contents($target, $squared, LOCK_EX) !== false : copy($source, $target);
        if (!$stored) throw new RuntimeException('Could not store the picture.');
        chmod($target, 0644);

        self::removeAvatarFile($userId);
        $relative = $relativeDir . '/' . $name;
        self::run('UPDATE mail_users SET avatar_path = ? WHERE id = ?', [$relative, $userId]);

        return $relative;
    }

    public static function removeAvatar(int $userId): void
    {
        self::removeAvatarFile($userId);
        self::run('UPDATE mail_users SET avatar_path = NULL WHERE id = ?', [$userId]);
    }

    public static function avatarUrl(array $user): string
    {
        $path = ltrim(trim((string) ($user['avatar_path'] ?? '')), '/');
        if ($path === '') return '';

        return rtrim((string) app_config()['app_url'], '/') . '/' . $path;
    }

    /** Lets any message header show the sender's picture without re-querying per row. */
    public static function avatarUrlForEmail(string $email): string
    {
        $key = normalize_email($email);
        if ($key === '') return '';
        if (!array_key_exists($key, self::$avatarCache)) {
            $row = self::one('SELECT avatar_path FROM mail_users WHERE email = ? LIMIT 1', [$key]);
            self::$avatarCache[$key] = $row !== null ? self::avatarUrl($row) : '';
        }

        return self::$avatarCache[$key];
    }

    /** Centre-crops to a square and downscales. Null means GD could not read it. */
    private static function squareImage(string $source): ?string
    {
        if (!function_exists('imagecreatefromstring')) return null;
        $raw = file_get_contents($source);
        if ($raw === false) return null;
        $image = @imagecreatefromstring($raw);
        if ($image === false) return null;
        try {
            $edge = min(imagesx($image), imagesy($image));
            $side = min($edge, self::AVATAR_SIZE);
            $canvas = imagecreatetruecolor($side, $side);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagecopyresampled(
                $canvas, $image, 0, 0,
                (int) ((imagesx($image) - $edge) / 2), (int) ((imagesy($image) - $edge) / 2),
                $side, $side, $edge, $edge
            );
            ob_start();
            imagepng($canvas, null, 9);
            $bytes = (string) ob_get_clean();
            imagedestroy($canvas);

            return $bytes !== '' ? $bytes : null;
        } finally {
            imagedestroy($image);
        }
    }

    private static function removeAvatarFile(int $userId): void
    {
        $current = (string) (self::one('SELECT avatar_path FROM mail_users WHERE id = ?', [$userId])['avatar_path'] ?? '');
        if (!str_starts_with($current, 'storage/uploads/mail-avatars/')) return;
        $path = dirname(__DIR__) . '/' . $current;
        if (is_file($path)) @unlink($path);
    }

    public static function htmlSandboxSrcdoc(string $html): string
    {
        $csp = "default-src 'none'; img-src https: data: cid:; style-src 'unsafe-inline'; font-src https: data:; frame-src https:; connect-src 'none'; form-action 'none'; base-uri 'none'";
        return '<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="' . e($csp) . '"><base target="_blank"><style>body{font:14px Arial,sans-serif;line-height:1.65;color:#202124;margin:0}img{max-width:100%;height:auto}</style></head><body>' . $html . '</body></html>';
    }

    public static function createUser(string $email, string $name, string $password, string $role): array
    {
        $email = normalize_email($email);
        if (!is_valid_email($email) || !str_ends_with($email, '@' . self::domain())) return ['ok' => false, 'error' => 'Use an @' . self::domain() . ' address.'];
        if (strlen($password) < 12) return ['ok' => false, 'error' => 'Use at least 12 characters.'];
        try {
            self::run('INSERT INTO mail_users (email,display_name,password_hash,role,is_active,must_change_password,avatar_color) VALUES (?,?,?,?,1,1,?)',
                [$email, trim($name), password_hash($password, PASSWORD_DEFAULT), $role === 'admin' ? 'admin' : 'user', '#0038FF']);
            return ['ok' => true];
        } catch (PDOException) {
            return ['ok' => false, 'error' => 'That mailbox already exists.'];
        }
    }

    public static function updateUser(int $id, array $fields): array
    {
        $user = self::findUserById($id, false);
        if (!$user) return ['ok' => false, 'error' => 'Mailbox not found.'];
        $role = ($fields['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $active = !empty($fields['is_active']) ? 1 : 0;
        if (($user['role'] ?? '') === 'admin' && (! $active || $role !== 'admin')) {
            $count = (int) (self::one('SELECT COUNT(*) total FROM mail_users WHERE role = "admin" AND is_active = 1')['total'] ?? 0);
            if ($count <= 1) return ['ok' => false, 'error' => 'The last active administrator cannot be disabled or demoted.'];
        }
        self::run('UPDATE mail_users SET display_name=?, role=?, is_active=? WHERE id=?', [trim((string) $fields['display_name']), $role, $active, $id]);
        if ((string) ($fields['password'] ?? '') !== '') {
            if (strlen((string) $fields['password']) < 12) return ['ok' => false, 'error' => 'Use at least 12 characters.'];
            self::run('UPDATE mail_users SET password_hash=?, must_change_password=1 WHERE id=?', [password_hash((string) $fields['password'], PASSWORD_DEFAULT), $id]);
        }
        return ['ok' => true];
    }

    public static function listUsers(): array
    {
        return self::all('SELECT id,email,display_name,role,is_active,must_change_password,avatar_color,created_at FROM mail_users ORDER BY email');
    }

    public static function makeSnippet(string $text, int $length = 120): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
    }

    /**
     * Sort inbound mail using the sender address, the subject and the bulk-mail
     * headers only. The body is never inspected: a person whose signature links
     * to Instagram is not a social notification, and "border" is not an order.
     */
    public static function categorizeInbound(string $from, string $subject, array $headers = []): string
    {
        $address = normalize_email($from);
        [$local, $domain] = array_pad(explode('@', $address, 2), 2, '');
        $subject = strtolower(trim($subject));
        $headers = self::normalizeHeaders($headers);

        foreach (self::SOCIAL_DOMAINS as $social) {
            if ($domain === $social || str_ends_with($domain, '.' . $social)) return 'social';
        }

        // Account notices, and document words only when paired with a reference
        // number, so "the borders on the invoice page" stays in Primary.
        $transactional = preg_match('/\b(verification code|verify your|confirm your (email|account)|password (reset|changed)|reset your password|security alert|sign-?in attempt|new sign-?in|two-?factor|one-?time (code|password))\b/', $subject) === 1
            || preg_match('/\b(invoice|receipt|statement|order|payment|shipment|tracking)\b[^a-z0-9]{0,12}(#|no\.?\s*\d|number|from|for|\d)/', $subject) === 1
            || preg_match('/\byour order (has|is|was)\b|\bhas shipped\b|\bout for delivery\b|\bpayment (received|failed|due)\b/', $subject) === 1;
        $automated = preg_match('/^(no-?reply|do-?not-?reply|notifications?|alerts?|receipts?|billing|invoices?|statements?|system|security|mailer-daemon|postmaster|bounces?)([._+-]|$)/', $local) === 1
            || str_contains(strtolower($headers['auto-submitted'] ?? ''), 'auto-');

        // List-Unsubscribe and friends are only added by bulk senders.
        $bulk = isset($headers['list-unsubscribe']) || isset($headers['list-id'])
            || preg_match('/bulk|list|junk/', strtolower($headers['precedence'] ?? '')) === 1
            || preg_match('/^(newsletters?|news|marketing|promo(tions?)?|offers?|deals?|campaigns?|updates?)([._+-]|$)/', $local) === 1;

        if ($transactional || $automated) return 'updates';
        if ($bulk) return 'promotions';
        return 'primary';
    }

    /** @param array<mixed> $headers accepts a name => value map or Resend's list of pairs */
    private static function normalizeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                $name = (string) ($value['name'] ?? $key);
                $value = (string) ($value['value'] ?? '');
            } else {
                $name = (string) $key;
                $value = (string) $value;
            }
            $name = strtolower(trim($name));
            if ($name !== '') $result[$name] = $value;
        }

        return $result;
    }

    public static function formatListDate(?string $datetime): string
    {
        $time = strtotime((string) $datetime);
        if (!$time) return '';
        if (date('Y-m-d', $time) === date('Y-m-d')) return date('g:i A', $time);
        return date((date('Y', $time) === date('Y')) ? 'M j' : 'M j, Y', $time);
    }

    public static function displaySender(array $message): string
    {
        if (($message['direction'] ?? '') === 'outbound') return (string) (self::decodeJsonList($message['to_emails'] ?? '')[0] ?? 'me');
        return trim((string) ($message['from_name'] ?? '')) ?: (string) ($message['from_email'] ?? '');
    }

    public static function listSignatures(int $userId): array
    {
        return self::all('SELECT * FROM mail_signatures WHERE user_id = ? ORDER BY is_default DESC, name', [$userId]);
    }

    public static function getSignature(int $userId, int $id): ?array
    {
        return self::one('SELECT * FROM mail_signatures WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function saveSignature(int $userId, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $body = self::materializeInlineImages(MailHtml::sanitize((string) ($data['body_html'] ?? '')), $userId);
        $id = (int) ($data['id'] ?? 0);
        if ($name === '') return ['ok' => false, 'error' => 'Give this signature a name.'];
        if (mb_strlen($name) > 120) return ['ok' => false, 'error' => 'Signature names must be 120 characters or fewer.'];
        if (MailHtml::toText($body) === '' && !str_contains($body, '<img')) return ['ok' => false, 'error' => 'Add some signature content.'];
        try {
            if ($id > 0) {
                if (self::getSignature($userId, $id) === null) return ['ok' => false, 'error' => 'Signature not found.'];
                self::run('UPDATE mail_signatures SET name=?, body_html=? WHERE id=? AND user_id=?', [$name, $body, $id, $userId]);
            } else {
                self::run('INSERT INTO mail_signatures (user_id,name,body_html,is_default) VALUES (?,?,?,0)', [$userId, $name, $body]);
                $id = (int) Database::connection()->lastInsertId();
            }
        } catch (PDOException) {
            return ['ok' => false, 'error' => 'You already have a signature with that name.'];
        }
        if (!empty($data['is_default'])) {
            self::setDefaultSignature($userId, $id);
        } elseif (self::defaultSignature($userId) === null) {
            self::setDefaultSignature($userId, $id);
        }
        return ['ok' => true, 'signature_id' => $id];
    }

    public static function setDefaultSignature(int $userId, int $id): void
    {
        self::run('UPDATE mail_signatures SET is_default = 0 WHERE user_id = ?', [$userId]);
        if ($id > 0) self::run('UPDATE mail_signatures SET is_default = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function deleteSignature(int $userId, int $id): void
    {
        $signature = self::getSignature($userId, $id);
        if ($signature === null) return;
        self::run('DELETE FROM mail_signatures WHERE id = ? AND user_id = ?', [$id, $userId]);
        if (!empty($signature['is_default'])) {
            $next = self::one('SELECT id FROM mail_signatures WHERE user_id = ? ORDER BY name LIMIT 1', [$userId]);
            if ($next !== null) self::setDefaultSignature($userId, (int) $next['id']);
        }
    }

    public static function defaultSignature(int $userId): ?array
    {
        return self::one('SELECT * FROM mail_signatures WHERE user_id = ? AND is_default = 1 LIMIT 1', [$userId]);
    }

    public static function updateSignaturePreferences(int $userId, bool $onNew, bool $onReply): void
    {
        self::run('UPDATE mail_users SET signature_on_new = ?, signature_on_reply = ? WHERE id = ?', [$onNew ? 1 : 0, $onReply ? 1 : 0, $userId]);
    }

    /** Signatures are stored sanitized, so they only need wrapping for insertion. */
    public static function signatureBlock(array $signature): string
    {
        return '<div class="lb-mail-signature" data-lb-signature="1"><br>' . (string) $signature['body_html'] . '</div>';
    }

    /**
     * Everything the compose panel needs for its initial state, whether it was
     * opened blank, from a draft, or as a reply/forward.
     */
    public static function composeContext(array $user, array $query): array
    {
        $requested = strtolower(trim((string) ($query['compose'] ?? '')));
        $mode = in_array($requested, ['reply', 'reply-all', 'forward', 'draft'], true) ? $requested : 'new';
        $context = ['open' => $requested !== '', 'mode' => $mode, 'to' => '', 'cc' => '', 'bcc' => '',
            'subject' => '', 'body_html' => '', 'in_reply_to' => '', 'draft_id' => 0, 'show_cc' => false, 'show_bcc' => false];

        $retry = $_SESSION['mail_compose_retry'] ?? null;
        unset($_SESSION['mail_compose_retry']);
        if (is_array($retry)) {
            return array_merge($context, [
                'open' => true,
                'to' => (string) ($retry['to'] ?? ''),
                'cc' => (string) ($retry['cc'] ?? ''),
                'bcc' => (string) ($retry['bcc'] ?? ''),
                'subject' => (string) ($retry['subject'] ?? ''),
                'body_html' => MailHtml::sanitize((string) ($retry['html'] ?? '')),
                'in_reply_to' => (string) ($retry['in_reply_to'] ?? ''),
                'draft_id' => (int) ($retry['draft_id'] ?? 0),
                'show_cc' => trim((string) ($retry['cc'] ?? '')) !== '',
                'show_bcc' => trim((string) ($retry['bcc'] ?? '')) !== '',
            ]);
        }

        $userId = (int) $user['id'];
        $signature = self::defaultSignature($userId);
        $sourceId = (int) ($query['source'] ?? 0);
        $draftId = (int) ($query['draft'] ?? 0);

        if ($mode === 'draft' && $draftId > 0) {
            $draft = self::getMessage($userId, $draftId);
            if ($draft !== null && (string) $draft['folder'] === 'drafts') {
                $context['draft_id'] = $draftId;
                foreach (['to', 'cc', 'bcc'] as $field) {
                    $context[$field] = implode(', ', self::decodeJsonList($draft[$field . '_emails'] ?? ''));
                }
                $context['subject'] = (string) $draft['subject'];
                $context['body_html'] = (string) ($draft['body_html'] ?: MailHtml::fromText((string) ($draft['body_text'] ?? '')));
                $context['show_cc'] = $context['cc'] !== '';
                $context['show_bcc'] = $context['bcc'] !== '';
                return $context;
            }
            $context['mode'] = $mode = 'new';
        }

        if (in_array($mode, ['reply', 'reply-all', 'forward'], true) && $sourceId > 0) {
            $source = self::getMessage($userId, $sourceId);
            if ($source !== null) {
                $quoted = self::quoteBlock($source);
                $appendSignature = $signature !== null && !empty($user['signature_on_reply']);
                if ($mode === 'forward') {
                    $context['subject'] = 'Fwd: ' . preg_replace('/^Fwd:\s*/i', '', (string) $source['subject']);
                } else {
                    $context['to'] = (string) $source['from_email'];
                    $context['subject'] = 'Re: ' . preg_replace('/^Re:\s*/i', '', (string) $source['subject']);
                    $context['in_reply_to'] = (string) ($source['message_id'] ?? '');
                    if ($mode === 'reply-all') {
                        $others = array_diff(
                            array_merge(self::decodeJsonList($source['to_emails'] ?? ''), self::decodeJsonList($source['cc_emails'] ?? '')),
                            [(string) $user['email']]
                        );
                        $context['cc'] = implode(', ', $others);
                        $context['show_cc'] = $context['cc'] !== '';
                    }
                }
                $context['body_html'] = '<div><br></div>'
                    . ($appendSignature ? self::signatureBlock($signature) : '')
                    . $quoted;
                return $context;
            }
        }

        if ($signature !== null && !empty($user['signature_on_new'])) {
            $context['body_html'] = '<div><br></div>' . self::signatureBlock($signature);
        }
        return $context;
    }

    /** Gmail-style attribution line plus the original message inside a quote block. */
    private static function quoteBlock(array $source): string
    {
        $when = app_format_datetime($source['received_at'] ?: $source['sent_at'] ?: $source['created_at'], 'M j, Y \\a\\t g:i A');
        if ($when === 'Just now') $when = date('M j, Y');
        $body = (string) ($source['body_html'] ?: MailHtml::fromText((string) ($source['body_text'] ?? '')));
        $intro = 'On ' . e($when) . ', ' . e(self::displaySender($source)) . ' &lt;' . e((string) $source['from_email']) . '&gt; wrote:';

        return '<div><br></div><div class="lb-mail-quote">' . $intro
            . '<blockquote style="margin:0 0 0 8px;padding-left:12px;border-left:2px solid #e1e5ee">'
            . MailHtml::sanitize($body) . '</blockquote></div>';
    }
}
