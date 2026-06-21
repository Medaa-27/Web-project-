<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check if user is employee
if (!$session->isLoggedIn() || !$session->hasRole('employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$property_id = intval($_GET['property_id'] ?? 0);

if ($property_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit;
}

try {
    // Get property images
    $sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, created_at ASC";
    $stmt = $db->prepare($sql);
    $images = $db->getMultiple($stmt, [$property_id]);
    
    // Process images to get correct URLs
    $processed_images = [];
    foreach ($images as $image) {
        if (!empty($image['image_url'])) {
            $filename = basename($image['image_url']);
            $image_url = '../assets/uploads/properties/' . $filename;
            
            // Check if file exists
            $full_path = UPLOAD_PATH . 'properties/' . $filename;
            if (!file_exists($full_path)) {
                $image_url = '../assets/images/default-avatar.svg';
            }
        } else {
            $image_url = '../assets/images/default-avatar.svg';
        }
        
        $processed_images[] = [
            'image_id' => $image['image_id'],
            'url' => $image_url,
            'is_primary' => (bool)$image['is_primary'],
            'created_at' => $image['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $processed_images,
        'count' => count($processed_images)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => "Error loading property images: " . $e->getMessage()
    ]);
}
?>
