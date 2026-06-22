<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$name = $_SESSION['user_name'] ?? '';
$email = $_SESSION['user_email'] ?? '';
$subject = '';
$messageText = '';

$successMessage = '';
$errorMessage = '';

if(empty($_SESSION['contact_csrf_token'])){

    $_SESSION['contact_csrf_token'] =
        bin2hex(random_bytes(32));
}

try{

    $db->exec("
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM(
                'unread',
                'read',
                'replied'
            ) DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

}catch(PDOException $exception){

    $errorMessage =
        'Unable to prepare the contact form.';
}

if(
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    $errorMessage === ''
){

    $csrfToken =
        $_POST['csrf_token'] ?? '';

    $name = trim(
        $_POST['name'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $subject = trim(
        $_POST['subject'] ?? ''
    );

    $messageText = trim(
        $_POST['message'] ?? ''
    );

    if(
        !hash_equals(
            $_SESSION['contact_csrf_token'],
            $csrfToken
        )
    ){
        $errorMessage =
            'Invalid request. Please refresh and try again.';

    }elseif(
        $name === ''
        ||
        $email === ''
        ||
        $subject === ''
        ||
        $messageText === ''
    ){
        $errorMessage =
            'Please complete all fields.';

    }elseif(strlen($name) < 2){

        $errorMessage =
            'Please enter a valid name.';

    }elseif(
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ){
        $errorMessage =
            'Please enter a valid email address.';

    }elseif(strlen($messageText) < 10){

        $errorMessage =
            'Message must contain at least 10 characters.';

    }else{

        try{

            $insertStatement = $db->prepare("
                INSERT INTO contact_messages
                (
                    user_id,
                    name,
                    email,
                    subject,
                    message
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $insertStatement->execute([
                $_SESSION['user_id'] ?? null,
                $name,
                $email,
                $subject,
                $messageText
            ]);

            $successMessage =
                'Your message was sent successfully.';

            $subject = '';
            $messageText = '';

            $_SESSION['contact_csrf_token'] =
                bin2hex(random_bytes(32));

        }catch(PDOException $exception){

            $errorMessage =
                'Unable to send your message right now.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';

?>
<main>

    <section class="contact-hero">

        <div class="container">

            <span class="hero-badge">

                <i class="fa-solid fa-envelope"></i>

                Contact StepStyle

            </span>

            <h1>
                We Are Here to Help
            </h1>

            <p>
                Have a question about products, orders or your
                account? Send us a message and our team will
                assist you.
            </p>

        </div>

    </section>

    <section class="contact-section">

        <div class="container">

            <div class="contact-grid">

                <div class="contact-info-card">

                    <h2>Contact Information</h2>

                    <div class="contact-info-item">

                        <i class="fa-solid fa-envelope"></i>

                        <div>

                            <h4>Email</h4>

                            <a href="mailto:support@stepstyle.com">
                                support@stepstyle.com
                            </a>

                        </div>

                    </div>

                    <div class="contact-info-item">

                    <i class="fa-solid fa-phone"></i>

                        <div>

                            <h4>Phone</h4>

                            <p>
                                99XXXXXX00
                            </p>

                        </div>

                    </div>

                    <div class="contact-info-item">

                        <i class="fa-solid fa-location-dot"></i>

                        <div>

                            <h4>Location</h4>

                            <p>
                                Mumbai, Maharashtra, India
                            </p>

                        </div>

                    </div>

                    <div class="contact-info-item">

                        <i class="fa-solid fa-clock"></i>

                        <div>

                            <h4>Support Hours</h4>

                            <p>
                                Monday to Saturday<br>
                                9:00 AM to 7:00 PM
                            </p>

                        </div>

                    </div>

                </div>

                <div class="contact-form-card">

                    <h2>Send Us a Message</h2>

                    <?php if($successMessage !== ''): ?>

                        <div class="success-message">

                            <i class="fa-solid fa-circle-check"></i>

                            <?= htmlspecialchars($successMessage) ?>

                        </div>

                    <?php endif; ?>

                    <?php if($errorMessage !== ''): ?>

                        <div class="error-message">

                            <i class="fa-solid fa-circle-exclamation"></i>

                            <?= htmlspecialchars($errorMessage) ?>

                        </div>

                    <?php endif; ?>
                                        <form
                        method="POST"
                        action=""
                        class="contact-form"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $_SESSION['contact_csrf_token']
                            ) ?>"
                        >

                        <div class="contact-form-grid">

                            <div class="form-group">

                                <label for="name">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="<?= htmlspecialchars($name) ?>"
                                    placeholder="Enter your name"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label for="email">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= htmlspecialchars($email) ?>"
                                    placeholder="Enter your email"
                                    required
                                >

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="subject">
                                Subject
                            </label>

                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                value="<?= htmlspecialchars($subject) ?>"
                                placeholder="What can we help you with?"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="message">
                                Message
                            </label>

                            <textarea
                                id="message"
                                name="message"
                                placeholder="Write your message here..."
                                required
                            ><?= htmlspecialchars($messageText) ?></textarea>

                        </div>

                        <button
                            type="submit"
                            class="btn contact-submit-button"
                        >

                            <i class="fa-solid fa-paper-plane"></i>

                            <span>Send Message</span>

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>