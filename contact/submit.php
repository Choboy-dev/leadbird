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
