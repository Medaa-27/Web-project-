<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$notice_id = $_GET['notice_id'] ?? null;
if (!$notice_id || !is_numeric($notice_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid notice ID']);
    exit;
}

try {
    // Get vacating notice details with all related information
    $sql = "SELECT vn.*, 
                   ra.tenant_id, ra.start_date, ra.end_date, ra.monthly_rent,
                   p.title as property_title, p.property_id, p.bedrooms, p.bathrooms, p.area, p.property_type, p.is_furnished,
                   l.location_name, l.subcity,
                   u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone,
                   pi.image_url as property_image
            FROM vacating_notices vn
            LEFT JOIN rental_agreements ra ON vn.agreement_id = ra.agreement_id
            LEFT JOIN properties p ON ra.property_id = p.property_id
            LEFT JOIN locations l ON p.location_id = l.location_id
            LEFT JOIN users u ON vn.tenant_id = u.user_id
            LEFT JOIN property_images pi ON p.property_id = pi.property_id AND pi.is_primary = 1
            WHERE vn.notice_id = ?";
    
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$notice_id]);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Vacating notice not found']);
        exit;
    }
    
    // Format data for JSON response
    $notice_data = [
        'notice_id' => (int)$result['notice_id'],
        'agreement_id' => (int)$result['agreement_id'],
        'vacating_date' => $result['vacating_date'],
        'reason' => $result['reason'] ?: '',
        'forwarding_address' => $result['forwarding_address'] ?: '',
        'contact_info' => $result['contact_info'] ?: '',
        'status' => $result['status'],
        'created_at' => $result['created_at'],
        'updated_at' => $result['updated_at'],
        
        // Property information
        'property' => [
            'property_id' => (int)$result['property_id'],
            'title' => $result['property_title'],
            'location_name' => $result['location_name'],
            'subcity' => $result['subcity'],
            'bedrooms' => (int)$result['bedrooms'],
            'bathrooms' => (int)$result['bathrooms'],
            'area' => (int)$result['area'],
            'property_type' => $result['property_type'],
            'is_furnished' => (bool)$result['is_furnished'],
            'image_url' => $result['property_image'] ?: '../assets/images/default-property.svg'
        ],
        
        // Rental agreement information
        'agreement' => [
            'start_date' => $result['start_date'],
            'end_date' => $result['end_date'],
            'monthly_rent' => (float)$result['monthly_rent']
        ],
        
        // Tenant information
        'tenant' => [
            'tenant_id' => (int)$result['tenant_id'],
            'full_name' => $result['tenant_name'],
            'email' => $result['tenant_email'],
            'phone' => $result['tenant_phone']
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $notice_data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
