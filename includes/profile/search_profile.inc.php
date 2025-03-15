<?php

declare(strict_types=1);

require_once '../config_session.inc.php';
$user_id = $_SESSION['user_id'] ?? null;
require_once '../Navbar/navbar.php';
require_once '../../logs/logger.inc.php';

if (!isset($_SESSION["user_id"])) {
    logUserActivity("'Guest'", "Unauthorized attempt to Profile Search page.");
    header("Location: ../../index.php");
    die();
}

logUserActivity($_SESSION["username"], "Accessed Profile Search page");
require_once 'search_profile_view.inc.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search User</title>
    <link rel="stylesheet" href="../../css/main.css">
</head>
<body>

    <?php display_profile_search_form(); ?>

</body>
</html>
