<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}

if(!isset($_SESSION['user_id'])){

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Please login again.'
    ]);

    exit;
}

$cartId = filter_input(
    INPUT_POST,
    'cart_id',
    FILTER_VALIDATE_INT
);

if(!$cartId || $cartId < 1){

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid cart item.'
    ]);

    exit;
}

$userId = (int)$_SESSION['user_id'];

try{

    $stmt = $db->prepare("
        DELETE FROM cart
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->execute([
        $cartId,
        $userId
    ]);

    if($stmt->rowCount() < 1){

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Cart item was not found.'
        ]);

        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product removed from cart.'
    ]);

}catch(PDOException $exception){

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to remove the product.'
    ]);
}