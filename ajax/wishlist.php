<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){

    echo json_encode([
        'success' => false,
        'message' => 'Please login first.'
    ]);

    exit;
}

$userId = (int)$_SESSION['user_id'];

$productId = (int)(
    $_POST['product_id'] ?? 0
);

$action = trim(
    $_POST['action'] ?? 'toggle'
);

if($productId <= 0){

    echo json_encode([
        'success' => false,
        'message' => 'Invalid product.'
    ]);

    exit;
}

try{

    $productStatement = $db->prepare("
        SELECT id
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $productStatement->execute([
        $productId
    ]);

    if(!$productStatement->fetch()){

        echo json_encode([
            'success' => false,
            'message' => 'Product not found.'
        ]);

        exit;
    }

    $wishlistStatement = $db->prepare("
        SELECT id
        FROM wishlist
        WHERE user_id = ?
        AND product_id = ?
        LIMIT 1
    ");

    $wishlistStatement->execute([
        $userId,
        $productId
    ]);

    $wishlistItem =
        $wishlistStatement->fetch(
            PDO::FETCH_ASSOC
        );

    if($action === 'remove'){

        $deleteStatement = $db->prepare("
            DELETE FROM wishlist
            WHERE user_id = ?
            AND product_id = ?
        ");

        $deleteStatement->execute([
            $userId,
            $productId
        ]);

        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Product removed from wishlist.'
        ]);

        exit;
    }

    if($wishlistItem){

        $deleteStatement = $db->prepare("
            DELETE FROM wishlist
            WHERE user_id = ?
            AND product_id = ?
        ");

        $deleteStatement->execute([
            $userId,
            $productId
        ]);

        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Product removed from wishlist.'
        ]);

        exit;
    }

    $insertStatement = $db->prepare("
        INSERT INTO wishlist
        (
            user_id,
            product_id
        )
        VALUES
        (
            ?,
            ?
        )
    ");

    $insertStatement->execute([
        $userId,
        $productId
    ]);

    echo json_encode([
        'success' => true,
        'action' => 'added',
        'message' => 'Product added to wishlist.'
    ]);

}catch(PDOException $exception){

    echo json_encode([
        'success' => false,
        'message' => 'Unable to update wishlist.'
    ]);
}