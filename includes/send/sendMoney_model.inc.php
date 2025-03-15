<?php

declare(strict_types=1);
require_once '../db.inc.php';

// Get user ID by username
function get_user_id_by_username(PDO $pdo, string $Username): ?int
{
    $stmt = $pdo->prepare("SELECT ID FROM Profile WHERE Username = :Username");
    $stmt->execute([':Username' => $Username]);

    return ($id = $stmt->fetchColumn()) ? (int) $id : null;
}

// Search users by username (case-insensitive search)
function search_users(PDO $pdo, string $searchTerm, string $type): array
{

    if (strlen($searchTerm) < 1) {
        return []; // Return empty array instead of top 5 users
    }

    if($type=="username"){
    $stmt = $pdo->prepare("SELECT Username FROM Profile WHERE LOWER(Username) LIKE LOWER(:searchTerm) LIMIT 5");
    $searchTerm = $searchTerm . '%'; // Append '%' before binding to parameter
    $stmt->execute([':searchTerm' => $searchTerm]);
    }
    else 
    {
        $stmt = $pdo->prepare("SELECT ID FROM Profile WHERE CAST(ID AS TEXT) LIKE :searchTerm LIMIT 5;");
        $searchTerm = $searchTerm . '%'; // Append '%' for partial matching
        $stmt->execute([':searchTerm' => $searchTerm]);
    }

    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $result;
}


// Transfer money between users
function transfer_money(PDO $pdo, int $senderId, string $receiverUsername, float $amount,string $type, ?string $comment): bool
{
    try {
        $pdo->beginTransaction();

        // Get receiver ID
        if($type=="username") $receiverId = get_user_id_by_username($pdo, $receiverUsername);
        else $receiverId = $receiverUsername;
        
        if (!$receiverId) {
            throw new Exception("Recipient not found.");
        }

        $amount2 = round($amount, 2);

        if ($amount2 - $amount != 0) 
        {
            throw new Exception("Invalid amount format");
        }

        $amount = $amount2 ;


        // Get sender's balance
        $stmt = $pdo->prepare("SELECT balance FROM balance WHERE id = :senderId");
        $stmt->execute([':senderId' => $senderId]);
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sender || $sender["balance"] < $amount) {
            throw new Exception("Insufficient funds from your side"); 
        }

        // Deduct from sender
        $stmt = $pdo->prepare("UPDATE balance SET balance = balance - :amount WHERE id = :senderId");
        $stmt->execute([':amount' => $amount, ':senderId' => $senderId]);

        // Add to receiver
        $stmt = $pdo->prepare("UPDATE balance SET balance = Balance + :amount WHERE id = :receiverId");
        $stmt->execute([':amount' => $amount, ':receiverId' => $receiverId]);

        // Insert transaction record
        $stmt = $pdo->prepare("INSERT INTO transactions (SenderID, ReceiverID, Amount, Comment) 
                              VALUES (:senderId, :receiverId, :amount, :comment)");
        $stmt->execute([
            ':senderId' => $senderId,
            ':receiverId' => $receiverId,
            ':amount' => $amount,
            ':comment' => htmlspecialchars($comment) // Prevent XSS
        ]);

        $pdo->commit();
        return true;
    } 
    catch (Exception $e) 
    {
        $pdo->rollBack();
        $knownErrors = [
            "Recipient not found.",
            "Invalid amount format",
            "Insufficient funds from your side"
        ];

        if (in_array($e->getMessage(), $knownErrors)) {
            $_SESSION["errors_transfer"] = $e->getMessage();
        } else {
            $_SESSION["errors_transfer"] = "Database error"; // Generic message for unexpected errors
        }

        return false;
    }
}

function get_user_balance(PDO $pdo,string $userID)
{
    $stmt = $pdo->prepare("SELECT balance FROM balance WHERE id = :senderId");
    $stmt->execute([':senderId' => $userID]);
    $balance = $stmt->fetch(PDO::FETCH_COLUMN);
    return $balance;
}

function get_username_by_id($pdo, $userID) {
    $stmt = $pdo->prepare("SELECT username FROM profile WHERE id = :userId");
    $stmt->execute([':userId' => $userID]);
    return $stmt->fetchColumn();
}