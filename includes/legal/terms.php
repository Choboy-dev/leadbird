<?php

declare(strict_types=1);

/** @var string $appUrl */
?>
<div class="legal-page bg-white">
    <div class="legal-shell min-h-screen flex flex-col">
        <?php require __DIR__ . '/../home/marketing-header.php'; ?>

        <main class="lg flex-1">

<header class="lg-hero">
  <div class="wrap">
    <p class="lg-kicker">Lead Bird terms</p>
    <h1>Terms &amp; Conditions</h1>
    <p class="lg-updated">Last updated 11 October 2026</p>
  </div>
</header>

<div class="wrap lg-layout">
  <nav class="lg-toc" aria-label="On this page">
    <b>On this page</b>
    <ol>
      <li><a href="#user-warranties">Your commitments</a></li><li><a href="#licence">Licence to use Lead Bird</a></li><li><a href="#subscription">Trials, plans and payment</a></li><li><a href="#your-obligations">Your rights and obligations</a></li><li><a href="#your-data">Your content and data</a></li><li><a href="#messaging-channels">WhatsApp and other messaging channels</a></li><li><a href="#our-warranties">Our commitments</a></li><li><a href="#suspension">Breach and suspension</a></li><li><a href="#indemnity">Indemnity</a></li><li><a href="#liability">Limitation of liability</a></li><li><a href="#ip">Intellectual property</a></li><li><a href="#ai">AI features</a></li><li><a href="#law">Governing law and disputes</a></li><li><a href="#general">General terms</a></li>    </ol>
  </nav>

  <article class="lg-body lg-body--numbered">
    <p>Lead Bird Technologies, a business name registered with the Corporate Affairs Commission of Nigeria (registration number RN9622070), whose principal place of business is at 7 Tiamiyu Savage Street, Victoria Island, Lagos State, Nigeria (“Lead Bird”, “we”, “us” or “our”), owns and operates the Lead Bird platform available at <a href="<?= e(rtrim($appUrl, '/') . '/') ?>">leadbird.app</a>, together with its sub-domains, web and mobile applications, application programming interfaces (APIs), documentation and related services (together, the “Platform”). The Platform lets businesses send, receive, automate and manage messages with their customers through WhatsApp and other messaging channels (the “Services”).</p>
    <p>These Terms &amp; Conditions (the “Terms”) form a contract between us and the business or organisation that signs up for, or uses, the Platform (“you” or the “Customer”). If you accept these Terms on behalf of a business, you confirm that you have authority to bind it. By creating an account, or by accessing or using the Platform, you agree to these Terms from that date (the “Effective Date”). If you do not agree, please do not use the Platform.</p>
    <p>If you have signed a separate written agreement with us for the Services, that agreement takes priority over these Terms where the two conflict. Our <a href="<?= e(app_route('privacy')) ?>">Privacy Policy</a> explains how we handle personal data.</p>

    <section id="user-warranties">
      <h2>1. Your commitments</h2>
      <p>You confirm that:</p>
      <ol class="lg-ol">
        <li>you are a legitimate business, you are legally able to enter into this contract, and you are not located in, or controlled by anyone in, a country or territory subject to Nigerian, UN, EU or US sanctions, and you do not appear on any sanctions list;</li>
        <li>you will use the Platform only for lawful business purposes and not for personal use, political campaigning, or anything unlawful, including multi-level marketing schemes, misleading advertising, illegal gambling, sale of counterfeit goods or controlled substances, hate speech, harassment or the exploitation of others (the “Permitted Purpose”); and</li>
        <li>you will comply at all times with the terms and policies of every messaging channel you use through the Platform, as described in section 6.</li>
      </ol>
    </section>

    <section id="licence">
      <h2>2. Licence to use Lead Bird</h2>
      <p>Subject to these Terms and to payment of the applicable fees, we grant you a limited, non-exclusive, non-transferable, revocable licence to access and use the Platform during your subscription, for your internal business purposes and within the limits of your plan. You may not sub-license, rent, lease or otherwise make the Platform available to any third party. All rights not expressly granted to you are reserved by us.</p>
    </section>

    <section id="subscription">
      <h2>3. Trials, plans and payment</h2>
      <ol class="lg-ol">
        <li><strong>Free trial.</strong> You may be offered a free trial of the Platform for a limited period (currently 7 days). Some features may not be available during the trial. The trial ends automatically and does not turn into a paid subscription unless you choose a plan. We may change or withdraw trial offers at any time.</li>
        <li><strong>Plans.</strong> Our plans, prices and features are described on our <a href="<?= e(app_route('plans')) ?>">pricing page</a> and may change from time to time. The features included in your plan are confirmed when you subscribe.</li>
        <li><strong>Subscription term.</strong> Paid subscriptions run monthly or annually, as chosen when you subscribe (the “Subscription Term”), and start when we receive your first payment. Fees are billed in advance for each Subscription Term.</li>
        <li><strong>Fees and taxes.</strong> All fees are shown before you place your order and are exclusive of VAT and other applicable taxes, which you are responsible for paying. We may change our fees by giving you at least 30 days’ notice; the change will apply from the start of your next Subscription Term.</li>
        <li><strong>Automatic renewal.</strong> Subscriptions renew automatically for the same length of term, using your chosen payment method, unless you cancel before the renewal date. If a renewal payment fails, we may suspend access until payment is received.</li>
        <li><strong>Upgrades.</strong> If you upgrade during a Subscription Term, we will charge the difference on a pro-rata basis for the remainder of that term.</li>
        <li><strong>Cancellation.</strong> You can cancel at any time from your account settings or by writing to us. Cancellation takes effect at the end of your current Subscription Term, and you keep access until then.</li>
        <li><strong>Refunds.</strong> Except where required by law, or where you end these Terms because of our material breach, fees already paid are non-refundable, including for partial periods, downgrades or unused features.</li>
        <li><strong>Usage and add-ons.</strong> If you exceed the limits of your plan or buy add-ons, additional charges apply as shown in the Platform or on our pricing page.</li>
        <li><strong>Messaging channel fees.</strong> Our fees do not include charges made by messaging channels for messages sent through them, such as Meta’s per-message charges for the WhatsApp Business Platform. These charges are your responsibility and are billed as described in the Platform.</li>
        <li><strong>Invoice disputes.</strong> If you dispute an invoice in good faith, please pay the undisputed amount and tell us in writing within 14 days of the invoice date. We will work with you to resolve it promptly.</li>
        <li><strong>Late payment.</strong> If payment is overdue, we may suspend your access until it is received and may charge interest on overdue amounts at <mark class="lg-fill" title="Placeholder: complete before publishing">[the applicable rate under Nigerian law]</mark>.</li>
        <li><strong>Billing information.</strong> You agree to give us accurate billing details, including your legal company name, business address and Tax Identification Number (TIN) where applicable.</li>
      </ol>
    </section>

    <section id="your-obligations">
      <h2>4. Your rights and obligations</h2>
      <ol class="lg-ol">
        <li>You may add team members (“Users”) up to the number allowed by your plan. You are responsible for everything done through your account and for your Users’ compliance with these Terms.</li>
        <li>You must keep login details confidential, use reasonable security measures, and tell us straight away if you become aware of any unauthorised access to your account.</li>
        <li>You must not: (a) resell or provide the Platform to third parties as a service; (b) use the Platform to store or send content that is unlawful or infringes anyone’s rights; (c) copy, modify, reverse engineer or attempt to obtain the source code of the Platform, except as permitted by law; (d) use the Platform to build a competing product; (e) attempt to avoid fees or usage limits; (f) access the Platform using bots or other automated means except through our published APIs; or (g) interfere with the security or performance of the Platform.</li>
      </ol>
    </section>

    <section id="your-data">
      <h2>5. Your content and data</h2>
      <ol class="lg-ol">
        <li><strong>Ownership.</strong> You own, or have the right to use, all content, contacts, messages, files and other data that you or your Users put into the Platform (“Customer Data”).</li>
        <li><strong>Data protection roles.</strong> For personal data within Customer Data, you are the controller and we act as your processor. Our Data Processing Agreement <mark class="lg-fill" title="Placeholder: complete before publishing">[publish and link the DPA]</mark> forms part of these Terms and sets out how we process that data in line with the Nigeria Data Protection Act 2023 and other applicable data protection laws.</li>
        <li><strong>Consent and opt-ins.</strong> You are responsible for obtaining every consent, opt-in and notice required by law and by the messaging channels before you add people to the Platform or message them, and for honouring opt-out requests promptly.</li>
        <li><strong>How we use Customer Data.</strong> We use Customer Data only to provide, secure and support the Services, as instructed by you, or where the law requires. We do not sell Customer Data. We may share it only with our sub-processors and service providers who help us provide the Services, under appropriate confidentiality and data protection terms.</li>
        <li><strong>Usage information.</strong> We may collect anonymised and aggregated information about how the Platform is used, which does not identify you or any individual, and use it to operate and improve our services.</li>
        <li><strong>Export and deletion.</strong> You can export your Customer Data while your subscription is active. After your subscription ends, we will delete Customer Data within 90 days, unless the law requires us to keep it.</li>
        <li><strong>Back-ups.</strong> We take regular back-ups, but you should keep your own copies of any Customer Data that is important to you.</li>
      </ol>
    </section>

    <section id="messaging-channels">
      <h2>6. WhatsApp and other messaging channels</h2>
      <ol class="lg-ol">
        <li>The Services depend on third-party messaging channels, including the WhatsApp Business Platform provided by Meta. Your use of each channel is also subject to that channel’s own terms and policies, including the <a href="https://www.whatsapp.com/legal/business-policy/" rel="noopener" target="_blank">WhatsApp Business Policy</a>, the <a href="https://www.whatsapp.com/legal/business-terms/" rel="noopener" target="_blank">WhatsApp Business Terms of Service</a> and Meta’s commerce and messaging policies, which may change without notice.</li>
        <li>You must complete any business verification and onboarding that a channel requires, use only message templates that the channel has approved where templates are required, and respect the channel’s messaging limits and quality rules.</li>
        <li><strong>Bulk WhatsApp Messaging.</strong> If you use bulk messaging (broadcasts), you may only send to people who have agreed to receive WhatsApp messages from your business, and only content the channel’s policies allow. Every broadcast message includes a way to opt out, and anyone who replies STOP is removed from your list and will not receive further broadcasts from you. We may pause a broadcast if your number is disconnected, the channel withdraws approval of your template, or you run out of tokens. Tokens charged for messages that were not sent are returned to your balance.</li>
        <li>A messaging channel may restrict, suspend or remove your number or account. We do not control those decisions and are not responsible for them, or for changes, outages or charges introduced by the channel. If a change made by a channel means we can no longer provide part of the Services, either of us may end the affected Services by notice.</li>
      </ol>
    </section>

    <section id="our-warranties">
      <h2>7. Our commitments</h2>
      <ol class="lg-ol">
        <li>We will provide the Services with reasonable skill and care, and in line with these Terms.</li>
        <li>Except as set out in these Terms, and to the extent permitted by law, the Platform is provided “as is” and “as available”, and we do not promise that it will be uninterrupted or error-free. We may carry out maintenance from time to time and will try to give advance notice of planned downtime.</li>
        <li>The Platform depends on your internet connection, devices and browsers, and on third-party services that we do not control.</li>
      </ol>
    </section>

    <section id="suspension">
      <h2>8. Breach and suspension</h2>
      <ol class="lg-ol">
        <li>We do not routinely review Customer Data, but we may remove or block content, or suspend access, if we reasonably believe it breaches these Terms or the law, if we receive a valid complaint or legal order, or if it puts you, your recipients, other customers or the Platform at risk. Where reasonable, we will tell you first and give you a chance to put things right.</li>
        <li>We may monitor use of the Platform to check that it complies with these Terms. If you materially breach these Terms, we may suspend your account until the breach is fixed, or end these Terms by notice.</li>
        <li>Either of us may end these Terms by written notice if the other materially breaches them and does not fix the breach within 30 days of being asked to, or if the other becomes insolvent. If you end these Terms because of our material breach, we will refund any prepaid fees for the period after termination.</li>
      </ol>
    </section>

    <section id="indemnity">
      <h2>9. Indemnity</h2>
      <p>You will defend and compensate us against any claims, losses, fines, penalties and reasonable costs that we suffer as a result of: (a) your use of the Platform in breach of the law or these Terms; (b) any claim that Customer Data, or messages you send, infringe a third party’s rights or breach data protection or marketing laws; or (c) misuse of your account by anyone who obtained your login details because of your failure to keep them secure.</p>
    </section>

    <section id="liability">
      <h2>10. Limitation of liability</h2>
      <ol class="lg-ol">
        <li>Nothing in these Terms limits liability for death or personal injury caused by negligence, for fraud or fraudulent misrepresentation, or for anything else that cannot be limited by law.</li>
        <li>Subject to section 10.1, neither of us is liable for any loss of profits, revenue, business, goodwill, anticipated savings or data, or for any indirect or consequential loss.</li>
        <li>Subject to section 10.1, our total liability under or in connection with these Terms in any 12-month period is limited to the fees you paid us in the 12 months before the event giving rise to the claim <mark class="lg-fill" title="Placeholder: complete before publishing">[confirm cap with your lawyer]</mark>.</li>
      </ol>
    </section>

    <section id="ip">
      <h2>11. Intellectual property</h2>
      <p>We and our licensors own all intellectual property rights in the Platform, our website, software, documentation and brand. You receive only the licence described in section 2. If you send us feedback or suggestions, we may use them freely without any obligation to you. You must not remove any copyright, trade mark or other notices from the Platform. The Lead Bird name and logo are our trade marks, and you may not use them without our permission.</p>
    </section>

    <section id="ai">
      <h2>12. AI features</h2>
      <ol class="lg-ol">
        <li><strong>Inputs and outputs.</strong> When you use AI features in the Platform, including Hannah (the “AI Features”), the information you provide (“Inputs”) and the content generated (“Outputs”) are part of your Customer Data.</li>
        <li><strong>Accuracy.</strong> AI-generated Outputs may be inaccurate, incomplete, biased or not unique to you. You are responsible for reviewing Outputs before you rely on them or send them to anyone, and for having a person review them where appropriate.</li>
        <li><strong>Important decisions.</strong> You must not use Outputs as the sole basis for decisions that have legal or similarly significant effects on individuals, such as decisions about credit, employment, housing, insurance, education, health or legal matters.</li>
        <li><strong>Transparency.</strong> You must not present AI-generated content as written by a person where that would mislead, and you must comply with any law that requires you to tell people they are interacting with an AI system.</li>
        <li><strong>Third-party AI providers.</strong> To provide the AI Features, we share Inputs with OpenAI, which acts as our sub-processor. OpenAI does not use data sent through its API to train its models.</li>
        <li><strong>Limits and changes.</strong> Use of AI Features may be limited by your plan or by credits. We may update, improve or withdraw AI Features over time, which may change the Outputs you receive.</li>
      </ol>
    </section>

    <section id="law">
      <h2>13. Governing law and disputes</h2>
      <ol class="lg-ol">
        <li>If you have a concern, please contact us first at the address in section 14 and we will try to resolve it quickly and fairly.</li>
        <li>These Terms and any dispute arising from them are governed by the laws of the Federal Republic of Nigeria, and the courts of Nigeria have exclusive jurisdiction.</li>
      </ol>
    </section>

    <section id="general">
      <h2>14. General terms</h2>
      <ol class="lg-ol">
        <li><strong>Notices.</strong> Notices to us must be in writing and sent to Lead Bird Technologies, 7 Tiamiyu Savage Street, Victoria Island, Lagos State, Nigeria, or by email to <a href="mailto:<?= e((string) (app_config()['contact']['email'] ?? '')) ?>"><?= e((string) (app_config()['contact']['email'] ?? '')) ?></a>. We will send notices to the email address on your account.</li>
        <li><strong>Changes to these Terms.</strong> We may update these Terms from time to time. We will give you at least 30 days’ notice of material changes by email or in the Platform. If you continue to use the Platform after a change takes effect, the updated Terms apply. If you do not agree, you may cancel before the change takes effect.</li>
        <li><strong>Events beyond our control.</strong> Except for payment obligations, neither of us is responsible for delays or failures caused by events beyond our reasonable control.</li>
        <li><strong>Sub-contractors.</strong> We may use sub-contractors to provide the Services, but we remain responsible for their performance.</li>
        <li><strong>Assignment.</strong> You may not transfer your rights under these Terms without our consent. We may transfer our rights to a successor in connection with a merger, acquisition or sale of assets.</li>
        <li><strong>Survival.</strong> Terms which by their nature should continue after these Terms end will continue, including those on payment, data, indemnity, liability and intellectual property.</li>
        <li><strong>Severability.</strong> If any part of these Terms is found to be unenforceable, the rest remains in effect.</li>
        <li><strong>Third parties.</strong> No one other than you and us has any right to enforce these Terms.</li>
        <li><strong>Entire agreement.</strong> These Terms, together with any documents they refer to, are the entire agreement between us about their subject matter.</li>
      </ol>
    </section>
  </article>
</div>

        </main>
    </div>

    <?php require __DIR__ . '/../home/footer.php'; ?>
</div>
