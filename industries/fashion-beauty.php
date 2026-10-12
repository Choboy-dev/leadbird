<?php

declare(strict_types=1);

$ind = [
    'slug' => 'fashion-beauty',
    'name' => 'Fashion & Beauty',
    'title' => 'Sell Clothes, Shoes and Beauty Products on WhatsApp',
    'description' => 'Share your products, answer price, size and delivery questions, and send payment details on WhatsApp, Instagram and Messenger with an AI salesgirl that replies day and night.',
    'h1' => 'Grow your fashion and beauty sales on WhatsApp',
    'lede' => 'Show your products, answer every “How much?” and send payment details, all in the chats your customers already use.',
    'clients' => 'Built for <strong>boutiques, shoe sellers and beauty brands</strong> that sell in the DMs',
    'band' => 'Turn more DMs into paid orders with WhatsApp',
    'cta' => 'Your customers already chat on WhatsApp and Instagram. Turn those chats into sales with Lead Bird.',
    'heroImage' => ['industries-fashion-hero.webp', 1024, 811],
    'hero' => [
        'store' => 'Amara Styles',
        'photo' => 'red-gown.jpg',
        'product' => 'Red satin gown',
        'price' => '₦45,000',
        'ask' => 'Is the red gown available in size 12?',
        'reply' => 'Yes! Size 12 is available 🎉',
        'alert' => 'Ada is ready to pay for the red gown',
    ],
    'features' => [
        ['<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>', 'Share your catalogue', 'Add your clothes, shoes and beauty products with photos and prices, and Hannah, your AI salesgirl, shares the right one when a customer asks.'],
        ['<path d="M13 2.5 5 13.5h6l-1 8 8-11h-6z"/>', 'Instant answers, day and night', 'Hannah answers questions about prices, sizes, colours and delivery in seconds, even at 2am, so buyers don’t go elsewhere.'],
        ['<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 9.5h19M6.5 15h4"/>', 'Payment details sent fast', 'When a customer is ready to buy, Hannah sends your account number or payment details right in the chat.'],
        ['<circle cx="12" cy="13" r="7.5"/><path d="M12 9v4l2.5 2M4 5l3-2.5M20 5l-3-2.5"/>', 'Follow-ups for quiet buyers', 'Hannah checks back with customers who stopped replying, on your schedule, so fewer sales slip away.'],
        ['<path d="M4 4h16v12H8l-4 4z"/><path d="M8.5 10l2.5 2.5 4.5-4.5"/>', 'Every chat in one inbox', 'Messages from WhatsApp, Instagram and Messenger land in one inbox, and you get a WhatsApp alert when a person is needed.'],
        ['<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4M8 14h3"/>', 'Plan your new arrival posts', 'Schedule posts for Instagram and Facebook ahead of time, so new collections go out while you focus on orders.'],
    ],
    'rows' => [
        [
            'Sell right inside the chat', 'Show, answer and close the sale without leaving WhatsApp.',
            [
                'Hannah shares photos and prices from your catalogue, so customers see exactly what they’re buying.',
                'She answers size, colour and delivery questions, then sends your payment details when they’re ready.',
            ],
            'surface', 'arrows',
            [
                ['out', '<span>Hi! Do you have the gold sandals in size 39?</span>'],
                ['in', '<b>Gold strap sandals</b><span>Yes! Size 39 is available. ₦18,500 with delivery in Lagos.</span>', ['Send payment details'], 'gold-sandals.jpg'],
                ['out', '<span>Perfect, I’ll take them</span>'],
            ],
        ],
        [
            'Less manual work for you', 'Hannah handles the questions you answer fifty times a day.',
            [
                'Teach her your prices, sizes, delivery fees and return rules once, and she uses them in every reply.',
                'She follows up with buyers who went quiet, so you spend less time chasing chats.',
            ],
            'blue', 'plus',
            [
                ['in', '<b>Shea body butter</b><span>Hi Tolu! Still thinking about the body butter? It’s ₦9,000 and ready to ship today.</span>', ['Send payment details'], 'shea-body-butter.jpg'],
                ['out', '<span>Yes please, send it</span>'],
                ['in', '<span>Great! Here are the account details. Send your receipt once you’ve paid 🙏</span>'],
            ],
        ],
        [
            'Fits the way you sell', 'Whether you sell from a shop, a page or your WhatsApp status.',
            [
                'Customers can reach you on WhatsApp, Instagram or Messenger, and every chat lands in one inbox.',
                'Hannah answers common questions herself and alerts you on WhatsApp when a customer needs a person.',
            ],
            'lime', 'cross',
            [
                ['out', '<span>Do you deliver to Abuja?</span>'],
                ['in', '<span>Yes! Delivery to Abuja takes 2–3 days and costs ₦4,500.</span>', ['Talk to a person']],
                ['out', '<span>Talk to a person</span>'],
                ['in', '<span>Sure! I’ve let Amara know, and she’ll reply here shortly.</span>'],
            ],
        ],
    ],
];

require dirname(__DIR__) . '/includes/industries/page.php';
