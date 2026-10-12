(function () {
    const section = document.querySelector('[data-landing-journey]');
    if (!section) {
        return;
    }

    const AI = 'Hannah';
    const LINKS = {
        signup: section.dataset.signupUrl || '#',
        plans: section.dataset.plansUrl || '#',
        guide: section.dataset.guideUrl || '#',
    };

    const PLANS_LINK = { kicker: 'Pricing', label: 'See plans and what each one includes', url: LINKS.plans };
    const GUIDE_LINK = { kicker: 'Guide', label: 'Connect WhatsApp, Instagram and Messenger', url: LINKS.guide };

    // Message types: in (customer), out (AI reply), sys (system event), note (what the AI did)
    const TABS = {
        goal: [
            {
                label: 'Win more sales',
                title: 'Turn "How much?" into paid orders',
                sub: 'She answers price, size and delivery questions in seconds, sends your account details when they are ready, and tells you when someone is about to pay.',
                stats: [['3X', 'More sales from your DMs'], ['95%', 'Less time typing replies'], ['<10s', 'To answer "How much?"']],
                link: PLANS_LINK,
                chat: { name: "Ada's Closet", ch: 'ig', messages: [
                    ['sys', 'New DM from your Instagram post'],
                    ['in', 'How much is the adire two piece? 👀'],
                    ['out', '₦28,500 ✓ It comes in sizes 8 to 16. What size do you wear?'],
                    ['in', 'Size 12. Can you deliver to Ikeja?'],
                    ['out', 'Yes! Ikeja delivery is ₦2,500, so ₦31,000 total. Here are the account details 🏦'],
                    ['note', 'Ready to buy · owner alerted on WhatsApp'],
                ] },
            },
            {
                label: 'Keep customers happy',
                title: 'Answer every question from what you taught her',
                sub: 'Your prices, policies, FAQs and catalog are her source of truth. When a chat needs you, take it over with one tap and she steps back.',
                stats: [['80%', 'Questions answered without you'], ['95%', 'Less workload'], ['0', 'Missed DMs, day or night']],
                link: GUIDE_LINK,
                chat: { name: "Ada's Closet", ch: 'wa', messages: [
                    ['in', "If it doesn't fit, can I return it?"],
                    ['note', 'Answered from your return policy'],
                    ['out', 'You can exchange within 3 days of delivery, as long as the tags are still on 😊'],
                    ['in', "Okay, I've paid. Sending the receipt now"],
                    ['sys', 'Payment proof received · owner alerted'],
                    ['out', 'Thank you! Your order is confirmed and goes out tomorrow morning.'],
                ] },
            },
            {
                label: 'Bring them back',
                title: 'Follow up on the ones who said "I will pay tomorrow"',
                sub: 'Quiet chats get a polite nudge at the time you choose, so warm leads don’t go cold while you are busy at the market.',
                stats: [['40%', 'More "I will pay tomorrow" leads recovered'], ['2X', 'More repeat orders'], ['30%', 'Faster time to payment']],
                link: PLANS_LINK,
                chat: { name: "Ada's Closet", ch: 'wa', messages: [
                    ['sys', 'Follow-up · no reply for 6 hours'],
                    ['out', 'Hi Funmi! Still want the linen co-ord in sand? There are 2 left in your size.'],
                    ['in', 'Yes please, I got paid today 😅'],
                    ['out', 'Perfect timing! Here are the account details again 🏦'],
                    ['note', 'Lead moved to ready to buy'],
                ] },
            },
        ],
        business: [
            {
                label: 'Fashion & beauty',
                title: 'Customers send a photo, she finds the product',
                sub: 'When someone sends a screenshot and asks "Do you have this?", she matches it to your product photos and sends pictures back.',
                stats: [['3X', 'More sales from Instagram DMs'], ['90%', 'Fewer "Is this available?" replies by you'], ['70%', 'Less time sending pictures']],
                link: GUIDE_LINK,
                chat: { name: 'Tote & Co', ch: 'ig', messages: [
                    ['in', 'Do you have this bag in black? 📷'],
                    ['note', 'Matched to your product photos'],
                    ['out', "Yes! That's our raffia tote, ₦18,000. Black is in stock. Want more pictures?"],
                    ['in', 'Yes, send'],
                    ['sys', '3 product photos sent'],
                ] },
            },
            {
                label: 'Services & bookings',
                title: 'Book appointments straight into your calendar',
                sub: 'For makeup artists, consultants and anyone who sells their time. She collects the details and adds the booking to your Google Calendar.',
                stats: [['2X', 'More bookings from your DMs'], ['85%', 'Less back-and-forth to book'], ['10 min', 'Saved on every booking']],
                link: PLANS_LINK,
                chat: { name: 'Glow by Tolu', ch: 'wa', messages: [
                    ['in', 'Can I book bridal makeup for Saturday the 24th?'],
                    ['out', 'Yes! I have 9am or 1pm on Saturday. Which works for you?'],
                    ['in', '9am please'],
                    ['out', 'Booked ✓ See you at 9am on Saturday the 24th.'],
                    ['sys', 'Added to Google Calendar · owner notified'],
                ] },
            },
            {
                label: 'Food & small vendors',
                title: 'Prices, delivery and payment in one conversation',
                sub: 'Busy kitchens and small shops get the same questions all day. She answers them all and sends your payment details without you typing a thing.',
                stats: [['2X', 'More orders on busy days'], ['95%', 'Fewer prices typed by you'], ['5 min', 'Saved on every order']],
                link: GUIDE_LINK,
                chat: { name: "Kemi's Kitchen", ch: 'fb', messages: [
                    ['in', 'Do you deliver to Lekki? How much is a tray of jollof?'],
                    ['out', 'We do! A full tray is ₦45,000 and Lekki delivery is ₦3,000.'],
                    ['in', "Okay, I'll take one for Friday"],
                    ['out', 'Lovely! Here are the payment details. Send the receipt once you pay 🏦'],
                    ['sys', 'Payment details sent · owner alerted'],
                ] },
            },
        ],
    };

    const BADGES = {
        wa: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.2a9.7 9.7 0 0 0-8.4 14.6L2.2 21.8l5.1-1.3A9.7 9.7 0 1 0 12 2.2zm0 17.7a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-3-.2-.3A8 8 0 1 1 12 19.9zm4.4-6c-.2-.1-1.4-.7-1.7-.8-.2-.1-.4-.1-.5.1l-.8 1c-.1.2-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 0 0-.7.3 2.8 2.8 0 0 0-.9 2.1 4.9 4.9 0 0 0 1 2.6 11.2 11.2 0 0 0 4.3 3.8c1.6.7 2.2.7 3 .6.5-.1 1.400-.6 1.600-1.100.2-.6.2-1 .1-1.1l-.5-.2z"/></svg>',
        ig: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zM12 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.41-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z"/></svg>',
        fb: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.17.16.14.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.98-.87c.17-.08.36-.09.53-.04.91.25 1.88.38 2.91.38 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm6 7.46-2.94 4.66c-.47.74-1.47.93-2.17.4l-2.34-1.75a.6.6 0 0 0-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.94-4.66c.47-.74 1.47-.93 2.17-.4l2.34 1.75a.6.6 0 0 0 .72 0l3.16-2.4c.42-.32.97.18.69.63z"/></svg>',
    };

    const axisGroup = section.querySelector('[data-jx-axis]');
    const tabsEl = section.querySelector('[data-jx-tabs]');
    const chatEl = section.querySelector('[data-jx-chat]');
    const linkEl = section.querySelector('[data-jx-link]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let axis = 'goal';
    let index = 0;
    let timers = [];

    const esc = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    const playChat = (chat) => {
        timers.forEach(clearTimeout);
        timers = [];
        chatEl.setAttribute('data-ch', chat.ch);

        chatEl.innerHTML =
            '<div class="landing-jx-chat-head">' +
                '<span class="landing-jx-ava-wrap">' +
                    '<span class="landing-jx-ava">' + esc(chat.name.charAt(0)) + '</span>' +
                    '<span class="landing-jx-badge landing-jx-badge--' + chat.ch + '">' + BADGES[chat.ch] + '</span>' +
                '</span>' +
                '<span class="landing-jx-who">' +
                    '<b>' + esc(chat.name) + '</b>' +
                    '<small><i></i>' + esc(AI) + ' · online</small>' +
                '</span>' +
            '</div>' +
            '<div class="landing-jx-body"></div>' +
            '<div class="landing-jx-input">Message</div>';

        const body = chatEl.querySelector('.landing-jx-body');
        chat.messages.forEach(([type, text], k) => {
            const add = () => {
                const el = document.createElement('div');
                el.className = 'landing-jx-msg is-' + type;
                el.innerHTML = (type === 'note' ? '<i aria-hidden="true">✦</i> ' : '') + esc(text) +
                    (type === 'out' ? '<span class="landing-jx-spark" aria-hidden="true"><img src="' + section.dataset.birdIcon + '" alt=""></span>' : '');
                body.appendChild(el);
            };
            if (reducedMotion.matches) {
                add();
            } else {
                timers.push(setTimeout(add, 300 + k * 1100));
            }
        });
    };

    const draw = () => {
        const list = TABS[axis];
        const tab = list[index];

        tabsEl.innerHTML = list.map((t, i) =>
            '<button type="button" data-i="' + i + '" aria-pressed="' + (i === index) + '"' + (i === index ? ' class="is-on"' : '') + '>' + esc(t.label) + '</button>'
        ).join('');

        section.querySelector('[data-jx-title]').textContent = tab.title;
        section.querySelector('[data-jx-sub]').textContent = tab.sub;
        section.querySelector('[data-jx-stats]').innerHTML = tab.stats.map(([value, label]) =>
            '<div><b>' + esc(value) + '</b><small>' + esc(label) + '</small></div>'
        ).join('');

        linkEl.href = tab.link.url;
        section.querySelector('[data-jx-link-kicker]').textContent = tab.link.kicker;
        section.querySelector('[data-jx-link-label]').textContent = tab.link.label;

        Array.from(axisGroup.children).forEach((button) => {
            const on = button.getAttribute('data-axis') === axis;
            button.classList.toggle('is-on', on);
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        playChat(tab.chat);
    };

    axisGroup.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button || button.getAttribute('data-axis') === axis) {
            return;
        }
        axis = button.getAttribute('data-axis');
        index = 0;
        draw();
        tabsEl.scrollLeft = 0;
    });

    tabsEl.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) {
            return;
        }
        index = Number(button.getAttribute('data-i'));
        const scrollLeft = tabsEl.scrollLeft;
        draw();
        tabsEl.scrollLeft = scrollLeft;
        const active = tabsEl.querySelector('.is-on');
        if (active && tabsEl.scrollWidth > tabsEl.clientWidth) {
            const target = active.offsetLeft - (tabsEl.clientWidth - active.offsetWidth) / 2;
            tabsEl.scrollTo({ left: target, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        }
    });

    draw();
})();
