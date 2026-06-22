<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if(!isset($_SESSION['user_id'])){

    header(
        'Location: '
        .
        SITE_URL
        .
        '/login.php'
    );

    exit;
}

$userId = (int)$_SESSION['user_id'];
$wishlistProducts = [];

try{

    $wishlistStatement = $db->prepare("
        SELECT
            w.id AS wishlist_id,
            p.id,
            p.name,
            p.price,
            p.sale_price,
            p.stock_quantity,
            p.status,
            p.main_image,
            c.name AS category_name,
            b.name AS brand_name
        FROM wishlist w
        INNER JOIN products p
            ON p.id = w.product_id
        LEFT JOIN categories c
            ON c.id = p.category_id
        LEFT JOIN brands b
            ON b.id = p.brand_id
        WHERE w.user_id = ?
        ORDER BY w.id DESC
    ");

    $wishlistStatement->execute([
        $userId
    ]);

    $wishlistProducts =
        $wishlistStatement->fetchAll(
            PDO::FETCH_ASSOC
        );

}catch(PDOException $exception){

    $wishlistProducts = [];
}

require_once __DIR__ . '/includes/header.php';

?>

<main>

    <section class="wishlist-page">

        <div class="container">

            <div class="page-header">

                <h1>My Wishlist</h1>

                <p>
                    Save your favourite shoes and add them
                    to your cart whenever you are ready.
                </p>

            </div>

            <?php if(!empty($wishlistProducts)): ?>

                <div class="wishlist-grid">

                    <?php foreach($wishlistProducts as $product): ?>

                        <?php

                        $regularPrice =
                            (float)$product['price'];

                        $salePrice =
                            (float)$product['sale_price'];

                        $currentPrice =
                            $salePrice > 0
                            &&
                            $salePrice < $regularPrice
                                ? $salePrice
                                : $regularPrice;

                        $isInStock =
                            (int)$product['stock_quantity'] > 0
                            &&
                            $product['status'] !== 'out_of_stock';

                        $imagePath =
                            trim(
                                $product['main_image'] ?? ''
                            );

                        if($imagePath === ''){

                            $imagePath =
                                SITE_URL
                                .
                                '/assets/images/product-placeholder.png';

                        }elseif(
                            !str_starts_with($imagePath, 'http://')
                            &&
                            !str_starts_with($imagePath, 'https://')
                            &&
                            !str_starts_with($imagePath, SITE_URL)
                        ){

                            $imagePath =
                                SITE_URL
                                .
                                '/'
                                .
                                ltrim($imagePath, '/');
                        }

                        ?>
                                                <article
                            class="wishlist-card"
                            data-product-id="<?= (int)$product['id'] ?>"
                        >

                            <a
                                href="<?= SITE_URL ?>/product.php?id=<?= (int)$product['id'] ?>"
                                class="wishlist-card-image"
                            >

                                <img
                                    src="<?= htmlspecialchars($imagePath) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    loading="lazy"
                                >

                                <?php if($isInStock): ?>

                                    <span class="wishlist-stock-badge in-stock-badge">

                                        <i class="fa-solid fa-circle-check"></i>

                                        In Stock

                                    </span>

                                <?php else: ?>

                                    <span class="wishlist-stock-badge out-stock-badge">

                                        <i class="fa-solid fa-circle-xmark"></i>

                                        Out of Stock

                                    </span>

                                <?php endif; ?>

                            </a>

                            <div class="wishlist-card-content">

                                <span class="product-brand">

                                    <?= htmlspecialchars(
                                        $product['brand_name']
                                        ?? 'StepStyle'
                                    ) ?>

                                </span>

                                <h3>

                                    <a
                                        href="<?= SITE_URL ?>/product.php?id=<?= (int)$product['id'] ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $product['name']
                                        ) ?>

                                    </a>

                                </h3>

                                <?php if(
                                    !empty($product['category_name'])
                                ): ?>

                                    <p class="wishlist-category">

                                        <?= htmlspecialchars(
                                            $product['category_name']
                                        ) ?>

                                    </p>

                                <?php endif; ?>

                                <div class="product-price">

                                    <span class="sale-price">

                                        ₹<?= number_format(
                                            $currentPrice,
                                            2
                                        ) ?>

                                    </span>

                                    <?php if(
                                        $salePrice > 0
                                        &&
                                        $salePrice < $regularPrice
                                    ): ?>

                                        <span class="old-price">

                                            ₹<?= number_format(
                                                $regularPrice,
                                                2
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="wishlist-actions">

                                    <button
                                        type="button"
                                        class="btn add-cart-btn add-to-cart-btn"
                                        data-id="<?= (int)$product['id'] ?>"
                                        data-product-id="<?= (int)$product['id'] ?>"
                                        <?= !$isInStock
                                            ? 'disabled'
                                            : '' ?>
                                    >

                                        <i class="fa-solid fa-bag-shopping"></i>

                                        <span>

                                            <?= $isInStock
                                                ? 'Add to Cart'
                                                : 'Out of Stock' ?>

                                        </span>

                                    </button>

                                    <button
                                        type="button"
                                        class="remove-wishlist-btn"
                                        data-id="<?= (int)$product['id'] ?>"
                                        data-product-id="<?= (int)$product['id'] ?>"
                                        aria-label="Remove <?= htmlspecialchars($product['name']) ?> from wishlist"
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                        <span>Remove</span>

                                    </button>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state wishlist-empty-state">

                    <i class="fa-regular fa-heart"></i>

                    <h3>Your Wishlist is Empty</h3>

                    <p>
                        Save your favourite shoes here and
                        return to them whenever you are ready.
                    </p>

                    <a
                        href="<?= SITE_URL ?>/shop.php"
                        class="btn"
                    >

                        <i class="fa-solid fa-shoe-prints"></i>

                        <span>Explore Products</span>

                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>