<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_login();
require_once dirname(__DIR__) . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_role(['ADMIN', 'PHARMACIST', 'STOREKEEPER']);

    run_action(function () use ($pdo): void {
        $code = strtoupper(post_string('medicine_code', 40));

        if (!preg_match('/^[A-Z0-9][A-Z0-9._-]*$/', $code)) {
            throw new InvalidArgumentException(
                'Medicine code may contain letters, numbers, dots, underscores and hyphens only.'
            );
        }

        $statement = $pdo->prepare(
            'INSERT INTO medicines
                (category_id, medicine_code, medicine_name, strength,
                 dosage_form, unit_of_measure, reorder_level)
             VALUES
                (:category, :code, :name, :strength, :form, :unit, :reorder)'
        );

        $statement->execute([
            'category' => post_int('category_id', 1),
            'code' => $code,
            'name' => post_string('medicine_name', 150),
            'strength' => post_string('strength', 60, false) ?: null,
            'form' => post_string('dosage_form', 60, false) ?: null,
            'unit' => post_string('unit_of_measure', 30),
            'reorder' => post_int('reorder_level', 0, 100000000),
        ]);
    }, 'Medicine added successfully.', 'Medicine could not be saved.');

    redirect('medicines.php');
}

$categories = $pdo->query(
    'SELECT category_id, category_name
     FROM medicine_categories
     ORDER BY category_name'
)->fetchAll();

$search = trim((string)($_GET['q'] ?? ''));
$search = utf8_substr($search, 0, 80);

$statement = $pdo->prepare(
    'SELECT v.*
     FROM vw_medicine_stock v
     WHERE (:q = "" OR v.medicine_name LIKE :nameq OR v.medicine_code LIKE :codeq)
     ORDER BY v.medicine_name
     LIMIT 200'
);

$statement->execute([
    'q' => $search,
    'nameq' => '%' . $search . '%',
    'codeq' => '%' . $search . '%',
]);

$rows = $statement->fetchAll();

page_header('Medicine catalogue', 'medicines');
?>

<div class="page-toolbar">
    <div>
        <p class="muted">Manage the approved medicine catalogue and reorder thresholds.</p>
    </div>
    <a class="btn primary" href="#add-medicine">＋ Add medicine</a>
</div>

<section class="panel form-panel" id="add-medicine">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">CATALOGUE ENTRY</span>
            <h3>Add a medicine</h3>
        </div>
    </div>

    <?php if (!$categories): ?>
        <p class="notice">Add a medicine category in the database before registering medicines.</p>
    <?php else: ?>
        <form method="post" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <label>
                Medicine name
                <input name="medicine_name" maxlength="150" required>
            </label>

            <label>
                Medicine code
                <input name="medicine_code" maxlength="40" placeholder="e.g. MED-004" required>
            </label>

            <label>
                Category
                <select name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['category_id'] ?>">
                            <?= e($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Strength
                <input name="strength" maxlength="60" placeholder="e.g. 500 mg">
            </label>

            <label>
                Dosage form
                <input name="dosage_form" maxlength="60" placeholder="Tablet, capsule...">
            </label>

            <label>
                Unit of measure
                <input name="unit_of_measure" maxlength="30" value="unit" required>
            </label>

            <label>
                Reorder level
                <input type="number" name="reorder_level" min="0" max="100000000" value="0" required>
            </label>

            <div class="form-actions">
                <button class="btn primary" type="submit">Save medicine</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">REGISTERED ITEMS</span>
            <h3>Medicine stock summary <span class="count"><?= count($rows) ?></span></h3>
        </div>

        <form class="search-form" method="get">
            <input name="q" value="<?= e($search) ?>" placeholder="Search name or code">
            <button class="btn secondary" type="submit">Search</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Code / medicine</th>
                    <th>Category</th>
                    <th>Strength / form</th>
                    <th>Usable stock</th>
                    <th>Expired stock</th>
                    <th>Reorder level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['medicine_name']) ?></strong>
                            <small><?= e($row['medicine_code']) ?></small>
                        </td>
                        <td><?= e($row['category_name']) ?></td>
                        <td>
                            <?= e(trim(($row['strength'] ?? '') . ' ' . ($row['dosage_form'] ?? ''))) ?>
                        </td>
                        <td><?= number_format((int)$row['usable_stock']) ?></td>
                        <td><?= number_format((int)$row['expired_stock']) ?></td>
                        <td><?= number_format((int)$row['reorder_level']) ?></td>
                        <td>
                            <?php if ((int)$row['usable_stock'] <= (int)$row['reorder_level']): ?>
                                <span class="pill warning">Reorder</span>
                            <?php else: ?>
                                <span class="pill success">In range</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="empty-state">No medicines match this search.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php page_footer(); ?>
