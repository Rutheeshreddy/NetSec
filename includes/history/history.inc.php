<?php

declare(strict_types=1);
require_once '../config_session.inc.php';
require_once 'history_view.inc.php'; //
require_once 'history_contr.inc.php'; //
$user_id = $_SESSION['user_id'] ?? null;
require_once '../Navbar/navbar.php';
require_once '../../logs/logger.inc.php';

if(!isset($_SESSION["user_id"])) {
    logUserActivity("'Guest'", "Unauthorized access attempt to transaction history");
    header("Location: ../../index.php");
    die();
} 
logUserActivity($_SESSION["username"], "Accessed transaction history");

$transactions = get_user_transactions();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Transfer</title>
    <link rel="stylesheet" href="../../css/main.css">
</head>
<body>

    <?php  display_transaction_history($transactions, $_SESSION["user_id"]) ?> 

</body>
</html>
