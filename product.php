<?php

require_once 'includes/db.php';
require_once 'includes/functions.php';

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if(!$productId || $productId < 1){
    header("Location: shop.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Fetch only the selected product
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.*,
        b.name AS brand_name,
        c.name AS category_name
    FROM products p
    LEFT JOIN brands b
        ON p.brand_id = b.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
    LIMIT 1
");

$stmt->execute([
    $productId
]);

$product = $stmt->fetch();

if(!$product){
    header("Location: shop.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Product availability
|--------------------------------------------------------------------------
*/

$isInStock =
    (int)$product['stock_quantity'] > 0
    &&
    $product['status'] === 'in_stock';

$currentPrice =
    !empty($product['sale_price'])
    &&
    (float)$product['sale_price'] > 0
        ? (float)$product['sale_price']
        : (float)$product['price'];

/*
|--------------------------------------------------------------------------
| Approved product reviews
|--------------------------------------------------------------------------
*/

$reviewStmt = $db->prepare("
    SELECT
        r.id,
        r.rating,
        r.comment,
        r.created_at,
        u.name AS customer_name
    FROM reviews r
    INNER JOIN users u
        ON r.user_id = u.id
    WHERE r.product_id = ?
      AND r.status = 'approved'
    ORDER BY r.created_at DESC
");

$reviewStmt->execute([
    $productId
]);

$reviews = $reviewStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Related products
|--------------------------------------------------------------------------
*/

$relatedStmt = $db->prepare("
    SELECT
        p.*,
        b.name AS brand_name
    FROM products p
    LEFT JOIN brands b
        ON p.brand_id = b.id
    WHERE p.category_id = ?
      AND p.id != ?
      AND p.status = 'in_stock'
      AND p.stock_quantity > 0
    ORDER BY p.id DESC
    LIMIT 4
");

$relatedStmt->execute([
    $product['category_id'],
    $productId
]);

$relatedProducts = $relatedStmt->fetchAll();

include 'includes/header.php';

?>

<main>

    <section class="product-page-section">

        <div class="container">

            <div class="product-details">

                <!-- Product Image -->

                <div class="product-image">

                    <img
                        src="<?= htmlspecialchars(
                            (string)$product['main_image']
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            (string)$product['name']
                        ) ?>"
                        loading="eager"
                    >

                </div>

                <!-- Product Information -->

                <div class="product-content">

                    <span class="product-brand">

                        <?= htmlspecialchars(
                            (string)($product['brand_name'] ?? 'StepStyle')
                        ) ?>

                    </span>

                    <h1>

                        <?= htmlspecialchars(
                            (string)$product['name']
                        ) ?>

                    </h1>

                    <p class="product-category">

                        Category:

                        <?= htmlspecialchars(
                            (string)($product['category_name'] ?? 'Shoes')
                        ) ?>

                    </p>

                    <?php if(!empty($product['sku'])): ?>

                        <p class="product-sku">

                            SKU:

                            <?= htmlspecialchars(
                                (string)$product['sku']
                            ) ?>

                        </p>

                    <?php endif; ?>

                    <div class="product-price">

                        <span class="sale-price">

                            <?= formatPrice($currentPrice) ?>

                        </span>

                        <?php if(
                            !empty($product['sale_price'])
                            &&
                            (float)$product['sale_price'] > 0
                            &&
                            (float)$product['price']
                            >
                            (float)$product['sale_price']
                        ): ?>

                            <span class="old-price">

                                <?= formatPrice(
                                    (float)$product['price']
                                ) ?>

                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- Stock Status -->

                    <div class="stock-status">

                        <?php if($isInStock): ?>

                            <span class="in-stock">

                                <i class="fa-solid fa-circle-check"></i>

                                In Stock
                                (<?= (int)$product['stock_quantity'] ?>)

                            </span>

                        <?php else: ?>

                            <span class="out-stock">

                                <i class="fa-solid fa-circle-xmark"></i>

                                Out Of Stock

                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- Description -->

                    <div class="product-description">

                        <h3>Description</h3>

                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    (string)$product['description']
                                )
                            ) ?>

                        </p>

                    </div>

                    <!-- Actions -->

                    <div class="product-actions">

                        <?php if($isInStock): ?>

                            <button
                                type="button"
                                class="btn add-cart-btn"
                                data-id="<?= (int)$product['id'] ?>"
                            >

                                <i class="fa-solid fa-cart-plus"></i>

                                Add To Cart

                            </button>

                        <?php else: ?>

                            <button
                                type="button"
                                class="btn"
                                disabled
                                style="
                                    opacity:0.55;
                                    cursor:not-allowed;
                                "
                            >

                                <i class="fa-solid fa-ban"></i>

                                Out Of Stock

                            </button>

                        <?php endif; ?>

                        <?php if(isset($_SESSION['user_id'])): ?>

                            <button
                                type="button"
                                class="btn wishlist-btn"
                                data-id="<?= (int)$product['id'] ?>"
                            >

                                <i class="fa-solid fa-heart"></i>

                                Add To Wishlist

                            </button>

                        <?php else: ?>

                            <a
                                href="login.php"
                                class="btn"
                            >

                                <i class="fa-solid fa-heart"></i>

                                Login For Wishlist

                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- Write Review -->

    <section class="product-review-form-section">

        <div class="container">

            <?php if(isset($_SESSION['user_id'])): ?>

                <div class="review-form">

                    <h2>Write a Review</h2>

                    <form
                        method="POST"
                        action="submit-review.php"
                    >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int)$product['id'] ?>"
                        >

                        <div class="form-group">

                            <label for="rating">
                                Rating
                            </label>

                            <select
                                id="rating"
                                name="rating"
                                required
                            >

                                <option value="">
                                    Select Rating
                                </option>

                                <option value="5">
                                    ★★★★★
                                </option>

                                <option value="4">
                                    ★★★★
                                </option>

                                <option value="3">
                                    ★★★
                                </option>

                                <option value="2">
                                    ★★
                                </option>

                                <option value="1">
                                    ★
                                </option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label for="comment">
                                Your Review
                            </label>

                            <textarea
                                id="comment"
                                name="comment"
                                rows="5"
                                placeholder="Share your experience with this product..."
                                required
                            ></textarea>

                        </div>

                        <button
                            type="submit"
                            class="btn"
                        >

                            Submit Review

                        </button>

                    </form>

                </div>

            <?php else: ?>

                <p>

                    Please

                    <a href="login.php">
                        login
                    </a>

                    to submit a review.

                </p>

            <?php endif; ?>

        </div>

    </section>


    <!-- Approved Reviews -->

    <section class="reviews-section">

        <div class="container">

            <h2 class="section-title">
                Customer Reviews
            </h2>

            <?php if(count($reviews) > 0): ?>

                <?php foreach($reviews as $review): ?>

                    <article class="review-card">

                        <div class="review-card-header">

                            <h4>

                                <?= htmlspecialchars(
                                    (string)$review['customer_name']
                                ) ?>

                            </h4>

                            <span class="review-rating">

                                <?= str_repeat(
                                    '★',
                                    (int)$review['rating']
                                ) ?>

                            </span>

                        </div>

                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    (string)$review['comment']
                                )
                            ) ?>

                        </p>

                        <small>

                            <?= date(
                                'd M Y',
                                strtotime($review['created_at'])
                            ) ?>

                        </small>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state">

                    <p>
                        No approved reviews for this product yet.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- Related Products -->

    <?php if(count($relatedProducts) > 0): ?>

        <section class="related-products">

            <div class="container">

                <h2 class="section-title">
                    Related Products
                </h2>

                <div class="product-grid">

                    <?php foreach($relatedProducts as $item): ?>

                        <?php

                        $relatedPrice =
                            !empty($item['sale_price'])
                            &&
                            (float)$item['sale_price'] > 0
                                ? (float)$item['sale_price']
                                : (float)$item['price'];

                        ?>

                        <article class="product-card">

                            <img
                                src="<?= htmlspecialchars(
                                    (string)$item['main_image']
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    (string)$item['name']
                                ) ?>"
                                loading="lazy"
                            >

                            <div class="product-info">

                                <small>

                                    <?= htmlspecialchars(
                                        (string)($item['brand_name'] ?? 'StepStyle')
                                    ) ?>

                                </small>

                                <h3>

                                    <?= htmlspecialchars(
                                        (string)$item['name']
                                    ) ?>

                                </h3>

                                <p class="price">

                                    <?= formatPrice(
                                        $relatedPrice
                                    ) ?>

                                </p>

                                <a
                                    href="product.php?id=<?= (int)$item['id'] ?>"
                                    class="btn"
                                >

                                    View Product

                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>