<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

$orderId = filter_input(
    INPUT_POST,
    'order_id',
    FILTER_VALIDATE_INT
);

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    empty($_SESSION['order_csrf_token']) ||
    !hash_equals(
        $_SESSION['order_csrf_token'],
        $csrfToken
    )
) {
    $_SESSION['order_error'] =
        'Invalid cancellation request.';

    header('Location: orders.php');
    exit;
}

if (!$orderId || $orderId < 1) {
    $_SESSION['order_error'] =
        'Invalid order selected.';

    header('Location: orders.php');
    exit;
}

try {
    $db->beginTransaction();

    /*
     * Check that the customer account is still active.
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
            'Your account is not allowed to cancel orders.'
        );
    }

    /*
     * Lock the order so it cannot be processed twice.
     */

    $orderStatement = $db->prepare("
        SELECT
            id,
            status,
            stock_restored
        FROM orders
        WHERE id = ?
        AND user_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $orderStatement->execute([
        $orderId,
        $userId
    ]);

    $order = $orderStatement->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$order) {
        throw new RuntimeException(
            'Order not found.'
        );
    }

    $allowedStatuses = [
        'Pending',
        'Confirmed'
    ];

    if (
        !in_array(
            $order['status'],
            $allowedStatuses,
            true
        )
    ) {
        throw new RuntimeException(
            'This order can no longer be cancelled.'
        );
    }

    if ((int) $order['stock_restored'] === 1) {
        throw new RuntimeException(
            'The stock for this order has already been restored.'
        );
    }

    /*
     * Get all items belonging to this order.
     */

    $itemStatement = $db->prepare("
        SELECT
            product_id,
            quantity
        FROM order_items
        WHERE order_id = ?
    ");

    $itemStatement->execute([$orderId]);

    $orderItems = $itemStatement->fetchAll(
        PDO::FETCH_ASSOC
    );

    if (empty($orderItems)) {
        throw new RuntimeException(
            'No products were found in this order.'
        );
    }

    /*
     * Prepared statements used while restoring inventory.
     */

    $productStatement = $db->prepare("
        SELECT
            stock_quantity,
            status
        FROM products
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $updateProductStatement = $db->prepare("
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

    foreach ($orderItems as $item) {
        $productId = (int) $item['product_id'];
        $quantity = (int) $item['quantity'];

        if ($productId < 1 || $quantity < 1) {
            throw new RuntimeException(
                'Invalid product information found in the order.'
            );
        }

        $productStatement->execute([
            $productId
        ]);

        $product = $productStatement->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$product) {
            throw new RuntimeException(
                'One of the ordered products no longer exists.'
            );
        }

        $oldStock = (int) $product['stock_quantity'];
        $newStock = $oldStock + $quantity;

        /*
         * Reactivate only products that were automatically marked
         * out of stock. Products manually set to inactive remain inactive.
         */

        $newProductStatus =
            $product['status'] === 'out_of_stock'
                ? 'active'
                : $product['status'];

        $updateProductStatement->execute([
            $newStock,
            $newProductStatus,
            $productId
        ]);

        $inventoryLogStatement->execute([
            $productId,
            $oldStock,
            $newStock,
            $userId
        ]);
    }

    /*
     * Cancel the order and mark its stock as restored.
     */

    $cancelStatement = $db->prepare("
        UPDATE orders
        SET
            status = 'Cancelled',
            stock_restored = 1,
            stock_restored_at = NOW()
        WHERE id = ?
        AND user_id = ?
        AND stock_restored = 0
    ");

    $cancelStatement->execute([
        $orderId,
        $userId
    ]);

    if ($cancelStatement->rowCount() !== 1) {
        throw new RuntimeException(
            'Unable to cancel this order.'
        );
    }

    $db->commit();

    $_SESSION['order_success'] =
        'Your order has been cancelled and the product stock has been restored.';

    $_SESSION['order_csrf_token'] =
        bin2hex(random_bytes(32));

    header('Location: orders.php');
    exit;

} catch (RuntimeException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $_SESSION['order_error'] =
        $exception->getMessage();

    $_SESSION['order_csrf_token'] =
        bin2hex(random_bytes(32));

    header('Location: orders.php');
    exit;

} catch (PDOException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $_SESSION['order_error'] =
        'Unable to cancel the order right now.';

    $_SESSION['order_csrf_token'] =
        bin2hex(random_bytes(32));

    header('Location: orders.php');
    exit;

} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $_SESSION['order_error'] =
        'Something went wrong while cancelling the order.';

    $_SESSION['order_csrf_token'] =
        bin2hex(random_bytes(32));

    header('Location: orders.php');
    exit;
}