<?php

declare(strict_types=1);
require_once '../config_session.inc.php';
require_once '../db.inc.php';
require_once 'profile_model.inc.php';
require_once 'profile_contr.inc.php';
require_once '../../logs/logger.inc.php';

if (!isset($_SESSION["user_id"])) {
    logUserActivity("'Guest'", "Unauthorized access attempt to profile page");
    header("Location: ../../index.php");
    exit();
}

$user = get_user_profile($pdo, $_SESSION["user_id"]);

if (!is_valid_user($user)) {
    die("User not found.");
}

$username = sanitize_input($user["username"] ?? "", 50);
$email = sanitize_input($user["email"] ?? "", 320);
$bio = sanitize_input($user["biography"] ?? "", 500);

logUserActivity($username, "Visited Profile Page");

if (!is_valid_email($email)) {
    die("Invalid email format.");
}

require_once '../Navbar/navbar.php';
$profile_image = $user["profileimagepath"] ?? null;
$upload_dir = "../../uploads/";

if ($profile_image && strpos($profile_image, $upload_dir) === 0 && !preg_match('/\.\.\//', substr($profile_image, strlen($upload_dir)))) {
    $profile_image = htmlspecialchars($profile_image, ENT_QUOTES, 'UTF-8');
} else {
    $profile_image = null;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../../css/main.css">
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        function setupCharCounter(inputId, counterId, maxChars) {
            const inputField = document.getElementById(inputId);
            const charCounter = document.getElementById(counterId);

            function updateCounter() {
                const remaining = maxChars - inputField.value.length;
                charCounter.textContent = `${remaining} characters left`;
                charCounter.classList.toggle("warning", remaining <= 10);
            }

            inputField.addEventListener("input", updateCounter);
            updateCounter();
        }

        setupCharCounter("email", "emailCount", 320);
        setupCharCounter("bio", "bioCount", 500);
        setupCharCounter("new_password", "passwordCount", 100);

        document.getElementById("profile_image").addEventListener("change", function () {
            const file = this.files[0];
            const maxSize = 2 * 1024 * 1024; 

            if (file && file.size > maxSize) {
                alert("File size exceeds 2MB. Please upload a smaller file.");
                this.value = ""; 
            }
        });
    });
    </script>

</head>
<body>
    <?php if (isset($_SESSION["profile_update_error"])): ?>
        <div class="error">
            <?= htmlspecialchars($_SESSION["profile_update_error"], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php unset($_SESSION["profile_update_error"]); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION["profile_update_success"])): ?>
        <div class="success">
            <?= htmlspecialchars($_SESSION["profile_update_success"], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php unset($_SESSION["profile_update_success"]); ?>
    <?php endif; ?>

    <div class="container">
        <?php include 'profile_view.inc.php'; ?>

        <div class="form-container">
            <h3>Update Profile</h3>
            <form action="profile_contr.inc.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" placeholder="Enter your email" maxlength="320" required>
                <p id="emailCount" class="char-counter"></p>

                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" placeholder="Enter your biography" maxlength="500"><?= htmlspecialchars($bio); ?></textarea>
                <p id="bioCount" class="char-counter"></p>

                <label for="profile_image">Profile Image (Max: 2MB):</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png">

                <button type="submit">Update Profile</button>
            </form>

            <h3>Change Password</h3>
            <form action="profile_contr.inc.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>

                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required maxlength="100">
                <p id="passwordCount" class="char-counter"></p>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>

                <button type="submit">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>
