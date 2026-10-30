<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();
require_once dirname(__DIR__) . '/app/layout.php';

$stats = [
    'medicines' => (int)$pdo->query('SELECT COUNT(*) FROM medicines WHERE active = 1')->fetchColumn(),
    'usable' => (int)$pdo->query('SELECT COALESCE(SUM(usable_stock),0) FROM vw_medicine_stock')->fetchColumn(),
    'low' => (int)$pdo->query('SELECT COUNT(*) FROM vw_low_stock_medicines')->fetchColumn(),
    'expired' => (int)$pdo->query('SELECT COUNT(*) FROM vw_expired_batches')->fetchColumn(),
];
$near = $pdo->query('SELECT * FROM vw_near_expiry_batches ORDER BY expiry_date ASC LIMIT 6')->fetchAll();
$low = $pdo->query('SELECT medicine_code, medicine_name, usable_stock, reorder_level, unit_of_measure FROM vw_low_stock_medicines ORDER BY usable_stock ASC LIMIT 6')->fetchAll();
$recent = $pdo->query(
    'SELECT sm.movement_date, sm.movement_type, sm.quantity_change, sm.reference_type,
            m.medicine_name, b.batch_number, s.full_name
     FROM stock_movements sm
     JOIN medicine_batches b ON b.batch_id = sm.batch_id
     JOIN medicines m ON m.medicine_id = b.medicine_id
     JOIN staff_users s ON s.staff_id = sm.performed_by
     ORDER BY sm.movement_date DESC LIMIT 7'
)->fetchAll();

page_header('Overview', 'index');
?>
<section class="welcome-panel">
  <div><span class="eyebrow light">INVENTORY CONTROL CENTRE</span><h2>Good <?= (int)date('G') < 12 ? 'morning' : ((int)date('G') < 17 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', current_user()['full_name'])[0]) ?>.</h2><p>Stay ahead of stock levels, expiry dates and medicine movement.</p></div>
  <div class="welcome-symbol">✚</div>
</section>
<section class="stat-grid">
  <article class="stat-card"><div class="stat-icon teal">▤</div><div class="stat-label">Active medicines</div><div class="stat-number"><?= number_format($stats['medicines']) ?></div><div class="stat-foot">Registered catalogue</div></article>
  <article class="stat-card"><div class="stat-icon blue">▦</div><div class="stat-label">Usable stock units</div><div class="stat-number"><?= number_format($stats['usable']) ?></div><div class="stat-foot">Unexpired batches only</div></article>
  <article class="stat-card"><div class="stat-icon amber">⌁</div><div class="stat-label">Low-stock medicines</div><div class="stat-number"><?= number_format($stats['low']) ?></div><div class="stat-foot">At or below reorder level</div></article>
  <article class="stat-card"><div class="stat-icon red">◷</div><div class="stat-label">Expired batches</div><div class="stat-number"><?= number_format($stats['expired']) ?></div><div class="stat-foot">With stock remaining</div></article>
</section>
<section class="content-grid">
  <article class="panel span-2"><div class="panel-heading"><div><span class="eyebrow">ATTENTION REQUIRED</span><h3>Approaching expiry</h3></div><a class="text-link" href="batches.php">View all →</a></div>
    <?php if (!$near): ?><div class="empty-state">No batches are approaching expiry within 90 days.</div><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Medicine</th><th>Batch</th><th>Expiry date</th><th>Remaining</th><th>Days left</th></tr></thead><tbody>
    <?php foreach ($near as $r): ?><tr><td><strong><?= e($r['medicine_name']) ?></strong><small><?= e($r['medicine_code']) ?></small></td><td><?= e($r['batch_number']) ?></td><td><?= e($r['expiry_date']) ?></td><td><?= number_format((int)$r['quantity_on_hand']) ?></td><td><span class="pill warning"><?= (int)$r['days_until_expiry'] ?> days</span></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </article>
  <article class="panel"><div class="panel-heading"><div><span class="eyebrow">REPLENISHMENT</span><h3>Low stock</h3></div></div>
    <?php if (!$low): ?><div class="empty-state">All medicines are above reorder levels.</div><?php else: ?>
    <div class="mini-list"><?php foreach ($low as $r): ?><div class="mini-row"><div><strong><?= e($r['medicine_name']) ?></strong><small><?= e($r['medicine_code']) ?></small></div><div class="stock-value"><?= number_format((int)$r['usable_stock']) ?><small> / <?= number_format((int)$r['reorder_level']) ?> <?= e($r['unit_of_measure']) ?></small></div></div><?php endforeach; ?></div><?php endif; ?>
  </article>
  <article class="panel span-3"><div class="panel-heading"><div><span class="eyebrow">TRACEABILITY</span><h3>Recent stock movements</h3></div><a class="text-link" href="reports.php">Full audit trail →</a></div>
    <?php if (!$recent): ?><div class="empty-state">No stock movement has been recorded yet.</div><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Date & time</th><th>Medicine / batch</th><th>Movement</th><th>Quantity</th><th>Recorded by</th></tr></thead><tbody>
    <?php foreach ($recent as $r): ?><tr><td><?= e($r['movement_date']) ?></td><td><strong><?= e($r['medicine_name']) ?></strong><small><?= e($r['batch_number']) ?></small></td><td><?= e(str_replace('_',' ', $r['movement_type'])) ?></td><td class="<?= (int)$r['quantity_change'] < 0 ? 'negative' : 'positive' ?>"><?= (int)$r['quantity_change'] > 0 ? '+' : '' ?><?= number_format((int)$r['quantity_change']) ?></td><td><?= e($r['full_name']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </article>
</section>
<?php page_footer(); ?>
