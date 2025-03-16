<?php

declare(strict_types=1);

require_once '../db.inc.php';
require_once 'sendMoney_model.inc.php';
require_once '../config_session.inc.php';
require_once '../../logs/logger.inc.php';

// CSRF Protection
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) 
{
    $_SESSION["errors_transfer"] = "Invalid CSRF token.";
    logUserActivity($_SESSION["username"] ?? "'Guest'", "Failed CSRF check during money transfer");
    header("Location: ../../index.inc.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["transfer"])) 
{
    if (!isset($_SESSION["user_id"])) 
    {
        $_SESSION["errors_transfer"] = "You must be logged in.";
        logUserActivity("'Guest'", "Attempted transfer without login");
        header("Location: ../../index.inc.php");
        exit();
    }    

    $senderUsername = htmlspecialchars(trim($_SESSION["username"]), ENT_QUOTES, 'UTF-8');
    $min_interval = 20; 

    if (isset($_SESSION["last_transfer_time"]) && (time() - $_SESSION["last_transfer_time"]) < $min_interval) {
        $_SESSION["errors_transfer"] = "Too many transfers! Try again later.";
        logUserActivity($senderUsername, "Rate limit exceeded for money transfer");
        header("Location: sendMoney.inc.php");
        exit();
    }

    $_SESSION["last_transfer_time"] = time();

    require_once '../contr_utils.inc.php';
    $receiverUsername = sanitize_input(trim($_POST["username"]), ENT_QUOTES, 'UTF-8');
    $comment = sanitize_output($_POST["comment"] ?? "", ENT_QUOTES, 'UTF-8');
    $amount = (float)$_POST["amount"];

    // Validate receiver username
    if (!isset($_POST["username"]) || empty($receiverUsername)) {
        $_SESSION["errors_transfer"] = "Please enter a valid username.";
        logUserActivity($senderUsername, "Entered invalid recipient username");
        header("Location: sendMoney.inc.php");
        exit();
    }

    if (strlen($receiverUsername) > 30) {
        $_SESSION["errors_transfer"] = "Username should be less than 30 characters.";
        logUserActivity($senderUsername, "Entered username exceeds 30 characters.");
        header("Location: sendMoney.inc.php");
        exit();
    }

    // Validate amount, also check amount length
    if (!isset($_POST["amount"]) || !is_numeric($_POST["amount"]) || (float)$_POST["amount"] <= 0) {
        $_SESSION["errors_transfer"] = "Please enter a valid amount.";
        logUserActivity($senderUsername, "Entered invalid transfer amount");
        header("Location: sendMoney.inc.php");
        exit();
    }
    //check identity type
    if ($_POST['search_type'] != "username" && $_POST['search_type']!="userID") {
        $_SESSION["errors_transfer"] = "Please send a valid identity type(username or userID).";
        logUserActivity($senderUsername, "Entered invalid user identity type");
        header("Location: sendMoney.inc.php");
        exit();
    }
    //check comment length
    if(!empty($comment) && strlen($comment) > 300)
    {
        $_SESSION["errors_transfer"] = "Comment too long, should be less than 300 characters.";
        logUserActivity($senderUsername, "Entered comment is larger than max size.");
        header("Location: sendMoney.inc.php");
        exit();
    }
    $senderId = $_SESSION["user_id"];

    if (transfer_money($pdo, $senderId, $receiverUsername, $amount,$_POST['search_type'],$comment)) {
        $_SESSION["transfer_success"] = "Transfer successful!";
        $susername = $_SESSION["username"];
        if ($_POST['search_type'] == "userID") {
            $receiverUsername = get_username_by_id($pdo, $receiverUsername);
        }
        logUserActivity($senderUsername, "Transferred $amount from $susername to $receiverUsername");
    } else {
        $_SESSION["errors_transfer"] = "Transfer failed: " . ($_SESSION["errors_transfer"] ?? "Unknown error.");
        logUserActivity($senderUsername, "Failed transfer of $amount from $susername to $receiverUsername");
    }

    header("Location: sendMoney.inc.php");
    exit();
}
else 
{
    header("Location: ../../index.php");
    die();
}

function user_balance(string $userID)
{
     return get_user_balance($pdo,$userID);
}