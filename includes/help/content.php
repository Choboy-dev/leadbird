<?php

declare(strict_types=1);

/**
 * Lead Bird Help Center content (PayPal-style topics, sections, articles).
 *
 * @return array{
 *     eyebrow: string,
 *     title: string,
 *     topics: array<string, array{
 *         label: string,
 *         slug: string,
 *         sections: list<array{
 *             title: string,
 *             slug: string,
 *             articles: list<array{slug: string, title: string, body: string}>
 *         }>
 *     }>,
 *     recommended: list<array{topic: string, slug: string}>,
 *     more_ways: list<array{title: string, description: string, href: string, icon: string}>
 * }
 */
function help_center_content(): array
{
    $guideUrl = app_route('guides/connect-meta-channels', ['return_to' => app_route('connected-accounts')]);

    $topics = [
        'channels' => [
            'label' => 'Channels',
            'slug' => 'channels',
            'sections' => [
                [
                    'title' => 'Connecting channels',
                    'slug' => 'connecting',
                    'articles' => [
                        [
                            'slug' => 'connect-instagram-facebook-whatsapp',
                            'title' => 'How do I connect Instagram, Facebook, or WhatsApp?',
                            'body' => 'Open <a href="' . e($guideUrl) . '">Connect Meta channels</a> or go to <a href="' . e(app_route('connected-accounts')) . '">Social Accounts</a>. Link Instagram to your Facebook Page in Meta first, then authorize Lead Bird for each channel you want live.',
                        ],
                        [
                            'slug' => 'channel-shows-offline',
                            'title' => 'Why does a channel show Offline after connecting?',
                            'body' => 'The token may have expired or Meta permissions changed. Open Social Accounts and tap Reconnect on that channel. If it keeps failing, disconnect and connect again from a desktop browser logged into the correct Meta account.',
                        ],
                        [
                            'slug' => 'multiple-businesses-or-channels',
                            'title' => 'Can I run more than one business or channel?',
                            'body' => 'Yes — depending on your plan. Use the business switcher in the dashboard header to change active business, then connect channels per business from Social Accounts. Upgrade in <a href="' . e(app_route('billing')) . '">Billing</a> if you hit limits.',
                        ],
                    ],
                ],
                [
                    'title' => 'Publishing & DMs',
                    'slug' => 'publishing',
                    'articles' => [
                        [
                            'slug' => 'dms-vs-feed-posts',
                            'title' => 'Does Lead Bird post on my feed or only reply in DMs?',
                            'body' => 'Your AI Salesgirl replies in direct messages on all plans. On the Vendor plan and above, Social Media Management lets you create, schedule, and publish posts to connected Instagram and Facebook accounts from your dashboard.',
                        ],
                    ],
                ],
            ],
        ],
        'ai-salesgirl' => [
            'label' => 'AI Salesgirl',
            'slug' => 'ai-salesgirl',
            'sections' => [
                [
                    'title' => 'How she works',
                    'slug' => 'basics',
                    'articles' => [
                        [
                            'slug' => 'what-does-ai-salesgirl-do',
                            'title' => 'What does the AI Salesgirl actually do?',
                            'body' => 'She reads inbound DMs on your connected channels, answers using your catalog, FAQs, policies, and payment instructions, and can guide customers toward checkout. You stay in control via <a href="' . e(app_route('ai-salesgirl')) . '">AI Salesgirl</a> settings and the inbox.',
                        ],
                        [
                            'slug' => 'will-customers-know-its-ai',
                            'title' => 'Will customers know they are talking to AI?',
                            'body' => 'You choose how she introduces herself. Many sellers use a friendly assistant name and tone so replies feel personal. You can make it explicit in your greeting if your industry requires disclosure.',
                        ],
                    ],
                ],
                [
                    'title' => 'Training & knowledge',
                    'slug' => 'knowledge',
                    'articles' => [
                        [
                            'slug' => 'how-she-learns',
                            'title' => 'How does she learn about my products and business?',
                            'body' => 'Add offers, FAQs, and policies under <a href="' . e(app_route('offers')) . '">Offers &amp; Catalog</a>. Sync your knowledge base on the AI Salesgirl page so she pulls the latest catalog and business details into every reply.',
                        ],
                        [
                            'slug' => 'change-tone-name-greeting',
                            'title' => 'Can I change her tone, name, and greeting?',
                            'body' => 'Yes. On <a href="' . e(app_route('ai-salesgirl')) . '">AI Salesgirl</a>, set the AI name, tone, and opening greeting. Use the test playground to preview replies before customers see them.',
                        ],
                        [
                            'slug' => 'selling-rules',
                            'title' => 'How do I train negotiation, follow-ups, and receipts?',
                            'body' => 'Open <a href="' . e(app_route('ai-salesgirl', ['view' => 'selling'])) . '">AI Salesgirl → Selling</a>. Vendor unlocks discount limits, objection notes, and upsells. Shop adds cold-lead follow-up timing and payment receipts.',
                        ],
                        [
                            'slug' => 'when-she-does-not-know',
                            'title' => 'What if she does not know the answer?',
                            'body' => 'She can pause and alert you based on rules you set under “When to call the owner.” Add the answer in your FAQs or reply once in the inbox — synced knowledge helps her handle it next time.',
                        ],
                    ],
                ],
            ],
        ],
        'billing' => [
            'label' => 'Billing & tokens',
            'slug' => 'billing',
            'sections' => [
                [
                    'title' => 'Tokens',
                    'slug' => 'tokens',
                    'articles' => [
                        [
                            'slug' => 'how-reply-tokens-work',
                            'title' => 'How do tokens work?',
                            'body' => 'Your plan includes a monthly token pool for AI replies, social AI writing, publishing posts, and other AI features. Unused tokens expire when your plan renews. View your balance and top up anytime on <a href="' . e(app_route('billing#tokens')) . '">Billing → Tokens</a>.',
                        ],
                        [
                            'slug' => 'out-of-tokens',
                            'title' => 'What happens when I run out of tokens?',
                            'body' => 'AI replies, AI writing, and publishing pause until you top up or your plan renews. Inbound messages are still saved — you can reply manually from the inbox.',
                        ],
                    ],
                ],
                [
                    'title' => 'Plans & payment methods',
                    'slug' => 'plans',
                    'articles' => [
                        [
                            'slug' => 'upgrade-or-change-plan',
                            'title' => 'How do I upgrade or change my plan?',
                            'body' => 'Open <a href="' . e(app_route('billing')) . '">Billing</a>, compare tiers, and use the upgrade or downgrade button on the plan you want. Changes apply to your subscription and plan limits immediately after payment.',
                        ],
                        [
                            'slug' => 'add-or-remove-card',
                            'title' => 'How do I add or remove a payment card?',
                            'body' => 'On Billing, use Add payment method to save a card for renewals. Remove old cards from the saved list — your default card is charged on renewal date shown on the plan card.',
                        ],
                    ],
                ],
            ],
        ],
        'customers' => [
            'label' => 'Customers & inbox',
            'slug' => 'customers',
            'sections' => [
                [
                    'title' => 'Inbox',
                    'slug' => 'inbox',
                    'articles' => [
                        [
                            'slug' => 'reply-manually',
                            'title' => 'Can I reply manually instead of the AI?',
                            'body' => 'Yes. Use <a href="' . e(app_route('inbox')) . '">Inbox</a> to read threads and send replies yourself. AI automation and manual replies share the same conversation history.',
                        ],
                    ],
                ],
                [
                    'title' => 'Customer records',
                    'slug' => 'records',
                    'articles' => [
                        [
                            'slug' => 'dm-becomes-customer',
                            'title' => 'How does a DM become a customer record?',
                            'body' => 'When someone messages a connected channel, Lead Bird creates a conversation and customer profile. View everyone in <a href="' . e(app_route('customers')) . '">Customers</a> with channel and last message.',
                        ],
                    ],
                ],
            ],
        ],
        'catalog' => [
            'label' => 'Catalog & offers',
            'slug' => 'catalog',
            'sections' => [
                [
                    'title' => 'Offers & FAQs',
                    'slug' => 'offers',
                    'articles' => [
                        [
                            'slug' => 'add-products-or-services',
                            'title' => 'How do I add products or services?',
                            'body' => 'Go to <a href="' . e(app_route('offers')) . '">Offers &amp; Catalog</a>, open the Catalog tab, and create offerings with name, price, type, and images. Sync knowledge on AI Salesgirl after big catalog changes.',
                        ],
                        [
                            'slug' => 'faqs-and-policies',
                            'title' => 'What are FAQs and policies for?',
                            'body' => 'FAQs answer repeat questions (shipping, sizing, hours). Policies cover returns, delivery, and terms. Both feed the AI so she quotes accurate info instead of guessing.',
                        ],
                        [
                            'slug' => 'customer-payments',
                            'title' => 'How do customers pay me through the Salesgirl?',
                            'body' => 'Set payment instructions in onboarding or <a href="' . e(app_route('onboarding/payment')) . '">Payment settings</a> — bank transfer, mobile money, or links. The AI shares only what you configure.',
                        ],
                    ],
                ],
            ],
        ],
        'account' => [
            'label' => 'Account & alerts',
            'slug' => 'account',
            'sections' => [
                [
                    'title' => 'Profile & notifications',
                    'slug' => 'profile',
                    'articles' => [
                        [
                            'slug' => 'owner-alerts',
                            'title' => 'When will the owner be alerted?',
                            'body' => 'Configure triggers in AI Salesgirl or <a href="' . e(app_route('onboarding/alerts')) . '">Notification settings</a> — for example when the AI is unsure, a customer is ready to buy, or a customer asks for a human.',
                        ],
                        [
                            'slug' => 'update-business-profile',
                            'title' => 'How do I update my business or owner profile?',
                            'body' => 'Open <a href="' . e(app_route('settings')) . '">Settings</a> → Business profile or Owner profile, or edit during onboarding. Keep description and contact details current for better AI answers.',
                        ],
                    ],
                ],
                [
                    'title' => 'Closing your account',
                    'slug' => 'closing',
                    'articles' => [
                        [
                            'slug' => 'delete-account',
                            'title' => 'How do I delete my account?',
                            'body' => 'Settings → Delete account. This permanently removes businesses, channels, messages, and billing data. Export anything you need from Customers and Inbox first.',
                        ],
                    ],
                ],
            ],
        ],
    ];

    return [
        'eyebrow' => 'Help Center — Lead Bird Account',
        'title' => 'How can we help?',
        'topics' => $topics,
        'recommended' => [
            ['topic' => 'channels', 'slug' => 'connect-instagram-facebook-whatsapp'],
            ['topic' => 'channels', 'slug' => 'channel-shows-offline'],
            ['topic' => 'billing', 'slug' => 'how-reply-tokens-work'],
            ['topic' => 'billing', 'slug' => 'out-of-tokens'],
            ['topic' => 'ai-salesgirl', 'slug' => 'what-does-ai-salesgirl-do'],
            ['topic' => 'customers', 'slug' => 'reply-manually'],
            ['topic' => 'billing', 'slug' => 'upgrade-or-change-plan'],
            ['topic' => 'ai-salesgirl', 'slug' => 'how-she-learns'],
        ],
        'more_ways' => [
            [
                'title' => 'Contact support',
                'description' => 'Billing, connections, or setup questions',
                'href' => app_route('contact'),
                'icon' => 'contact',
            ],
            [
                'title' => 'Ask your Salesgirl',
                'description' => 'Test replies and tune your AI assistant',
                'href' => app_route('ai-salesgirl'),
                'icon' => 'ai',
            ],
            [
                'title' => 'Social Accounts',
                'description' => 'Connect or reconnect Instagram, Facebook, WhatsApp',
                'href' => app_route('connected-accounts'),
                'icon' => 'channels',
            ],
            [
                'title' => 'Billing & plans',
                'description' => 'Upgrade, tokens, and payment methods',
                'href' => app_route('billing'),
                'icon' => 'billing',
            ],
            [
                'title' => 'Privacy policy',
                'description' => 'How we handle your data',
                'href' => app_route('privacy'),
                'icon' => 'policy',
            ],
        ],
    ];
}
