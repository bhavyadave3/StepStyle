<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$baseUrl = rtrim(SITE_URL, '/');

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$name = '';
$email = '';
$errorMessage = '';

if (empty($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] =
        bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(
        trim($_POST['email'] ?? '')
    );
    $password = $_POST['password'] ?? '';
    $confirmPassword =
        $_POST['confirm_password'] ?? '';

    if (
        !hash_equals(
            $_SESSION['register_csrf_token'],
            $csrfToken
        )
    ) {
        $errorMessage =
            'Invalid request. Please refresh and try again.';

    } elseif (
        $name === '' ||
        $email === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $errorMessage =
            'Please fill in all fields.';

    } elseif (strlen($name) < 2) {

        $errorMessage =
            'Name must contain at least 2 characters.';

    } elseif (strlen($name) > 100) {

        $errorMessage =
            'Name must not exceed 100 characters.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $errorMessage =
            'Please enter a valid email address.';

    } elseif (strlen($password) < 8) {

        $errorMessage =
            'Password must contain at least 8 characters.';

    } elseif ($password !== $confirmPassword) {

        $errorMessage =
            'Passwords do not match.';

    } else {

        try {

            $emailCheckStatement = $db->prepare("
                SELECT
                    id,
                    account_status
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $emailCheckStatement->execute([$email]);

            $existingUser = $emailCheckStatement->fetch(
                PDO::FETCH_ASSOC
            );
                        if ($existingUser) {

                if (
                    ($existingUser['account_status'] ?? 'active')
                    === 'deleted'
                ) {
                    $errorMessage =
                        'An account with this email was previously deleted.';
                } else {
                    $errorMessage =
                        'An account with this email already exists.';
                }

            } else {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insertStatement = $db->prepare("
                    INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role,
                        account_status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'user',
                        'active'
                    )
                ");

                $insertStatement->execute([
                    $name,
                    $email,
                    $hashedPassword
                ]);

                $newUserId =
                    (int) $db->lastInsertId();

                session_regenerate_id(true);

                $_SESSION['user_id'] =
                    $newUserId;

                $_SESSION['user_name'] =
                    $name;

                $_SESSION['user_email'] =
                    $email;

                $_SESSION['user_role'] =
                    'user';

                unset(
                    $_SESSION['register_csrf_token']
                );

                header('Location: index.php');
                exit;
            }

        } catch (PDOException $exception) {

            $errorMessage =
                'Unable to create your account right now. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<main>

    <section class="auth-section">

        <div class="auth-container">

            <div class="auth-heading">

                <span class="auth-icon">
                    <i class="fa-solid fa-user-plus"></i>
                </span>

                <h1>Create Account</h1>

                <p>
                    Join StepStyle and start shopping
                </p>

            </div>
                        <?php if ($errorMessage !== ''): ?>

                <div
                    class="error-message"
                    role="alert"
                >
                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars(
                            $errorMessage
                        ) ?>
                    </span>
                </div>

            <?php endif; ?>

            <form
                method="POST"
                action=""
                class="auth-form register-form"
                data-prevent-double-submit
                novalidate
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['register_csrf_token']
                    ) ?>"
                >

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="input-icon-wrapper">

                        <i class="fa-solid fa-user"></i>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($name) ?>"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            maxlength="100"
                            required
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-icon-wrapper">

                        <i class="fa-solid fa-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="Enter your email"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>
                                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-icon-wrapper">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimum 8 characters"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-target="password"
                            aria-label="Show password"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>

                    </div>

                </div>

                <div class="form-group">

                    <label for="confirmPassword">
                        Confirm Password
                    </label>

                    <div class="input-icon-wrapper">

                        <i class="fa-solid fa-shield-halved"></i>

                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirm_password"
                            placeholder="Enter password again"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-target="confirmPassword"
                            aria-label="Show password"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>

                    </div>

                </div>

                <button
                    type="submit"
                    class="btn auth-submit-button"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Create Account</span>
                </button>

            </form>

            <p class="auth-footer">

                Already have an account?

                <a
                    href="<?= htmlspecialchars(
                        $baseUrl . '/login.php'
                    ) ?>"
                    class="auth-link"
                >
                    Login
                </a>

            </p>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>