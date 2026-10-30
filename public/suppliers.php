<?php
require_once dirname(__DIR__) . '/app/bootstrap.php'; require_login(); require_once dirname(__DIR__) . '/app/layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); require_role(['ADMIN','STOREKEEPER']); run_action(function() use($pdo){
$name=post_string('supplier_name',150); $email=post_string('email',150,false); if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid supplier email address.');
$s=$pdo->prepare('INSERT INTO suppliers (supplier_name,contact_person,phone,email,address) VALUES (?,?,?,?,?)');
$s->execute([$name,post_string('contact_person',120,false)?:null,post_string('phone',30,false)?:null,$email?:null,post_string('address',255,false)?:null]);
},'Supplier added successfully.','Supplier could not be saved.'); redirect('suppliers.php'); }
$rows=$pdo->query('SELECT supplier_id,supplier_name,contact_person,phone,email,address,created_at FROM suppliers ORDER BY supplier_name')->fetchAll();
page_header('Suppliers','suppliers'); ?>
<div class="content-grid two-col"><section class="panel"><div class="panel-heading"><div><span class="eyebrow">PARTNER DIRECTORY</span><h3>Add supplier</h3></div></div><form method="post" class="stack-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label>Supplier name<input name="supplier_name" maxlength="150" required></label><label>Contact person<input name="contact_person" maxlength="120"></label><label>Phone<input name="phone" maxlength="30"></label><label>Email<input type="email" name="email" maxlength="150"></label><label>Address<input name="address" maxlength="255"></label><button class="btn primary">Save supplier</button></form></section>
<section class="panel"><div class="panel-heading"><div><span class="eyebrow">REGISTERED PARTNERS</span><h3>Supplier directory</h3></div><span class="count"><?= count($rows) ?></span></div><div class="mini-list"><?php foreach($rows as $r): ?><div class="directory-row"><strong><?= e($r['supplier_name']) ?></strong><small><?= e($r['contact_person']??'') ?> · <?= e($r['phone']??'') ?></small><small><?= e($r['email']??'') ?></small></div><?php endforeach; ?><?php if(!$rows): ?><div class="empty-state">No suppliers registered.</div><?php endif; ?></div></section></div>
<?php page_footer(); ?>
