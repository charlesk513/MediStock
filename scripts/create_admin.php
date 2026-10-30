<?php
/*
 * Run from terminal, not through the web:
 * php scripts/create_admin.php
 * This creates a first ADMIN account only when the username is unused.
 */
declare(strict_types=1);
$config = require dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/db.php';
$pdo = db($config['db']);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}
echo "MediStock administrator setup\n";
echo "Full name: ";
$name = trim((string)fgets(STDIN));
echo "Username: ";
$username = trim((string)fgets(STDIN));
echo "Password (minimum 12 characters): ";
if (PHP_OS_FAMILY === 'Windows') {
    $password = trim((string)fgets(STDIN));
} else {
    system('stty -echo');
    $password = trim((string)fgets(STDIN));
    system('stty echo');
    echo PHP_EOL;
}
if ($name === '' || $username === '' || strlen($username) > 60 || strlen($password) < 12 || strlen($password) > 200) {
    fwrite(STDERR, "Invalid details. Name and username are required; password must be 12–200 characters.\n");
    exit(1);
}
$check = $pdo->prepare('SELECT COUNT(*) FROM staff_users WHERE username = ?');
$check->execute([$username]);
if ((int)$check->fetchColumn() > 0) {
    fwrite(STDERR, "That username already exists.\n");
    exit(1);
}
$insert = $pdo->prepare("INSERT INTO staff_users (full_name,username,password_hash,role,active) VALUES (?,?,?,'ADMIN',1)");
$insert->execute([$name,$username,password_hash($password, PASSWORD_DEFAULT)]);
echo "Administrator created. Sign in through /login.php.\n";
