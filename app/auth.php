<?php
declare(strict_types=1);

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }

    // End abandoned sessions after 30 minutes without activity.
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
    if ($lastActivity > 0 && (time() - $lastActivity) > 1800) {
        unset($_SESSION['user'], $_SESSION['last_activity'], $_SESSION['csrf_token']);
        session_regenerate_id(true);
        flash('error', 'Your session expired. Please sign in again.');
        redirect('login.php');
    }
    // Re-check the account on each authenticated request so a disabled user
    // cannot continue using an old session.
    global $pdo;
    $statement = $pdo->prepare(
        'SELECT full_name, username, role, active
         FROM staff_users
         WHERE staff_id = :staff_id
         LIMIT 1'
    );
    $statement->execute(['staff_id' => (int)current_user()['staff_id']]);
    $account = $statement->fetch();

    if (!$account || !(bool)$account['active']) {
        logout_user();
        redirect('login.php');
    }

    $_SESSION['user']['full_name'] = (string)$account['full_name'];
    $_SESSION['user']['username'] = (string)$account['username'];
    $_SESSION['user']['role'] = (string)$account['role'];
    $_SESSION['last_activity'] = time();
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_user()['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to perform this action.');
    }
}

function login_user(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare(
        'SELECT staff_id, full_name, username, password_hash, role, active
         FROM staff_users
         WHERE username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !(bool)$user['active'] || !password_verify($password, (string)$user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'staff_id' => (int)$user['staff_id'],
        'full_name' => (string)$user['full_name'],
        'username' => (string)$user['username'],
        'role' => (string)$user['role'],
    ];
    $_SESSION['last_activity'] = time();
    // Rotate the CSRF token when the authentication state changes.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool)$params['secure'],
            'httponly' => (bool)$params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}
