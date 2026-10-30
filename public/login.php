<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || strlen($username) > 60 || strlen($password) > 200) {
        $error = 'Enter a valid username and password.';
    } elseif (login_user($pdo, $username, $password)) {
        redirect('index.php');
    } else {
        $error = 'The username or password is incorrect, or the account is inactive.';
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · MediStock</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-body"><div class="login-wrap"><div class="login-brand"><span class="brand-mark">✚</span><div><strong>MediStock</strong><small>PHARMACY OPERATIONS</small></div></div><section class="login-card"><span class="eyebrow">SECURE ACCESS</span><h1>Welcome back</h1><p>Sign in to manage pharmacy inventory.</p><?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><form method="post" autocomplete="on"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label>Username<input name="username" maxlength="60" autocomplete="username" required autofocus></label><label>Password<input type="password" name="password" maxlength="200" autocomplete="current-password" required></label><button class="btn primary full" type="submit">Sign in securely <span>→</span></button></form></section><div class="login-note">Authorized hospital pharmacy personnel only.</div></div></body></html>
