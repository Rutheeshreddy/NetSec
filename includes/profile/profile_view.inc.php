<?php

declare(strict_types=1);
require_once '../config_session.inc.php';
require_once '../db.inc.php';
require_once 'profile_model.inc.php';

$user_id = $_SESSION["user_id"];
$user = get_user_profile($pdo, $user_id);

$username = sanitize_input($user["username"] ?? "", 50);
$email = sanitize_input($user["email"] ?? "", 100);
$bio = sanitize_input($user["biography"] ?? "", 500);

$profile_image = $user["profileimagepath"] ?? null;
$upload_dir = "../../uploads/";

if ($profile_image && strpos($profile_image, $upload_dir) === 0 && !preg_match('/\.\.\//', substr($profile_image, strlen($upload_dir)))) {
    $profile_image = htmlspecialchars($profile_image);
} else {
    $profile_image = null;
}

?>

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
