<?php

$baseUrl = rtrim(
    defined('SITE_URL')
        ? SITE_URL
        : '/StepStyle',
    '/'
);

$currentYear = date('Y');

$appJsPath =
    __DIR__ . '/../assets/js/app.js';

$authJsPath =
    __DIR__ . '/../assets/js/auth.js';

$cartJsPath =
    __DIR__ . '/../assets/js/cart.js';

$wishlistJsPath =
    __DIR__ . '/../assets/js/wishlist.js';

$appJsVersion =
    file_exists($appJsPath)
        ? filemtime($appJsPath)
        : time();

$authJsVersion =
    file_exists($authJsPath)
        ? filemtime($authJsPath)
        : time();

$cartJsVersion =
    file_exists($cartJsPath)
        ? filemtime($cartJsPath)
        : time();

$wishlistJsVersion =
    file_exists($wishlistJsPath)
        ? filemtime($wishlistJsPath)
        : time();

?>

<section class="newsletter-section">

    <div class="container">

        <div class="newsletter-content">

            <div class="newsletter-text">

                <span class="newsletter-label">
                    StepStyle Updates
                </span>

                <h2>
                    Stay Ahead of Every Step
                </h2>

                <p>
                    Subscribe for new arrivals, exclusive offers
                    and the latest footwear trends.
                </p>

            </div>

            <form
                class="newsletter-form"
                id="newsletterForm"
                novalidate
            >

                <label
                    for="newsletterEmail"
                    class="visually-hidden"
                >
                    Email address
                </label>

                <input
                    type="email"
                    id="newsletterEmail"
                    name="newsletter_email"
                    placeholder="Enter your email address"
                    autocomplete="email"
                    required
                >

                <button
                    type="submit"
                    class="subscribe-btn"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    <span>Subscribe</span>

                </button>

            </form>

        </div>

    </div>

</section>

<footer class="footer">

    <div class="container">

        <div class="footer-grid footer-grid-single">

    <div class="footer-brand">

        <a
            href="<?= $baseUrl ?>/index.php"
            class="footer-logo"
        >
            <span>Step</span><strong>Style</strong>
        </a>

        <p>
            Premium footwear designed for comfort,
            confidence and everyday style.
        </p>

        <div class="footer-social-links">

            <a
                href="#"
                aria-label="Instagram"
                title="Instagram"
            >
                <i class="fa-brands fa-instagram"></i>
            </a>

            <a
                href="#"
                aria-label="Facebook"
                title="Facebook"
            >
                <i class="fa-brands fa-facebook-f"></i>
            </a>

            <a
                href="#"
                aria-label="Twitter"
                title="Twitter"
            >
                <i class="fa-brands fa-x-twitter"></i>
            </a>

            <a
                href="#"
                aria-label="YouTube"
                title="YouTube"
            >
                <i class="fa-brands fa-youtube"></i>
            </a>

            </div>

            </div>

            </div>
            </div>

            <div class="footer-column">

    <h4>Quick Links</h4>

    <ul>

        <li>
            <a href="<?= $baseUrl ?>/index.php">
                Home
            </a>
        </li>

        <li>
            <a href="<?= $baseUrl ?>/shop.php">
                Products
            </a>
        </li>

        <li>
            <a href="<?= $baseUrl ?>/about.php">
                About Us
            </a>
        </li>

        <li>
            <a href="<?= $baseUrl ?>/contact.php">
                Contact Us
            </a>
        </li>

    </ul>

</div>

<div class="footer-column">

    <h4>Customer Account</h4>

    <ul>

        <li>
            <a href="<?= $baseUrl ?>/dashboard.php">
                Dashboard
            </a>
        </li>

        <li>
            <a href="<?= $baseUrl ?>/orders.php">
                My Orders
            </a>
        </li>

        <li>
            <a href="<?= $baseUrl ?>/wishlist.php">
                Wishlist
            </a>
        </li>

        <li>
            <a href="<?= $baseUrl ?>/cart.php">
                Shopping Cart
            </a>
        </li>

    </ul>

</div>

            <div class="footer-column">

                <h4>Customer Account</h4>

                <ul>

                    <li>
                        <a href="<?= $baseUrl ?>/dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li>
                        <a href="<?= $baseUrl ?>/orders.php">
                            My Orders
                        </a>
                    </li>

                    <li>
                        <a href="<?= $baseUrl ?>/wishlist.php">
                            Wishlist
                        </a>
                    </li>

                    <li>
                        <a href="<?= $baseUrl ?>/cart.php">
                            Shopping Cart
                        </a>
                    </li>

                </ul>

            </div>

        </div>

        <hr>

        <div class="footer-bottom">

            <p class="copyright">

                &copy; <?= $currentYear ?>
                StepStyle. All rights reserved.

            </p>

            <div class="footer-bottom-links">

                <a href="#">
                    Privacy Policy
                </a>

                <a href="#">
                    Terms and Conditions
                </a>

            </div>

        </div>

    </div>

</footer>

<script>
    window.STEPSTYLE_URL =
        <?= json_encode($baseUrl) ?>;
</script>

<script
    src="<?= $baseUrl ?>/assets/js/app.js?v=<?= $appJsVersion ?>"
></script>

<script
    src="<?= $baseUrl ?>/assets/js/auth.js?v=<?= $authJsVersion ?>"
></script>

<script
    src="<?= $baseUrl ?>/assets/js/wishlist.js?v=<?= $wishlistJsVersion ?>"
></script>

<script
    src="<?= $baseUrl ?>/assets/js/cart.js?v=<?= $cartJsVersion ?>"
></script>

</body>
</html>