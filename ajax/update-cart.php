<?php

require_once '../includes/db.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    exit;
}

$cartId = (int)($_POST['cart_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($quantity < 1) {
    $quantity = 1;
}

$stmt = $db->prepare("
UPDATE cart
SET quantity = ?
WHERE id = ?
");

$stmt->execute([
    $quantity,
    $cartId
]);

echo json_encode([
    'success' => true
]);