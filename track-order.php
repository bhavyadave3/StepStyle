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

$trackingSteps = [
    'Pending',
    'Confirmed',
    'Processing',
    'Shipped',
    'Out For Delivery',
    'Delivered'
];

$currentStatus = $order['status'];

$currentStepIndex = array_search(
    $currentStatus,
    $trackingSteps,
    true
);

if ($currentStepIndex === false) {
    $currentStepIndex = -1;
}

$isCancelled = in_array(
    $currentStatus,
    [
        'Cancelled',
        'Returned',
        'Refunded'
    ],
    true
);

$statusClass = strtolower(
    str_replace(
        [' ', '_'],
        '-',
        $currentStatus
    )
);

require_once __DIR__ . '/includes/header.php';

?>
<main class="track-order-page">

    <section class="track-order-header">

        <div class="container">

            <a
                href="<?= SITE_URL ?>/orders.php"
                class="order-back-link"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Back to My Orders
            </a>

            <div class="track-order-heading">

                <div>

                    <span class="hero-badge">
                        <i class="fa-solid fa-truck-fast"></i>
                        Track Order
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
                    <?= htmlspecialchars($currentStatus) ?>
                </span>

            </div>

        </div>

    </section>

    <section class="track-order-content">

        <div class="container">

            <div class="tracking-card">

                <div class="tracking-card-heading">

                    <div>

                        <h2>Delivery Progress</h2>

                        <p>
                            Follow the current status of your order.
                        </p>

                    </div>

                    <div class="tracking-order-total">

                        <span>Order Total</span>

                        <strong>
                            ₹<?= number_format(
                                (float) $order['total_amount'],
                                2
                            ) ?>
                        </strong>

                    </div>

                </div>

                <?php if ($isCancelled): ?>

                    <div class="tracking-cancelled-message">

                        <i class="fa-solid fa-circle-xmark"></i>

                        <div>

                            <h3>
                                Order <?= htmlspecialchars(
                                    $currentStatus
                                ) ?>
                            </h3>

                            <p>
                                This order is no longer moving
                                through the delivery process.
                            </p>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="tracking-timeline">
                                            <?php foreach (
                            $trackingSteps as $index => $step
                        ): ?>

                            <?php
                            $stepClass = '';

                            if ($index < $currentStepIndex) {
                                $stepClass = 'completed';
                            } elseif ($index === $currentStepIndex) {
                                $stepClass = 'active';
                            }
                            ?>

                            <div
                                class="tracking-step <?= $stepClass ?>"
                            >

                                <div class="tracking-step-marker">

                                    <?php if (
                                        $index < $currentStepIndex
                                    ): ?>

                                        <i class="fa-solid fa-check"></i>

                                    <?php else: ?>

                                        <span>
                                            <?= $index + 1 ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="tracking-step-content">

                                    <h3>
                                        <?= htmlspecialchars($step) ?>
                                    </h3>

                                    <p>

                                        <?php if (
                                            $index < $currentStepIndex
                                        ): ?>

                                            This stage has been completed.

                                        <?php elseif (
                                            $index === $currentStepIndex
                                        ): ?>

                                            Your order is currently at this stage.

                                        <?php else: ?>

                                            Waiting for this stage.

                                        <?php endif; ?>

                                    </p>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <div class="tracking-information-grid">

                    <div class="tracking-information-item">

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

                    <div class="tracking-information-item">

                        <i class="fa-solid fa-calendar-check"></i>

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

                </div>
                                <div class="tracking-actions">

                    <a
                        href="<?= SITE_URL ?>/order-details.php?id=<?= (int) $order['id'] ?>"
                        class="btn"
                    >
                        <i class="fa-solid fa-receipt"></i>
                        View Order Details
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

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>