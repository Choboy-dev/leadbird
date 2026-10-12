<?php

declare(strict_types=1);

/**
 * Plan-based feature gating for the logged-in dashboard.
 */
final class PlanAccess
{
    /** @var array<string, list<string>> */
    private const FEATURE_PLANS = [
        'overview' => ['starter', 'vendor', 'shop', 'enterprise'],
        'inbox' => ['starter', 'vendor', 'shop', 'enterprise'],
        'customers' => ['vendor', 'shop', 'enterprise'],
        'offers' => ['starter', 'vendor', 'shop', 'enterprise'],
        'ai_salesgirl' => ['starter', 'vendor', 'shop', 'enterprise'],
        'ai_salesgirl_full' => ['vendor', 'shop', 'enterprise'],
        'connected_accounts' => ['starter', 'vendor', 'shop', 'enterprise'],
        'billing' => ['starter', 'vendor', 'shop', 'enterprise'],
        'billing_history' => ['shop', 'enterprise'],
        'settings' => ['starter', 'vendor', 'shop', 'enterprise'],
        'help' => ['starter', 'vendor', 'shop', 'enterprise'],
        'channel_performance' => ['vendor', 'shop', 'enterprise'],
        'social_media' => ['vendor', 'shop', 'enterprise'],
        'broadcasts' => ['vendor', 'shop', 'enterprise'],
        'owner_whatsapp_alerts' => ['vendor', 'shop', 'enterprise'],
        'ai_negotiation' => ['vendor', 'shop', 'enterprise'],
        'proactive_followups' => ['shop', 'enterprise'],
        'payment_receipts' => ['shop', 'enterprise'],
        'refer_earn' => ['starter', 'vendor', 'shop', 'enterprise'],
        'team_members' => ['enterprise'],
        'api_access' => ['enterprise'],
    ];

    /** @var array<string, array{title: string, description: string, min_plan: string}> */
    private const LOCKED_COPY = [
        'customers' => [
            'title' => 'Customers',
            'description' => 'See every contact in one place — name, channel, and recent messages.',
            'min_plan' => 'Vendor',
        ],
        'ai_salesgirl_full' => [
            'title' => 'Full AI Salesgirl settings',
            'description' => 'Escalation rules, owner alerts, and WhatsApp handoff controls.',
            'min_plan' => 'Vendor',
        ],
        'ai_negotiation' => [
            'title' => 'Price negotiation',
            'description' => 'Train discount limits, objection handling, upsells, and closing rules.',
            'min_plan' => 'Vendor',
        ],
        'proactive_followups' => [
            'title' => 'Cold lead follow-ups',
            'description' => 'Automatically re-engage buyers who stop replying.',
            'min_plan' => 'Shop',
        ],
        'payment_receipts' => [
            'title' => 'Payment receipts',
            'description' => 'Send a receipt message after a customer says they paid.',
            'min_plan' => 'Shop',
        ],
        'channel_performance' => [
            'title' => 'Channel performance',
            'description' => 'Compare Instagram, Messenger, and WhatsApp message volume.',
            'min_plan' => 'Vendor',
        ],
        'social_media' => [
            'title' => 'Planner',
            'description' => 'Create posts with AI, schedule content, and publish to Instagram and Facebook from one place.',
            'min_plan' => 'Vendor',
        ],
        'broadcasts' => [
            'title' => 'Bulk WhatsApp Messaging',
            'description' => 'Send bulk WhatsApp messages to all your opted-in customers, then let Hannah answer the replies.',
            'min_plan' => 'Vendor',
        ],
        'billing_history' => [
            'title' => 'Billing history',
            'description' => 'View past invoices and payment records.',
            'min_plan' => 'Shop',
        ],
        'team_members' => [
            'title' => 'Team members',
            'description' => 'Invite teammates to help manage your business.',
            'min_plan' => 'Enterprise',
        ],
        'api_access' => [
            'title' => 'API access',
            'description' => 'Connect Lead Bird to your own tools and workflows.',
            'min_plan' => 'Enterprise',
        ],
    ];

    public static function allows(string $planSlug, string $feature): bool
    {
        $allowed = self::FEATURE_PLANS[$feature] ?? [];

        return in_array($planSlug, $allowed, true);
    }

    /**
     * @return array{title: string, description: string, min_plan: string}
     */
    public static function lockedMeta(string $feature): array
    {
        return self::LOCKED_COPY[$feature] ?? [
            'title' => 'This feature',
            'description' => 'Upgrade your plan to unlock this area.',
            'min_plan' => 'Vendor',
        ];
    }

    public static function minimumPlanSlugFor(string $feature): string
    {
        $meta = self::lockedMeta($feature);

        return match ($meta['min_plan']) {
            'Starter' => 'starter',
            'Vendor' => 'vendor',
            'Shop' => 'shop',
            'Enterprise' => 'enterprise',
            default => 'vendor',
        };
    }
}
