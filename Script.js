<?php
session_start();

$_SESSION = array();

// Completely destroy the active session instance
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect clean back to the storefront landing directory
header("Location: ../store/home.php");
exit();