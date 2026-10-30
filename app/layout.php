<?php
declare(strict_types=1);
function page_header(string $title, string $active = ''): void
{
    $user = current_user();
    $flash = get_flash();
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102c35">
  <title><?= e($title) ?> · MediStock</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-mark">✚</span><span>MediStock<small>PHARMACY OPERATIONS</small></span></a>
    <div class="nav-label">WORKSPACE</div>
    <nav>
      <?php
      $links = [
        'index' => ['Overview', '▦', 'index.php'],
        'medicines' => ['Medicines', '▤', 'medicines.php'],
        'batches' => ['Batch inventory', '◷', 'batches.php'],
        'receive' => ['Receive stock', '↓', 'receive.php'],
        'dispense' => ['Dispense medicine', '↗', 'dispense.php'],
        'suppliers' => ['Suppliers', '⌂', 'suppliers.php'],
        'patients' => ['Patients', '♙', 'patients.php'],
        'reports' => ['Reports & audit', '▥', 'reports.php'],
      ];
      foreach ($links as $key => [$label, $icon, $url]):
      ?>
      <a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= e($url) ?>"><span><?= e($icon) ?></span><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
      <div class="user-chip"><div class="avatar"><?= e(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))) ?></div><div><strong><?= e($user['full_name'] ?? '') ?></strong><small><?= e($user['role'] ?? '') ?></small></div></div>
      <form method="post" action="logout.php"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="logout" type="submit">↪ &nbsp; Sign out</button></form>
    </div>
  </aside>
  <main class="main-area">
    <header class="topbar"><div><span class="eyebrow">HOSPITAL PHARMACY</span><h1><?= e($title) ?></h1></div><div class="topbar-right"><span class="live-dot"></span><span>System online</span><span class="top-date"><?= e(date('D, d M Y')) ?></span></div></header>
    <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
    <div class="page-content">
<?php
}

function page_footer(): void
{
    ?>
    </div>
    <footer class="footer">MediStock · Hospital Pharmacy Stock & Expiry Management <span>Secure inventory workspace</span></footer>
  </main>
</div>
<script src="assets/app.js" defer></script>
</body></html>
<?php
}
