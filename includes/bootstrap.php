<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = str_starts_with($config['app_url'], 'https://');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $secure,
    ]);
    session_name($config['auth']['session_cookie']);
    session_start();
}

require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/Security.php';
require_once dirname(__DIR__) . '/lib/Uuid.php';
require_once dirname(__DIR__) . '/lib/Database.php';
require_once dirname(__DIR__) . '/lib/OtpEmailTemplate.php';
require_once dirname(__DIR__) . '/lib/ResendClient.php';
require_once dirname(__DIR__) . '/lib/MailHtml.php';
require_once dirname(__DIR__) . '/lib/Mail.php';
require_once dirname(__DIR__) . '/lib/repositories/BaseRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/UserRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/EmailOtpRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/OauthStateRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/BusinessRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/BusinessSettingsRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/ProductRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/OfferingRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/ConnectedAccountRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/CustomerRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/ConversationRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/ConversationFollowupRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/InboxLabelRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/MessageRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/OrderRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/OrderItemRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/OwnerAlertRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/HandoffRepository.php';
require_once dirname(__DIR__) . '/lib/RepositoryFactory.php';
require_once dirname(__DIR__) . '/lib/UserService.php';
require_once dirname(__DIR__) . '/lib/GoogleAuthService.php';
require_once dirname(__DIR__) . '/lib/OtpService.php';
require_once dirname(__DIR__) . '/lib/SessionAuth.php';
require_once dirname(__DIR__) . '/lib/BusinessContext.php';
require_once dirname(__DIR__) . '/lib/OnboardingService.php';
require_once dirname(__DIR__) . '/lib/PostAuthRedirect.php';
require_once dirname(__DIR__) . '/config/money.php';
require_once dirname(__DIR__) . '/includes/money.php';
require_once dirname(__DIR__) . '/lib/FxRateService.php';
require_once dirname(__DIR__) . '/lib/CheckoutPricing.php';
require_once dirname(__DIR__) . '/lib/PlanService.php';
require_once dirname(__DIR__) . '/lib/PlanAccess.php';
require_once dirname(__DIR__) . '/lib/SalesAutomationService.php';
require_once dirname(__DIR__) . '/lib/PlanSelection.php';
require_once dirname(__DIR__) . '/lib/ReferralSelection.php';
require_once dirname(__DIR__) . '/lib/FlutterwaveService.php';
require_once dirname(__DIR__) . '/lib/SubscriptionService.php';
require_once dirname(__DIR__) . '/lib/RenewalNotice.php';
require_once dirname(__DIR__) . '/lib/SubscriptionRenewalRunner.php';
require_once dirname(__DIR__) . '/lib/repositories/PaymentMethodRepository.php';
require_once dirname(__DIR__) . '/lib/PaymentMethodService.php';
require_once dirname(__DIR__) . '/lib/repositories/BillingProfileRepository.php';
require_once dirname(__DIR__) . '/lib/BillingProfileService.php';
require_once dirname(__DIR__) . '/lib/PlanLimits.php';
require_once dirname(__DIR__) . '/lib/TokenCosts.php';
require_once dirname(__DIR__) . '/lib/DashboardService.php';
require_once dirname(__DIR__) . '/lib/repositories/SubscriptionRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/TokenBalanceRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/TokenLedgerRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/TokenTopupRepository.php';
require_once dirname(__DIR__) . '/lib/TokenService.php';
require_once dirname(__DIR__) . '/lib/TopupService.php';
require_once dirname(__DIR__) . '/config/token-topups.php';
require_once dirname(__DIR__) . '/lib/repositories/AutomationQueueRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/BusinessKnowledgeRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/KnowledgeLinkRepository.php';
require_once dirname(__DIR__) . '/lib/BusinessKnowledgeSyncService.php';
require_once dirname(__DIR__) . '/lib/WebsiteCrawlService.php';
require_once dirname(__DIR__) . '/lib/PdfTextExtractor.php';
require_once dirname(__DIR__) . '/lib/KnowledgeTrainingService.php';
require_once dirname(__DIR__) . '/lib/BusinessKnowledgeRetrievalService.php';
require_once dirname(__DIR__) . '/lib/KnowledgeEmbeddingService.php';
require_once dirname(__DIR__) . '/lib/KnowledgeImageMatchService.php';
require_once dirname(__DIR__) . '/lib/KnowledgeLinkService.php';
require_once dirname(__DIR__) . '/lib/ConversationLearningService.php';
require_once dirname(__DIR__) . '/lib/BusinessKnowledgeStatsService.php';
require_once dirname(__DIR__) . '/lib/MetaConnectService.php';
require_once dirname(__DIR__) . '/lib/TikTokConnectService.php';
require_once dirname(__DIR__) . '/lib/BusinessDeletionService.php';
require_once dirname(__DIR__) . '/lib/repositories/AffiliateRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/AffiliateCommissionRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/AffiliatePayoutRepository.php';
require_once dirname(__DIR__) . '/lib/AffiliateService.php';
require_once dirname(__DIR__) . '/lib/CommissionService.php';
require_once dirname(__DIR__) . '/lib/PayoutService.php';
require_once dirname(__DIR__) . '/lib/PaymentSetupService.php';
require_once dirname(__DIR__) . '/lib/OfferingImageService.php';
require_once dirname(__DIR__) . '/lib/OfferingTypeRegistry.php';
require_once dirname(__DIR__) . '/lib/OfferingInputParser.php';
require_once dirname(__DIR__) . '/lib/GoogleCalendarService.php';
require_once dirname(__DIR__) . '/lib/GoogleCalendarConnectService.php';
require_once dirname(__DIR__) . '/lib/ConsultationBookingService.php';
require_once dirname(__DIR__) . '/lib/repositories/BusinessGoogleCalendarRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/BusinessMetaConnectionRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/MetaDeletionRequestRepository.php';
require_once dirname(__DIR__) . '/lib/MetaDataDeletionService.php';
require_once dirname(__DIR__) . '/lib/repositories/ConsultationAppointmentRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/ConversationBookingStateRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/ScheduledPostRepository.php';
require_once dirname(__DIR__) . '/lib/SocialMediaImageService.php';
require_once dirname(__DIR__) . '/lib/MetaPublishService.php';
require_once dirname(__DIR__) . '/lib/TikTokPublishService.php';
require_once dirname(__DIR__) . '/lib/SocialMediaAiService.php';
require_once dirname(__DIR__) . '/lib/SocialMediaService.php';
require_once dirname(__DIR__) . '/lib/InboxLabelService.php';
require_once dirname(__DIR__) . '/lib/InboxConversationService.php';
require_once dirname(__DIR__) . '/lib/AutomationQueue.php';
require_once dirname(__DIR__) . '/lib/repositories/AdminAuditLogRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/AdminRepository.php';
require_once dirname(__DIR__) . '/lib/repositories/WalletDisputeRepository.php';
require_once dirname(__DIR__) . '/lib/AdminAuth.php';
require_once dirname(__DIR__) . '/lib/AdminAuditService.php';
require_once dirname(__DIR__) . '/lib/AdminService.php';
require_once dirname(__DIR__) . '/lib/repositories/BroadcastRepository.php';
require_once dirname(__DIR__) . '/lib/WhatsAppBroadcastService.php';
require_once dirname(__DIR__) . '/lib/WhatsAppStarterTemplates.php';
require_once dirname(__DIR__) . '/lib/WhatsAppTemplateLibrary.php';
require_once dirname(__DIR__) . '/includes/brand-ai-icon.php';
