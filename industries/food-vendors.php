<?php

declare(strict_types=1);

$ind = [
    'slug' => 'food-vendors',
    'name' => 'Food & Small Vendors',
    'title' => 'Take More Food Orders on WhatsApp',
    'description' => 'Share your menu, answer price and delivery questions, and send payment details on WhatsApp, Instagram and Messenger with an AI salesgirl that replies day and night.',
    'h1' => 'Take more food orders on WhatsApp',
    'lede' => 'Share your menu, answer every “How much?” and send payment details, all in the chats your customers already use.',
    'clients' => 'Built for <strong>caterers, food vendors and small shops</strong> that sell in the DMs',
    'band' => 'Turn more DMs into paid orders with WhatsApp',
    'cta' => 'Your customers already chat on WhatsApp and Instagram. Turn those chats into orders with Lead Bird.',
    'hero' => [
        'store' => 'Mama Tee’s Kitchen',
        'photo' => 'jollof-rice-tray.jpg',
        'product' => 'Party jollof tray',
        'price' => '₦35,000',
        'ask' => 'Can I get a jollof tray for Saturday?',
        'reply' => 'Yes! Saturday is still open 🎉',
        'alert' => 'Bisi is ready to pay for a jollof tray',
    ],
    'features' => [
        ['<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>', 'Share your menu', 'Add your trays, platters and packs with photos and prices, and Hannah, your AI salesgirl, shares the right one when a customer asks.'],
        ['<path d="M13 2.5 5 13.5h6l-1 8 8-11h-6z"/>', 'Instant answers, day and night', 'Hannah answers questions about prices, portion sizes and delivery in seconds, even while you’re busy cooking.'],
        ['<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 9.5h19M6.5 15h4"/>', 'Payment details sent fast', 'When a customer is ready to order, Hannah sends your account number or payment details right in the chat.'],
        ['<circle cx="12" cy="13" r="7.5"/><path d="M12 9v4l2.5 2M4 5l3-2.5M20 5l-3-2.5"/>', 'Follow-ups for quiet buyers', 'Hannah checks back with customers who stopped replying, on your schedule, so fewer orders slip away.'],
        ['<path d="M4 4h16v12H8l-4 4z"/><path d="M8.5 10l2.5 2.5 4.5-4.5"/>', 'Every chat in one inbox', 'Messages from WhatsApp, Instagram and Messenger land in one inbox, and you get a WhatsApp alert when a person is needed.'],
        ['<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4M8 14h3"/>', 'Plan your menu posts', 'Schedule posts for Instagram and Facebook ahead of time, so weekend specials go out while you focus on orders.'],
    ],
    'rows' => [
        [
            'Take orders right inside the chat', 'Show, answer and confirm the order without leaving WhatsApp.',
            [
                'Hannah shares photos and prices from your menu, so customers see exactly what they’re ordering.',
                'She answers portion, date and delivery questions, then sends your payment details when they’re ready.',
            ],
            'surface', 'arrows',
            [
                ['out', '<span>How much is the small chops tray?</span>'],
                ['in', '<b>Small chops tray</b><span>It’s ₦25,000 and serves 20 guests. Delivery in Lagos is ₦3,000.</span>', ['Send payment details'], 'small-chops-tray.jpg'],
                ['out', '<span>I’ll take one for Friday</span>'],
            ],
        ],
        [
            'Less manual work for you', 'Hannah handles the questions you answer fifty times a day.',
            [
                'Teach her your menu, prices, delivery areas and order cut-off times once, and she uses them in every reply.',
                'She follows up with buyers who went quiet, so you spend less time chasing chats.',
            ],
            'blue', 'plus',
            [
                ['in', '<b>Fried rice tray</b><span>Hi Kunle! Still want the fried rice tray for Sunday? It’s ₦30,000.</span>', ['Send payment details'], 'fried-rice-tray.jpg'],
                ['out', '<span>Yes please, book it</span>'],
                ['in', '<span>Great! Here are the account details. Send your receipt once you’ve paid 🙏</span>'],
            ],
        ],
        [
            'Fits the way you sell', 'Whether you sell from a kitchen, a stall or your WhatsApp status.',
            [
                'Customers can reach you on WhatsApp, Instagram or Messenger, and every chat lands in one inbox.',
                'Hannah answers common questions herself and alerts you on WhatsApp when a customer needs a person.',
            ],
            'lime', 'cross',
            [
                ['out', '<span>Can you cater for 150 guests?</span>'],
                ['in', '<b>Asun platter</b><span>We’d love to! For large events, Tee will confirm the menu with you.</span>', ['Talk to a person'], 'asun-platter.jpg'],
                ['out', '<span>Talk to a person</span>'],
                ['in', '<span>Sure! I’ve let Tee know, and she’ll reply here shortly.</span>'],
            ],
        ],
    ],
];

require dirname(__DIR__) . '/includes/industries/page.php';
