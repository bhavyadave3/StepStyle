<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

?>

<main>

    <section class="about-hero">

        <div class="container">

            <span class="hero-badge">

                <i class="fa-solid fa-shoe-prints"></i>

                About StepStyle

            </span>

            <h1>
                Footwear Made for Every Step
            </h1>

            <p>
                StepStyle brings together comfort, quality
                and modern design to help you move with
                confidence every day.
            </p>

        </div>

    </section>

    <section class="about-section">

        <div class="container">

            <div class="about-grid">

                <div class="about-image">

                    <img
                        src="<?= SITE_URL ?>/assets/images/about-shoes.jpg"
                        alt="StepStyle premium footwear collection"
                        loading="lazy"
                    >

                </div>

                <div class="about-content">

                    <span class="section-label">
                        Our Story
                    </span>

                    <h2>
                        Style That Moves With You
                    </h2>

                    <p>
                        StepStyle was created with one simple
                        goal: to make premium footwear easier
                        to discover and enjoy.
                    </p>

                    <p>
                        We carefully select shoes that combine
                        lasting comfort, reliable performance
                        and modern style for every lifestyle.
                    </p>

                    <p>
                        Whether you are training, travelling,
                        working or relaxing, StepStyle helps
                        you find the right pair for every
                        moment.
                    </p>

                    <a
                        href="<?= SITE_URL ?>/shop.php"
                        class="btn"
                    >

                        <i class="fa-solid fa-bag-shopping"></i>

                        <span>Explore Products</span>

                    </a>

                </div>

            </div>

        </div>

    </section>

    <section class="section about-values-section">

        <div class="container">

            <div class="section-heading">

                <span class="section-label">
                    Why StepStyle
                </span>

                <h2 class="section-title">
                    Built Around Your Comfort
                </h2>

                <p class="section-subtitle">
                    Every part of StepStyle is designed to
                    make your shopping experience simple,
                    secure and enjoyable.
                </p>

            </div>

            <div class="values-grid">

                <article class="value-card">

                    <i class="fa-solid fa-gem"></i>

                    <h3>Premium Quality</h3>

                    <p>
                        Carefully selected footwear from
                        trusted brands with dependable
                        materials and construction.
                    </p>

                </article>

                <article class="value-card">

                    <i class="fa-solid fa-heart"></i>

                    <h3>Customer First</h3>

                    <p>
                        Your comfort and satisfaction guide
                        every product and feature we provide.
                    </p>

                </article>

                <article class="value-card">

                    <i class="fa-solid fa-shield-halved"></i>

                    <h3>Secure Shopping</h3>

                    <p>
                        A protected and reliable shopping
                        experience from account creation
                        through order completion.
                    </p>

                </article>

            </div>

        </div>

    </section>
        <section class="section about-mission-section">

        <div class="container">

            <div class="about-mission-card">

                <div class="about-mission-content">

                    <span class="section-label">
                        Our Mission
                    </span>

                    <h2>
                        Helping You Walk With Confidence
                    </h2>

                    <p>
                        Our mission is to provide stylish,
                        comfortable and dependable footwear
                        through a simple online shopping
                        experience.
                    </p>

                    <div class="about-mission-points">

                        <div class="mission-point">

                            <i class="fa-solid fa-circle-check"></i>

                            <span>
                                Carefully selected products
                            </span>

                        </div>

                        <div class="mission-point">

                            <i class="fa-solid fa-circle-check"></i>

                            <span>
                                Clear product information
                            </span>

                        </div>

                        <div class="mission-point">

                            <i class="fa-solid fa-circle-check"></i>

                            <span>
                                Reliable customer support
                            </span>

                        </div>

                        <div class="mission-point">

                            <i class="fa-solid fa-circle-check"></i>

                            <span>
                                Secure order management
                            </span>

                        </div>

                    </div>

                </div>

                <div class="about-stats">

                    <div class="about-stat">

                        <strong>100%</strong>

                        <span>
                            Quality Focus
                        </span>

                    </div>

                    <div class="about-stat">

                        <strong>24/7</strong>

                        <span>
                            Online Shopping
                        </span>

                    </div>

                    <div class="about-stat">

                        <strong>Easy</strong>

                        <span>
                            Order Tracking
                        </span>

                    </div>

                    <div class="about-stat">

                        <strong>Secure</strong>

                        <span>
                            Customer Accounts
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <section class="section about-cta-section">

        <div class="container">

            <div class="about-cta-card">

                <div>

                    <span class="section-label">
                        Find Your Perfect Pair
                    </span>

                    <h2>
                        Ready to Take Your Next Step?
                    </h2>

                    <p>
                        Browse our latest in-stock shoes and
                        discover footwear that matches your
                        lifestyle.
                    </p>

                </div>

                <a
                    href="<?= SITE_URL ?>/shop.php"
                    class="btn"
                >

                    <span>Shop Collection</span>

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </div>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>