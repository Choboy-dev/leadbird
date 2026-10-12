<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
}

if (!csrf_verify((string) ($input['_csrf'] ?? ''))) {
    json_response(['ok' => false, 'error' => 'Session expired. Refresh the page and try again.'], 419);
}

// Bots fill the hidden "website" field. Pretend it worked so they move on.
if (trim((string) ($input['website'] ?? '')) !== '') {
    json_response(['ok' => true]);
}

if (!rate_limit_allow('tool_lead', 20, 3600)) {
    json_response(['ok' => false, 'error' => 'Too many requests. Please try again later.'], 429);
}

$allowedTools = ['chat-link-generator', 'qr-code-generator', 'chat-button-generator'];
$tool = (string) ($input['tool'] ?? '');
$email = normalize_email((string) ($input['email'] ?? ''));
$phone = preg_replace('/\D+/', '', (string) ($input['number'] ?? '')) ?? '';
$brand = trim(preg_replace('/\s+/u', ' ', (string) ($input['brand'] ?? '')) ?? '');
$brand = $brand === '' ? null : mb_substr($brand, 0, 60);

if (!in_array($tool, $allowedTools, true)) {
    json_response(['ok' => false, 'error' => 'Unknown tool.'], 422);
}

if (!is_valid_email($email) || strlen($email) > 190) {
    json_response(['ok' => false, 'error' => 'Please enter a valid business email address.'], 422);
}

if (strlen($phone) < 7 || strlen($phone) > 15) {
    json_response(['ok' => false, 'error' => 'Please enter a valid WhatsApp number.'], 422);
}

try {
    $statement = Database::connection()->prepare(
        'INSERT INTO tool_leads (tool, email, phone, brand_name, ip_address, user_agent)
         VALUES (:tool, :email, :phone, :brand_name, :ip_address, :user_agent)'
    );
    $statement->execute([
        'tool' => $tool,
        'email' => $email,
        'phone' => '+' . $phone,
        'brand_name' => $brand,
        'ip_address' => client_ip(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
} catch (Throwable $e) {
    error_log('tool lead save failed: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Could not save your details.'], 500);
}

json_response(['ok' => true]);
