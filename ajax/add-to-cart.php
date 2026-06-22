<?php

require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {

    echo json_encode([
        'success' => false,
        'message' => 'Please login first.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$userId = $_SESSION['user_id'];

$productStmt = $db->prepare("
    SELECT *
    FROM products
    WHERE id = ?
");

$productStmt->execute([$productId]);

$product = $productStmt->fetch();

if (!$product) {

    echo json_encode([
        'success' => false,
        'message' => 'Product not found.'
    ]);
    exit;
}

$cartStmt = $db->prepare("
    SELECT *
    FROM cart
    WHERE user_id = ?
    AND product_id = ?
");

$cartStmt->execute([
    $userId,
    $productId
]);

$existing = $cartStmt->fetch();

if ($existing) {

    $update = $db->prepare("
        UPDATE cart
        SET quantity = quantity + 1
        WHERE id = ?
    ");

    $update->execute([
        $existing['id']
    ]);

} else {

    $insert = $db->prepare("
        INSERT INTO cart
        (
            user_id,
            product_id,
            quantity
        )
        VALUES
        (
            ?,
            ?,
            1
        )
    ");

    $insert->execute([
        $userId,
        $productId
    ]);
}

echo json_encode([
    'success' => true,
    'message' => 'Added to cart.'
]);