<?php
require_once dirname(__DIR__) . '/app/bootstrap.php'; require_login(); require_once dirname(__DIR__) . '/app/layout.php';
$tab=$_GET['tab']??'stock';
$allowed=['stock','expired','near','dispensing','audit']; if(!in_array($tab,$allowed,true))$tab='stock';
$data=[];
if($tab==='stock')$data=$pdo->query('SELECT * FROM vw_medicine_stock ORDER BY medicine_name')->fetchAll();
if($tab==='expired')$data=$pdo->query('SELECT * FROM vw_expired_batches ORDER BY expiry_date')->fetchAll();
if($tab==='near')$data=$pdo->query('SELECT * FROM vw_near_expiry_batches ORDER BY expiry_date')->fetchAll();
if($tab==='dispensing')$data=$pdo->query('SELECT * FROM vw_dispensing_summary ORDER BY dispensing_date DESC LIMIT 250')->fetchAll();
if($tab==='audit')$data=$pdo->query('SELECT sm.movement_id,sm.movement_date,sm.movement_type,sm.quantity_change,sm.reference_type,sm.reference_id,sm.remarks,m.medicine_name,b.batch_number,s.full_name FROM stock_movements sm JOIN medicine_batches b ON b.batch_id=sm.batch_id JOIN medicines m ON m.medicine_id=b.medicine_id JOIN staff_users s ON s.staff_id=sm.performed_by ORDER BY sm.movement_date DESC LIMIT 500')->fetchAll();
$tabs=['stock'=>'Stock summary','expired'=>'Expired stock','near'=>'Near expiry','dispensing'=>'Dispensing','audit'=>'Movement audit'];
page_header('Reports & audit','reports'); ?>
<div class="panel"><div class="tabs"><?php foreach($tabs as $k=>$v): ?><a class="<?= $tab===$k?'selected':'' ?>" href="?tab=<?= e($k) ?>"><?= e($v) ?></a><?php endforeach; ?></div>
<div class="panel-heading"><div><span class="eyebrow">OPERATIONAL REPORT</span><h3><?= e($tabs[$tab]) ?> <span class="count"><?= count($data) ?></span></h3></div><button class="btn secondary" onclick="window.print()" type="button">Print report</button></div>
<div class="table-wrap"><table><thead><tr><?php if($tab==='stock'): ?><th>Medicine</th><th>Category</th><th>Usable</th><th>Expired</th><th>Reorder level</th><?php elseif($tab==='expired'||$tab==='near'): ?><th>Medicine</th><th>Batch</th><th>Expiry</th><th>Quantity</th><?php if($tab==='near'): ?><th>Days left</th><?php endif; ?><?php elseif($tab==='dispensing'): ?><th>Dispensing #</th><th>Date</th><th>Patient</th><th>Staff</th><th>Status</th><th>Units</th><th>Total</th><?php else: ?><th>Date</th><th>Medicine / batch</th><th>Movement</th><th>Change</th><th>Reference</th><th>Performed by</th><?php endif; ?></tr></thead><tbody>
<?php foreach($data as $r): ?><tr>
<?php if($tab==='stock'): ?><td><strong><?= e($r['medicine_name']) ?></strong><small><?= e($r['medicine_code']) ?></small></td><td><?= e($r['category_name']) ?></td><td><?= number_format((int)$r['usable_stock']) ?></td><td><?= number_format((int)$r['expired_stock']) ?></td><td><?= number_format((int)$r['reorder_level']) ?></td>
<?php elseif($tab==='expired'||$tab==='near'): ?><td><strong><?= e($r['medicine_name']) ?></strong><small><?= e($r['medicine_code']) ?></small></td><td><?= e($r['batch_number']) ?></td><td><?= e($r['expiry_date']) ?></td><td><?= number_format((int)$r['quantity_on_hand']) ?></td><?php if($tab==='near'): ?><td><?= (int)$r['days_until_expiry'] ?></td><?php endif; ?>
<?php elseif($tab==='dispensing'): ?><td>#<?= (int)$r['dispensing_id'] ?></td><td><?= e($r['dispensing_date']) ?></td><td><?= e($r['patient_name']) ?><small><?= e($r['patient_number']) ?></small></td><td><?= e($r['dispensed_by']) ?></td><td><?= e($r['status']) ?></td><td><?= number_format((int)$r['total_units']) ?></td><td><?= number_format((float)$r['total_amount'],2) ?></td>
<?php else: ?><td><?= e($r['movement_date']) ?></td><td><strong><?= e($r['medicine_name']) ?></strong><small><?= e($r['batch_number']) ?></small></td><td><?= e($r['movement_type']) ?></td><td class="<?= (int)$r['quantity_change']<0?'negative':'positive' ?>"><?= (int)$r['quantity_change']>0?'+':'' ?><?= number_format((int)$r['quantity_change']) ?></td><td><?= e($r['reference_type'].' #'.$r['reference_id']) ?></td><td><?= e($r['full_name']) ?></td><?php endif; ?></tr><?php endforeach; ?>
<?php if(!$data): ?><tr><td colspan="8" class="empty-state">No records are available for this report.</td></tr><?php endif; ?></tbody></table></div></div>
<?php page_footer(); ?>
