<?php

declare(strict_types=1);

$ind = [
    'slug' => 'services-bookings',
    'name' => 'Services & Bookings',
    'title' => 'Book Consultations and Answer Clients on WhatsApp',
    'description' => 'Book consultations, answer questions about your services and prices, and follow up with clients on WhatsApp, Instagram and Messenger with an AI assistant that replies day and night.',
    'h1' => 'Book more consultations on WhatsApp',
    'lede' => 'Answer enquiries, book sessions and follow up with clients, all in the chats they already use.',
    'clients' => 'Built for <strong>consultants, salons, coaches and service pros</strong> that book in the chats',
    'band' => 'Turn more enquiries into booked sessions on WhatsApp',
    'cta' => 'Your clients already chat on WhatsApp and Instagram. Turn those chats into bookings with Lead Bird.',
    'quotesLede' => 'How businesses use Lead Bird to answer customers on WhatsApp and Instagram.',
    'heroVisual' => 'booking',
    'hero' => [
        'icon' => 'case',
        'store' => 'Bright Path Consulting',
        'ask' => 'Can I book a business consultation?',
        'reply' => 'Yes! Which time works for you?',
        'slots' => 'Mon 10:00am|Wed 1:00pm|Fri 4:00pm',
        'pick' => 'Wed 1:00pm',
        'booked' => 'You’re booked for Wednesday at 1:00pm ✅',
        'day' => 'Wed 16',
        'time' => '1:00pm consultation',
        'alert' => 'New booking: Emeka, Wed 1:00pm',
    ],
    'features' => [
        ['<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/><path d="M9 15.5l2 2 4-4"/>', 'Sessions booked in the chat', 'Hannah, your AI assistant, collects the name, phone number and preferred time, then adds the session to your Google Calendar. Or she sends your own booking link.'],
        ['<path d="M13 2.5 5 13.5h6l-1 8 8-11h-6z"/>', 'Instant answers, day and night', 'Hannah answers questions about your services, prices, packages and availability in seconds, even while you’re with a client.'],
        ['<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>', 'An alert for every booking', 'You get a WhatsApp alert when someone books, and when a question needs you personally.'],
        ['<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>', 'Follow-ups for quiet enquiries', 'Hannah checks back with people who asked about a service and stopped replying, on your schedule.'],
        ['<path d="M6 2.5h8l4.5 4.5v14.5H6z"/><path d="M14 2.5V7h4.5M9.5 12h6M9.5 15.5h6M9.5 19h3"/>', 'Brochures and price lists on request', 'Upload your packages, price list or portfolio, and Hannah sends the right file when someone asks.'],
        ['<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 9.5h19M6.5 15h4"/>', 'Payment details sent fast', 'When a client is ready to pay a deposit or fee, Hannah sends your account number or payment details in the chat.'],
    ],
    'rows' => [
        [
            'Book sessions in the chat', 'No phone tag or long message threads.',
            [
                'Hannah asks for the details you need and books the slot straight into your Google Calendar, avoiding times you’re already busy.',
                'Prefer your own booking system? She sends your booking link instead.',
            ],
            'blue', 'arrows',
            [
                ['out', '<span>Can I book a consultation?</span>'],
                ['in', '<span>Of course! What day and time works for you?</span>'],
                ['out', '<span>Wednesday 1pm</span>'],
                ['in', '<span>You’re booked for Wednesday, 16 Oct at 1:00pm ✅ We have your name and phone number.</span>'],
            ],
        ],
        [
            'Answer every enquiry quickly', 'Clients get answers while they’re still interested.',
            [
                'Teach Hannah your services, packages, prices and policies once, and she uses them in every reply.',
                'Tricky or custom requests trigger a WhatsApp alert, so you can step in from the same inbox.',
            ],
            'lime', 'cross',
            [
                ['out', '<span>How much is a bridal makeup session?</span>'],
                ['in', '<span>Bridal makeup is ₦80,000, including a trial session. A 50% deposit secures your date.</span>', ['Send payment details']],
                ['out', '<span>Can you also do 6 bridesmaids?</span>'],
                ['in', '<span>Great question! I’ve let Funke know, and she’ll reply here with a quote shortly.</span>'],
            ],
        ],
        [
            'Keep clients coming back', 'Helpful messages that turn one booking into many.',
            [
                'Hannah sends your brochures, price lists and portfolios whenever someone asks.',
                'She follows up with people who enquired but never booked, so fewer clients slip away.',
            ],
            'surface', 'plus',
            [
                ['out', '<span>Can you send your packages?</span>'],
                ['in', '<span>Here you go! Let me know which one suits you and I’ll book you in.</span><span class="ph-file"><i class="ph-dl"></i>Packages 2026.pdf</span>', ['Book a session']],
                ['out', '<span>The gold package, please</span>'],
            ],
        ],
    ],
];

require dirname(__DIR__) . '/includes/industries/page.php';
