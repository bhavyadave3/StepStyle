<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';

header(
    'Location: ' .
    rtrim(SITE_URL, '/') .
    '/index.php'
);

exit;