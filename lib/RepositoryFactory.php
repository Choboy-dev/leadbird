<?php

declare(strict_types=1);

/**
 * Central access to MySQL repositories (single PDO connection).
 */
final class RepositoryFactory
{
    private static ?PDO $db = null;

    private static function db(): PDO
    {
        return self::$db ??= Database::connection();
    }

    public static function users(): UserRepository
    {
        return new UserRepository(self::db());
    }

    public static function emailOtps(): EmailOtpRepository
    {
        return new EmailOtpRepository(self::db());
    }

    public static function oauthStates(): OauthStateRepository
    {
        return new OauthStateRepository(self::db());
    }

    public static function businesses(): BusinessRepository
    {
        return new BusinessRepository(self::db());
    }

    public static function businessSettings(): BusinessSettingsRepository
    {
        return new BusinessSettingsRepository(self::db());
    }

    public static function products(): ProductRepository
    {
        return new ProductRepository(self::db());
    }

    public static function offerings(): OfferingRepository
    {
        return new OfferingRepository(self::db());
    }

    public static function connectedAccounts(): ConnectedAccountRepository
    {
        return new ConnectedAccountRepository(self::db());
    }

    public static function customers(): CustomerRepository
    {
        return new CustomerRepository(self::db());
    }

    public static function conversations(): ConversationRepository
    {
        return new ConversationRepository(self::db());
    }

    public static function conversationFollowups(): ConversationFollowupRepository
    {
        return new ConversationFollowupRepository(self::db());
    }

    public static function inboxLabels(): InboxLabelRepository
    {
        return new InboxLabelRepository(self::db());
    }

    public static function messages(): MessageRepository
    {
        return new MessageRepository(self::db());
    }

    public static function orders(): OrderRepository
    {
        return new OrderRepository(self::db());
    }

    public static function orderItems(): OrderItemRepository
    {
        return new OrderItemRepository(self::db());
    }

    public static function ownerAlerts(): OwnerAlertRepository
    {
        return new OwnerAlertRepository(self::db());
    }

    public static function handoffs(): HandoffRepository
    {
        return new HandoffRepository(self::db());
    }

    public static function subscriptions(): SubscriptionRepository
    {
        return new SubscriptionRepository(self::db());
    }

    public static function paymentMethods(): PaymentMethodRepository
    {
        return new PaymentMethodRepository(self::db());
    }

    public static function billingProfiles(): BillingProfileRepository
    {
        return new BillingProfileRepository(self::db());
    }

    public static function automationQueue(): AutomationQueueRepository
    {
        return new AutomationQueueRepository(self::db());
    }

    public static function tokenBalances(): TokenBalanceRepository
    {
        return new TokenBalanceRepository(self::db());
    }

    public static function tokenLedger(): TokenLedgerRepository
    {
        return new TokenLedgerRepository(self::db());
    }

    public static function tokenTopups(): TokenTopupRepository
    {
        return new TokenTopupRepository(self::db());
    }

    public static function businessKnowledge(): BusinessKnowledgeRepository
    {
        return new BusinessKnowledgeRepository(self::db());
    }

    public static function knowledgeLinks(): KnowledgeLinkRepository
    {
        return new KnowledgeLinkRepository(self::db());
    }

    public static function scheduledPosts(): ScheduledPostRepository
    {
        return new ScheduledPostRepository(self::db());
    }

    public static function openAiUsage(): OpenAiUsageRepository
    {
        return new OpenAiUsageRepository(self::db());
    }

    public static function affiliates(): AffiliateRepository
    {
        return new AffiliateRepository(self::db());
    }

    public static function affiliateCommissions(): AffiliateCommissionRepository
    {
        return new AffiliateCommissionRepository(self::db());
    }

    public static function affiliatePayouts(): AffiliatePayoutRepository
    {
        return new AffiliatePayoutRepository(self::db());
    }

    public static function businessGoogleCalendar(): BusinessGoogleCalendarRepository
    {
        return new BusinessGoogleCalendarRepository(self::db());
    }

    public static function businessMetaConnections(): BusinessMetaConnectionRepository
    {
        return new BusinessMetaConnectionRepository(self::db());
    }

    public static function metaDeletionRequests(): MetaDeletionRequestRepository
    {
        return new MetaDeletionRequestRepository(self::db());
    }

    public static function consultationAppointments(): ConsultationAppointmentRepository
    {
        return new ConsultationAppointmentRepository(self::db());
    }

    public static function conversationBookingState(): ConversationBookingStateRepository
    {
        return new ConversationBookingStateRepository(self::db());
    }

    public static function admin(): AdminRepository
    {
        return new AdminRepository(self::db());
    }

    public static function adminAuditLog(): AdminAuditLogRepository
    {
        return new AdminAuditLogRepository(self::db());
    }

    public static function walletDisputes(): WalletDisputeRepository
    {
        return new WalletDisputeRepository(self::db());
    }
}
