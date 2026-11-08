<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();
require_once dirname(__DIR__) . '/app/layout.php';

$rows = $pdo->query(
    'SELECT
        b.batch_id,
        b.batch_number,
        b.expiry_date,
        b.quantity_on_hand,
        b.purchase_unit_cost,
        m.medicine_name,
        m.medicine_code,
        DATEDIFF(b.expiry_date, CURRENT_DATE) AS days_left
     FROM medicine_batches b
     INNER JOIN medicines m ON m.medicine_id = b.medicine_id
     ORDER BY b.expiry_date ASC
     LIMIT 500'
)->fetchAll();

page_header('Batch inventory', 'batches');
?>

<section class="panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">BATCH TRACEABILITY</span>
            <h3>Medicine batches <span class="count"><?= count($rows) ?></span></h3>
            <p class="muted">Quantities are tracked separately for every batch and expiry date.</p>
        </div>
        <a class="btn primary" href="receive.php">Receive stock</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Batch number</th>
                    <th>Expiry date</th>
                    <th>Days left</th>
                    <th>Quantity</th>
                    <th>Unit cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['medicine_name']) ?></strong>
                            <small><?= e($row['medicine_code']) ?></small>
                        </td>
                        <td><?= e($row['batch_number']) ?></td>
                        <td><?= e($row['expiry_date']) ?></td>
                        <td>
                            <?php if ((int)$row['days_left'] < 0): ?>
                                <span class="pill danger">Expired</span>
                            <?php elseif ((int)$row['days_left'] <= 90): ?>
                                <span class="pill warning"><?= (int)$row['days_left'] ?> days</span>
                            <?php else: ?>
                                <?= (int)$row['days_left'] ?> days
                            <?php endif; ?>
                        </td>
                        <td><?= number_format((int)$row['quantity_on_hand']) ?></td>
                        <td><?= number_format((float)$row['purchase_unit_cost'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No batches have been received yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php page_footer(); ?>
