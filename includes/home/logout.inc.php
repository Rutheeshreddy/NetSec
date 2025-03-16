<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? "'Guest'";

require_once '../../logs/logger.inc.php';
logUserActivity($username, "Logout called");

session_unset();
session_destroy();

header("Location: ../../index.php");
exit();
?>
