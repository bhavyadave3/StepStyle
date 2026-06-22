<?php

require_once 'includes/auth.php';
require_once 'includes/db.php';

requireLogin();

$productId = (int)$_POST['product_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);

$stmt = $db->prepare("
INSERT INTO reviews
(
    user_id,
    product_id,
    rating,
    comment,
    status
)
VALUES
(
    ?,
    ?,
    ?,
    ?,
    'pending'
)
");

$stmt->execute([
    $_SESSION['user_id'],
    $productId,
    $rating,
    $comment
]);

header(
    "Location: product.php?id=".$productId
);

exit;