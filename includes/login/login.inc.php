<?php

require_once '../../logs/logger.inc.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $pwd = $_POST["password"];
    $logUsername = "'Guest'";

    $env = parse_ini_file(__DIR__ . '/../../.env');
    $hcaptcha_secret = $env['CAPTCHA_SECRET'] ?? '';

    if (!isset($_POST['h-captcha-response']) || empty($_POST['h-captcha-response'])) {
        logUserActivity($logUsername, "Login failed due to missing CAPTCHA.");
        header("Location: ../../index.php?error=captcha_missing");
        exit();
    }
    
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
        logUserActivity($logUsername, "Login failed due to invalid CAPTCHA.");
        header("Location: ../../index.php?error=captcha_invalid");
        exit();
    }

    try {
        require_once '../db.inc.php';
        require_once 'login_model.inc.php';
        require_once 'login_contr.inc.php';

        $errors = [];

        if (strlen($username) > 30) { 
            $errors["username_length"] = "Username should be less than 30 characters";
        }

        if (strlen($pwd) > 100) { 
            $errors["password_length"] = "Password length should be less than 100 characters";
        }

        $pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,}$/';
        if(strlen($pwd) < 8 || !preg_match($pattern, $pwd) === 1)
        {
            $errors["errors_login"] = "The password should have atleast 8 characters,atleast 1 uppercase letter, atleast 1 lowercase letter, 1 digit,1 special character(@#$%^&*)";
        }
        require_once '../config_session.inc.php';
        if ($errors) {      
            $_SESSION["errors_login"] = $errors;
            logUserActivity($logUsername, "Login failed due to validation errors.");
            header("Location: ../../index.php");
            exit();
        }

        if (is_input_empty($username, $pwd)) {
            $errors["empty_input"] = "Fill in all the fields";
        }

        //sanitise username because the sanitized version was stored
        require_once '../contr_utils.inc.php';
        $username =  sanitize_input($username);
        $result = get_user($pdo, $username);


        if (is_username_invalid($result)) { 
            $errors["username_invalid"] = "This username doesn't exist";
        } else {
            $logUsername = htmlspecialchars($result["username"]);
        }

        if (!is_username_invalid($result) && !is_pwd_correct($pwd, $result["passwordhash"])) { 
            $errors["wrong_password"] = "The password is wrong";
        }

        require_once '../config_session.inc.php'; // To start session

        if ($errors) {      
            $_SESSION["errors_login"] = $errors;
            logUserActivity($logUsername, "Login failed: " . implode(", ", array_keys($errors)));
            header("Location: ../../index.php");
            exit();
        }

        session_regenerate_id(true); 

        $_SESSION["user_id"] = $result["id"];
        $_SESSION["username"] = $logUsername; 
        $_SESSION["last_regeneration"] = time();

        logUserActivity($logUsername, "Successfully logged in");

        header("Location: ../profile/profile.inc.php");
        die();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        logUserActivity($logUsername, "Login attempt failed due to database error.");
        die("Query failed: " . $e->getMessage());
    }
} else {
    header("Location: ../../index.php");
    die();
}
