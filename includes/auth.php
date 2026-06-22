<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {

        header("Location: login.php");
        exit;
    }
}

function requireAdmin(): void
{
    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'admin'
    ) {

        header("Location: ../login.php");
        exit;
    }
}