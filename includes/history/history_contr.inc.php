<?php

declare(strict_types=1);


require_once '../config_session.inc.php';
require_once 'history_model.inc.php';
require_once '../../logs/logger.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logUserActivity($_SESSION["username"] ?? "'Guest'", "Failed CSRF check for history page.");
        $_SESSION["errors_transfer"] = "Invalid CSRF token.";
        header("Location: ../../index.php");
        exit();
    }
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit();
}

function get_user_transactions(): array 
{
    global $pdo; 
    $userId = $_SESSION["user_id"];
    return get_transaction_history($pdo, $userId);
}
