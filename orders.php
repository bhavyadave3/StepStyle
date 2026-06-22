<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);

if (empty($_SESSION['order_csrf_token'])) {
    $_SESSION['order_csrf_token'] =
        bin2hex(random_bytes(32));
}

$successMessage =
    $_SESSION['order_success'] ?? '';

$errorMessage =
    $_SESSION['order_error'] ?? '';

unset(
    $_SESSION['order_success'],
    $_SESSION['order_error']
);

$userStatement = $db->prepare("
    SELECT
        id,
        account_status
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStatement->execute([$userId]);

$currentUser = $userStatement->fetch(
    PDO::FETCH_ASSOC
);

if (
    !$currentUser ||
    ($currentUser['account_status'] ?? 'active')
        !== 'active'
) {
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

$orderStatement = $db->prepare("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.payment_method,
        o.status,
        o.created_at,
        COUNT(oi.id) AS total_items,
        COALESCE(
            SUM(oi.quantity),
            0
        ) AS total_quantity
    FROM orders o
    LEFT JOIN order_items oi
        ON oi.order_id = o.id
    WHERE o.user_id = ?
    GROUP BY
        o.id,
        o.order_number,
        o.total_amount,
        o.payment_method,
        o.status,
        o.created_at
    ORDER BY o.created_at DESC
");

$orderStatement->execute([$userId]);

$orders = $orderStatement->fetchAll(
    PDO::FETCH_ASSOC
);

require_once __DIR__ . '/includes/header.php';

?>
<main class="orders-page">

    <section class="orders-header-section">

        <div class="container">

            <span class="hero-badge">
                <i class="fa-solid fa-box"></i>
                My Orders
            </span>

            <h1>Order History</h1>

            <p>
                View your purchases, track orders and
                cancel eligible orders.
            </p>

        </div>

    </section>

    <section class="orders-content-section">

        <div class="container">

            <?php if ($successMessage !== ''): ?>

                <div
                    class="success-message"
                    role="alert"
                >
                    <i class="fa-solid fa-circle-check"></i>

                    <span>
                        <?= htmlspecialchars($successMessage) ?>
                    </span>
                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>

                <div
                    class="error-message"
                    role="alert"
                >
                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($errorMessage) ?>
                    </span>
                </div>

            <?php endif; ?>

            <?php if (empty($orders)): ?>

                <div class="orders-empty-state">

                    <div class="orders-empty-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>

                    <h2>No Orders Yet</h2>

                    <p>
                        You have not placed any orders.
                        Explore our latest footwear collection.
                    </p>

                    <a
                        href="<?= SITE_URL ?>/shop.php"
                        class="btn"
                    >
                        <i class="fa-solid fa-arrow-right"></i>
                        Start Shopping
                    </a>

                </div>

            <?php else: ?>

                <div class="orders-list">

                    <?php foreach ($orders as $order): ?>

                        <?php
                        $statusClass = strtolower(
                            str_replace(
                                [' ', '_'],
                                '-',
                                $order['status']
                            )
                        );

                        $canCancel = in_array(
                            $order['status'],
                            ['Pending', 'Confirmed'],
                            true
                        );
                        ?>

                        <article class="order-card">

                            <div class="order-card-header">

                                <div>

                                    <span class="order-label">
                                        Order Number
                                    </span>

                                    <h2>
                                        <?= htmlspecialchars(
                                            $order['order_number']
                                        ) ?>
                                    </h2>

                                </div>

                                <span
                                    class="order-status order-status-<?= htmlspecialchars(
                                        $statusClass
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $order['status']
                                    ) ?>
                                </span>

                            </div>
                                                        <div class="order-card-body">

                                <div class="order-information-grid">

                                    <div class="order-information-item">

                                        <i class="fa-solid fa-calendar"></i>

                                        <div>
                                            <span>Order Date</span>

                                            <strong>
                                                <?= date(
                                                    'd M Y',
                                                    strtotime(
                                                        $order['created_at']
                                                    )
                                                ) ?>
                                            </strong>
                                        </div>

                                    </div>

                                    <div class="order-information-item">

                                        <i class="fa-solid fa-shoe-prints"></i>

                                        <div>
                                            <span>Total Items</span>

                                            <strong>
                                                <?= (int) $order['total_quantity'] ?>
                                            </strong>
                                        </div>

                                    </div>

                                    <div class="order-information-item">

                                        <i class="fa-solid fa-credit-card"></i>

                                        <div>
                                            <span>Payment</span>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $order['payment_method']
                                                ) ?>
                                            </strong>
                                        </div>

                                    </div>

                                    <div class="order-information-item">

                                        <i class="fa-solid fa-indian-rupee-sign"></i>

                                        <div>
                                            <span>Total Amount</span>

                                            <strong>
                                                ₹<?= number_format(
                                                    (float) $order['total_amount'],
                                                    2
                                                ) ?>
                                            </strong>
                                        </div>

                                    </div>

                                </div>

                                <div class="order-card-actions">

                                    <a
                                        href="<?= SITE_URL ?>/order-details.php?id=<?= (int) $order['id'] ?>"
                                        class="btn order-details-button"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                        View Details
                                    </a>

                                    <a
                                        href="<?= SITE_URL ?>/track-order.php?id=<?= (int) $order['id'] ?>"
                                        class="order-track-button"
                                    >
                                        <i class="fa-solid fa-location-dot"></i>
                                        Track Order
                                    </a>

                                    <?php if ($canCancel): ?>

                                        <form
                                            method="POST"
                                            action="<?= SITE_URL ?>/cancel-order.php"
                                            class="cancel-order-form"
                                            onsubmit="return confirm(
                                                'Are you sure you want to cancel this order?'
                                            );"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(
                                                    $_SESSION['order_csrf_token']
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?= (int) $order['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="cancel-order-button"
                                            >
                                                <i class="fa-solid fa-ban"></i>
                                                Cancel Order
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>
                                            <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>