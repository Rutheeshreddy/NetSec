<?php
declare(strict_types=1);
require_once '../config_session.inc.php';
require_once '../db.inc.php';
require_once 'profile_model.inc.php';
require_once 'profile_contr.inc.php';
require_once '../Navbar/navbar.php';
require_once '../../logs/logger.inc.php';

if (!isset($_GET["user_id"]) || !is_numeric($_GET["user_id"])) {
    $username = $_SESSION["username"] ?? "'Guest'";
    logUserActivity($username, "Attempting to access profile page of user that doesn't exist");
    die("Invalid user ID.");
}

$user_id = (int) $_GET["user_id"];
$user = get_user_profile($pdo, $user_id);

if (!is_valid_user($user)) {
    die("User not found.");
}

$username = sanitize_input($user["username"] ?? "", 50);
$email = sanitize_input($user["email"] ?? "", 320);
$bio = sanitize_input($user["biography"] ?? "", 500);

$rusername = $_SESSION['username'] ?? "'Guest'";
logUserActivity($rusername, "Visited Profile Page of $username");

if (!is_valid_email($email)) {
    die("Invalid email format.");
}

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
    <title><?= $username ?>'s Profile</title>
    <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="profile-card">
                <?php if ($profile_image): ?>
                    <img class="profile-pic" src="<?= $profile_image; ?>" alt="Profile Image">
                <?php else: ?>
                    <div class="profile-pic default-pic">?</div>
                <?php endif; ?>

                <h2><?= $username; ?></h2>
                <p><?= $email; ?></p>
                <p><strong>Bio:</strong> <?= nl2br($bio ?: "No bio available"); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
