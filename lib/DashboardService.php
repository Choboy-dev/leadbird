<?php

declare(strict_types=1);

/**
 * Dashboard metrics and summaries — structured for real data; placeholders where empty.
 */
final class DashboardService
{
    /**
     * @return array{
     *     open_conversations: int,
     *     ai_replies_sent: int,
     *     ai_replies_month: int,
     *     connected_accounts: int
     * }
     */
    public function getOverviewStats(?string $businessId): array
    {
        if ($businessId === null) {
            return $this->emptyStats();
        }

        return [
            'open_conversations' => $this->countOpenConversations($businessId),
            'ai_replies_sent' => $this->countAiReplies($businessId),
            'ai_replies_month' => RepositoryFactory::messages()->countAiRepliesSince($businessId, $this->monthStart()),
            'connected_accounts' => RepositoryFactory::connectedAccounts()->countActiveByBusinessId($businessId),
        ];
    }

    /**
     * @return array{used: int, limit: int|null, label: string}
     */
    public function getDmUsage(?string $businessId, ?int $dmLimit): array
    {
        $used = $businessId !== null
            ? RepositoryFactory::messages()->countAiRepliesSince($businessId, $this->monthStart())
            : 0;

        if ($dmLimit === null) {
            return ['used' => $used, 'limit' => null, 'label' => $used . ' replies used this month'];
        }

        return [
            'used' => $used,
            'limit' => $dmLimit,
            'label' => 'You have used ' . $used . ' of ' . $dmLimit . ' replies',
        ];
    }

    /**
     * @return array{used: int, limit: int|null, label: string}
     */
    public function getAccountUsage(?string $businessId, ?int $channelLimit): array
    {
        $used = $businessId !== null
            ? RepositoryFactory::connectedAccounts()->countActiveByBusinessId($businessId)
            : 0;

        if ($channelLimit === null) {
            return ['used' => $used, 'limit' => null, 'label' => $used . ' connected account' . ($used === 1 ? '' : 's')];
        }

        return [
            'used' => $used,
            'limit' => $channelLimit,
            'label' => $used . ' of ' . $channelLimit . ' account' . ($channelLimit === 1 ? '' : 's') . ' used',
        ];
    }

    /**
     * @return list<array{platform: string, label: string, connected: bool, messages: int, replies: int}>
     */
    public function getChannelPerformance(?string $businessId): array
    {
        $platforms = [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook Messenger',
            'whatsapp' => 'WhatsApp',
        ];

        $connected = [];
        if ($businessId !== null) {
            foreach (RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true) as $account) {
                $connected[(string) ($account['platform'] ?? '')] = $account;
            }
        }

        $rows = [];
        foreach ($platforms as $slug => $label) {
            $rows[] = [
                'platform' => $slug,
                'label' => $label,
                'connected' => isset($connected[$slug]),
                'messages' => $businessId !== null ? $this->countMessagesByPlatform($businessId, $slug, 'customer') : 0,
                'replies' => $businessId !== null ? $this->countMessagesByPlatform($businessId, $slug, 'ai') : 0,
            ];
        }

        return $rows;
    }

    /**
     * Recent inbox activity for the overview table.
     *
     * @return list<array{
     *     kind: string,
     *     date_short: string,
     *     entity_name: string,
     *     platform: string,
     *     description: string,
     *     category: string,
     *     status_badge: string|null
     * }>
     */
    public function getRecentTransactions(?string $businessId, int $limit = 10): array
    {
        if ($businessId === null) {
            return [];
        }

        $rows = [];

        foreach (RepositoryFactory::messages()->listRecentByBusinessId($businessId, $limit) as $message) {
            $sender = (string) ($message['sender_type'] ?? '');
            $customerId = (string) ($message['customer_id'] ?? '');
            $platform = (string) ($message['platform'] ?? '');
            $createdAt = (string) ($message['created_at'] ?? '');

            $rows[] = [
                'kind' => 'message',
                'date_short' => $this->formatDateShort($createdAt),
                'sort_key' => $createdAt,
                'entity_name' => $sender === 'ai'
                    ? 'AI Salesgirl'
                    : ($this->customerDisplayName($customerId) ?: 'Customer'),
                'platform' => $platform,
                'description' => $this->truncate((string) ($message['message_text'] ?? ''), 72),
                'category' => $sender === 'ai' ? 'AI reply' : 'Message',
                'status_badge' => null,
            ];
        }

        if ($rows === []) {
            return [];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) $b['sort_key'], (string) $a['sort_key']));

        $trimmed = [];
        foreach (array_slice($rows, 0, $limit) as $row) {
            unset($row['sort_key']);
            $trimmed[] = $row;
        }

        return $trimmed;
    }

    /**
     * @return list<array{type: string, label: string, detail: string, time: string}>
     */
    public function getRecentActivity(?string $businessId, int $limit = 8): array
    {
        if ($businessId === null) {
            return [];
        }

        $items = [];

        foreach (RepositoryFactory::messages()->listRecentByBusinessId($businessId, $limit) as $message) {
            $sender = (string) ($message['sender_type'] ?? '');
            $items[] = [
                'type' => $sender === 'ai' ? 'ai_replied' : 'message_received',
                'label' => $sender === 'ai' ? 'AI replied' : 'New message received',
                'detail' => $this->truncate((string) ($message['message_text'] ?? ''), 80),
                'time' => $this->formatTime((string) ($message['created_at'] ?? '')),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{type: string, label: string, detail: string, href: string|null}>
     */
    public function getActionItems(
        ?string $businessId,
        ?array $subscription,
        ?array $settings,
        array $tokenUsage,
        int $accountsUsed,
        ?int $accountLimit
    ): array {
        $items = [];

        if ($subscription !== null && ($subscription['status'] ?? '') !== 'active') {
            $items[] = [
                'type' => 'payment_failed',
                'label' => 'Payment failed',
                'detail' => 'Your subscription needs attention. Update your payment method.',
                'href' => app_route('billing'),
            ];
        }

        if ($businessId !== null) {
            $activePlatforms = [];
            $inactivePlatforms = [];

            foreach (RepositoryFactory::connectedAccounts()->listByBusinessId($businessId) as $account) {
                $platform = (string) ($account['platform'] ?? '');
                if ($platform === '') {
                    continue;
                }

                if ((bool) ($account['is_active'] ?? false)) {
                    $activePlatforms[$platform] = true;
                    continue;
                }

                $inactivePlatforms[$platform] = true;
            }

            foreach (array_keys($inactivePlatforms) as $platform) {
                if (isset($activePlatforms[$platform])) {
                    continue;
                }

                $items[] = [
                    'type' => 'account_disconnected',
                    'label' => 'Account disconnected',
                    'detail' => ucfirst($platform) . ' needs to be reconnected.',
                    'href' => app_route('connected-accounts'),
                ];
            }
        }

        if (($settings['faq'] ?? '') === '' && ($settings['business_description'] ?? '') === '') {
            $items[] = [
                'type' => 'ai_training',
                'label' => 'AI needs more training',
                'detail' => 'Add business details and FAQs so your Salesgirl can answer better.',
                'href' => app_route('ai-salesgirl'),
            ];
        }

        if (!($tokenUsage['unlimited'] ?? false) && ($tokenUsage['depleted'] ?? false)) {
            $items[] = [
                'type' => 'tokens_depleted',
                'label' => 'Tokens used up',
                'detail' => 'Top up tokens so Lead Bird can keep replying, writing posts, and publishing.',
                'href' => app_route('billing#tokens'),
            ];
        } elseif (!($tokenUsage['unlimited'] ?? false) && ($tokenUsage['low_balance'] ?? false)) {
            $items[] = [
                'type' => 'tokens_low',
                'label' => 'Running low on tokens',
                'detail' => 'You have ' . (int) ($tokenUsage['balance'] ?? 0) . ' tokens left. Top up anytime.',
                'href' => app_route('billing#tokens'),
            ];
        }

        if ($accountLimit !== null && $accountsUsed >= $accountLimit) {
            $items[] = [
                'type' => 'account_limit',
                'label' => 'Account limit reached',
                'detail' => 'Upgrade to connect more social accounts.',
                'href' => app_route('billing'),
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCustomersWithSummary(string $businessId): array
    {
        $customers = RepositoryFactory::customers()->listByBusinessId($businessId);
        $rows = [];

        foreach ($customers as $customer) {
            $customerId = (string) $customer['id'];
            $rows[] = array_merge($customer, [
                'last_message' => $this->lastMessageForCustomer($businessId, $customerId),
            ]);
        }

        return $rows;
    }

    /**
     * @return array{
     *     open_conversations: int,
     *     ai_replies_sent: int,
     *     ai_replies_month: int,
     *     connected_accounts: int
     * }
     */
    private function emptyStats(): array
    {
        return [
            'open_conversations' => 0,
            'ai_replies_sent' => 0,
            'ai_replies_month' => 0,
            'connected_accounts' => 0,
        ];
    }

    private function countOpenConversations(string $businessId): int
    {
        $rows = RepositoryFactory::conversations()->listByBusinessId($businessId, 'open');

        return count($rows);
    }

    private function countAiReplies(string $businessId): int
    {
        return RepositoryFactory::messages()->countBySenderType($businessId, 'ai');
    }

    private function countMessagesByPlatform(string $businessId, string $platform, string $senderType): int
    {
        return RepositoryFactory::messages()->countByPlatformAndSender($businessId, $platform, $senderType);
    }

    private function lastMessageForCustomer(string $businessId, string $customerId): string
    {
        $row = RepositoryFactory::messages()->findLastForCustomer($businessId, $customerId);

        return $this->truncate((string) ($row['message_text'] ?? ''), 60);
    }

    private function monthStart(): string
    {
        return date('Y-m-01 00:00:00');
    }

    private function formatTime(string $datetime): string
    {
        if ($datetime === '') {
            return '';
        }

        $ts = strtotime($datetime);

        return $ts !== false ? date('M j, g:ia', $ts) : $datetime;
    }

    private function formatDateShort(string $datetime): string
    {
        if ($datetime === '') {
            return '—';
        }

        $ts = strtotime($datetime);

        return $ts !== false ? date('M d', $ts) : $datetime;
    }

    private function customerDisplayName(string $customerId): string
    {
        if ($customerId === '') {
            return '';
        }

        $customer = RepositoryFactory::customers()->findById($customerId);
        if ($customer === null) {
            return '';
        }

        return trim((string) ($customer['customer_name'] ?? ''));
    }

    private function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 1) . '…';
    }
}
