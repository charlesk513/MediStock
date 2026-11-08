<?php
declare(strict_types=1);

/** Create the single PDO connection used by the current request. */
function db(array $settings): PDO
{
    foreach (['host', 'port', 'name', 'user', 'password'] as $key) {
        if (!array_key_exists($key, $settings)) {
            throw new InvalidArgumentException("Missing database setting: {$key}");
        }
    }

    $charset = (string)($settings['charset'] ?? 'utf8mb4');
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
        throw new InvalidArgumentException('Invalid database character set.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $settings['host'],
        (int)$settings['port'],
        $settings['name'],
        $charset
    );

    $pdo = new PDO($dsn, (string)$settings['user'], (string)$settings['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);

    // Keep MySQL date-based expiry rules aligned with the Uganda application timezone.
    $pdo->exec("SET time_zone = '+03:00'");

    return $pdo;
}
