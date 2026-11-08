<?php
declare(strict_types=1);

/*
 * Copy this file to config.php and set your local MySQL credentials.
 * Never commit config.php or real credentials to version control.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'hospital_pharmacy_db_v2',
        'user' => 'medistock_app',
        'password' => 'CHANGE_THIS_TO_THE_PASSWORD_SET_IN_CREATE_APP_USER_SQL',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'MediStock',
        'session_name' => 'medistock_session',
        'timezone' => 'Africa/Kampala',
    ],
];
