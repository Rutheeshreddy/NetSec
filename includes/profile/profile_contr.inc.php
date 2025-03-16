<?php
require_once '../config_session.inc.php';
require_once '../db.inc.php';
require_once 'profile_model.inc.php';
require_once '../../logs/logger.inc.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit();
}

function is_valid_user($user) {
    return !empty($user) && is_array($user);
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logUserActivity($_SESSION["username"] ?? "Guest", "Failed CSRF check for profile update.");
        $_SESSION["profile_update_error"] = "Invalid CSRF token.";
        header("Location: profile.inc.php");
        exit();
    }

    if (!isset($_SESSION['profile_update_attempts'])) {
        $_SESSION['profile_update_attempts'] = 0;
        $_SESSION['profile_update_time'] = time();
    }
    
    if (time() - $_SESSION['profile_update_time'] > 60) {
        $_SESSION['profile_update_attempts'] = 0;
        $_SESSION['profile_update_time'] = time();
    }
    
    if ($_SESSION['profile_update_attempts'] >= 5) {
        $_SESSION["profile_update_error"] = "Too many profile updates. Try again later.";
        logUserActivity($_SESSION["username"], "Rate limited: Too many profile updates");
        header("Location: profile.inc.php");
        exit();
    }
    
    $_SESSION['profile_update_attempts']++;

    $email = sanitize_input($_POST["email"] ?? "", 320);
    $bio = sanitize_input($_POST["bio"] ?? "", 500);
    if (!empty($email) && update_user_profile($pdo, $user_id, $email, $bio)) {
        $_SESSION["profile_update_success"] = "Profile updated successfully!";
        logUserActivity($_SESSION["username"], "Profile updated successfully!");
    } 
    else if(!empty($email)){
        $_SESSION["profile_update_error"] = "Failed to update profile. Email already Exists";
        logUserActivity($_SESSION["username"], "Failed to update profile. Email already Exists");
        header("Location: profile.inc.php");
        exit();
    }

    if (!empty($_POST["current_password"]) && !empty($_POST["new_password"]) && !empty($_POST["confirm_password"])) {
        $current_password = sanitize_input($_POST["current_password"], 100);
        $new_password = sanitize_input($_POST["new_password"], 100);
        $confirm_password = sanitize_input($_POST["confirm_password"], 100);
        
        if ($new_password !== $confirm_password) {
            $_SESSION["profile_update_error"] = "New passwords do not match.";
            logUserActivity($_SESSION["username"], "Failed Change pwd attempt: New passwords do not match.");
            header("Location: profile.inc.php");
            exit();
        }
    
        if (!isset($_SESSION["failed_attempts"])) {
            $_SESSION["failed_attempts"] = 0;
        }
        
        if ($_SESSION["failed_attempts"] >= 5) {
            logUserActivity($_SESSION["username"], "Failed to change pwd multiple times");
            // Force logout after 5 failures
            session_destroy();
            header("Location: ../login/login.inc.php?error=too_many_attempts");
            exit();
        }
        
        $stored_hash = get_user_password_hash($pdo, $user_id);
        $is_valid = password_verify($current_password, $stored_hash);
        
        if (!$stored_hash || !$is_valid) {
            $_SESSION["failed_attempts"] += 1;
            $_SESSION["profile_update_error"] = "Incorrect current password.";
            logUserActivity($_SESSION["username"], "Failed Change pwd attempt: Incorrect current password.");
            header("Location: profile.inc.php");
            exit();
        }
        //checking password requirements
        $pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).+$/';
        if(strlen($new_password) < 8 || !preg_match($pattern, $new_password) === 1)
        {
            $errors["profile_update_error"] = "The password should have atleast 8 characters,atleast 1 uppercase letter, atleast 1 lowercase letter, 1 digit,1 special character(@#$%^&*)";
        }
        if (update_user_password($pdo, $user_id, $new_password)) {
            $_SESSION["failed_attempts"] = 0; // Reset only after success
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION["profile_update_success"] = "Password updated successfully!";
            logUserActivity($_SESSION["username"], "Password updated successfully!");
        }
        else {
            $_SESSION["profile_update_error"] = "Failed to update password.";
            logUserActivity($_SESSION["username"], "Failed to update password.");
        }
    }

    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
        $target_dir = "../../uploads/";
        $allowed_extensions = ["jpg", "jpeg", "png"];
        $allowed_mime_types = ["image/jpeg", "image/png"];
        $max_file_size = 2 * 1024 * 1024; // 2MB
    
        $file_info = pathinfo($_FILES["profile_image"]["name"]);
        $file_extension = strtolower($file_info["extension"]);
        $file_size = $_FILES["profile_image"]["size"];
    
        // Secure MIME check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES["profile_image"]["tmp_name"]);
        finfo_close($finfo);
    
        // Prevent excessive upload attempts
        if (!isset($_SESSION['upload_attempts'])) {
            $_SESSION['upload_attempts'] = 0;
            $_SESSION['upload_time'] = time();
        }
    
        if (time() - $_SESSION['upload_time'] < 60) { // Within 1 minute
            if ($_SESSION['upload_attempts'] >= 3) {
                $_SESSION["profile_update_error"] = "Too many upload attempts. Try again later.";
                logUserActivity($_SESSION["username"], "Failed to update Profile Pic: Too many upload attempts");
                header("Location: profile.inc.php");
                exit();
            }
        } else {
            $_SESSION['upload_attempts'] = 0; // Reset only if 1 minute has passed
        }
        
        $_SESSION['upload_attempts']++;
    
        // Validate extension, MIME type, and file size
        if (!in_array($file_extension, $allowed_extensions) || 
            !in_array($mime_type, $allowed_mime_types) || 
            $file_size > $max_file_size) {
            $_SESSION["profile_update_error"] = "Invalid image file.";
            logUserActivity($_SESSION["username"], "Failed to update Profile Pic: Invalid image file");
            header("Location: profile.inc.php");
            exit();
        }
    
        // Generate a secure, unique filename
        $new_filename = "profile_" . $user_id . "_" . bin2hex(random_bytes(16)) . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
    
        // Move uploaded file securely
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $old_image = get_old_profile_image($pdo, $user_id);
    
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }
    
            update_profile_image($pdo, $user_id, $target_file);
            $_SESSION["profile_update_success"] = "Profile image updated!";
            logUserActivity($_SESSION["username"], "Profile image updated!");
        } else {
            $_SESSION["profile_update_error"] = "Failed to upload image.";
            logUserActivity($_SESSION["username"], "Failed to upload image.");
        }
    }
    

    header("Location: profile.inc.php");
    exit();
}
?>
