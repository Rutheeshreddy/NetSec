<?php

require_once '../../logs/logger.inc.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $logUsername = "'Guest'"; // Always log as "'Guest'" until signup succeeds

    // hCaptcha Secret Key
    $hcaptcha_secret = "ES_16939cff4011414e8b822d50c4810b89";

    if (!isset($_POST['h-captcha-response']) || empty($_POST['h-captcha-response'])) {
        logUserActivity($logUsername, "Signup failed due to missing CAPTCHA.");
        header("Location: ../../index.php?error=captcha_missing");
        exit();
    }

    // Verify hCaptcha
    $captcha_response = $_POST['h-captcha-response'];
    $verify_url = "https://api.hcaptcha.com/siteverify";
    
    $data = [
        "secret" => $hcaptcha_secret,
        "response" => $captcha_response
    ];
    
    $options = [
        "http" => [
            "header" => "Content-Type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $verify_response = file_get_contents($verify_url, false, $context);
    $captcha_success = json_decode($verify_response, true);

    if (!$captcha_success["success"]) {
        logUserActivity($logUsername, "Signup failed due to invalid CAPTCHA.");
        header("Location: ../../index.php?error=captcha_invalid");
        exit();
    }
    
    $username = $_POST["username"];
    $pwd = $_POST["password"];
    $email = $_POST["email"];

    try {
        require_once '../db.inc.php';
        require_once 'signup_model.inc.php';
        require_once 'signup_contr.inc.php';

        $errors = [];

        // Error handling
        if (strlen($username) > 30) { 
            $errors["username_length"] = "Username should be less than 30 characters";
        }

       if (strlen($pwd) > 100) { 
            $errors["password_length"] = "Password length should be less than 100 characters";
        }

        if (strlen($email) > 320) { 
            $errors["email_length"] = "Email length should be less than 320 characters";
        }

        if ($errors) {
            require_once '../config_session.inc.php';
            $_SESSION["errors_signup"] = $errors;

            logUserActivity($logUsername, "Signup failed due to validation errors.");
            header("Location: ../../index.php");
            exit();
        }

        if (is_input_empty($username, $pwd, $email)) {
            $errors["empty_input"] = "Fill in all the fields";
        }

        if (is_email_valid($email)) {
            $errors["invalid_email"] = "Enter a valid email address";
        }

        if (is_username_taken($pdo, $username)) { 
            $errors["username_exists"] = "This username is taken";
        }

        if (is_email_taken($pdo, $email)) { 
            $errors["email_exists"] = "This email is already registered";
        }

        if ($errors) {      
            require_once '../config_session.inc.php';
            $_SESSION["errors_signup"] = $errors;

            $signup_data = [
                "username" => $username,
                "email" => $email
            ];
            $_SESSION["signup_data"] = $signup_data;

            logUserActivity($logUsername, "Signup failed: " . implode(", ", array_keys($errors)));
            header("Location: ../../index.php");
            exit();
        }
          
        // should sanitise usernname, before storing, email is already sannitise? doubt
        require_once '../contr_utils.inc.php';
        $username =  sanitize_input($username);
        create_user($pdo,$username,$pwd,$email);
        logUserActivity(htmlspecialchars($username), "Successfully signed up");

        header("Location: ../../index.php?signup=success");
        exit();

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        logUserActivity($logUsername, "Signup attempt failed due to database error.");
        die("Query failed: " . $e->getMessage());
    }
} else {
    header("Location: ../../index.php");
    die();
}
