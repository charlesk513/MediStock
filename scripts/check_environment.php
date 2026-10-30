<?php
declare(strict_types=1);
$required = ['pdo_mysql','mbstring'];
$missing = array_values(array_filter($required, fn($ext) => !extension_loaded($ext)));
echo "PHP " . PHP_VERSION . PHP_EOL;
foreach ($required as $ext) echo ($ext . ': ' . (extension_loaded($ext) ? 'OK' : 'MISSING') . PHP_EOL);
if ($missing) exit(1);
