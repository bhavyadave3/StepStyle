<?php

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$userId = $_SESSION['user_id'];

$stmt = $db->prepare("
SELECT
    c.product_id,
    c.quantity,
    p.name,
    p.sale_price,
    p.main_image,
    p.stock_quantity
FROM cart c
INNER JOIN products p
ON c.product_id = p.id
WHERE c.user_id = ?
");

$stmt->execute([
    $userId
]);

$items = $stmt->fetchAll();

if(count($items) === 0){

    header("Location: cart.php");
    exit;
}

$total = 0;

include 'includes/header.php';

?>

<div class="container">

    <h1>Checkout</h1>

    <table class="cart-table">

        <thead>

            <tr>

                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>

            </tr>

        </thead>

        <tbody>

        <?php foreach($items as $item): ?>

            <?php

            $itemTotal =
            $item['sale_price']
            *
            $item['quantity'];

            $total += $itemTotal;

            ?>

            <tr>

                <td>

                    <?= htmlspecialchars($item['name']) ?>

                </td>

                <td>

                    <?= formatPrice($item['sale_price']) ?>

                </td>

                <td>

                    <?= $item['quantity'] ?>

                </td>

                <td>

                    <?= formatPrice($itemTotal) ?>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <div class="cart-total">

        <h2>

            Grand Total:
            <?= formatPrice($total) ?>

        </h2>

    </div>

    <form
    method="POST"
    action="place-order.php"
    >

        <div class="form-group">

            <label>
                Payment Method
            </label>

            <select
            name="payment_method"
            required
            >

                <option value="COD">
                    Cash On Delivery
                </option>

            </select>

        </div>

        <button
        type="submit"
        class="btn"
        >
            Place Order
        </button>

    </form>

</div>

<?php include 'includes/footer.php'; ?>