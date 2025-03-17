<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../Navbar/navbar.php';
require_once '../../logs/logger.inc.php';

if (!isset($_SESSION['user_id'])) {
    logUserActivity("'Guest'", "Unauthorized access attempt to Home Page");
    echo print_r($_SESSION);
    header("Location: ../../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    </div>
</body>
</html>
