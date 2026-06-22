```php
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$orderId || $orderId < 1) {
    header('Location: orders.php');
    exit;
}

$orderStatement = $db->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        payment_method,
        status,
        created_at
    FROM orders
    WHERE id = ?
    AND user_id = ?
    LIMIT 1
");

$orderStatement->execute([
    $orderId,
    $userId
]);

$order = $orderStatement->fetch(
    PDO::FETCH_ASSOC
);

if (!$order) {
    header('Location: orders.php');
    exit;
}

$itemStatement = $db->prepare("
    SELECT
        oi.product_id,
        oi.quantity,
        oi.price,
        p.name,
        p.main_image,
        p.sku
    FROM order_items oi
    LEFT JOIN products p
        ON p.id = oi.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

$itemStatement->execute([$orderId]);

$orderItems = $itemStatement->fetchAll(
    PDO::FETCH_ASSOC
);

$statusClass = strtolower(
    str_replace(
        [' ', '_'],
        '-',
        $order['status']
    )
);

require_once __DIR__ . '/includes/header.php';

?>
<main class="order-details-page">

    <section class="order-details-header">

        <div class="container">

            <a
                href="<?= SITE_URL ?>/orders.php"
                class="order-back-link"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Back to My Orders
            </a>

            <div class="order-details-heading">

                <div>

                    <span class="hero-badge">
                        <i class="fa-solid fa-receipt"></i>
                        Order Details
                    </span>

                    <h1>
                        <?= htmlspecialchars(
                            $order['order_number']
                        ) ?>
                    </h1>

                    <p>
                        Placed on
                        <?= date(
                            'd M Y, h:i A',
                            strtotime($order['created_at'])
                        ) ?>
                    </p>

                </div>

                <span
                    class="order-status order-status-<?= htmlspecialchars(
                        $statusClass
                    ) ?>"
                >
                    <?= htmlspecialchars($order['status']) ?>
                </span>

            </div>

        </div>

    </section>

    <section class="order-details-content">

        <div class="container">

            <div class="order-summary-grid">

                <div class="order-summary-item">

                    <i class="fa-solid fa-box"></i>

                    <div>
                        <span>Total Products</span>

                        <strong>
                            <?= count($orderItems) ?>
                        </strong>
                    </div>

                </div>

                <div class="order-summary-item">

                    <i class="fa-solid fa-credit-card"></i>

                    <div>
                        <span>Payment Method</span>

                        <strong>
                            <?= htmlspecialchars(
                                $order['payment_method']
                            ) ?>
                        </strong>
                    </div>

                </div>

                <div class="order-summary-item">

                    <i class="fa-solid fa-indian-rupee-sign"></i>

                    <div>
                        <span>Order Total</span>

                        <strong>
                            ₹<?= number_format(
                                (float) $order['total_amount'],
                                2
                            ) ?>
                        </strong>
                    </div>

                </div>

            </div>

            <div class="order-products-card">

                <div class="order-products-heading">

                    <h2>Ordered Products</h2>

                    <span>
                        <?= count($orderItems) ?>
                        product<?= count($orderItems) === 1 ? '' : 's' ?>
                    </span>

                </div>
                                <div class="order-products-list">

                    <?php foreach ($orderItems as $item): ?>

                        <?php
                        $quantity = (int) $item['quantity'];
                        $price = (float) $item['price'];
                        $lineTotal = $quantity * $price;

                        $imagePath = trim(
                            $item['main_image'] ?? ''
                        );

                        if (
                            $imagePath !== '' &&
                            !filter_var(
                                $imagePath,
                                FILTER_VALIDATE_URL
                            )
                        ) {
                            $imagePath =
                                SITE_URL . '/' .
                                ltrim($imagePath, '/');
                        }
                        ?>

                        <article class="order-product-item">

                            <div class="order-product-image">

                                <?php if ($imagePath !== ''): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            $imagePath
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $item['name'] ??
                                            'Ordered product'
                                        ) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <i class="fa-solid fa-shoe-prints"></i>

                                <?php endif; ?>

                            </div>

                            <div class="order-product-information">

                                <h3>
                                    <?= htmlspecialchars(
                                        $item['name'] ??
                                        'Product unavailable'
                                    ) ?>
                                </h3>

                                <?php if (!empty($item['sku'])): ?>

                                    <span>
                                        SKU:
                                        <?= htmlspecialchars(
                                            $item['sku']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                                <p>
                                    ₹<?= number_format($price, 2) ?>
                                    ×
                                    <?= $quantity ?>
                                </p>

                            </div>

                            <div class="order-product-total">

                                <span>Subtotal</span>

                                <strong>
                                    ₹<?= number_format(
                                        $lineTotal,
                                        2
                                    ) ?>
                                </strong>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>
                                <div class="order-total-section">

                    <div class="order-total-row">

                        <span>Order Total</span>

                        <strong>
                            ₹<?= number_format(
                                (float) $order['total_amount'],
                                2
                            ) ?>
                        </strong>

                    </div>

                    <p>
                        Payment method:
                        <?= htmlspecialchars(
                            $order['payment_method']
                        ) ?>
                    </p>

                </div>

            </div>

            <div class="order-details-actions">

                <a
                    href="<?= SITE_URL ?>/track-order.php?id=<?= (int) $order['id'] ?>"
                    class="btn"
                >
                    <i class="fa-solid fa-location-dot"></i>
                    Track Order
                </a>

                <a
                    href="<?= SITE_URL ?>/shop.php"
                    class="order-continue-shopping"
                >
                    <i class="fa-solid fa-bag-shopping"></i>
                    Continue Shopping
                </a>

            </div>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>
