<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/reviews.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';

$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$pageTitle = $brandName . ' for Schools & Training | Grow Student Sign-ups on WhatsApp';
$metaDescription = 'Answer course enquiries, guide parents through admissions and send enrolment links on WhatsApp, Instagram and Messenger with ' . $brandName . '’s AI salesgirl.';
$metaUrl = app_route('industries/schools-training');
$extraStylesheets = ['solution.css?v=8', 'industry.css?v=12'];
$activeNav = null;

$signupUrl = app_route('signup');
$contactUrl = app_route('contact');

$features = [
    ['<rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M13 7h5M13 17h5"/>', 'Every enquiry in one inbox', 'Chats from WhatsApp, Instagram and Messenger land in one inbox, with labels to sort new enquiries, parents and current students.'],
    ['<path d="M12 2.5l2.4 1.8 3-.2.9 2.9 2.4 1.8-1 2.8 1 2.8-2.4 1.8-.9 2.9-3-.2L12 21.5l-2.4-1.8-3 .2-.9-2.9L3.3 15.2l1-2.8-1-2.8 2.4-1.8.9-2.9 3 .2z"/><path d="M8.5 12l2.3 2.3 4.7-4.6"/>', 'Instant answers, day and night', 'Hannah, your AI salesgirl, answers questions about fees, start dates and schedules, and alerts you on WhatsApp when a person is needed.'],
    ['<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2.5" y="13" width="4" height="6" rx="1.5"/><rect x="17.5" y="13" width="4" height="6" rx="1.5"/><path d="M19.5 19c0 1.5-2 2.5-5 2.5"/>', 'Help with admissions', 'Teach Hannah your admission steps and requirements, so parents get clear answers and know exactly what to do next.'],
    ['<path d="M6 2.5h8l4.5 4.5v14.5H6z"/><path d="M14 2.5V7h4.5M9.5 12h6M9.5 15.5h6M9.5 19h3"/>', 'Course outlines on request', 'Upload your brochures and course outlines, and Hannah sends the right file when a student asks for it.'],
    ['<circle cx="12" cy="13" r="7.5"/><path d="M12 9v4l2.5 2M4 5l3-2.5M20 5l-3-2.5"/>', 'Follow-ups for quiet enquiries', 'Hannah checks back with parents and students who stopped replying, on your schedule, so fewer sign-ups slip away.'],
    ['<circle cx="12" cy="7" r="3.5"/><path d="M5 21v-1.5A5.5 5.5 0 0 1 10.5 14h3a5.5 5.5 0 0 1 5.5 5.5V21"/><path d="M9 10.5 7 12M15 10.5l2 1.5"/>', 'Enrolment links and payment details', 'When someone is ready to join, Hannah sends your enrolment link or payment details right in the chat.'],
];

$rows = [
    [
        'Turn enquiries into enrolments', 'Answer every course enquiry the moment it arrives.',
        [
            'Hannah explains what each course covers, the fees and how to join, using the details you add.',
            'She collects the student’s name and email, then sends your enrolment link.',
        ],
        'industries-schools-enquiries.webp',
    ],
    [
        'Enrol more students', 'Promote your classes and reply fast to every sales question.',
        [
            'Add your courses with photos and prices, and Hannah shares them when students ask.',
            'She answers the questions that decide a sign-up, like start dates, fees and payment options, within the rules you set.',
        ],
        'industries-schools-enrol.webp',
    ],
    [
        'Keep parents in the loop', 'Clear, quick answers for parents and students, any time.',
        [
            'Hannah answers questions about resumption dates, timetables and fees from what you teach her.',
            'Complaints and tricky questions trigger a WhatsApp alert, so you can step in from the same inbox.',
        ],
        'industries-schools-parents.webp',
    ],
];

$quotes = lb_reviews();

require dirname(__DIR__) . '/includes/home/layout-start.php';
?>
<div class="bg-white min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/includes/home/marketing-header.php'; ?>

    <main class="sol ind flex-1">

        <section class="ind-hero">
            <div class="lk-wrap sol-split">
                <div>
                    <span class="ind-eyebrow"><?= e($brandName) ?> for Schools &amp; Training</span>
                    <h1>Engage and grow student sign&#8209;ups on WhatsApp</h1>
                    <p class="sol-p">Answer course enquiries, guide parents through admissions and send enrolment links, all in the chats your students already use.</p>
                    <div class="sol-btns">
                        <a class="sol-btn ind-hero-btn" href="<?= e($signupUrl) ?>">Get started</a>
                    </div>
                    <div class="ind-trust">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="var(--brand-lime)"/><path d="M7.5 12.5l3 3 6-6.5" stroke="var(--brand-black)" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>WhatsApp, Instagram &amp; Messenger</span>
                        <i></i>
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="var(--brand-blue)"/><path d="M12 7v5l3 2" stroke="var(--brand-white)" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>AI replies day and night</span>
                    </div>
                </div>
                <div aria-hidden="true">
                    <img class="ind-hero-img" src="<?= e(rtrim($appUrl, '/') . '/assets/images/industries-schools-hero.webp') ?>" alt="" width="1024" height="844">
                </div>
            </div>
        </section>

        <?php
        $toolSections = ['clients'];
        $clientsHeading = 'Built for <strong>schools, tutors and training centres</strong> that enrol in the chats';
        require dirname(__DIR__) . '/includes/tools/sections-bottom.php';
        ?>

        <section class="ind-feat-sec">
            <div class="lk-wrap">
                <div class="ind-feat-box">
                    <ul class="ind-feats">
                        <?php foreach ($features as [$icon, $title, $copy]): ?>
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg>
                                <div><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="ind-band">
            <div class="lk-wrap"><h2>Communicate better with students and parents on WhatsApp</h2></div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <?php foreach ($rows as $i => [$title, $sub, $points, $rowImage]): ?>
                    <div class="sol-split ind-row<?= $i % 2 === 1 ? ' flip' : '' ?>">
                        <div class="ind-row-copy">
                            <h2><?= e($title) ?></h2>
                            <h3><?= e($sub) ?></h3>
                            <ul>
                                <?php foreach ($points as $point): ?>
                                    <li><?= e($point) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <img class="ind-row-img" src="<?= e(rtrim($appUrl, '/') . '/assets/images/' . $rowImage) ?>" alt="" width="923" height="756" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="sol-pad ind-testi">
            <div class="lk-wrap">
                <h2 class="sol-h2 center">What our customers say</h2>
                <p class="sol-p center">How businesses use <?= e($brandName) ?> to answer customers on WhatsApp and Instagram.</p>
                <div class="ind-quotes">
                    <?php foreach ($quotes as [$headline, $quote, $name, $role, $channel]): ?>
                        <figure class="ind-quote">
                            <h3>“<?= e($headline) ?>”</h3>
                            <blockquote><?= e($quote) ?></blockquote>
                            <figcaption><?= lb_review_channel_badge($channel) ?><span><b><?= e($name) ?></b><small><?= e($role) ?></small></span></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="sol-pad">
            <div class="lk-wrap">
                <div class="ind-cta">
                    <p><strong>Discover more.</strong> WhatsApp is where students and parents already spend their time. Reach them there with <?= e($brandName) ?>.</p>
                    <div class="sol-btns">
                        <a class="sol-btn white" href="<?= e($signupUrl) ?>">Get started</a>
                        <a class="sol-btn-line" href="<?= e($contactUrl) ?>">Talk to us</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php require dirname(__DIR__) . '/includes/home/footer.php'; ?>
</div>
<?php require dirname(__DIR__) . '/includes/home/layout-end.php'; ?>
