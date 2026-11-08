<?php
declare(strict_types=1);

$requiredExtensions = ['pdo_mysql'];
$missing = array_values(array_filter(
    $requiredExtensions,
    static fn(string $extension): bool => !extension_loaded($extension)
));

printf("MediStock environment check\nPHP %s\n", PHP_VERSION);
foreach ($requiredExtensions as $extension) {
    printf("%-12s %s\n", $extension, extension_loaded($extension) ? 'OK' : 'MISSING');
}

if ($missing) {
    fwrite(STDERR, "Install the missing PHP extensions before running MediStock.\n");
    exit(1);
}

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "config.php is missing. Copy config.example.php to config.php and configure MySQL.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/app/db.php';

try {
    $config = require $configFile;
    $pdo = db($config['db']);
    $version = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    printf("MySQL server: %s\n", $version);

    if (stripos($version, 'mariadb') !== false) {
        fwrite(STDERR, "This SQL package targets MySQL 8.0.16+; MariaDB has not been validated.\n");
        exit(1);
    }

    $versionNumber = preg_match('/^(\d+)\.(\d+)\.(\d+)/', $version, $matches)
        ? ((int)$matches[1] * 10000 + (int)$matches[2] * 100 + (int)$matches[3])
        : 0;
    if ($versionNumber < 80016) {
        fwrite(STDERR, "MediStock requires MySQL 8.0.16 or newer for enforced CHECK constraints.\n");
        exit(1);
    }

    $requiredTables = [
        'medicine_categories', 'suppliers', 'staff_users', 'patients', 'medicines',
        'medicine_batches', 'purchases', 'purchase_items', 'dispensings',
        'dispensing_items', 'stock_movements',
    ];
    $requiredViews = [
        'vw_medicine_stock', 'vw_low_stock_medicines', 'vw_expired_batches',
        'vw_near_expiry_batches', 'vw_dispensing_summary',
    ];
    $requiredProcedures = [
        'sp_receive_stock', 'sp_dispense_medicine', 'sp_cancel_dispensing',
    ];


    $tableCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? AND table_type = ?'
    );
    $missingObjects = [];
    foreach ($requiredTables as $name) {
        $tableCheck->execute([$name, 'BASE TABLE']);
        if ((int)$tableCheck->fetchColumn() !== 1) {
            $missingObjects[] = "table: {$name}";
        }
    }
    foreach ($requiredViews as $name) {
        $tableCheck->execute([$name, 'VIEW']);
        if ((int)$tableCheck->fetchColumn() !== 1) {
            $missingObjects[] = "view: {$name}";
        }
    }

    $routineCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = ? AND routine_type = ?'
    );
    foreach ($requiredProcedures as $name) {
        $routineCheck->execute([$name, 'PROCEDURE']);
        if ((int)$routineCheck->fetchColumn() !== 1) {
            $missingObjects[] = "procedure: {$name}";
        }
    }



    if ($missingObjects) {
        fwrite(STDERR, "Missing database objects:\n - " . implode("\n - ", $missingObjects) . "\n");
        exit(1);
    }

    echo "Database tables, views and stored procedures: OK\n";
    echo "This check verifies object presence, not the correctness of every business operation.\n";
} catch (Throwable $exception) {
    error_log('MediStock environment check failed: ' . $exception->getMessage());
    fwrite(STDERR, "Database check failed. Check MySQL, config.php and the imported schema.\n");
    exit(1);
}
