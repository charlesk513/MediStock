<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();
require_role(['ADMIN', 'PHARMACIST']);
require_once dirname(__DIR__) . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    run_action(function () use ($pdo): void {
        $parameters = [
            'patient' => post_int('patient_id', 1),
            'staff' => current_user()['staff_id'],
            'medicine' => post_int('medicine_id', 1),
            'qty' => post_int('quantity', 1, 100000000),
            'price' => post_decimal('unit_price'),
        ];

        execute_procedure(
            $pdo,
            'CALL sp_dispense_medicine(
                :patient,
                :staff,
                :medicine,
                :qty,
                :price,
                @dispensing_id
            )',
            $parameters
        );
    }, 'Medicine dispensed successfully. Stock and audit records were updated.', 'Dispensing failed.');

    redirect('dispense.php');
}

$patients = $pdo->query(
    'SELECT patient_id, patient_number, full_name
     FROM patients
     ORDER BY full_name'
)->fetchAll();

$medicines = $pdo->query(
    'SELECT medicine_id, medicine_code, medicine_name, usable_stock, unit_of_measure
     FROM vw_medicine_stock
     WHERE usable_stock > 0
     ORDER BY medicine_name'
)->fetchAll();

page_header('Dispense medicine', 'dispense');
?>

<div class="content-grid two-col">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">PATIENT ISSUE</span>
                <h3>Record a dispensing</h3>
                <p class="muted">Eligible batches are selected automatically using FEFO.</p>
            </div>
        </div>

        <?php if (!$patients || !$medicines): ?>
            <div class="notice">
                You need a registered patient and available unexpired stock before dispensing.
            </div>
        <?php else: ?>
            <form method="post" class="stack-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                <label>
                    Patient
                    <select name="patient_id" required>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= (int)$patient['patient_id'] ?>">
                                <?= e($patient['full_name'] . ' · ' . $patient['patient_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Medicine
                    <select name="medicine_id" required>
                        <?php foreach ($medicines as $medicine): ?>
                            <option value="<?= (int)$medicine['medicine_id'] ?>">
                                <?= e(
                                    $medicine['medicine_name'] . ' · ' .
                                    $medicine['medicine_code'] . ' — ' .
                                    $medicine['usable_stock'] . ' ' .
                                    $medicine['unit_of_measure'] . ' available'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="form-grid">
                    <label>
                        Quantity
                        <input type="number" name="quantity" min="1" max="100000000" required>
                    </label>

                    <label>
                        Unit price
                        <input type="number" name="unit_price" min="0" step="0.01" required>
                    </label>
                </div>

                <button class="btn primary" type="submit">Confirm dispensing →</button>
            </form>
        <?php endif; ?>
    </section>

    <aside class="panel guidance">
        <div class="guidance-icon">✓</div>
        <h3>Protected dispensing</h3>
        <ul>
            <li>Expired batches cannot be issued.</li>
            <li>Oldest-expiring eligible batches are allocated first.</li>
            <li>A request can be fulfilled from more than one batch.</li>
            <li>Insufficient stock rolls back the complete dispensing.</li>
            <li>Each stock deduction creates an audit movement.</li>
        </ul>
    </aside>
</div>

<?php page_footer(); ?>
