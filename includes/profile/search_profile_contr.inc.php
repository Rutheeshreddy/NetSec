<?php

declare(strict_types=1);
require_once '../db.inc.php'; // Database connection
require_once 'profile_model.inc.php'; // Contains search_users function
require_once '../../logs/logger.inc.php';

header("Content-Type: application/json");
session_start();

$username = $_SESSION["username"] ?? "Guest";

if (!isset($_SESSION['search_attempts']) || time() - $_SESSION['search_attempts']['time'] > 60) {
    $_SESSION['search_attempts'] = ['count' => 0, 'time' => time()];
}

if ($_SESSION['search_attempts']['count'] >= 100) { 
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

if (!isset($_GET["type"]) || empty(trim($_GET["type"])) || ($_GET["type"] !== "username" && $_GET["type"] !== "userID")) {
    echo json_encode(["message" => "No results found"]);
    exit();
} 

$searchTerm = trim($_GET["query"]);
$searchTerm = htmlspecialchars(strip_tags($searchTerm), ENT_QUOTES, 'UTF-8');

try {
    $results = search_users($pdo, $searchTerm, $_GET["type"]);

    if (empty($results)) {
        logUserActivity($username, "Searched for '$searchTerm' but found no results");
        echo json_encode(["message" => "No results found"]);
    } else {
        $safe_results = [];
        
        foreach ($results as $user) {
            if ($_GET["type"] === "username") {
                $user_id = get_user_id_by_username($pdo, $user);
                if ($user_id !== null) {
                    $safe_results[] = ["username" => htmlspecialchars($user, ENT_QUOTES, 'UTF-8'), "user_id" => $user_id];
                }
            } else {
                $safe_results[] = ["user_id" => $user];
            }
        }

        logUserActivity($username, "Searched for '$searchTerm' and found " . count($safe_results) . " results");
        echo json_encode($safe_results);
    }
} catch (Exception $e) {
    echo json_encode(["error" => "An error occurred."]);
}
?>
