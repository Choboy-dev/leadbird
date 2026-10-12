<?php

declare(strict_types=1);

$ind = [
    'slug' => 'health-wellness',
    'name' => 'Health & Wellness',
    'title' => 'Book Appointments and Answer Patients on WhatsApp',
    'description' => 'Book appointments, answer questions about services, prices and opening hours, and follow up with patients and clients on WhatsApp, Instagram and Messenger with an AI assistant that replies day and night.',
    'h1' => 'Connect with your patients and clients on WhatsApp',
    'lede' => 'Book appointments, answer questions about your services and follow up after visits, all in the chats people already use.',
    'clients' => 'Built for <strong>clinics, pharmacies, spas and wellness coaches</strong> that book in the chats',
    'band' => 'Quick, personal care conversations on WhatsApp',
    'cta' => 'Your patients and clients already use WhatsApp every day. Make your care easier to reach with Lead Bird.',
    'quotesLede' => 'How businesses use Lead Bird to answer customers on WhatsApp and Instagram.',
    'heroVisual' => 'booking',
    'heroImage' => ['industries-health-hero.webp', 1010, 938],
    'hero' => [
        'store' => 'Cedar Clinic',
        'ask' => 'Hi, can I book a check-up this week?',
        'reply' => 'Of course! Which day suits you?',
        'slots' => 'Tue 9:00am|Wed 11:30am|Thu 2:00pm',
        'pick' => 'Tue 9:00am',
        'booked' => 'You’re booked for Tuesday at 9:00am ✅',
        'day' => 'Tue 15',
        'time' => '9:00am check-up',
        'alert' => 'New appointment: Grace, Tue 9:00am',
    ],
    'features' => [
        ['<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/><path d="M9 15.5l2 2 4-4"/>', 'Appointments booked in the chat', 'Hannah, your AI assistant, collects the name, phone number and preferred time, then adds the appointment to your Google Calendar. Or she sends your own booking link.'],
        ['<path d="M13 2.5 5 13.5h6l-1 8 8-11h-6z"/>', 'Instant answers, day and night', 'Hannah answers questions about your services, prices, opening hours and location in seconds, even after you close.'],
        ['<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>', 'An alert for every booking', 'You get a WhatsApp alert when someone books, and when a question needs a person on your team.'],
        ['<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>', 'Follow-ups for quiet enquiries', 'Hannah checks back with people who asked about a service and stopped replying, on your schedule.'],
        ['<path d="M6 2.5h8l4.5 4.5v14.5H6z"/><path d="M14 2.5V7h4.5M9.5 12h6M9.5 15.5h6M9.5 19h3"/>', 'Guides and price lists on request', 'Upload your price list, service menu or aftercare guides, and Hannah sends the right file when someone asks.'],
        ['<circle cx="12" cy="13" r="8.5"/><rect x="8.5" y="11" width="7" height="6" rx="1"/><path d="M10 11V9a2 2 0 0 1 4 0v2"/>', 'Medical questions go to your team', 'Hannah only answers from what you teach her. Questions about symptoms or treatment are passed to your staff, not guessed.'],
    ],
    'rows' => [
        [
            'Book appointments in the chat', 'No phone calls or back-and-forth messages.',
            [
                'Hannah asks for the details you need and books the slot straight into your Google Calendar, avoiding times you’re already busy.',
                'Prefer your own booking system? She sends your booking link instead.',
            ],
            'blue', 'arrows',
            [
                ['out', '<span>I’d like to book a check-up.</span>'],
                ['in', '<span>Of course! What day and time works for you?</span>'],
                ['out', '<span>Tuesday 9am please</span>'],
                ['in', '<span>You’re booked for Tuesday, 15 Oct at 9:00am ✅ We have your name and phone number.</span>'],
            ],
        ],
        [
            'Answer every question quickly', 'Shorter waits for patients, fewer calls for your front desk.',
            [
                'Teach Hannah your services, prices, opening hours and directions once, and she uses them in every reply.',
                'Questions about symptoms or treatment trigger a WhatsApp alert, so your team can reply in the same inbox.',
            ],
            'lime', 'cross',
            [
                ['out', '<span>How much is a full blood test?</span>'],
                ['in', '<span>A full blood count is ₦15,000. Results are ready in 24 hours.</span>', ['Book a test']],
                ['out', '<span>Should I stop my medication before it?</span>'],
                ['in', '<span>That’s best answered by our nurse. I’ve let her know, and she’ll reply here shortly.</span>'],
            ],
        ],
        [
            'Stay in touch after the visit', 'Helpful messages that keep people coming back.',
            [
                'Hannah sends your aftercare guides, price lists and service menus whenever someone asks.',
                'She follows up with people who enquired but never booked, so fewer appointments slip away.',
            ],
            'surface', 'plus',
            [
                ['out', '<span>Do you have the aftercare guide for my physio?</span>'],
                ['in', '<span>Here it is! Let us know if you’d like to book your next session.</span><span class="ph-file"><i class="ph-dl"></i>Physio aftercare.pdf</span>', ['Book next session']],
                ['out', '<span>Thank you!</span>'],
            ],
        ],
    ],
];

require dirname(__DIR__) . '/includes/industries/page.php';
