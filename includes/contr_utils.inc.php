<?php
require_once 'htmlpurifier-4.15.0/library/HTMLPurifier.auto.php';

function sanitize_input(string $input): string {
    $input = strip_tags($input); // Remove all HTML tags
    $input = preg_replace('/[^a-zA-Z0-9@.,!?()\s-]/u', '', $input); // Allow Aplha Numeric only
    $input = trim($input); // Remove excess spaces
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); // Encode special characters,redundant?

    return $input; 
}

//this function is ideal for handling inputs and outputs of bio
function sanitize_output($input) {
    // Sanitize the input using HTML Purifier
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);
    $sanitized_input = $purifier->purify($input);
    return $sanitized_input;
}


// Validate email
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}