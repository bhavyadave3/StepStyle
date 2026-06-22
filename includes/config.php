<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'StepStyle');
define('SITE_URL', 'http://localhost/StepStyle');

define('DB_HOST', 'localhost');
define('DB_NAME', 'stepstyle');
define('DB_USER', 'root');
define('DB_PASS', '');

date_default_timezone_set('Asia/Kolkata');

error_reporting(E_ALL);
ini_set('display_errors', '1');
if (!defined('ADMIN_REGISTRATION_CODE')) {
    define(
        'ADMIN_REGISTRATION_CODE',
        'STEPSTYLE-ADMIN-2026'
    );
}