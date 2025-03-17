<?php

declare(strict_types=1);
require_once '../db.inc.php';

function get_transaction_history(PDO $pdo, int $userId): array 
{
    $stmt = $pdo->prepare("
        SELECT t.TransactionID, 
               t.SenderID, 
               t.ReceiverID, 
               t.Amount, 
               t.Comment, 
               t.CreatedAt, 
               u1.Username AS SenderName, 
               u2.Username AS ReceiverName
        FROM Transactions t
        JOIN Profile u1 ON t.SenderID = u1.ID
        JOIN Profile u2 ON t.ReceiverID = u2.ID
        WHERE t.SenderID = :userId OR t.ReceiverID = :userId
        ORDER BY t.CreatedAt DESC
    ");

    $stmt->execute([':userId' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
