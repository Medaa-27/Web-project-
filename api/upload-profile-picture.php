<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['profile_picture'];
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowed[$mime])) {
    echo json_encode(['success' => false, 'message' => 'Invalid image type']);
    exit;
}

$ext = $allowed[$mime];
$user_id = (int)$session->getUserId();
$name = "profile_{$user_id}_" . time() . ".$ext";
$dir = realpath(__DIR__ . '/../uploads/profiles');
if ($dir === false) {
    echo json_encode(['success' => false, 'message' => 'Upload directory missing']);
    exit;
}
$path = $dir . DIRECTORY_SEPARATOR . $name;

if (!move_uploaded_file($file['tmp_name'], $path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

$stmt = $db->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE user_id = ?");
if (!$db->execute($stmt, [$name, $user_id])) {
    @unlink($path);
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    exit;
}

echo json_encode(['success' => true, 'filename' => $name]);
