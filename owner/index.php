
<?php
require_once '../includes/config.php';
$session->requireRole('owner');

header('Location: dashboard.php');
exit;

