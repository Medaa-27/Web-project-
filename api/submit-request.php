<?php

require_once '../includes/config.php';



header('Content-Type: application/json');



if (!$session->isLoggedIn() || $session->getUserRole() != 'tenant') {

    echo json_encode(['success' => false, 'message' => 'Unauthorized']);

    exit();

}



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode(['success' => false, 'message' => 'Invalid request method']);

    exit();

}



$tenant_id = $session->getUserId();

$property_id = $_POST['property_id'] ?? 0;

$message = $_POST['message'] ?? '';



// Validate

if (empty($property_id) || !is_numeric($property_id)) {

    echo json_encode(['success' => false, 'message' => 'Invalid property']);

    exit();

}



// Check if property exists and is available

$sql = "SELECT status, owner_id FROM properties WHERE property_id = ?";

$stmt = $db->prepare($sql);

$property = $db->getSingle($stmt, [$property_id]);



if (!$property) {

    echo json_encode(['success' => false, 'message' => 'Property not found']);

    exit();

}



if ($property['status'] === 'rented') {

    echo json_encode(['success' => false, 'message' => 'This property is already rented.']);

    exit();

}

if ($property['status'] !== 'available') {

    echo json_encode(['success' => false, 'message' => 'Property is not available for rent']);

    exit();

}



// Check if user already has an active request for this property

$sql = "SELECT request_id FROM rental_requests 

        WHERE tenant_id = ? AND property_id = ? AND status IN ('pending', 'approved')";

$stmt = $db->prepare($sql);

$existing = $db->getSingle($stmt, [$tenant_id, $property_id]);



if ($existing) {

    echo json_encode(['success' => false, 'message' => 'You already have a pending request for this property']);

    exit();

}



// Generate approval code (Business Rule BR3)

$approval_code = strtoupper(bin2hex(random_bytes(4)));



// Insert rental request

$sql = "INSERT INTO rental_requests (tenant_id, property_id, request_date, message, status, approval_code, created_at) 

        VALUES (?, ?, CURDATE(), ?, 'pending', ?, NOW())";

$stmt = $db->prepare($sql);



if ($db->execute($stmt, [$tenant_id, $property_id, $message, $approval_code])) {

    $request_id = $db->lastInsertId();

    

    // Update property status

    $sql = "UPDATE properties SET status = 'requested' WHERE property_id = ?";

    $stmt = $db->prepare($sql);

    $db->execute($stmt, [$property_id]);
    // send email to property owner using template (ignore failures)
    try {
        require_once __DIR__ . '/../includes/functions.php';
        $ownerStmt = $db->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
        $owner = $db->getSingle($ownerStmt, [$property['owner_id']]);
        if ($owner && !empty($owner['email'])) {
            $tenantInfo = $db->getSingle($db->prepare("SELECT full_name FROM users WHERE user_id = ?"), [$tenant_id]);
            $propTitle = '';
            $ptStmt = $db->prepare("SELECT title FROM properties WHERE property_id = ?");
            $ptRow = $db->getSingle($ptStmt, [$property_id]);
            if ($ptRow) {
                $propTitle = $ptRow['title'];
            }
            $subject = "New rental request for your property";
            sendEmailTemplate($owner['email'], $subject, 'new_request', [
                'owner_name' => $owner['full_name'],
                'tenant_name' => $tenantInfo['full_name'] ?? '',
                'property_title' => $propTitle,
                'request_link' => SITE_URL . 'owner/requests.php',
                'site_name' => SITE_NAME
            ]);
        }
    } catch (Exception $e) {
        error_log("Failed to send new request email: " . $e->getMessage());
    }

    

    // Create notification for property owner

    $sql = "INSERT INTO notifications (user_id, title, message, type, link) 

            VALUES (?, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', '../owner/requests.php')";

    $stmt = $db->prepare($sql);

    $db->execute($stmt, [$property['owner_id']]);

    

    // Create notification for tenant

    $sql = "INSERT INTO notifications (user_id, title, message, type) 

            VALUES (?, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success')";

    $stmt = $db->prepare($sql);

    $db->execute($stmt, [$tenant_id]);

    

    // Log activity

    $session->logActivity($tenant_id, 'submit_request', 'rental_requests', $request_id);

    

    echo json_encode(['success' => true, 'message' => 'Request submitted successfully']);

} else {

    echo json_encode(['success' => false, 'message' => 'Failed to submit request']);

}

?>