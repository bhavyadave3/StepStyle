<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

$paymentMethod = trim(
    $_POST['payment_method'] ?? 'COD'
);

if ($userId < 1) {
    header('Location: login.php');
    exit;
}

if (
    $paymentMethod === '' ||
    strlen($paymentMethod) > 50
) {
    $_SESSION['order_error'] =
        'Please select a valid payment method.';

    header('Location: cart.php');
    exit;
}

try {
    $db->beginTransaction();

    /*
     * Lock and verify the customer account.
     */

    $userStatement = $db->prepare("
        SELECT
            id,
            status,
            account_status
        FROM users
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $userStatement->execute([$userId]);

    $currentUser = $userStatement->fetch(
        PDO::FETCH_ASSOC
    );

    if (
        !$currentUser ||
        ($currentUser['status'] ?? 'blocked') !== 'active' ||
        ($currentUser['account_status'] ?? 'deleted') !== 'active'
    ) {
        throw new RuntimeException(
            'Your account cannot place orders.'
        );
    }

    /*
     * Lock cart and product rows while creating the order.
     */

    $cartStatement = $db->prepare("
        SELECT
            c.product_id,
            c.quantity,
            p.name,
            p.price,
            p.sale_price,
            p.stock_quantity,
            p.status,
            CASE
                WHEN
                    p.sale_price IS NOT NULL
                    AND p.sale_price > 0
                    AND p.sale_price < p.price
                THEN p.sale_price
                ELSE p.price
            END AS final_price
        FROM cart c
        INNER JOIN products p
            ON p.id = c.product_id
        WHERE c.user_id = ?
        FOR UPDATE
    ");

    $cartStatement->execute([$userId]);

    $cartItems = $cartStatement->fetchAll(
        PDO::FETCH_ASSOC
    );

    if (empty($cartItems)) {
        throw new RuntimeException(
            'Your cart is empty.'
        );
    }

    $totalAmount = 0.00;

    foreach ($cartItems as $item) {
        $quantity = (int) $item['quantity'];
        $availableStock =
            (int) $item['stock_quantity'];

        $productStatus =
            (string) $item['status'];

        $finalPrice =
            (float) $item['final_price'];

        if ($quantity < 1) {
            throw new RuntimeException(
                'Invalid product quantity found in your cart.'
            );
        }

        if ($productStatus !== 'active') {
            throw new RuntimeException(
                $item['name'] .
                ' is currently unavailable.'
            );
        }

        if ($availableStock < $quantity) {
            throw new RuntimeException(
                'Only ' .
                $availableStock .
                ' unit(s) of ' .
                $item['name'] .
                ' are available.'
            );
        }

        $totalAmount +=
            $finalPrice * $quantity;
    }

    $totalAmount = round(
        $totalAmount,
        2
    );

    $orderNumber =
        'ORD' .
        date('YmdHis') .
        random_int(100, 999);

    $orderStatement = $db->prepare("
        INSERT INTO orders
        (
            user_id,
            order_number,
            total_amount,
            payment_method,
            status,
            stock_restored
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'Pending',
            0
        )
    ");

    $orderStatement->execute([
        $userId,
        $orderNumber,
        $totalAmount,
        $paymentMethod
    ]);

    $orderId = (int) $db->lastInsertId();
  
    /*
     * Prepare order item, product stock and inventory log queries.
     */

    $orderItemStatement = $db->prepare("
        INSERT INTO order_items
        (
            order_id,
            product_id,
            quantity,
            price
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");

    $updateStockStatement = $db->prepare("
        UPDATE products
        SET
            stock_quantity = ?,
            status = ?
        WHERE id = ?
    ");

    $inventoryLogStatement = $db->prepare("
        INSERT INTO inventory_logs
        (
            product_id,
            old_stock,
            new_stock,
            updated_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");

    /*
     * Save every order item and deduct its stock.
     */

    foreach ($cartItems as $item) {
        $productId =
            (int) $item['product_id'];

        $quantity =
            (int) $item['quantity'];

        $oldStock =
            (int) $item['stock_quantity'];

        $finalPrice =
            (float) $item['final_price'];

        $newStock =
            $oldStock - $quantity;

        $newProductStatus =
            $newStock <= 0
                ? 'out_of_stock'
                : 'active';

        $orderItemStatement->execute([
            $orderId,
            $productId,
            $quantity,
            $finalPrice
        ]);

        $updateStockStatement->execute([
            $newStock,
            $newProductStatus,
            $productId
        ]);

        if ($updateStockStatement->rowCount() !== 1) {
            throw new RuntimeException(
                'Unable to update stock for ' .
                $item['name'] .
                '.'
            );
        }

        $inventoryLogStatement->execute([
            $productId,
            $oldStock,
            $newStock,
            $userId
        ]);
    }


    $clearCartStatement = $db->prepare("
        DELETE FROM cart
        WHERE user_id = ?
    ");

    $clearCartStatement->execute([
        $userId
    ]);

    $db->commit();

    $_SESSION['order_success'] =
        'Your order ' .
        $orderNumber .
        ' has been placed successfully.';

    header('Location: orders.php');
    exit;

} catch (RuntimeException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $_SESSION['order_error'] =
        $exception->getMessage();

    header('Location: cart.php');
    exit;

} catch (PDOException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $_SESSION['order_error'] =
        'Unable to place your order right now.';

    header('Location: cart.php');
    exit;

} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $_SESSION['order_error'] =
        'Something went wrong while placing your order.';

    header('Location: cart.php');
    exit;
}

