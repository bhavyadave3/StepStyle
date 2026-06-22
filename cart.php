<?php

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$userId =
    (int)$_SESSION['user_id'];

$stmt = $db->prepare("
SELECT
    c.id,
    c.product_id,
    c.quantity,
    p.name,
    COALESCE(
        NULLIF(p.sale_price, 0),
        p.price
    ) AS unit_price,
    p.main_image,
    p.stock_quantity,
    p.status
FROM cart c
INNER JOIN products p
ON c.product_id = p.id
WHERE c.user_id = ?
ORDER BY c.id DESC
");

$stmt->execute([
    $userId
]);

$items =
    $stmt->fetchAll();

$total = 0;

$canCheckout = true;

foreach ($items as $item) {

    if (
        $item['stock_quantity'] <= 0
        ||
        $item['status'] === 'out_of_stock'
        ||
        $item['quantity']
        >
        $item['stock_quantity']
    ) {

        $canCheckout = false;

    }

}

include 'includes/header.php';

?>

<div class="container">

    <h1>
        Shopping Cart
    </h1>

    <?php if(count($items) > 0): ?>

        <div
        style="
        width:100%;
        overflow-x:auto;
        "
        >

            <table class="cart-table">

                <thead>

                    <tr>

                        <th>Image</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach($items as $item): ?>

                    <?php

                    $unitPrice =
                        (float)$item['unit_price'];

                    $quantity =
                        (int)$item['quantity'];

                    $itemTotal =
                        $unitPrice * $quantity;

                    $total += $itemTotal;

                    $isAvailable =
                        $item['stock_quantity'] > 0
                        &&
                        $item['status']
                        ===
                        'in_stock';

                    ?>

                    <tr>

                        <td>

                            <img
                            src="<?= htmlspecialchars(
                                $item['main_image']
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $item['name']
                            ) ?>"
                            width="80"
                            height="80"
                            style="
                            object-fit:cover;
                            border-radius:10px;
                            "
                            >

                        </td>

                        <td>

                            <strong>
                                <?= htmlspecialchars(
                                    $item['name']
                                ) ?>
                            </strong>

                            <?php if(!$isAvailable): ?>

                                <div
                                style="
                                color:#dc2626;
                                font-size:13px;
                                font-weight:600;
                                margin-top:5px;
                                "
                                >
                                    Out Of Stock
                                </div>

                            <?php elseif(
                                $quantity
                                >
                                $item['stock_quantity']
                            ): ?>

                                <div
                                style="
                                color:#dc2626;
                                font-size:13px;
                                font-weight:600;
                                margin-top:5px;
                                "
                                >
                                    Only
                                    <?= (int)$item['stock_quantity'] ?>
                                    available
                                </div>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?= formatPrice(
                                $unitPrice
                            ) ?>

                        </td>

                        <td>

                            <input
                            type="number"
                            class="cart-qty"
                            data-id="<?= (int)$item['id'] ?>"
                            value="<?= $quantity ?>"
                            min="1"
                            max="<?= max(
                                1,
                                (int)$item['stock_quantity']
                            ) ?>"
                            style="width:70px;"
                            <?= !$isAvailable
                                ? 'disabled'
                                : '' ?>
                            >

                        </td>

                        <td>

                            <?= formatPrice(
                                $itemTotal
                            ) ?>

                        </td>

                        <td>

                            <button
                            type="button"
                            class="remove-cart-btn"
                            data-id="<?= (int)$item['id'] ?>"
                            >
                                Remove
                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="cart-total">

            <h2>

                Grand Total:

                <?= formatPrice(
                    (float)$total
                ) ?>

            </h2>

            <?php if($canCheckout): ?>

                <a
                href="checkout.php"
                class="btn"
                >
                    Checkout
                </a>

            <?php else: ?>

                <p
                style="
                color:#dc2626;
                font-weight:600;
                margin-bottom:12px;
                "
                >
                    Remove unavailable items or reduce their
                    quantities before checkout.
                </p>

                <span
                class="btn"
                style="
                opacity:.55;
                cursor:not-allowed;
                "
                >
                    Checkout Unavailable
                </span>

            <?php endif; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">

            <h3>
                Your cart is empty.
            </h3>

            <a
            href="shop.php"
            class="btn"
            >
                Continue Shopping
            </a>

        </div>

    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>