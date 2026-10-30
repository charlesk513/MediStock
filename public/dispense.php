<?php
require_once dirname(__DIR__) . '/app/bootstrap.php'; require_login(); require_role(['ADMIN','PHARMACIST']); require_once dirname(__DIR__) . '/app/layout.php';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();run_action(function()use($pdo){
$s=$pdo->prepare('CALL sp_dispense_medicine(:patient,:staff,:medicine,:qty,:price,@dispensing_id)');
$s->execute(['patient'=>post_int('patient_id',1),'staff'=>current_user()['staff_id'],'medicine'=>post_int('medicine_id',1),'qty'=>post_int('quantity',1,100000000),'price'=>post_decimal('unit_price')]);
$s->closeCursor();
},'Dispensing completed. FEFO allocation and stock movement records were generated.','Dispensing failed.','');redirect('dispense.php');}
$patients=$pdo->query('SELECT patient_id,patient_number,full_name FROM patients ORDER BY full_name')->fetchAll();
$medicines=$pdo->query('SELECT medicine_id,medicine_code,medicine_name,usable_stock,unit_of_measure FROM vw_medicine_stock WHERE usable_stock>0 ORDER BY medicine_name')->fetchAll();
page_header('Dispense medicine','dispense'); ?>
<div class="content-grid two-col"><section class="panel"><div class="panel-heading"><div><span class="eyebrow">PATIENT ISSUE</span><h3>Dispense medicine</h3><p class="muted">The database procedure allocates the earliest-expiring eligible batches first (FEFO).</p></div></div>
<?php if(!$patients||!$medicines): ?><div class="notice">Register a patient and receive usable stock before dispensing.</div><?php else: ?>
<form method="post" class="stack-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label>Patient<select name="patient_id" required><?php foreach($patients as $p): ?><option value="<?= (int)$p['patient_id'] ?>"><?= e($p['full_name'].' · '.$p['patient_number']) ?></option><?php endforeach; ?></select></label><label>Medicine<select name="medicine_id" required><?php foreach($medicines as $m): ?><option value="<?= (int)$m['medicine_id'] ?>"><?= e($m['medicine_name'].' · '.$m['medicine_code'].' — '.$m['usable_stock'].' '.$m['unit_of_measure'].' available') ?></option><?php endforeach; ?></select></label><div class="form-grid"><label>Quantity<input type="number" name="quantity" min="1" max="100000000" required></label><label>Unit price<input type="number" name="unit_price" min="0" step="0.01" required></label></div><button class="btn primary">Confirm dispensing →</button></form><?php endif; ?></section>
<aside class="panel guidance"><div class="guidance-icon">⌁</div><h3>Clinical stock safeguards</h3><ul><li>Only unexpired stock is eligible.</li><li>Insufficient total stock is rejected.</li><li>FEFO allocation may split a request across batches.</li><li>Database row locks protect concurrent dispensing.</li><li>Each line updates the stock movement audit ledger.</li></ul></aside></div>
<?php page_footer(); ?>
