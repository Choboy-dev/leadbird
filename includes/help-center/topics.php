<?php

declare(strict_types=1);

/**
 * Public Help Center topics.
 * Each entry: slug, name, icon key (see help_center_icon_paths), extra search keywords, article count.
 *
 * @return list<array{slug: string, name: string, icon: string, keywords: string, articles: int}>
 */
function help_center_topics(): array
{
    $topics = [
        ['overview', 'Lead Bird Overview', 'book', 'product tour features what is lead bird hannah plans'],
        ['getting-started', 'Getting Started', 'clipboard', 'setup onboarding sign up first steps account create'],
        ['hannah', 'Meet Hannah', 'cursor', 'your ai salesgirl ai salesgirl agent assistant replies dms automatic'],
        ['training', 'Training Hannah', 'sparkles', 'knowledge documents links website faq train learn tone personality greeting'],
        ['whatsapp', 'WhatsApp', 'chat', 'whatsapp business number connect cloud api'],
        ['instagram', 'Instagram', 'camera', 'instagram dm direct messages connect'],
        ['messenger', 'Facebook Messenger', 'bolt', 'facebook page messenger connect'],
        ['tiktok', 'TikTok', 'film', 'tiktok connect videos posts'],
        ['meta-connect', 'Meta Accounts', 'check-circle', 'connecting meta business suite pages permissions reconnect offline login'],
        ['inbox', 'Inbox & Live Chats', 'chat-ellipsis', 'inbox reply manually take over pause ai labels conversations'],
        ['customers', 'Customers', 'users', 'customer records contacts profiles history'],
        ['offers', 'Products & Offers', 'bag', 'products services prices catalog images stock currency'],
        ['orders', 'Orders & Payments', 'currency', 'customer orders receipts account number bank transfer payment confirm'],
        ['follow-ups', 'Follow-ups', 'arrow-path', 'reminders follow up remind pay tomorrow abandoned chase automation'],
        ['social-media', 'Social Media Planner', 'megaphone', 'schedule posts planner publish reels captions'],
        ['insights', 'Insights & Analytics', 'chart', 'analytics reports insights audience performance sales'],
        ['alerts', 'Owner Alerts', 'phone', 'alerts notifications owner escalation handover'],
        ['businesses', 'Multiple Businesses', 'arrows-right-left', 'switch businesses vendor shop add business'],
        ['billing', 'Plans & Billing', 'currency', 'payments plans subscription invoice card renewal upgrade cancel pricing'],
        ['tokens', 'Tokens & Top-ups', 'trending-up', 'tokens credits top up usage run out'],
        ['refer', 'Refer & Earn', 'users', 'affiliate referral commission wallet payout'],
        ['account', 'Account & Settings', 'at', 'login otp google profile email settings'],
        ['security', 'Privacy & Security', 'key', 'privacy security gdpr data deletion delete account'],
        ['troubleshooting', 'Troubleshooting', 'command', 'errors not replying offline fix problem'],
    ];

    return array_map(
        static fn (array $t): array => [
            'slug' => $t[0],
            'name' => $t[1],
            'icon' => $t[2],
            'keywords' => $t[3],
            'articles' => 0,
        ],
        $topics
    );
}

function help_center_find_topic(string $slug): ?array
{
    foreach (help_center_topics() as $topic) {
        if ($topic['slug'] === $slug) {
            return $topic;
        }
    }

    return null;
}

/** Inner SVG markup for a 40x40 stroke icon (stroke = currentColor). */
function help_center_icon(string $key): string
{
    $paths = [
        'arrow-path' => 'M26.704 15.58h8.322v-.002M4.974 32.74v-8.32m0 0h8.32m-8.32 0l5.301 5.304a13.687 13.687 0 006.166 3.558c7.335 1.965 14.874-2.388 16.84-9.723M6.718 16.44c1.965-7.335 9.505-11.688 16.84-9.722a13.687 13.687 0 016.166 3.557l5.302 5.302m0-8.318v8.318',
        'trending-up' => 'M3.75 30L15 18.75l7.177 7.177a19.917 19.917 0 019.69-9.198l4.568-2.034m0 0l-9.902-3.8m9.902 3.8l-3.801 9.903',
        'arrows-right-left' => 'M12.5 35L5 27.5m0 0l7.5-7.5M5 27.5h22.5m0-22.5l7.5 7.5m0 0L27.5 20m7.5-7.5H12.5',
        'check-circle' => 'M15 21.25L18.75 25 25 16.25M35 20c0 8.284-6.716 15-15 15-8.284 0-15-6.716-15-15 0-8.284 6.716-15 15-15 8.284 0 15 6.716 15 15z',
        'currency' => 'M20 10v20m-5-4.697l1.465 1.099c1.952 1.464 5.118 1.464 7.07 0 1.953-1.465 1.953-3.84 0-5.304C22.56 20.366 21.28 20 20 20c-1.208 0-2.416-.366-3.338-1.098-1.844-1.465-1.844-3.84 0-5.304 1.843-1.464 4.832-1.464 6.676 0l.691.55M35 20c0 8.284-6.716 15-15 15-8.284 0-15-6.716-15-15 0-8.284 6.716-15 15-15 8.284 0 15 6.716 15 15z',
        'chart' => 'M6.25 5v18.75A3.75 3.75 0 0010 27.5h3.75M6.25 5h-2.5m2.5 0h27.5m0 0h2.5m-2.5 0v18.75A3.75 3.75 0 0130 27.5h-3.75m-12.5 0h12.5m-12.5 0l-1.667 5m14.167-5l1.667 5m0 0l.833 2.5m-.833-2.5H12.083m0 0L11.25 35m1.25-15l5-5 3.58 3.58a20.103 20.103 0 016.42-5.904',
        'phone' => 'M3.75 11.25c0 13.807 11.193 25 25 25h3.75a3.75 3.75 0 003.75-3.75v-2.286c0-.86-.586-1.61-1.42-1.82l-7.372-1.842a1.875 1.875 0 00-1.955.694l-1.617 2.156c-.47.626-1.281.902-2.015.633A20.059 20.059 0 019.965 18.13c-.27-.734.007-1.546.633-2.015l2.156-1.617a1.875 1.875 0 00.694-1.955L11.605 5.17a1.875 1.875 0 00-1.819-1.42H7.5A3.75 3.75 0 003.75 7.5v3.75z',
        'book' => 'M20 10.07a14.944 14.944 0 00-10-3.82c-1.753 0-3.436.3-5 .854v23.75A14.977 14.977 0 0110 30a14.94 14.94 0 0110 3.82m0-23.75a14.944 14.944 0 0110-3.82c1.753 0 3.436.3 5 .854v23.75A14.977 14.977 0 0030 30a14.944 14.944 0 00-10 3.82m0-23.75v23.75',
        'clipboard' => 'M18.916 6.394c-.108.35-.166.72-.166 1.106 0 .69.56 1.25 1.25 1.25h7.5c.69 0 1.25-.56 1.25-1.25 0-.385-.058-.757-.166-1.106m-9.668 0A3.752 3.752 0 0122.5 3.75H25a3.752 3.752 0 013.584 2.644m-9.668 0a77.01 77.01 0 00-1.872.133c-1.886.157-3.294 1.762-3.294 3.653v3.57m14.834-7.356c.626.037 1.25.081 1.872.133 1.886.157 3.294 1.762 3.294 3.653V27.5A3.75 3.75 0 0130 31.25h-3.75m-12.5-17.5H8.125c-1.036 0-1.875.84-1.875 1.875v18.75c0 1.035.84 1.875 1.875 1.875h16.25c1.035 0 1.875-.84 1.875-1.875V31.25m-12.5-17.5h10.625c1.035 0 1.875.84 1.875 1.875V31.25m-13.75-5l2.5 2.5 5-6.25',
        'bolt' => 'M6.25 22.5l17.5-18.75L20 17.5h13.75l-17.5 18.75L20 22.5H6.25z',
        'sparkles' => 'M16.355 26.506L15 31.25l-1.355-4.744a7.5 7.5 0 00-5.151-5.15L3.75 20l4.744-1.355a7.5 7.5 0 005.15-5.151L15 8.75l1.355 4.744a7.5 7.5 0 005.151 5.15L26.25 20l-4.744 1.355a7.5 7.5 0 00-5.15 5.151zm14.076-11.982L30 16.25l-.431-1.726a5.625 5.625 0 00-4.093-4.093L23.75 10l1.726-.431a5.625 5.625 0 004.093-4.093L30 3.75l.431 1.726a5.625 5.625 0 004.093 4.093L36.25 10l-1.726.431a5.625 5.625 0 00-4.093 4.093zm-2.274 19.755L27.5 36.25l-.657-1.971a3.75 3.75 0 00-2.372-2.372L22.5 31.25l1.971-.657a3.75 3.75 0 002.372-2.372l.657-1.971.657 1.971a3.75 3.75 0 002.372 2.372l1.971.657-1.971.657a3.75 3.75 0 00-2.372 2.372z',
        'camera' => 'M11.378 10.291a3.85 3.85 0 01-2.735 1.759 79.85 79.85 0 00-1.89.292C5 12.634 3.75 14.178 3.75 15.957V30a3.75 3.75 0 003.75 3.75h25A3.75 3.75 0 0036.25 30V15.957c0-1.78-1.248-3.323-3.003-3.615a80.409 80.409 0 00-1.89-.292 3.85 3.85 0 01-2.735-1.759l-1.369-2.193a3.654 3.654 0 00-2.894-1.731 81.291 81.291 0 00-8.718 0 3.654 3.654 0 00-2.894 1.731l-1.369 2.193zM27.5 21.25a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0zm3.75-3.75h.012v.012h-.012V17.5z',
        'film' => 'M5.625 32.5h28.75a1.875 1.875 0 001.875-1.875V9.375A1.875 1.875 0 0034.375 7.5H5.625A1.875 1.875 0 003.75 9.375v21.25c0 1.035.84 1.875 1.875 1.875zM10 7.5v25M30 7.5v25M3.75 13.75H10M3.75 20H10m-6.25 6.25H10M30 13.75h6.25M30 20h6.25M30 26.25h6.25',
        'key' => 'M26.25 8.75a5 5 0 015 5m5 0c0 5.523-4.477 10-10 10a10.1 10.1 0 01-1.715-.146c-.938-.162-1.932.043-2.605.716l-4.43 4.43h-3.75v3.75H10v3.75H3.75v-4.697a3.75 3.75 0 011.098-2.651L15.68 18.07c.673-.673.878-1.667.716-2.606a10.07 10.07 0 01-.146-1.714c0-5.523 4.477-10 10-10s10 4.477 10 10z',
        'bag' => 'M26.25 17.5V10a6.25 6.25 0 10-12.5 0v7.5m18.927-3.321l2.105 20a1.875 1.875 0 01-1.865 2.071H7.083a1.875 1.875 0 01-1.865-2.071l2.105-20c.1-.955.905-1.679 1.865-1.679h21.624c.96 0 1.764.724 1.865 1.679zM14.375 17.5a.625.625 0 11-1.25 0 .625.625 0 011.25 0zm12.5 0a.625.625 0 11-1.25 0 .625.625 0 011.25 0z',
        'chat' => 'M12.5 13.75h15m-15 5H20M3.75 21.266c0 2.668 1.872 4.99 4.512 5.379 1.881.277 3.783.488 5.704.63a1.9 1.9 0 011.442.837L20 35l4.592-6.887a1.9 1.9 0 011.442-.837 80.207 80.207 0 005.704-.63c2.64-.389 4.512-2.712 4.512-5.38V11.234c0-2.668-1.872-4.991-4.512-5.38A80.656 80.656 0 0020 5c-3.987 0-7.906.292-11.738.855-2.64.388-4.512 2.71-4.512 5.38v10.03z',
        'chat-ellipsis' => 'M14.375 16.25a.625.625 0 11-1.25 0 .625.625 0 011.25 0zm0 0h-.625m6.875 0a.625.625 0 11-1.25 0 .625.625 0 011.25 0zm0 0H20m6.875 0a.625.625 0 11-1.25 0 .625.625 0 011.25 0zm0 0h-.625m-22.5 5.016c0 2.668 1.872 4.99 4.512 5.379 1.81.266 3.64.472 5.488.614V35l6.973-6.973a1.9 1.9 0 011.296-.552 80.477 80.477 0 009.719-.83c2.64-.388 4.512-2.71 4.512-5.38v-10.03c0-2.67-1.872-4.992-4.512-5.38A80.656 80.656 0 0020 5c-3.987 0-7.906.292-11.738.855-2.64.388-4.512 2.71-4.512 5.38v10.03z',
        'users' => 'M25 31.88c1.388.403 2.856.62 4.375.62 2.465 0 4.796-.57 6.869-1.587a6.875 6.875 0 00-12.555-4.154M25 31.879v-.004c0-1.855-.475-3.599-1.31-5.116m1.31 5.12c0 .06 0 .119-.002.178A20.53 20.53 0 0114.376 35a20.53 20.53 0 01-10.623-2.943l-.002-.182c0-5.868 4.757-10.625 10.625-10.625 4.013 0 7.507 2.225 9.314 5.509M20 10.625a5.625 5.625 0 11-11.25 0 5.625 5.625 0 0111.25 0zm13.75 3.75a4.375 4.375 0 11-8.75 0 4.375 4.375 0 018.75 0z',
        'at' => 'M27.5 20a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0zm0 0c0 2.761 1.679 5 3.75 5 2.071 0 3.75-2.239 3.75-5 0-3.839-1.465-7.678-4.393-10.607-5.858-5.857-15.356-5.857-21.214 0-5.857 5.858-5.857 15.356 0 21.214 5.858 5.858 15.356 5.858 21.214 0M27.5 20v-6.25',
        'command' => 'M11.25 12.5l5 3.75-5 3.75m7.5 0h5m-15 13.75h22.5A3.75 3.75 0 0035 30V10a3.75 3.75 0 00-3.75-3.75H8.75A3.75 3.75 0 005 10v20a3.75 3.75 0 003.75 3.75z',
        'cursor' => 'M25.07 36.12l-2.264-8.452m0 0l-4.183 3.709.948-15.783 8.712 13.194-5.477-1.12zM20 3.75V7.5m9.723.277L27.07 10.43m6.68 7.07H30m-17.071 7.071l-2.652 2.652M10 17.5H6.25m6.679-7.071l-2.652-2.652',
        'megaphone' => 'M17.234 26.4a40.553 40.553 0 00-3.484-.15H12.5a7.5 7.5 0 010-15h1.25c1.174 0 2.336-.05 3.484-.15m0 15.3a29.767 29.767 0 001.64 4.639c.413.917.1 2.016-.771 2.518l-1.094.632c-.92.53-2.1.195-2.545-.768a34.742 34.742 0 01-2.4-7.136m5.17.115a30.046 30.046 0 01-.984-7.65c0-2.644.342-5.207.984-7.65m0 15.3a39.747 39.747 0 0114.724 4.225M17.234 11.1a39.748 39.748 0 0014.724-4.225m0 0A39.638 39.638 0 0031.324 5m.634 1.875a39.854 39.854 0 011.69 8.992m-1.69 14.758a39.66 39.66 0 01-.634 1.875m.634-1.875a39.855 39.855 0 001.69-8.991m0-5.768A3.742 3.742 0 0135 18.75c0 1.16-.526 2.196-1.352 2.884m0-5.768a40.6 40.6 0 010 5.768',
    ];

    $d = $paths[$key] ?? $paths['book'];

    return '<svg viewBox="0 0 40 40" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="' . $d . '"/></svg>';
}
