<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$appUrl = app_config()['app_url'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('contact'));
}

if (!rate_limit_allow('contact_submit', 5, 3600)) {
    flash_set('error', 'Too many messages sent. Please try again later.');
    redirect(app_route('contact'));
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$email = normalize_email((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($firstName === '' || $lastName === '' || $subject === '' || $message === '') {
    flash_set('error', 'Please fill in all required fields.');
    redirect(app_route('contact'));
}

if (!is_valid_email($email)) {
    flash_set('error', 'Please enter a valid email address.');
    redirect(app_route('contact'));
}

$formAge = time() - (int) ($_SESSION['contact_form_at'] ?? 0);
$fullName = $firstName . ' ' . $lastName;
$isSpam = trim((string) ($_POST['company_site'] ?? '')) !== ''
    || $formAge < 3
    || $formAge > 86400
    // Bot names like "LarrypafPA" / "AndrewcogCX", or jumbled names with digits.
    || preg_match('/\b[A-Z][a-z]{4,}[A-Z]{2}\b/', $fullName) === 1
    || preg_match('/\d/', $fullName) === 1
    || preg_match_all('#https?://|www\.#i', $subject . ' ' . $message) > 2;

if ($isSpam) {
    error_log('Contact form: blocked likely spam from ' . client_ip());
    flash_set('success', 'Thanks for reaching out. We will get back to you shortly.');
    redirect(app_route('contact'));
}
unset($_SESSION['contact_form_at']);

try {
    (new ResendClient())->sendContactMessage([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
    ]);

    flash_set('success', 'Thanks for reaching out. We will get back to you shortly.');
} catch (Throwable $e) {
    $errorMessage = 'Could not send your message right now. Please try again or email us directly.';

    flash_set('error', $errorMessage);
}

redirect(app_route('contact'));
