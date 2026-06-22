<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$successMessage = '';
$errorMessage = '';

if (empty($_SESSION['settings_csrf_token'])) {
    $_SESSION['settings_csrf_token'] =
        bin2hex(random_bytes(32));
}

$userStatement = $db->prepare("
    SELECT id, password, account_status
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStatement->execute([$userId]);
$user = $userStatement->fetch(PDO::FETCH_ASSOC);

if (
    !$user ||
    ($user['account_status'] ?? 'active') !== 'active'
) {
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (
        !hash_equals(
            $_SESSION['settings_csrf_token'],
            $csrfToken
        )
    ) {
        $errorMessage =
            'Invalid request. Please refresh and try again.';

    } elseif ($action === 'change_password') {

        $currentPassword =
            $_POST['current_password'] ?? '';

        $newPassword =
            $_POST['new_password'] ?? '';

        $confirmPassword =
            $_POST['confirm_password'] ?? '';

        if (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === ''
        ) {
            $errorMessage =
                'Please complete all password fields.';

        } elseif (
            !password_verify(
                $currentPassword,
                $user['password']
            )
        ) {
            $errorMessage =
                'Your current password is incorrect.';

        } elseif (strlen($newPassword) < 8) {
            $errorMessage =
                'New password must contain at least 8 characters.';

        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage =
                'New passwords do not match.';
        }
                if ($errorMessage === '') {

            try {

                $hashedPassword = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                $updateStatement = $db->prepare("
                    UPDATE users
                    SET password = ?
                    WHERE id = ?
                    AND account_status = 'active'
                ");

                $updateStatement->execute([
                    $hashedPassword,
                    $userId
                ]);

                $user['password'] = $hashedPassword;

                $successMessage =
                    'Password changed successfully.';

                $_SESSION['settings_csrf_token'] =
                    bin2hex(random_bytes(32));

            } catch (PDOException $exception) {

                $errorMessage =
                    'Unable to change your password right now.';
            }
        }

    } else {

        $errorMessage =
            'Invalid settings request.';
    }
}

require_once __DIR__ . '/includes/header.php';

?>

<main class="settings-page">

    <section class="settings-header-section">

        <div class="container">

            <span class="hero-badge">

                <i class="fa-solid fa-gear"></i>

                Account Settings

            </span>

            <h1>Security Settings</h1>

            <p>
                Update your password and keep your
                StepStyle account protected.
            </p>

        </div>

    </section>

    <section class="settings-content-section">

        <div class="container">

            <div class="settings-card">

                <?php if ($successMessage !== ''): ?>

                    <div class="success-message" role="alert">

                        <i class="fa-solid fa-circle-check"></i>

                        <?= htmlspecialchars($successMessage) ?>

                    </div>

                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>

                    <div class="error-message" role="alert">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars($errorMessage) ?>

                    </div>

                <?php endif; ?>

                <div class="settings-card-heading">

                    <div class="settings-icon">

                        <i class="fa-solid fa-lock"></i>

                    </div>

                    <div>

                        <h2>Change Password</h2>

                        <p>
                            Use a strong password with at least
                            8 characters.
                        </p>

                    </div>

                </div>
                                <form
                    method="POST"
                    class="settings-form"
                    data-prevent-double-submit
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION['settings_csrf_token']
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="change_password"
                    >

                    <div class="form-group">

                        <label for="currentPassword">
                            Current Password
                        </label>

                        <div class="input-icon-wrapper">

                            <i class="fa-solid fa-key"></i>

                            <input
                                type="password"
                                id="currentPassword"
                                name="current_password"
                                placeholder="Enter current password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-password-target="currentPassword"
                                aria-label="Show current password"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </button>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="newPassword">
                            New Password
                        </label>

                        <div class="input-icon-wrapper">

                            <i class="fa-solid fa-lock"></i>

                            <input
                                type="password"
                                id="newPassword"
                                name="new_password"
                                placeholder="Minimum 8 characters"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-password-target="newPassword"
                                aria-label="Show new password"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </button>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="confirmPassword">
                            Confirm New Password
                        </label>

                        <div class="input-icon-wrapper">

                            <i class="fa-solid fa-shield-halved"></i>

                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirm_password"
                                placeholder="Enter new password again"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-password-target="confirmPassword"
                                aria-label="Show confirmed password"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </button>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn settings-save-button"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Change Password
                    </button>

                </form>

            </div>

        </div>

    </section>

</main>

<?php

require_once __DIR__ . '/includes/footer.php';

?>