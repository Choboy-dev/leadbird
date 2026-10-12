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
    <p class="lg-kicker">Lead Bird privacy</p>
    <h1>Privacy Policy</h1>
    <p class="lg-updated">Last updated 11 October 2026</p>
  </div>
</header>

<div class="wrap lg-layout">
  <nav class="lg-toc" aria-label="On this page">
    <b>On this page</b>
    <ol>
      <li><a href="#who-we-are">Who we are</a></li><li><a href="#roles">Our two roles</a></li><li><a href="#collect">What we collect and why</a></li><li><a href="#whatsapp">WhatsApp and the Cloud API</a></li><li><a href="#ai">AI features</a></li><li><a href="#sharing">Who we share data with</a></li><li><a href="#transfers">International transfers</a></li><li><a href="#retention">How long we keep data</a></li><li><a href="#security">How we protect data</a></li><li><a href="#rights">Your rights</a></li><li><a href="#cookies">Cookies and similar technologies</a></li><li><a href="#children">Children</a></li><li><a href="#changes">Changes to this policy</a></li><li><a href="#contact">Contact us</a></li>    </ol>
  </nav>

  <article class="lg-body">
    <p>When you use our website or platform, you trust us with information about you. This policy explains what personal data we collect, why we collect it, who we share it with, and the choices and rights you have.</p>

    <section id="who-we-are">
      <h2>1. Who we are</h2>
      <p>Lead Bird is a customer engagement platform that lets businesses talk to their customers on WhatsApp and other messaging channels. It is operated by Lead Bird Technologies, a business name registered with the Corporate Affairs Commission of Nigeria (registration number RN9622070), with its principal place of business at 7 Tiamiyu Savage Street, Victoria Island, Lagos State, Nigeria (“Lead Bird”, “we”, “us” or “our”).</p>
      <p>We process personal data in line with the Nigeria Data Protection Act 2023 (NDPA) and the regulations of the Nigeria Data Protection Commission (NDPC). For any privacy or data protection question, contact our privacy team at <a href="mailto:<?= e((string) (app_config()['contact']['email'] ?? '')) ?>"><?= e((string) (app_config()['contact']['email'] ?? '')) ?></a>.</p>
    </section>

    <section id="roles">
      <h2>2. Our two roles</h2>
      <ul class="lg-ul">
        <li><strong>Controller.</strong> For information about visitors to our website, people who contact us or request a demo, and the people who sign up for and use Lead Bird accounts, we decide how and why the data is used. This policy covers that data.</li>
        <li><strong>Processor.</strong> Our customers use Lead Bird to store and send messages to their own contacts. For that data (“Customer Data”), the business using Lead Bird is the controller and we process it only on their instructions, under our <a href="<?= e(app_route('terms')) ?>">Terms</a> and Data Processing Agreement. If you received a message from a business that uses Lead Bird, please contact that business about your data. We will help them respond to your request.</li>
      </ul>
    </section>

    <section id="collect">
      <h2>3. What we collect and why</h2>
      <div class="lg-table-wrap">
        <table class="lg-table">
          <thead><tr><th scope="col">When</th><th scope="col">What we collect</th><th scope="col">Why we use it</th><th scope="col">Lawful basis</th></tr></thead>
          <tbody>
            <tr><td>You book a demo</td><td>Name, business email, country, WhatsApp number, team size, and your marketing preference</td><td>To arrange and run your demo and follow up</td><td>Legitimate interests; consent for marketing emails</td></tr>
            <tr><td>You create an account</td><td>First and last name, business email, password (stored only as a secure hash), phone number and country, how you heard about us, and your marketing preference</td><td>To create and manage your account, provide the service, and keep it secure</td><td>Contract</td></tr>
            <tr><td>You log in or reset your password</td><td>Email address, IP address, time of failed attempts, and one-time reset links (stored only as a hash)</td><td>To protect accounts from unauthorised access and abuse</td><td>Legitimate interests</td></tr>
            <tr><td>You subscribe to our newsletter</td><td>Email address</td><td>To send you our newsletter</td><td>Consent</td></tr>
            <tr><td>You request an eBook</td><td>Name, email, company (optional), the eBook requested</td><td>To send the eBook you asked for and, with your agreement, related updates</td><td>Consent</td></tr>
            <tr><td>You use our WhatsApp chat link or chat button generator</td><td>Business email, WhatsApp number and, for the chat button, your brand name (welcome messages aren’t stored)</td><td>To provide the tool and follow up about Lead Bird. You can object at any time</td><td>Legitimate interests</td></tr>
            <tr><td>You visit a website that uses our WhatsApp chat widget</td><td>The widget script is loaded from our servers, so we receive your IP address, browser details and the page address in our server logs. The widget sets no cookies</td><td>To deliver the widget and keep it secure</td><td>Legitimate interests</td></tr>
            <tr><td>You ask us for a new integration</td><td>Email and the integration name</td><td>To let you know when it is available</td><td>Legitimate interests</td></tr>
            <tr><td>You buy a paid plan</td><td>Company name, billing address, Tax Identification Number (TIN), payment details (handled by our payment provider Flutterwave, we don’t store card numbers)</td><td>To take payment, invoice you and meet tax obligations</td><td>Contract; legal obligation</td></tr>
            <tr><td>You use the platform</td><td>Usage and log information such as features used, device and browser type, IP address and error logs</td><td>To run, support, secure and improve the service</td><td>Legitimate interests</td></tr>
            <tr><td>You contact us</td><td>Your name, contact details and what you tell us</td><td>To reply and help you</td><td>Legitimate interests</td></tr>
            <tr><td>You visit our website</td><td>IP address, browser and request details recorded in server logs</td><td>To deliver the website and keep it secure</td><td>Legitimate interests</td></tr>
          </tbody>
        </table>
      </div>
      <p>All of our forms record the date and the IP address the form was sent from, to help us prevent spam and abuse. We only send marketing emails if you ticked the box to receive them, and every email includes a way to unsubscribe.</p>
    </section>

    <section id="whatsapp">
      <h2>4. WhatsApp and the Cloud API</h2>
      <p>Lead Bird sends and receives WhatsApp messages through the WhatsApp Business Platform (Cloud API), operated by Meta. When a customer sends a message using Lead Bird, the message, the recipient’s phone number and related information pass through Meta’s systems, which may store them temporarily to deliver them. Meta’s handling of that data is described in its <a href="https://www.whatsapp.com/legal/business-data-processing-terms" rel="noopener" target="_blank">WhatsApp Business Data Processing Terms</a> and <a href="https://www.whatsapp.com/legal/privacy-policy" rel="noopener" target="_blank">WhatsApp Privacy Policy</a>.</p>
      <p>If a business uses bulk WhatsApp messaging, we store the contact list it adds (names and WhatsApp numbers), whether each person has opted out, and the delivery status of each message, so we can send its broadcasts and honour opt-outs. We process this on the business’s behalf. If you receive a broadcast from a business and no longer want them, reply STOP and you will not receive further broadcasts from that business.</p>
    </section>

    <section id="ai">
      <h2>5. AI features</h2>
      <p>If you use AI features such as Hannah, the information you provide and the content generated are processed to provide the feature. We use OpenAI as a sub-processor to do this. OpenAI processes this data in the United States and does not use data sent through its API to train its models. You can choose not to use AI features.</p>
    </section>

    <section id="sharing">
      <h2>6. Who we share data with</h2>
      <p>We do not sell personal data. We share it only as follows:</p>
      <ul class="lg-ul">
        <li><strong>Service providers</strong> who help us run Lead Bird, under contracts that require them to protect the data and use it only on our instructions. These include our hosting provider (Hostinger), Meta for WhatsApp, Instagram and Facebook Messenger, TikTok for the TikTok accounts you connect, Google for Google sign-in and website fonts, our email provider Resend, our payment provider Flutterwave and our AI provider OpenAI.</li>
        <li><strong>Business changes.</strong> If we are involved in a merger, acquisition, financing or sale of assets, data may be transferred as part of that deal, subject to this policy.</li>
        <li><strong>Legal reasons.</strong> Where we must by law, or to respond to valid requests from public authorities.</li>
        <li><strong>Protecting rights and safety.</strong> To enforce our terms, or to prevent fraud, abuse or harm.</li>
      </ul>
    </section>

    <section id="transfers">
      <h2>7. International transfers</h2>
      <p>Some of our service providers process data outside Nigeria. When that happens, we make sure the transfer meets the requirements of the Nigeria Data Protection Act 2023, for example by relying on an adequacy decision of the Nigeria Data Protection Commission, binding corporate rules, or contractual clauses that give your data the same level of protection. Our hosting is located in Germany, in the European Union.</p>
    </section>

    <section id="retention">
      <h2>8. How long we keep data</h2>
      <div class="lg-table-wrap">
        <table class="lg-table">
          <thead><tr><th scope="col">Data</th><th scope="col">How long</th></tr></thead>
          <tbody>
            <tr><td>Account information</td><td>While your account is open, then 12 months after it closes</td></tr>
            <tr><td>Customer Data (contacts, messages)</td><td>While the subscription is active, then deleted within 90 days, as set out in our Terms</td></tr>
            <tr><td>Billing and invoices</td><td>6 years, to meet Nigerian tax and accounting rules</td></tr>
            <tr><td>Demo, eBook and integration requests</td><td>24 months from your last contact with us</td></tr>
            <tr><td>Newsletter subscription</td><td>Until you unsubscribe</td></tr>
            <tr><td>Failed login records</td><td>30 days</td></tr>
            <tr><td>Password reset links</td><td>Expire after 60 minutes and are deleted once used or replaced</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section id="security">
      <h2>9. How we protect data</h2>
      <p>We use technical and organisational measures appropriate to the risk, including encryption in transit (HTTPS), passwords stored only as strong one-way hashes, one-time reset links, limits on repeated login attempts, access controls that restrict data to people who need it, and configuration files kept outside the public website. No system is completely secure, so if you think your account has been compromised, please contact us straight away.</p>
    </section>

    <section id="rights">
      <h2>10. Your rights</h2>
      <p>Under the Nigeria Data Protection Act 2023 you have the right to:</p>
      <ul class="lg-ul">
        <li><strong>access</strong> the personal data we hold about you;</li>
        <li><strong>correct</strong> data that is inaccurate or incomplete;</li>
        <li><strong>delete</strong> your data in certain circumstances;</li>
        <li><strong>restrict</strong> how we use your data in certain circumstances;</li>
        <li><strong>data portability</strong>, to receive your data in a structured, machine-readable format;</li>
        <li><strong>object</strong> to processing based on legitimate interests, and to direct marketing at any time; and</li>
        <li><strong>withdraw consent</strong> at any time, where we rely on consent.</li>
      </ul>
      <p>To use any of these rights, contact us using the details in section 14. We will respond within 30 days, and may need to confirm your identity first. If you are unhappy with how we handle your data, you can complain to the <a href="https://ndpc.gov.ng" rel="noopener" target="_blank">Nigeria Data Protection Commission</a>, although we would appreciate the chance to help first.</p>
    </section>

    <section id="cookies">
      <h2>11. Cookies and similar technologies</h2>
      <p>We keep cookies to a minimum. At the moment we use:</p>
      <div class="lg-table-wrap">
        <table class="lg-table">
          <thead><tr><th scope="col">Name</th><th scope="col">Type</th><th scope="col">Purpose</th><th scope="col">Duration</th></tr></thead>
          <tbody>
            <tr><td><code>PHPSESSID</code></td><td>Strictly necessary cookie</td><td>Keeps you logged in and protects our forms against cross-site request forgery</td><td>Until you close your browser</td></tr>
            <tr><td><code>ve-newsletter-seen</code></td><td>Browser storage (not sent to us)</td><td>Remembers that you have seen the blog newsletter pop-up so it isn’t shown again</td><td>Until you clear it</td></tr>
          </tbody>
        </table>
      </div>
      <p>Our website loads fonts from Google Fonts, which means your browser connects to Google’s servers and shares your IP address with Google. See <a href="https://policies.google.com/privacy" rel="noopener" target="_blank">Google’s Privacy Policy</a>.</p>
      <p>We do not currently use analytics or advertising cookies. If we add them, we will update this policy and ask for your consent first.</p>
    </section>

    <section id="children">
      <h2>12. Children</h2>
      <p>Lead Bird is a business service and is not intended for anyone under 18. We do not knowingly collect personal data from children. If you believe a child has given us personal data, please contact us and we will delete it.</p>
    </section>

    <section id="changes">
      <h2>13. Changes to this policy</h2>
      <p>We may update this policy from time to time. We will change the “last updated” date above and, for significant changes, let account holders know by email or in the platform.</p>
    </section>

    <section id="contact">
      <h2>14. Contact us</h2>
      <p>For questions about this policy or to use your rights, contact us at <a href="mailto:<?= e((string) (app_config()['contact']['email'] ?? '')) ?>"><?= e((string) (app_config()['contact']['email'] ?? '')) ?></a> or write to Lead Bird Limited, <mark class="lg-fill" title="Placeholder: complete before publishing">[registered address]</mark>.</p>
    </section>
  </article>
</div>

        </main>
    </div>

    <?php require __DIR__ . '/../home/footer.php'; ?>
</div>
