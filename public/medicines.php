<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();
require_once dirname(__DIR__) . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role(['ADMIN','PHARMACIST','STOREKEEPER']);
    run_action(function () use ($pdo) {
        $code = strtoupper(post_string('medicine_code', 40));
        if (!preg_match('/^[A-Z0-9][A-Z0-9._-]*$/', $code)) throw new InvalidArgumentException('Medicine code may contain letters, numbers, dots, underscores and hyphens only.');
        $stmt = $pdo->prepare('INSERT INTO medicines (category_id, medicine_code, medicine_name, strength, dosage_form, unit_of_measure, reorder_level) VALUES (:category, :code, :name, :strength, :form, :unit, :reorder)');
        $stmt->execute([
            'category' => post_int('category_id',1),
            'code' => $code,
            'name' => post_string('medicine_name',150),
            'strength' => post_string('strength',60,false) ?: null,
            'form' => post_string('dosage_form',60,false) ?: null,
            'unit' => post_string('unit_of_measure',30),
            'reorder' => post_int('reorder_level',0,100000000),
        ]);
    }, 'Medicine added successfully.', 'Medicine could not be saved.');
    redirect('medicines.php');
}
$categories = $pdo->query('SELECT category_id, category_name FROM medicine_categories ORDER BY category_name')->fetchAll();
$search = trim((string)($_GET['q'] ?? ''));
$stmt = $pdo->prepare('SELECT v.* FROM vw_medicine_stock v WHERE (:q = "" OR v.medicine_name LIKE :likeq OR v.medicine_code LIKE :likeq) ORDER BY v.medicine_name LIMIT 200');
$stmt->execute(['q'=>$search,'likeq'=>'%'.$search.'%']);
$rows = $stmt->fetchAll();
page_header('Medicine catalogue','medicines');
?>
<div class="page-toolbar"><div><p class="muted">Manage the approved medicine catalogue and reorder thresholds.</p></div><a class="btn primary" href="#add-medicine">＋ Add medicine</a></div>
<div class="panel form-panel" id="add-medicine"><div class="panel-heading"><div><span class="eyebrow">CATALOGUE ENTRY</span><h3>Add a medicine</h3></div></div>
<?php if (!$categories): ?><p class="notice">Add a medicine category in the database before registering medicines.</p><?php else: ?>
<form method="post" class="form-grid"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
<label>Medicine name<input name="medicine_name" maxlength="150" required></label><label>Medicine code<input name="medicine_code" maxlength="40" required placeholder="e.g. MED-004"></label>
<label>Category<select name="category_id" required><?php foreach($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option><?php endforeach; ?></select></label>
<label>Strength<input name="strength" maxlength="60" placeholder="e.g. 500 mg"></label><label>Dosage form<input name="dosage_form" maxlength="60" placeholder="Tablet, capsule..."></label><label>Unit of measure<input name="unit_of_measure" maxlength="30" value="unit" required></label><label>Reorder level<input type="number" name="reorder_level" min="0" max="100000000" value="0" required></label><div class="form-actions"><button class="btn primary">Save medicine</button></div></form><?php endif; ?></div>
<div class="panel"><div class="panel-heading"><div><span class="eyebrow">REGISTERED ITEMS</span><h3>Medicine stock summary <span class="count"><?= count($rows) ?></span></h3></div><form class="search-form"><input name="q" value="<?= e($search) ?>" placeholder="Search name or code"><button class="btn secondary">Search</button></form></div>
<div class="table-wrap"><table><thead><tr><th>Code / medicine</th><th>Category</th><th>Strength / form</th><th>Usable stock</th><th>Expired stock</th><th>Reorder level</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><strong><?= e($r['medicine_name']) ?></strong><small><?= e($r['medicine_code']) ?></small></td><td><?= e($r['category_name']) ?></td><td><?= e(trim(($r['strength']??'').' '.($r['dosage_form']??''))) ?></td><td><strong><?= number_format((int)$r['usable_stock']) ?></strong> <?= e($r['unit_of_measure']) ?></td><td><?= number_format((int)$r['expired_stock']) ?></td><td><?= number_format((int)$r['reorder_level']) ?></td><td><?= (int)$r['usable_stock'] <= (int)$r['reorder_level'] ? '<span class="pill danger">Low stock</span>' : '<span class="pill success">Healthy</span>' ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="7" class="empty-state">No medicines found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php page_footer(); ?>
