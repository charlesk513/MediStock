<?php
/* Run from the terminal only: php scripts/create_admin.php */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Missing config.php. Copy config.example.php and configure MySQL first.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/app/db.php';
$config = require $configFile;
$pdo = db($config['db']);

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    return trim((string)fgets(STDIN));
}

function prompt_password(): string
{
    fwrite(STDOUT, 'Password (12–200 characters): ');
    $canHide = PHP_OS_FAMILY !== 'Windows' && function_exists('shell_exec');
    if ($canHide) {
        system('stty -echo');
    }
    $password = trim((string)fgets(STDIN));
    if ($canHide) {
        system('stty echo');
        fwrite(STDOUT, PHP_EOL);
    }
    return $password;
}

echo "MediStock administrator setup\n";
$name = prompt('Full name: ');
$username = prompt('Username: ');
$password = prompt_password();

if ($name === '' || (preg_match_all('/./us', $name) ?: strlen($name)) > 150 || !preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username) || strlen($password) < 12 || strlen($password) > 200) {
    fwrite(STDERR, "Invalid details. Name is required, username must be 3–60 letters/numbers/dots/underscores/hyphens, and password must be 12–200 characters.\n");
    exit(1);
}

$check = $pdo->prepare('SELECT COUNT(*) FROM staff_users WHERE username = ?');
$check->execute([$username]);
if ((int)$check->fetchColumn() > 0) {
    fwrite(STDERR, "That username already exists.\n");
    exit(1);
}

$insert = $pdo->prepare("INSERT INTO staff_users (full_name, username, password_hash, role, active) VALUES (?, ?, ?, 'ADMIN', 1)");
$insert->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
echo "Administrator created. Sign in through /login.php.\n";
