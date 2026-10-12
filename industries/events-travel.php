<?php

declare(strict_types=1);

$ind = [
    'slug' => 'events-travel',
    'name' => 'Events & Travel',
    'title' => 'Sell Tickets and Trips on WhatsApp',
    'description' => 'Share event details and travel packages, answer questions about dates, prices and venues, and send ticket links and payment details on WhatsApp, Instagram and Messenger with an AI salesgirl that replies day and night.',
    'h1' => 'Sell more tickets and trips on WhatsApp',
    'lede' => 'Share event details and travel packages, answer every question and send ticket links or payment details, all in the chats people already use.',
    'clients' => 'Built for <strong>event organisers, venues and travel agents</strong> that sell in the DMs',
    'band' => 'Turn more DMs into ticket sales and bookings with WhatsApp',
    'cta' => 'Your guests and travellers already chat on WhatsApp and Instagram. Turn those chats into sales with Lead Bird.',
    'quotesLede' => 'How businesses use Lead Bird to answer customers on WhatsApp and Instagram.',
    'heroVisual' => 'booking',
    'heroImage' => ['industries-events-hero.webp', 740, 740],
    'hero' => [
        'icon' => 'ticket',
        'store' => 'Afro Nights Live',
        'ask' => 'Are tickets still available for Saturday?',
        'reply' => 'Yes! Which ticket would you like?',
        'slots' => 'Regular ₦10,000|VIP ₦25,000|Table for 6',
        'pick' => 'VIP ₦25,000',
        'booked' => 'Great choice! Here’s your ticket link 🎟️',
        'day' => 'Sat 19',
        'time' => 'Eko Hall, 6:00pm',
        'badge' => 'VIP ✓',
        'alert' => 'Tobi is buying a VIP ticket',
    ],
    'features' => [
        ['<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/><circle cx="12" cy="15.5" r="2"/>', 'Event details on demand', 'Add your events with the date, venue and ticket prices, and Hannah, your AI salesgirl, shares them the moment someone asks.'],
        ['<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6.5v11"/>', 'Ticket links sent fast', 'When a guest is ready to buy, Hannah sends your ticket link or your payment details right in the chat.'],
        ['<path d="M2.5 16.5 21 7.5l-2-2-6.5 2.5-5-2.5-2 1 3.5 3.5-4 2-2-1-1 1z"/><path d="M3 20.5h18"/>', 'Travel packages with photos', 'Add your trips and packages with photos and prices, and Hannah shares the right one when a traveller asks.'],
        ['<path d="M13 2.5 5 13.5h6l-1 8 8-11h-6z"/>', 'Instant answers, day and night', 'Hannah answers questions about dates, prices, venues, visas and what’s included, even at 2am.'],
        ['<path d="M6 2.5h8l4.5 4.5v14.5H6z"/><path d="M14 2.5V7h4.5M9.5 12h6M9.5 15.5h6M9.5 19h3"/>', 'Itineraries and brochures on request', 'Upload your itineraries, event programmes and brochures, and Hannah sends the right file when someone asks.'],
        ['<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>', 'Follow-ups for quiet enquiries', 'Hannah checks back with people who asked about an event or trip and stopped replying, on your schedule.'],
    ],
    'rows' => [
        [
            'Sell tickets right in the chat', 'Answer, convince and send the ticket link in one conversation.',
            [
                'Hannah shares the date, venue, line-up and ticket prices from the details you add.',
                'When a guest is ready, she sends your ticket link or payment details, and you get a WhatsApp alert.',
            ],
            'blue', 'arrows',
            [
                ['out', '<span>How much are tickets for Afro Nights?</span>'],
                ['in', '<span>Regular is ₦10,000 and VIP is ₦25,000. It’s Saturday 19 Oct, 6pm at Eko Hall 🎶</span>', ['Get tickets']],
                ['out', '<span>VIP please!</span>'],
                ['in', '<span>Here’s your VIP ticket link 🎟️ See you on Saturday!</span>'],
            ],
        ],
        [
            'Plan trips without the back‑and‑forth', 'Travellers get clear answers while they’re still excited.',
            [
                'Teach Hannah your packages, prices, dates and what’s included once, and she uses them in every reply.',
                'Custom requests, like group or family quotes, trigger a WhatsApp alert so you can step in from the same inbox.',
            ],
            'lime', 'cross',
            [
                ['out', '<span>Do you have a Zanzibar trip in December?</span>'],
                ['in', '<span>Yes! 5 nights from ₦1,450,000 per person, with flights and hotel included.</span>', ['Send the itinerary']],
                ['out', '<span>Can you add 2 kids?</span>'],
                ['in', '<span>Lovely! I’ve let Tolu know, and she’ll reply here with a family quote shortly.</span>'],
            ],
        ],
        [
            'Keep guests and travellers coming back', 'Helpful messages that turn one booking into many.',
            [
                'Hannah sends your itineraries, event programmes and brochures whenever someone asks.',
                'She follows up with people who enquired but never bought, so fewer sales slip away.',
            ],
            'surface', 'plus',
            [
                ['out', '<span>Can you send the full itinerary?</span>'],
                ['in', '<span>Here it is! Let me know when you’re ready and I’ll send the payment details.</span><span class="ph-file"><i class="ph-dl"></i>Zanzibar itinerary.pdf</span>', ['Book this trip']],
                ['out', '<span>Perfect, thank you!</span>'],
            ],
        ],
    ],
];

require dirname(__DIR__) . '/includes/industries/page.php';
