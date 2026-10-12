<?php

declare(strict_types=1);

/**
 * Ready-made template bodies Hannah offers without calling the AI. Several versions per type so she can rotate.
 * Details from the customer's record use worded variables; details the seller types per send use numbered ones.
 * "{business}" is replaced with the seller's business name, in bodies and samples.
 */
final class WhatsAppTemplateLibrary
{
    private const TYPES = [
        'event_invite' => [
            'label' => 'Event invite',
            'keywords' => ['event', 'invite', 'invitation', 'party', 'launch', 'program', 'programme', 'conference', 'concert', 'show', 'open day', 'hangout', 'seminar', 'workshop'],
            'bodies' => [
                ["Hi {{1}}, you're invited for {{2}} at {business}.\n\nDate: {{3}}\nTime: {{4}}\nVenue: {{5}}\n\nCome with your friends. We will be happy to see you.", ['1' => 'Ada', '2' => 'our Customer Appreciation Day', '3' => 'Saturday, 24 October', '4' => '2:00 PM', '5' => '12 Admiralty Way, Lekki']],
                ["Hello {{1}}, we are having {{2}} on {{3}} and we would love you to come.\n\nTime: {{4}}\nPlace: {{5}}\n\nReply to this message if you have any questions.", ['1' => 'Ada', '2' => 'our end of year party', '3' => 'Friday, 18 December', '4' => '4:00 PM', '5' => 'our Ikeja shop']],
                ["Hi {{1}}, {business} is hosting {{2}}.\n\nIt holds on {{3}} by {{4}} at {{5}}.\n\nPlease come and bring someone along. Reply YES so we can keep a seat for you.", ['1' => 'Ada', '2' => 'a free styling class', '3' => 'Saturday, 7 November', '4' => '11:00 AM', '5' => 'our Wuse 2 shop']],
            ],
        ],
        'event_reminder' => [
            'label' => 'Event reminder',
            'keywords' => ['event reminder', 'registration', 'registered', 'ticket', 'attend', 'starts tomorrow', 'coming up'],
            'bodies' => [
                ["Hi {{1}}, this is a reminder that {{2}} is on {{3}} at {{4}}.\n\nVenue: {{5}}\n\nReply to this message if you have any questions.", ['1' => 'Ada', '2' => 'our Customer Appreciation Day', '3' => 'Saturday, 24 October', '4' => '2:00 PM', '5' => '12 Admiralty Way, Lekki']],
                ["Hello {{1}}, just a reminder about {{2}}.\n\nDate: {{3}}\nTime: {{4}}\nVenue: {{5}}\n\nWe look forward to seeing you.", ['1' => 'Ada', '2' => 'the baking class', '3' => 'Saturday, 24 October', '4' => '10:00 AM', '5' => 'our Ikeja shop']],
                ["Hi {{1}}, {{2}} is coming up on {{3}}. Please arrive by {{4}}.\n\nVenue: {{5}}\n\nSee you there.", ['1' => 'Ada', '2' => 'our end of year party', '3' => 'Friday, 18 December', '4' => '3:30 PM', '5' => 'our Ikeja shop']],
            ],
        ],
        'order_received' => [
            'label' => 'Order received',
            'keywords' => ['order received', 'new order', 'confirm order', 'order confirmation', 'thank you for your order', 'placed order', 'order'],
            'bodies' => [
                "Hi {{first_name}}, we have received your order {{order_number}}. We will let you know as soon as it is on its way. Thank you for buying from {business}.",
                "Hello {{first_name}}, thank you for your order. Your order number is {{order_number}}. We are getting it ready and will update you here.",
                "Hi {{first_name}}, your order {{order_number}} is confirmed. We will send you a message when it leaves our shop.",
            ],
        ],
        'order_ready' => [
            'label' => 'Order ready for pickup',
            'keywords' => ['pickup', 'pick up', 'ready', 'collect', 'collection'],
            'bodies' => [
                "Hi {{first_name}}, your order {{order_number}} is ready for pickup at {business}. Please come with your order number.",
                "Hello {{first_name}}, good news. Your order {{order_number}} is ready. You can pick it up from our shop any day this week.",
                "Hi {{first_name}}, your order {{order_number}} is packed and waiting for you. Reply to this message to tell us when you will come for it.",
            ],
        ],
        'delivery_update' => [
            'label' => 'Delivery update',
            'keywords' => ['delivery', 'deliver', 'shipped', 'shipping', 'dispatch', 'on the way', 'rider', 'waybill', 'tracking', 'track'],
            'bodies' => [
                "Hi {{first_name}}, your order {{order_number}} is on its way. It should get to you by {{delivery_date}}. Reply to this message if you have any questions.",
                "Hello {{first_name}}, we have sent out your order {{order_number}}. The rider will call you before {{delivery_date}}.",
                "Hi {{first_name}}, your order {{order_number}} has left our shop and should arrive on {{delivery_date}}. Please keep your phone close so the rider can reach you.",
            ],
        ],
        'payment_reminder' => [
            'label' => 'Payment reminder',
            'keywords' => ['payment reminder', 'pay', 'balance', 'owe', 'owing', 'due', 'invoice', 'debt', 'outstanding', 'school fees', 'fees'],
            'bodies' => [
                "Hi {{first_name}}, this is a reminder that your payment of {{amount}} is due on {{due_date}}. Reply to this message if you need help.",
                "Hello {{first_name}}, you have a balance of {{amount}} with {business}. Please pay by {{due_date}}. Reply here if you have already paid.",
                "Hi {{first_name}}, a friendly reminder that {{amount}} is due on {{due_date}}. Reply to this message and we will send you our account details.",
            ],
        ],
        'payment_received' => [
            'label' => 'Payment received',
            'keywords' => ['payment received', 'received payment', 'receipt', 'paid', 'payment confirmed', 'confirm payment'],
            'bodies' => [
                "Hi {{first_name}}, we have received your payment of {{amount}}. Thank you for buying from {business}.",
                "Hello {{first_name}}, your payment of {{amount}} for order {{order_number}} has been confirmed. We will start working on your order now.",
                "Hi {{first_name}}, thank you. Your payment of {{amount}} came in on {{payment_date}}. Reply to this message if you need a receipt.",
            ],
        ],
        'appointment_reminder' => [
            'label' => 'Appointment reminder',
            'keywords' => ['appointment', 'booking', 'booked', 'book', 'session', 'consultation', 'visit', 'fitting', 'salon', 'clinic'],
            'bodies' => [
                "Hi {{first_name}}, this is a reminder of your appointment with {business} on {{appointment_date}} at {{appointment_time}}. Can you still make it?",
                "Hello {{first_name}}, we have you booked for {{appointment_date}} at {{appointment_time}}. Please let us know if you need to change it.",
                "Hi {{first_name}}, see you on {{appointment_date}} at {{appointment_time}}. Reply to this message if anything changes.",
            ],
        ],
        'new_arrivals' => [
            'label' => 'New arrivals',
            'keywords' => ['new arrival', 'new arrivals', 'new stock', 'new collection', 'just arrived', 'new items', 'new products', 'new goods'],
            'bodies' => [
                ["Hi {{1}}, new items just arrived at {business}. Would you like to see them? Reply and we will send you photos and prices.", ['1' => 'Ada']],
                ["Hello {{1}}, our new {{2}} is in. Sizes and colours are limited, so reply to this message if you want us to keep one for you.", ['1' => 'Ada', '2' => 'Ankara collection']],
                ["Hi {{1}}, we just got new {{2}} in the shop. Do you want us to send you pictures?", ['1' => 'Ada', '2' => 'sneakers']],
            ],
        ],
        'back_in_stock' => [
            'label' => 'Back in stock',
            'keywords' => ['back in stock', 'restock', 'restocked', 'available again', 'in stock'],
            'bodies' => [
                ["Hi {{1}}, {{2}} is back in stock at {business}. Would you like us to keep one for you?", ['1' => 'Ada', '2' => 'the black leather bag']],
                ["Hello {{1}}, the {{2}} you asked about is available again. Reply to this message to order before it finishes.", ['1' => 'Ada', '2' => 'shea body butter']],
                ["Hi {{1}}, good news. We have {{2}} again. Do you still want it?", ['1' => 'Ada', '2' => 'the gold sandals']],
            ],
        ],
        'special_offer' => [
            'label' => 'Sale or discount',
            'keywords' => ['sale', 'discount', 'promo', 'promotion', 'offer', '% off', 'percent off', 'black friday', 'deal', 'price drop', 'clearance'],
            'bodies' => [
                ["Hi {{1}}, everything at {business} is {{2}} off until {{3}}. Would you like us to help you place an order?", ['1' => 'Ada', '2' => '20%', '3' => 'Sunday, 1 November']],
                ["Hello {{1}}, our {{2}} sale is on. You get {{3}} off from now until {{4}}. Reply to this message to order.", ['1' => 'Ada', '2' => 'Black Friday', '3' => '30%', '4' => 'Monday, 30 November']],
                ["Hi {{1}}, for this week only, {{2}} is {{3}} off at {business}. The offer ends on {{4}}. Do you want one?", ['1' => 'Ada', '2' => 'every handbag', '3' => '15%', '4' => 'Saturday, 31 October']],
            ],
        ],
        'welcome' => [
            'label' => 'Welcome or opt-in',
            'keywords' => ['welcome', 'opt in', 'opt-in', 'join', 'subscribe', 'new customer', 'updates'],
            'bodies' => [
                "Hi {{first_name}}, would you like to get updates and offers from {business} on WhatsApp? Tap Yes to join or No to skip. You can stop anytime.",
                "Hello {{first_name}}, thank you for shopping with {business}. Can we send you news about new items and offers here? You can stop anytime.",
                "Hi {{first_name}}, welcome to {business}. You can message us here any time you need help with an order.",
            ],
        ],
        'feedback_request' => [
            'label' => 'Feedback request',
            'keywords' => ['feedback', 'review', 'rate', 'rating', 'experience', 'survey', 'how was'],
            'bodies' => [
                "Hi {{first_name}}, thank you for buying from {business}. How was your experience?",
                "Hello {{first_name}}, did you like your order {{order_number}}? Your answer helps us serve you better.",
                "Hi {{first_name}}, how did we do with your last order? Please tap a button below to tell us.",
            ],
        ],
        'shop_notice' => [
            'label' => 'Shop notice',
            'keywords' => ['notice', 'announcement', 'closed', 'closing', 'close', 'opening hours', 'open', 'holiday', 'moving', 'new address', 'new location', 'relocat'],
            'bodies' => [
                ["Hi {{1}}, please note that {business} will be closed on {{2}}. We will open again on {{3}}. You can still send us a message here.", ['1' => 'Ada', '2' => 'Friday, 25 December', '3' => 'Monday, 28 December']],
                ["Hello {{1}}, our opening hours are changing. From {{2}}, we will be open {{3}}. Thank you for understanding.", ['1' => 'Ada', '2' => 'Monday, 2 November', '3' => '9:00 AM to 7:00 PM, Monday to Saturday']],
                ["Hi {{1}}, {business} has moved. Our new address is {{2}}. We open from {{3}}. We hope to see you soon.", ['1' => 'Ada', '2' => '5 Allen Avenue, Ikeja', '3' => '9:00 AM to 6:00 PM']],
            ],
        ],
        'follow_up' => [
            'label' => 'Follow up on enquiry',
            'keywords' => ['follow up', 'follow-up', 'enquiry', 'inquiry', 'asked about', 'interested', 'cart', 'abandoned', 'still interested'],
            'bodies' => [
                ["Hi {{1}}, you asked about {{2}} a few days ago. Are you still interested? We still have it.", ['1' => 'Ada', '2' => 'the red gown']],
                ["Hello {{1}}, just checking in about the {{2}} you wanted. Would you like us to keep one for you?", ['1' => 'Ada', '2' => 'small chops tray']],
                ["Hi {{1}}, do you still want the {{2}}? Reply to this message and we will help you order it.", ['1' => 'Ada', '2' => 'jollof rice tray']],
            ],
        ],
        'registration_confirmed' => [
            'label' => 'Registration confirmed',
            'keywords' => ['registration confirmed', 'sign up', 'signed up', 'signup', 'enrol', 'enroll', 'admission', 'membership', 'account created'],
            'bodies' => [
                "Hi {{first_name}}, your registration with {business} is confirmed. Your reference number is {{reference_number}}. Reply to this message if you have any questions.",
                "Hello {{first_name}}, thank you for signing up. Your reference number is {{reference_number}}. Please keep it for when you contact us.",
                "Hi {{first_name}}, you are now registered with {business}. Your reference number is {{reference_number}}. We will send updates here.",
            ],
        ],
    ];

    /**
     * @return list<array{key: string, label: string, keywords: list<string>, bodies: list<array{text: string, samples: array<string, string>}>}>
     */
    public static function all(string $businessName): array
    {
        $business = trim($businessName) !== '' ? trim($businessName) : 'our shop';
        $types = [];
        foreach (self::TYPES as $key => $type) {
            $types[] = [
                'key' => $key,
                'label' => $type['label'],
                'keywords' => $type['keywords'],
                'bodies' => array_map(static function (string|array $entry) use ($business): array {
                    [$text, $samples] = is_array($entry) ? $entry : [$entry, []];
                    preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/', $text, $named);
                    foreach (array_unique($named[1]) as $variable) {
                        $samples[$variable] = WhatsAppStarterTemplates::sampleFor($variable);
                    }

                    return [
                        'text' => str_replace('{business}', $business, $text),
                        'samples' => array_map(static fn(string $sample): string => str_replace('{business}', $business, $sample), $samples),
                    ];
                }, $type['bodies']),
            ];
        }

        return $types;
    }
}
