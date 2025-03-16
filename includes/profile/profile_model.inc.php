<?php

function get_user_profile(object $pdo, int $user_id)
{
    $query = "SELECT ID, Username, Email, Biography, ProfileImagePath FROM Profile WHERE ID = :user_id;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function update_user_profile(object $pdo, int $user_id, ?string $email, ?string $bio): bool
{
    try {
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT ID FROM Profile WHERE Email = :email AND ID != :user_id");
            $stmt->execute(['email' => $email, 'user_id' => $user_id]);

            if ($stmt->fetch()) {
                return false; 
            }
        }

        $query = "UPDATE Profile SET ";
        $params = [];

        if (!empty($email)) {
            $query .= "Email = :email, ";
            $params[':email'] = $email;
        }
        if (!empty($bio)) {
            $query .= "Biography = :bio, ";
            $params[':bio'] = $bio;
        }

        if (empty($params)) {
            return false; 
        }

        $query = rtrim($query, ", ") . " WHERE ID = :user_id";
        $params[':user_id'] = $user_id;

        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        return false;
    }
}


function get_user_password_hash(object $pdo, int $user_id): ?string {
    $query = "SELECT PasswordHash FROM Profile WHERE ID = :user_id LIMIT 1;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user["passwordhash"] ?? null;
}

//updates password
function update_user_password(object $pdo, int $user_id, string $new_password): bool {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $query = "UPDATE Profile SET PasswordHash = :password WHERE ID = :user_id;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

function update_profile_image(object $pdo, int $user_id, string $file_path)
{
    $query = "UPDATE Profile SET ProfileImagePath = :file_path WHERE ID = :user_id;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":file_path", $file_path, PDO::PARAM_STR);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    return $stmt->execute();
}

function get_old_profile_image(PDO $pdo, int $user_id): ?string
{
    $query = "SELECT ProfileImagePath FROM Profile WHERE ID = :user_id LIMIT 1;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result["profileimagepath"] : null;
}

//process data securely
function sanitize_input(string $input, int $max_length): string {
    $input = strip_tags($input); 
    $input = preg_replace('/[^a-zA-Z0-9@.,!?()\s-]/u', '', $input); 
    $input = trim($input); 
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); 

    return substr($input, 0, $max_length);
}

//validate email
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

//get user ID by username
function get_user_id_by_username(PDO $pdo, string $Username): ?int
{
    $stmt = $pdo->prepare("SELECT ID FROM Profile WHERE Username = :Username");
    $stmt->execute([':Username' => $Username]);

    return ($id = $stmt->fetchColumn()) ? (int) $id : null;
}

//search users by username
function search_users(PDO $pdo, string $searchTerm, string $type): array
{

    if (strlen($searchTerm) < 1) {
        return []; 
    }

    if($type=="username"){
    $stmt = $pdo->prepare("SELECT Username FROM Profile WHERE LOWER(Username) LIKE LOWER(:searchTerm) LIMIT 5");
    $searchTerm = $searchTerm . '%'; 
    $stmt->execute([':searchTerm' => $searchTerm]);
    }
    else 
    {
        $stmt = $pdo->prepare("SELECT ID FROM Profile WHERE CAST(ID AS TEXT) LIKE :searchTerm LIMIT 5;");
        $searchTerm = $searchTerm . '%'; 
        $stmt->execute([':searchTerm' => $searchTerm]);
    }

    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $result;
}
?>
