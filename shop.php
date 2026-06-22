<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$brandId = (int)($_GET['brand'] ?? 0);
$sort = $_GET['sort'] ?? 'latest';

$categories = [];
$brands = [];
$products = [];

try{

    $categoryStatement = $db->query("
        SELECT id, name
        FROM categories
        ORDER BY name ASC
    ");

    $categories = $categoryStatement->fetchAll(
        PDO::FETCH_ASSOC
    );

    $brandStatement = $db->query("
        SELECT id, name
        FROM brands
        ORDER BY name ASC
    ");

    $brands = $brandStatement->fetchAll(
        PDO::FETCH_ASSOC
    );

    $sql = "
        SELECT
            p.id,
            p.name,
            p.description,
            p.price,
            p.sale_price,
            p.stock_quantity,
            p.main_image,
            p.created_at,
            c.name AS category_name,
            b.name AS brand_name
        FROM products p
        LEFT JOIN categories c
            ON c.id = p.category_id
        LEFT JOIN brands b
            ON b.id = p.brand_id
        WHERE p.stock_quantity > 0
        AND p.status <> 'out_of_stock'
    ";

    $parameters = [];

    if($search !== ''){

        $sql .= "
            AND (
                p.name LIKE ?
                OR p.description LIKE ?
                OR c.name LIKE ?
                OR b.name LIKE ?
            )
        ";

        $searchValue = '%' . $search . '%';

        $parameters[] = $searchValue;
        $parameters[] = $searchValue;
        $parameters[] = $searchValue;
        $parameters[] = $searchValue;
    }

    if($categoryId > 0){

        $sql .= "
            AND p.category_id = ?
        ";

        $parameters[] = $categoryId;
    }

    if($brandId > 0){

        $sql .= "
            AND p.brand_id = ?
        ";

        $parameters[] = $brandId;
    }

    switch($sort){

        case 'price_low':
            $sql .= "
                ORDER BY
                    CASE
                        WHEN p.sale_price > 0
                        THEN p.sale_price
                        ELSE p.price
                    END ASC
            ";
            break;

        case 'price_high':
            $sql .= "
                ORDER BY
                    CASE
                        WHEN p.sale_price > 0
                        THEN p.sale_price
                        ELSE p.price
                    END DESC
            ";
            break;

        case 'name_asc':
            $sql .= "
                ORDER BY p.name ASC
            ";
            break;

        case 'name_desc':
            $sql .= "
                ORDER BY p.name DESC
            ";
            break;

        default:
            $sql .= "
                ORDER BY p.created_at DESC
            ";
            break;
    }

    $productStatement = $db->prepare($sql);

    $productStatement->execute(
        $parameters
    );

    $products = $productStatement->fetchAll(
        PDO::FETCH_ASSOC
    );

}catch(PDOException $exception){

    $products = [];
}

require_once __DIR__ . '/includes/header.php';

?>

<main>

    <section class="shop-section">

        <div class="container">

            <div class="page-header">

                <h1>Shop Shoes</h1>

                <p>
                    Discover premium footwear designed for
                    comfort, performance and style.
                </p>

            </div>

            <div class="product-filters">

               <form
    method="GET"
    action="<?= SITE_URL ?>/shop.php"
    class="filter-form compact-filter-form"
>

    <?php if ($search !== ''): ?>

        <input
            type="hidden"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
        >

    <?php endif; ?>

    <div class="filter-field filter-sort-field">

        <label for="sort">
            Sort By
        </label>

        <select
            id="sort"
            name="sort"
        >

            <option
                value="latest"
                <?= $sort === 'latest'
                    ? 'selected'
                    : '' ?>
            >
                Latest
            </option>

            <option
                value="price_low"
                <?= $sort === 'price_low'
                    ? 'selected'
                    : '' ?>
            >
                Price: Low to High
            </option>

            <option
                value="price_high"
                <?= $sort === 'price_high'
                    ? 'selected'
                    : '' ?>
            >
                Price: High to Low
            </option>

            <option
                value="name_asc"
                <?= $sort === 'name_asc'
                    ? 'selected'
                    : '' ?>
            >
                Name: A to Z
            </option>

            <option
                value="name_desc"
                <?= $sort === 'name_desc'
                    ? 'selected'
                    : '' ?>
            >
                Name: Z to A
            </option>

        </select>

    </div>

    <div class="filter-field filter-category-field">

        <label for="category">
            Category
        </label>

        <select
            id="category"
            name="category"
        >

            <option value="0">
                All Categories
            </option>

            <?php foreach ($categories as $category): ?>

                <option
                    value="<?= (int) $category['id'] ?>"
                    <?= $categoryId === (int) $category['id']
                        ? 'selected'
                        : '' ?>
                >
                    <?= htmlspecialchars(
                        $category['name']
                    ) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="filter-field filter-brand-field">

        <label for="brand">
            Brand
        </label>

        <select
            id="brand"
            name="brand"
        >

            <option value="0">
                All Brands
            </option>

            <?php foreach ($brands as $brand): ?>

                <option
                    value="<?= (int) $brand['id'] ?>"
                    <?= $brandId === (int) $brand['id']
                        ? 'selected'
                        : '' ?>
                >
                    <?= htmlspecialchars(
                        $brand['name']
                    ) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="filter-actions">

        <button
            type="submit"
            class="btn filter-apply-button"
        >
            <i class="fa-solid fa-filter"></i>
            <span>Apply</span>
        </button>

        <a
            href="<?= SITE_URL ?>/shop.php"
            class="btn btn-outline filter-clear-button"
        >
            <i class="fa-solid fa-rotate-left"></i>
            <span>Clear</span>
        </a>

    </div>

</form>
            </div>

            <?php if(!empty($products)): ?>

                <div class="product-grid">

                    <?php foreach($products as $product): ?>

                        <?php

                        $currentPrice =
                            (float)$product['sale_price'] > 0
                                ? (float)$product['sale_price']
                                : (float)$product['price'];

                        $imagePath =
                            trim($product['main_image'] ?? '');

                        if($imagePath === ''){

                            $imagePath =
                                SITE_URL
                                .
                                '/assets/images/product-placeholder.png';

                        }elseif(
                            !str_starts_with(
                                $imagePath,
                                'http'
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
                                ltrim($imagePath, '/');
                        }

                        ?>

                        <article class="product-card">

                            <a
                                href="<?= SITE_URL ?>/product.php?id=<?= (int)$product['id'] ?>"
                                class="product-card-image"
                            >

                                <img
                                    src="<?= htmlspecialchars($imagePath) ?>"
                                    alt="<?= htmlspecialchars(
                                        $product['name']
                                    ) ?>"
                                    loading="lazy"
                                >

                                <span class="product-status-label">

                                    <i class="fa-solid fa-check"></i>

                                    In Stock

                                </span>

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

                                <div class="product-price">

                                    <?php if(
                                        (float)$product['sale_price'] > 0
                                        &&
                                        (float)$product['sale_price']
                                        <
                                        (float)$product['price']
                                    ): ?>

                                        <span class="sale-price">

                                            ₹<?= number_format(
                                                $currentPrice,
                                                2
                                            ) ?>

                                        </span>

                                        <span class="old-price">

                                            ₹<?= number_format(
                                                (float)$product['price'],
                                                2
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="sale-price">

                                            ₹<?= number_format(
                                                $currentPrice,
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
                                        aria-label="Add to wishlist"
                                    >

                                        <i class="fa-regular fa-heart"></i>

                                    </button>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <i class="fa-solid fa-shoe-prints"></i>

                    <h3>No products found</h3>

                    <p>
                        No in-stock products match your filters.
                        Try changing your search or filter options.
                    </p>

                    <a
                        href="<?= SITE_URL ?>/shop.php"
                        class="btn"
                    >
                        View All Products
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>