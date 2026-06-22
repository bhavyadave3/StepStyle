<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$successMessage = '';
$email = '';

if (
    isset($_GET['account_deleted']) &&
    $_GET['account_deleted'] === '1'
) {
    $successMessage =
        'Your account has been deleted successfully.';
}

if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] =
        bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (
        !hash_equals(
            $_SESSION['login_csrf_token'],
            $csrfToken
        )
    ) {
        $errorMessage =
            'Invalid request. Please refresh and try again.';

    } elseif ($email === '' || $password === '') {

        $errorMessage =
            'Please enter your email and password.';

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errorMessage =
            'Please enter a valid email address.';

    } else {

        try {

            $statement = $db->prepare("
                SELECT
                    id,
                    name,
                    email,
                    password,
                    role,
                    status,
                    account_status
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $statement->execute([$email]);

            $user = $statement->fetch(
                PDO::FETCH_ASSOC
            );
                        if (
                !$user ||
                !password_verify(
                    $password,
                    $user['password']
                )
            ) {
                $errorMessage =
                    'Incorrect email or password.';

            } elseif (
                ($user['account_status'] ?? 'active')
                === 'deleted'
            ) {
                $errorMessage =
                    'This account has been deleted.';

            } elseif (
                ($user['status'] ?? 'active')
                !== 'active'
            ) {
                $errorMessage =
                    'Your account has been blocked by the administrator.';

            } else {

                session_regenerate_id(true);

                $_SESSION['user_id'] =
                    (int) $user['id'];

                $_SESSION['user_name'] =
                    $user['name'];

                $_SESSION['user_email'] =
                    $user['email'];

                $_SESSION['user_role'] =
                    $user['role'] ?? 'user';

                unset(
                    $_SESSION['login_csrf_token']
                );

                header('Location: index.php');
                exit;
            }

        } catch (PDOException $exception) {

            $errorMessage =
                'Unable to log in right now. Please try again.';
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
                    <i class="fa-solid fa-user"></i>
                </span>

                <h1>Login</h1>

                <p>Welcome back to StepStyle</p>

            </div>

            <?php if ($successMessage !== ''): ?>

                <div class="success-message" role="alert">

                    <i class="fa-solid fa-circle-check"></i>

                    <span>
                        <?= htmlspecialchars($successMessage) ?>
                    </span>

                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>

                <div class="error-message" role="alert">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($errorMessage) ?>
                    </span>

                </div>

            <?php endif; ?>

            <form
                method="POST"
                action=""
                class="auth-form login-form"
                data-prevent-double-submit
                novalidate
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['login_csrf_token']
                    ) ?>"
                >

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
                            placeholder="Enter your password"
                            autocomplete="current-password"
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

                <button
                    type="submit"
                    class="btn auth-submit-button"
                >
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Login</span>
                </button>

            </form>

            <p class="auth-footer">

                Don&apos;t have an account?

                <a
                    href="<?= SITE_URL ?>/register.php"
                    class="auth-link"
                >
                    Register
                </a>

            </p>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>