<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Redirect to appropriate page based on login status
if ($session->isLoggedIn()) {
    $session->redirectToDashboard();
} else {
    header("Location: public/index.php");
    exit();
}
?>