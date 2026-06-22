<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$currentPage = basename(
    $_SERVER['PHP_SELF'] ?? ''
);

$isLoggedIn = isset(
    $_SESSION['user_id']
);

$currentUser = [
    'name' => 'My Account',
    'email' => ''
];

$cartCount = 0;
$wishlistCount = 0;

if($isLoggedIn){

    $userId = (int)$_SESSION['user_id'];

    try{

        $userStmt = $db->prepare("
            SELECT
                name,
                email
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $userStmt->execute([
            $userId
        ]);

        $userData = $userStmt->fetch(
            PDO::FETCH_ASSOC
        );

        if($userData){

            $currentUser = [
                'name' => $userData['name'] ?? 'My Account',
                'email' => $userData['email'] ?? ''
            ];
        }

        $cartStmt = $db->prepare("
            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                )
            FROM cart
            WHERE user_id = ?
        ");

        $cartStmt->execute([
            $userId
        ]);

        $cartCount = (int)$cartStmt->fetchColumn();

        $wishlistStmt = $db->prepare("
            SELECT COUNT(*)
            FROM wishlist
            WHERE user_id = ?
        ");

        $wishlistStmt->execute([
            $userId
        ]);

        $wishlistCount =
            (int)$wishlistStmt->fetchColumn();

    }catch(PDOException $exception){

        $cartCount = 0;
        $wishlistCount = 0;
    }
}

$userInitial = strtoupper(
    substr(
        trim($currentUser['name']),
        0,
        1
    )
);

if($userInitial === ''){
    $userInitial = 'U';
}

if(!function_exists('isActivePage')){

    function isActivePage(
        string $page,
        string $currentPage
    ): string {

        return $page === $currentPage
            ? 'active'
            : '';
    }
}

$stylePath =
    __DIR__ . '/../assets/css/style.css';

$styleVersion =
    file_exists($stylePath)
        ? filemtime($stylePath)
        : time();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="StepStyle premium online shoe store."
    >

    <title>StepStyle</title>

    <script>
        (function () {

            const savedTheme =
                localStorage.getItem("theme");

            if(savedTheme === "dark"){

                document.documentElement.classList.add(
                    "dark-theme"
                );
            }

        })();
    </script>

    <link
        rel="stylesheet"
        href="<?= SITE_URL ?>/assets/css/style.css?v=<?= $styleVersion ?>"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>

<script>
    if(
        localStorage.getItem("theme")
        ===
        "dark"
    ){
        document.body.classList.add("dark");
    }
</script>

<header class="site-header">

    <div class="header-container">

        <a
            href="<?= SITE_URL ?>/index.php"
            class="site-logo"
            aria-label="StepStyle homepage"
        >

            <span class="logo-step">
                Step
            </span>

            <span class="logo-style">
                Style
            </span>

        </a>

<form
    method="GET"
    action="<?= SITE_URL ?>/shop.php"
    class="header-search-form"
    role="search"
>

    <div class="header-search-box">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="search"
            name="search"
            value="<?= htmlspecialchars(
                trim($_GET['search'] ?? '')
            ) ?>"
            placeholder="Search shoes, brands or categories..."
            aria-label="Search products"
            autocomplete="off"
        >

        <button
            type="submit"
            aria-label="Submit search"
            title="Search"
        >
            <i class="fa-solid fa-arrow-right"></i>
        </button>

    </div>

</form>

        <button
            type="button"
            class="mobile-menu-button"
            id="mobileMenuButton"
            aria-label="Open navigation menu"
            aria-expanded="false"
            aria-controls="mainNavigation"
        >

            <i class="fa-solid fa-bars"></i>

        </button>

        <nav
            class="main-navigation"
            id="mainNavigation"
        >

            <a
                href="<?= SITE_URL ?>/index.php"
                class="nav-link <?= isActivePage(
                    'index.php',
                    $currentPage
                ) ?>"
            >

                <i class="fa-solid fa-house"></i>

                <span>Home</span>

            </a>

            <a
                href="<?= SITE_URL ?>/shop.php"
                class="nav-link <?= in_array(
                    $currentPage,
                    [
                        'shop.php',
                        'product.php'
                    ],
                    true
                )
                    ? 'active'
                    : '' ?>"
            >

                <i class="fa-solid fa-shoe-prints"></i>

                <span>Products</span>

            </a>

            <a
                href="<?= SITE_URL ?>/about.php"
                class="nav-link <?= isActivePage(
                    'about.php',
                    $currentPage
                ) ?>"
            >

                <i class="fa-solid fa-circle-info"></i>

                <span>About</span>

            </a>

            <a
                href="<?= SITE_URL ?>/contact.php"
                class="nav-link <?= isActivePage(
                    'contact.php',
                    $currentPage
                ) ?>"
            >

                <i class="fa-solid fa-envelope"></i>

                <span>Contact</span>

            </a>

        </nav>

        <div class="header-actions">

            <button
                type="button"
                class="header-icon-button theme-toggle-button"
                id="darkModeBtn"
                aria-label="Toggle dark and light mode"
                title="Toggle theme"
            >

                <i class="fa-solid fa-moon"></i>

            </button>

            <a
                href="<?= SITE_URL ?>/wishlist.php"
                class="header-icon-button <?= isActivePage(
                    'wishlist.php',
                    $currentPage
                ) ?>"
                aria-label="Wishlist"
                title="Wishlist"
            >

                <i class="fa-regular fa-heart"></i>

                <?php if($wishlistCount > 0): ?>

                    <span class="header-count-badge">

                        <?= $wishlistCount > 99
                            ? '99+'
                            : $wishlistCount ?>

                    </span>

                <?php endif; ?>

            </a>

            <a
                href="<?= SITE_URL ?>/cart.php"
                class="header-icon-button <?= isActivePage(
                    'cart.php',
                    $currentPage
                ) ?>"
                aria-label="Shopping cart"
                title="Cart"
            >

                <i class="fa-solid fa-bag-shopping"></i>

                <?php if($cartCount > 0): ?>

                    <span class="header-count-badge">

                        <?= $cartCount > 99
                            ? '99+'
                            : $cartCount ?>

                    </span>

                <?php endif; ?>

            </a>

            <?php if($isLoggedIn): ?>

                <div class="account-menu">

                    <button
                        type="button"
                        class="account-menu-button"
                        aria-label="Open account menu"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >

                        <span class="account-avatar">

                            <?= htmlspecialchars(
                                $userInitial
                            ) ?>

                        </span>

                        <span class="account-button-details">

                            <small>Welcome</small>

                            <strong>

                                <?= htmlspecialchars(
                                    $currentUser['name']
                                ) ?>

                            </strong>

                        </span>

                        <i class="fa-solid fa-chevron-down"></i>

                    </button>

                    <div class="account-dropdown">

                        <div class="account-dropdown-header">

                            <span class="account-dropdown-avatar">

                                <?= htmlspecialchars(
                                    $userInitial
                                ) ?>

                            </span>

                            <div>

                                <strong>

                                    <?= htmlspecialchars(
                                        $currentUser['name']
                                    ) ?>

                                </strong>

                                <?php if(
                                    $currentUser['email'] !== ''
                                ): ?>

                                    <small>

                                        <?= htmlspecialchars(
                                            $currentUser['email']
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="account-dropdown-section">

                            <span class="account-section-title">
                                Dashboard
                            </span>

                            <a
                                href="<?= SITE_URL ?>/profile.php"
                                class="account-dropdown-link"
                            >

                                <i class="fa-solid fa-user"></i>

                                <span>Profile</span>

                            </a>

                            <a
                                href="<?= SITE_URL ?>/orders.php"
                                class="account-dropdown-link"
                            >

                                <i class="fa-solid fa-box"></i>

                                <span>My Orders</span>

                            </a>

                            <a
                                href="<?= SITE_URL ?>/wishlist.php"
                                class="account-dropdown-link"
                            >

                                <i class="fa-solid fa-heart"></i>

                                <span>My Wishlist</span>

                            </a>

                            <a
                                href="<?= SITE_URL ?>/settings.php"
                                class="account-dropdown-link"
                            >

                                <i class="fa-solid fa-gear"></i>

                                <span>Settings</span>

                            </a>

                        </div>

                        <div class="account-dropdown-section logout-section">

                            <span class="account-section-title">
                                Logout
                            </span>

                            <a
                                href="<?= SITE_URL ?>/logout.php"
                                class="account-dropdown-link logout-link"
                            >

                                <i class="fa-solid fa-right-from-bracket"></i>

                                <span>Logout</span>

                            </a>

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="guest-account-actions">

                    <a
                        href="<?= SITE_URL ?>/login.php"
                        class="header-login-button"
                    >

                        <i class="fa-solid fa-user"></i>

                        <span>Login</span>

                    </a>

                    <a
                        href="<?= SITE_URL ?>/register.php"
                        class="header-register-button"
                    >

                        Create Account

                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</header>