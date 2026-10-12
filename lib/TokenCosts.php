<?php

declare(strict_types=1);

/**
 * Fixed product-unit costs for the shared business token pool.
 * 1 token ≈ one base unit of work (e.g. one AI inbox reply).
 */
final class TokenCosts
{
    public const DM_REPLY = 1;
    public const AI_TEST_CHAT = 1;
    public const SOCIAL_CAPTION = 2;
    public const SOCIAL_HASHTAGS = 1;
    public const DASHBOARD_ASSISTANT = 2;
    public const KNOWLEDGE_AI_REFRESH = 5;
    public const SOCIAL_PUBLISH = 1;
    public const BROADCAST_RECIPIENT = 1;
    public const BROADCAST_DRAFT = 1;

    public static function depletedMessage(): string
    {
        return 'You are out of tokens. Top up on Billing, or wait for your next plan renewal.';
    }

    public static function insufficientMessage(int $needed): string
    {
        if ($needed <= 1) {
            return self::depletedMessage();
        }

        return 'You need ' . $needed . ' tokens for this action. Top up on Billing, or wait for your next plan renewal.';
    }
}
