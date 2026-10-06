<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$config = app_config()['mail'];
$email = normalize_email((string) $config['admin_email']);
$stmt = Database::connection()->prepare('SELECT id FROM mail_users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    fwrite(STDOUT, "Mail administrator already exists: {$email}\n");
    exit(0);
}

$password = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
$stmt = Database::connection()->prepare(
    'INSERT INTO mail_users (email, display_name, password_hash, role, is_active, must_change_password, avatar_color)
     VALUES (?, ?, ?, "admin", 1, 1, ?)'
);
$stmt->execute([
    $email,
    (string) $config['admin_display_name'],
    password_hash($password, PASSWORD_DEFAULT),
    '#0038FF',
]);

fwrite(STDOUT, "Created mail administrator: {$email}\n");
fwrite(STDERR, "ONE-TIME MAIL ADMIN PASSWORD: {$password}\n");
