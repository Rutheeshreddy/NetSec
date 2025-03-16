<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// // Secure session settings
// session_set_cookie_params([
//     'lifetime' => 0, // Session expires when the browser closes
//     'path' => '/',
//     'domain' => '', // Change this if you have a specific domain
//     'secure' => true, // Ensures cookies are sent over HTTPS
//     'httponly' => true, // Prevents JavaScript access to session cookies
//     'samesite' => 'Strict' // Protects against CSRF attacks
// ]);


// CSRF token generation and periodic regeneration
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Regenerate CSRF token periodically (every 30 minutes)
if (!isset($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 1800) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

if(isset($_SESSION["user_id"]))
{
    if(!isset($_SESSION["last_regeneration"]))
    {
        regenerate_session_id_loggedin();
    } else 
    {
        $interval = 60*30;
        if(time()- $_SESSION["last_regeneration"] >= $interval)
        {
            regenerate_session_id_loggedin();
        }
    }

} else {
        if(!isset($_SESSION["last_regeneration"]))
        {
            regenerate_session_id();
        } else 
        {
            $interval = 60*30;
            if(time()- $_SESSION["last_regeneration"] >= $interval)
            {
                regenerate_session_id();
            }
        }
}


function regenerate_session_id()
{
    session_regenerate_id(true);
    $_SESSION["last_regeneration"] = time();
}


function regenerate_session_id_loggedin()
{
    $newSessionId = session_create_id();
    $sessionId = $newSessionId ."_". $_SESSION["user_id"]; //make this more secure, unguessabel
    session_id($sessionId);
    $_SESSION["last_regeneration"] = time();
}