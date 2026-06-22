<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';

if (empty($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] =
        bin2hex(random_bytes(32));
}

function loadProfileUser(PDO $db, int $userId): array|false
{
    $statement = $db->prepare("
        SELECT
            id,
            name,
            email,
            phone,
            gender,
            date_of_birth,
            address,
            city,
            state,
            postal_code,
            profile_image,
            account_status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $statement->execute([$userId]);

    return $statement->fetch(PDO::FETCH_ASSOC);
}

$user = loadProfileUser($db, $userId);

if (
    !$user ||
    ($user['account_status'] ?? 'active') === 'deleted'
) {
    session_unset();
    session_destroy();

    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? 'update_profile';

    if (
        !hash_equals(
            $_SESSION['profile_csrf_token'],
            $csrfToken
        )
    ) {
        $errorMessage =
            'Invalid request. Please refresh and try again.';
    } elseif ($action === 'delete_account') {

        $confirmation =
            $_POST['confirm_delete'] ?? '';

        if ($confirmation !== 'yes') {
            $errorMessage =
                'Please confirm that you want to delete your account.';
        } else {

            try {
                $db->beginTransaction();

                $deleteStatement = $db->prepare("
                    UPDATE users
                    SET
                        account_status = 'deleted',
                        deleted_at = NOW()
                    WHERE id = ?
                ");

                $deleteStatement->execute([$userId]);
                                $db->commit();

                session_unset();
                session_destroy();

                header(
                    'Location: ' .
                    SITE_URL .
                    '/login.php?account_deleted=1'
                );
                exit;

            } catch (PDOException $exception) {

                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $errorMessage =
                    'Unable to delete your account right now.';
            }
        }

    } elseif ($action === 'update_profile') {

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dateOfBirth =
            trim($_POST['date_of_birth'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $postalCode =
            trim($_POST['postal_code'] ?? '');

        if (strlen($name) < 2) {

            $errorMessage =
                'Please enter a valid name.';

        } elseif (
            $phone !== '' &&
            !preg_match(
                '/^[0-9+\-\s]{7,20}$/',
                $phone
            )
        ) {

            $errorMessage =
                'Please enter a valid phone number.';

        } elseif (
            $gender !== '' &&
            !in_array(
                $gender,
                ['Male', 'Female', 'Other'],
                true
            )
        ) {

            $errorMessage =
                'Please select a valid gender.';

        } elseif ($dateOfBirth !== '') {

            $dateObject =
                DateTime::createFromFormat(
                    'Y-m-d',
                    $dateOfBirth
                );

            $today = new DateTime('today');

            if (
                !$dateObject ||
                $dateObject->format('Y-m-d') !==
                    $dateOfBirth ||
                $dateObject > $today
            ) {
                $errorMessage =
                    'Please enter a valid date of birth.';
            }
        }

        if (
            $errorMessage === '' &&
            strlen($postalCode) > 20
        ) {
            $errorMessage =
                'Postal code is too long.';
        }

        $profileImage =
            $user['profile_image'] ?? null;
                    if (
            $errorMessage === '' &&
            isset($_FILES['profile_image']) &&
            $_FILES['profile_image']['error'] !==
                UPLOAD_ERR_NO_FILE
        ) {
            $uploadedFile = $_FILES['profile_image'];

            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {

                $errorMessage =
                    'Unable to upload the profile image.';

            } elseif ($uploadedFile['size'] > 2097152) {

                $errorMessage =
                    'Profile image must be smaller than 2 MB.';

            } else {

                $fileInfo =
                    new finfo(FILEINFO_MIME_TYPE);

                $mimeType = $fileInfo->file(
                    $uploadedFile['tmp_name']
                );

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mimeType])) {

                    $errorMessage =
                        'Only JPG, PNG and WebP images are allowed.';

                } else {

                    $uploadDirectory =
                        __DIR__ .
                        '/assets/uploads/profiles/';

                    if (!is_dir($uploadDirectory)) {
                        mkdir(
                            $uploadDirectory,
                            0775,
                            true
                        );
                    }

                    $fileName =
                        'profile_' .
                        $userId .
                        '_' .
                        bin2hex(random_bytes(6)) .
                        '.' .
                        $allowedTypes[$mimeType];

                    if (
                        move_uploaded_file(
                            $uploadedFile['tmp_name'],
                            $uploadDirectory . $fileName
                        )
                    ) {
                        $profileImage =
                            'assets/uploads/profiles/' .
                            $fileName;
                    } else {
                        $errorMessage =
                            'Unable to save the profile image.';
                    }
                }
            }
        }

        if ($errorMessage === '') {

            try {

                $updateStatement = $db->prepare("
                    UPDATE users
                    SET
                        name = ?,
                        phone = ?,
                        gender = ?,
                        date_of_birth = ?,
                        address = ?,
                        city = ?,
                        state = ?,
                        postal_code = ?,
                        profile_image = ?
                    WHERE id = ?
                    AND account_status = 'active'
                ");

                $updateStatement->execute([
                    $name,
                    $phone !== '' ? $phone : null,
                    $gender !== '' ? $gender : null,
                    $dateOfBirth !== '' ?
                        $dateOfBirth : null,
                    $address !== '' ? $address : null,
                    $city !== '' ? $city : null,
                    $state !== '' ? $state : null,
                    $postalCode !== '' ?
                        $postalCode : null,
                    $profileImage,
                    $userId
                ]);

                $_SESSION['user_name'] = $name;

                $user = loadProfileUser(
                    $db,
                    $userId
                );

                $successMessage =
                    'Profile updated successfully.';

            } catch (PDOException $exception) {

                $errorMessage =
                    'Unable to update your profile.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<main class="profile-page">

    <section class="profile-header-section">

        <div class="container">

            <span class="hero-badge">
                <i class="fa-solid fa-user"></i>
                My Profile
            </span>

            <h1>Personal Information</h1>

            <p>
                Manage your account details and profile picture.
            </p>

        </div>

    </section>

    <section class="profile-content-section">

        <div class="container">

            <div class="profile-card">

                <?php if ($successMessage !== ''): ?>

                    <div class="success-message">
                        <i class="fa-solid fa-circle-check"></i>

                        <?= htmlspecialchars($successMessage) ?>
                    </div>

                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>

                    <div class="error-message">
                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars($errorMessage) ?>
                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="profile-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION['profile_csrf_token']
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="update_profile"
                    >

                    <div class="profile-image-section">

                        <div class="profile-image-preview">

                            <?php if (!empty($user['profile_image'])): ?>

                                <img
                                    id="profilePreview"
                                    src="<?= SITE_URL . '/' .
                                        htmlspecialchars(
                                            $user['profile_image']
                                        ) ?>"
                                    alt="Profile picture"
                                >

                            <?php else: ?>

                                <div
                                    class="profile-initial"
                                    id="profileInitial"
                                >
                                    <?= htmlspecialchars(
                                        strtoupper(
                                            substr(
                                                $user['name'],
                                                0,
                                                1
                                            )
                                        )
                                    ) ?>
                                </div>

                                <img
                                    id="profilePreview"
                                    src=""
                                    alt="Profile picture"
                                    hidden
                                >

                            <?php endif; ?>

                        </div>

                        <div class="profile-image-controls">

                            <h3>Profile Picture</h3>

                            <p>
                                JPG, PNG or WebP. Maximum 2 MB.
                            </p>

                            <label
                                for="profileImageInput"
                                class="btn profile-upload-button"
                            >
                                <i class="fa-solid fa-camera"></i>
                                Choose Image
                            </label>

                            <input
                                type="file"
                                id="profileImageInput"
                                name="profile_image"
                                accept=".jpg,.jpeg,.png,.webp"
                                hidden
                            >

                            <span id="profileFileName">
                                No image selected
                            </span>

                        </div>

                    </div>
                                        <div class="profile-form-grid">

                        <div class="form-group">
                            <label for="name">Full Name</label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= htmlspecialchars(
                                    $user['name']
                                ) ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>

                            <input
                                type="email"
                                id="email"
                                value="<?= htmlspecialchars(
                                    $user['email']
                                ) ?>"
                                disabled
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars(
                                    $user['phone'] ?? ''
                                ) ?>"
                                placeholder="Enter phone number"
                            >
                        </div>

                        <div class="form-group">
                            <label for="gender">
                                Gender
                                <span class="optional-label">
                                    (Optional)
                                </span>
                            </label>

                            <select id="gender" name="gender">

                                <option value="">
                                    Prefer not to specify
                                </option>

                                <?php foreach (
                                    ['Male', 'Female', 'Other']
                                    as $genderOption
                                ): ?>

                                    <option
                                        value="<?= $genderOption ?>"
                                        <?= (
                                            ($user['gender'] ?? '') ===
                                            $genderOption
                                        ) ? 'selected' : '' ?>
                                    >
                                        <?= $genderOption ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">
                                Date of Birth
                            </label>

                            <input
                                type="date"
                                id="date_of_birth"
                                name="date_of_birth"
                                max="<?= date('Y-m-d') ?>"
                                value="<?= htmlspecialchars(
                                    $user['date_of_birth'] ?? ''
                                ) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="postal_code">
                                Postal Code
                            </label>

                            <input
                                type="text"
                                id="postal_code"
                                name="postal_code"
                                value="<?= htmlspecialchars(
                                    $user['postal_code'] ?? ''
                                ) ?>"
                                placeholder="Enter postal code"
                            >
                        </div>

                        <div class="form-group">
                            <label for="city">City</label>

                            <input
                                type="text"
                                id="city"
                                name="city"
                                value="<?= htmlspecialchars(
                                    $user['city'] ?? ''
                                ) ?>"
                                placeholder="Enter city"
                            >
                        </div>

                        <div class="form-group">
                            <label for="state">State</label>

                            <input
                                type="text"
                                id="state"
                                name="state"
                                value="<?= htmlspecialchars(
                                    $user['state'] ?? ''
                                ) ?>"
                                placeholder="Enter state"
                            >
                        </div>

                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="Enter your address"
                        ><?= htmlspecialchars(
                            $user['address'] ?? ''
                        ) ?></textarea>
                    </div>

                    <button
                        type="submit"
                        class="btn profile-save-button"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>

                </form>

                <div class="delete-account-section">

                    <div class="delete-account-text">
                        <h3>Delete Account</h3>

                        <p>
                            Your account will be disabled and
                            marked as deleted in the admin panel.
                        </p>
                    </div>

                    <form
                        method="POST"
                        class="delete-account-form"
                        onsubmit="return confirm(
                            'Are you sure you want to delete your account?'
                        );"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $_SESSION['profile_csrf_token']
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="delete_account"
                        >

                        <input
                            type="hidden"
                            name="confirm_delete"
                            value="yes"
                        >

                        <button
                            type="submit"
                            class="delete-account-button"
                        >
                            <i class="fa-solid fa-trash"></i>
                            Delete Account
                        </button>

                    </form>

                </div>
                            </div>

        </div>

    </section>

</main>

<script>
const profileInput =
    document.getElementById('profileImageInput');

const profilePreview =
    document.getElementById('profilePreview');

const profileInitial =
    document.getElementById('profileInitial');

const profileFileName =
    document.getElementById('profileFileName');

if (profileInput) {

    profileInput.addEventListener('change', function () {

        const file = this.files[0];

        if (!file) {
            profileFileName.textContent =
                'No image selected';

            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {

            alert(
                'Please select a JPG, PNG or WebP image.'
            );

            this.value = '';

            profileFileName.textContent =
                'No image selected';

            return;
        }

        if (file.size > 2 * 1024 * 1024) {

            alert(
                'Profile image must be smaller than 2 MB.'
            );

            this.value = '';

            profileFileName.textContent =
                'No image selected';

            return;
        }

        profileFileName.textContent = file.name;

        const reader = new FileReader();

        reader.addEventListener('load', function (event) {

            profilePreview.src = event.target.result;
            profilePreview.hidden = false;

            if (profileInitial) {
                profileInitial.hidden = true;
            }
        });

        reader.readAsDataURL(file);
    });
}
</script>

<?php

require_once __DIR__ . '/includes/footer.php';

?>