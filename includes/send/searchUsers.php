<?php

declare(strict_types=1);
require_once '../db.inc.php';
require_once 'sendMoney_model.inc.php';
require_once '../../logs/logger.inc.php';

header("Content-Type: application/json");
session_start();

$username = $_SESSION["username"] ?? "'Guest'";

if (!isset($_SESSION['search_attempts']) || time() - $_SESSION['search_attempts']['time'] > 60) {
    $_SESSION['search_attempts'] = ['count' => 0, 'time' => time()];
}

if ($_SESSION['search_attempts']['count'] >= 10) { 
    logUserActivity($username, "Rate limited after excessive search attempts");
    header("HTTP/1.1 429 Too Many Requests");
    echo json_encode(["error" => "Too many requests. Please try again later."]);
    exit();
}

$_SESSION['search_attempts']['count']++;


if (!isset($_GET["query"]) || empty(trim($_GET["query"]))) {
    logUserActivity($username, "Performed a search with an empty query");
    echo json_encode(["message" => "No results found"]);
    exit();
}
if (!isset($_GET["type"]) || empty(trim($_GET["type"])) || ($_GET["type"]!="username" && $_GET["type"]!="userID")) {
    echo json_encode(["message" => "No results found"]);
    exit();
} 

//check if searchterm is integer
if($_GET["type"]=="userID" && !filter_var($_GET["query"], FILTER_VALIDATE_INT))
{   
    echo json_encode(["message" => "No results found"]);
    exit();
}

$searchTerm = trim($_GET["query"]);

// Sanitize input to prevent XSS and potential PHP execution,change to use utils
 $searchTerm = htmlspecialchars(strip_tags($searchTerm), ENT_QUOTES, 'UTF-8');


try {
    $results = search_users($pdo, $searchTerm,$_GET["type"]);

    if (empty($results)) {
        logUserActivity($username, "Searched for '$searchTerm' but found no results");
        echo json_encode(["message" => "No results found"]);
    } else {
        if($_GET["type"]!="userID") $safe_results = array_map(fn($name) => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), $results);
        else $safe_results  = $results;
        logUserActivity($username, "Searched for '$searchTerm' and found " . count($safe_results) . " results");
        echo json_encode($safe_results);

    }
} catch (Exception $e) {
    //error_log($e->getMessage());
    echo json_encode(["error" => "An error occurred."]);
}
?>