<?php
require_once dirname(__DIR__) . '/app/bootstrap.php'; require_login(); require_once dirname(__DIR__) . '/app/layout.php';
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); require_role(['ADMIN','PHARMACIST']); run_action(function() use($pdo){
$number=strtoupper(post_string('patient_number',40)); if(!preg_match('/^[A-Z0-9._-]+$/',$number)) throw new InvalidArgumentException('Patient number contains unsupported characters.');
$dob=post_string('date_of_birth',10,false); if($dob!==''){ $d=DateTimeImmutable::createFromFormat('!Y-m-d',$dob); if(!$d || $d->format('Y-m-d')!==$dob || $d>new DateTimeImmutable('today')) throw new InvalidArgumentException('Enter a valid date of birth that is not in the future.'); } else $dob=null;
$s=$pdo->prepare('INSERT INTO patients (patient_number,full_name,date_of_birth,phone) VALUES (?,?,?,?)');
$s->execute([$number,post_string('full_name',150),$dob,post_string('phone',30,false)?:null]);
},'Patient registered successfully.','Patient could not be saved.'); redirect('patients.php'); }
$rows=$pdo->query('SELECT patient_id,patient_number,full_name,date_of_birth,phone,created_at FROM patients ORDER BY full_name LIMIT 250')->fetchAll();
page_header('Patients','patients'); ?>
<div class="content-grid two-col"><section class="panel"><div class="panel-heading"><div><span class="eyebrow">PATIENT REGISTRY</span><h3>Register patient</h3></div></div><form method="post" class="stack-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label>Patient number<input name="patient_number" maxlength="40" required></label><label>Full name<input name="full_name" maxlength="150" required></label><label>Date of birth<input type="date" name="date_of_birth" max="<?= e(date('Y-m-d')) ?>"></label><label>Phone<input name="phone" maxlength="30"></label><button class="btn primary">Save patient</button></form></section>
<section class="panel"><div class="panel-heading"><div><span class="eyebrow">REGISTERED PATIENTS</span><h3>Patient list</h3></div><span class="count"><?= count($rows) ?></span></div><div class="table-wrap"><table><thead><tr><th>Patient</th><th>Date of birth</th><th>Phone</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><strong><?= e($r['full_name']) ?></strong><small><?= e($r['patient_number']) ?></small></td><td><?= e($r['date_of_birth']??'—') ?></td><td><?= e($r['phone']??'—') ?></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="3" class="empty-state">No patients registered.</td></tr><?php endif; ?></tbody></table></div></section></div>
<?php page_footer(); ?>
