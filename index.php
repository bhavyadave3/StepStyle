<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$featuredProducts = [];

try{

    $productStatement = $db->prepare("
        SELECT
            p.id,
            p.name,
            p.price,
            p.sale_price,
            p.stock_quantity,
            p.main_image,
            p.featured,
            p.status,
            c.name AS category_name,
            b.name AS brand_name
        FROM products p
        LEFT JOIN categories c
            ON c.id = p.category_id
        LEFT JOIN brands b
            ON b.id = p.brand_id
        WHERE p.stock_quantity > 0
        AND p.status <> 'out_of_stock'
        ORDER BY
            p.featured DESC,
            p.created_at DESC
        LIMIT 6
    ");

    $productStatement->execute();

    $featuredProducts =
        $productStatement->fetchAll(
            PDO::FETCH_ASSOC
        );

}catch(PDOException $exception){

    $featuredProducts = [];
}

require_once __DIR__ . '/includes/header.php';

?>

<main>

    <section class="hero">

        <div class="container">

            <div class="hero-content">

                <span class="hero-badge">

                    <i class="fa-solid fa-bolt"></i>

                    Premium Footwear Collection

                </span>

                <h1>

                    Step Into

                    <span>
                        Your Style
                    </span>

                </h1>

                <p>
                    Discover premium shoes designed for
                    comfort, performance and confidence.
                    Find the perfect pair for every step.
                </p>

                <div class="hero-actions">

                    <a
                        href="<?= SITE_URL ?>/shop.php"
                        class="btn"
                    >

                        <i class="fa-solid fa-bag-shopping"></i>

                        <span>Shop Now</span>

                    </a>

                    <a
                        href="<?= SITE_URL ?>/about.php"
                        class="btn btn-outline"
                    >

                        <i class="fa-solid fa-circle-info"></i>

                        <span>Learn More</span>

                    </a>

                </div>

            </div>

        </div>

    </section>

    <section class="featured-products">

        <div class="container">

            <div class="section-heading">

                <span class="section-label">
                    Featured Collection
                </span>

                <h2 class="section-title">
                    Step Into Something Better
                </h2>

                <p class="section-subtitle">
                    Explore our latest in-stock footwear,
                    selected for comfort, quality and style.
                </p>

            </div>

            <?php if(!empty($featuredProducts)): ?>

                <div class="product-grid">
                                        <?php foreach($featuredProducts as $product): ?>

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
                            !str_starts_with(
                                $imagePath,
                                'http://'
                            )
                            &&
                            !str_starts_with(
                                $imagePath,
                                'https://'
                            )
                            &&
                            !str_starts_with(
                                $imagePath,
                                SITE_URL
                            )
                        ){

                            $imagePath =
                                SITE_URL
                                .
                                '/'
                                .
                                ltrim(
                                    $imagePath,
                                    '/'
                                );
                        }

                        ?>

                        <article class="product-card">

                            <a
                                href="<?= SITE_URL ?>/product.php?id=<?= (int)$product['id'] ?>"
                                class="product-card-image"
                            >

                                <img
                                    src="<?= htmlspecialchars($imagePath) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    loading="lazy"
                                >

                                <?php if(
                                    $salePrice > 0
                                    &&
                                    $salePrice < $regularPrice
                                ): ?>

                                    <span class="product-status-label">

                                        <i class="fa-solid fa-tag"></i>

                                        Sale

                                    </span>

                                <?php else: ?>

                                    <span class="product-status-label">

                                        <i class="fa-solid fa-check"></i>

                                        In Stock

                                    </span>

                                <?php endif; ?>

                            </a>

                            <div class="product-card-content">

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
                                    !empty(
                                        $product['category_name']
                                    )
                                ): ?>

                                    <small>

                                        <?= htmlspecialchars(
                                            $product['category_name']
                                        ) ?>

                                    </small>

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

                                <div class="product-card-actions">

                                    <button
                                        type="button"
                                        class="btn add-cart-btn add-to-cart-btn"
                                        data-id="<?= (int)$product['id'] ?>"
                                        data-product-id="<?= (int)$product['id'] ?>"
                                    >

                                        <i class="fa-solid fa-bag-shopping"></i>

                                        <span>Add to Cart</span>

                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline wishlist-btn"
                                        data-id="<?= (int)$product['id'] ?>"
                                        data-product-id="<?= (int)$product['id'] ?>"
                                        aria-label="Add <?= htmlspecialchars($product['name']) ?> to wishlist"
                                    >

                                        <i class="fa-regular fa-heart"></i>

                                    </button>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <div class="featured-products-action">

                    <a
                        href="<?= SITE_URL ?>/shop.php"
                        class="btn btn-outline"
                    >

                        <span>View All Products</span>

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <i class="fa-solid fa-shoe-prints"></i>

                    <h3>No Products Available</h3>

                    <p>
                        There are currently no in-stock products.
                        Please check again soon.
                    </p>

                    <a
                        href="<?= SITE_URL ?>/shop.php"
                        class="btn"
                    >
                        Visit Shop
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <section class="section home-benefits">

        <div class="container">

            <div class="features-grid">

                <article class="feature-card">

                    <i class="fa-solid fa-truck-fast"></i>

                    <h3>Fast Delivery</h3>

                    <p>
                        Quick and reliable delivery for every
                        StepStyle order.
                    </p>

                </article>

                <article class="feature-card">

                    <i class="fa-solid fa-shield-halved"></i>

                    <h3>Secure Shopping</h3>

                    <p>
                        Your account and order information remain
                        protected.
                    </p>

                </article>

                <article class="feature-card">

                    <i class="fa-solid fa-headset"></i>

                    <h3>Customer Support</h3>

                    <p>
                        Friendly support whenever you need help
                        with your purchase.
                    </p>

                </article>

            </div>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>