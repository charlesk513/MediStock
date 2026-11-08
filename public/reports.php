<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$tab = $_GET['tab'] ?? 'stock';
$allowedTabs = ['stock', 'expired', 'near', 'dispensing', 'audit'];

if (!is_string($tab) || !in_array($tab, $allowedTabs, true)) {
    $tab = 'stock';
}

// Check authorization before querying patient-linked dispensing information.
if ($tab === 'dispensing') {
    require_role(['ADMIN', 'PHARMACIST']);
}

require_once dirname(__DIR__) . '/app/layout.php';

$tabs = [
    'stock' => 'Stock summary',
    'expired' => 'Expired stock',
    'near' => 'Near expiry',
    'dispensing' => 'Dispensing',
    'audit' => 'Movement audit',
];

$data = [];

if ($tab === 'stock') {
    $data = $pdo->query(
        'SELECT * FROM vw_medicine_stock ORDER BY medicine_name'
    )->fetchAll();
} elseif ($tab === 'expired') {
    $data = $pdo->query(
        'SELECT * FROM vw_expired_batches ORDER BY expiry_date'
    )->fetchAll();
} elseif ($tab === 'near') {
    $data = $pdo->query(
        'SELECT * FROM vw_near_expiry_batches ORDER BY expiry_date'
    )->fetchAll();
} elseif ($tab === 'dispensing') {
    $data = $pdo->query(
        'SELECT * FROM vw_dispensing_summary
         ORDER BY dispensing_date DESC
         LIMIT 250'
    )->fetchAll();
} elseif ($tab === 'audit') {
    $data = $pdo->query(
        'SELECT
            sm.movement_id,
            sm.movement_date,
            sm.movement_type,
            sm.quantity_change,
            sm.reference_type,
            sm.reference_id,
            sm.remarks,
            m.medicine_name,
            b.batch_number,
            s.full_name
         FROM stock_movements sm
         INNER JOIN medicine_batches b ON b.batch_id = sm.batch_id
         INNER JOIN medicines m ON m.medicine_id = b.medicine_id
         INNER JOIN staff_users s ON s.staff_id = sm.performed_by
         ORDER BY sm.movement_date DESC
         LIMIT 500'
    )->fetchAll();
}

page_header('Reports & audit', 'reports');
?>

<section class="panel">
    <div class="tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <?php if ($key !== 'dispensing' || in_array(current_user()['role'], ['ADMIN', 'PHARMACIST'], true)): ?>
                <a
                    class="<?= $tab === $key ? 'selected' : '' ?>"
                    href="?tab=<?= e($key) ?>"
                >
                    <?= e($label) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="panel-heading">
        <div>
            <span class="eyebrow">OPERATIONAL REPORT</span>
            <h3>
                <?= e($tabs[$tab]) ?>
                <span class="count"><?= count($data) ?></span>
            </h3>
        </div>
        <button class="btn secondary" onclick="window.print()" type="button">
            Print report
        </button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if ($tab === 'stock'): ?>
                        <th>Medicine</th>
                        <th>Category</th>
                        <th>Usable</th>
                        <th>Expired</th>
                        <th>Reorder level</th>
                    <?php elseif ($tab === 'expired' || $tab === 'near'): ?>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Quantity</th>
                        <?php if ($tab === 'near'): ?>
                            <th>Days left</th>
                        <?php endif; ?>
                    <?php elseif ($tab === 'dispensing'): ?>
                        <th>Dispensing #</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Staff</th>
                        <th>Status</th>
                        <th>Units</th>
                        <th>Total</th>
                    <?php else: ?>
                        <th>Date</th>
                        <th>Medicine / batch</th>
                        <th>Movement</th>
                        <th>Change</th>
                        <th>Reference</th>
                        <th>Performed by</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php if ($tab === 'stock'): ?>
                            <td>
                                <strong><?= e($row['medicine_name']) ?></strong>
                                <small><?= e($row['medicine_code']) ?></small>
                            </td>
                            <td><?= e($row['category_name']) ?></td>
                            <td><?= number_format((int)$row['usable_stock']) ?></td>
                            <td><?= number_format((int)$row['expired_stock']) ?></td>
                            <td><?= number_format((int)$row['reorder_level']) ?></td>

                        <?php elseif ($tab === 'expired' || $tab === 'near'): ?>
                            <td>
                                <strong><?= e($row['medicine_name']) ?></strong>
                                <small><?= e($row['medicine_code']) ?></small>
                            </td>
                            <td><?= e($row['batch_number']) ?></td>
                            <td><?= e($row['expiry_date']) ?></td>
                            <td><?= number_format((int)$row['quantity_on_hand']) ?></td>
                            <?php if ($tab === 'near'): ?>
                                <td><?= (int)$row['days_until_expiry'] ?></td>
                            <?php endif; ?>

                        <?php elseif ($tab === 'dispensing'): ?>
                            <td>#<?= (int)$row['dispensing_id'] ?></td>
                            <td><?= e($row['dispensing_date']) ?></td>
                            <td>
                                <?= e($row['patient_name']) ?>
                                <small><?= e($row['patient_number']) ?></small>
                            </td>
                            <td><?= e($row['dispensed_by']) ?></td>
                            <td><?= e($row['status']) ?></td>
                            <td><?= number_format((int)$row['total_units']) ?></td>
                            <td><?= number_format((float)$row['total_amount'], 2) ?></td>

                        <?php else: ?>
                            <td><?= e($row['movement_date']) ?></td>
                            <td>
                                <strong><?= e($row['medicine_name']) ?></strong>
                                <small><?= e($row['batch_number']) ?></small>
                            </td>
                            <td><?= e($row['movement_type']) ?></td>
                            <td class="<?= (int)$row['quantity_change'] < 0 ? 'negative' : 'positive' ?>">
                                <?= (int)$row['quantity_change'] > 0 ? '+' : '' ?>
                                <?= number_format((int)$row['quantity_change']) ?>
                            </td>
                            <td>
                                <?= e($row['reference_type'] . ' #' . $row['reference_id']) ?>
                            </td>
                            <td><?= e($row['full_name']) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$data): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            No records are available for this report.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php page_footer(); ?>
