<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["1=1"];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "p.review_status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR u.full_name LIKE ? OR l.location_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get properties for export
$sql = "SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone,
               l.location_name, l.subcity,
               (SELECT COUNT(*) FROM property_images pi WHERE pi.property_id = p.property_id) as image_count,
               reviewer.full_name as reviewer_name,
               (SELECT COUNT(*) FROM rental_requests rr WHERE rr.property_id = p.property_id) as request_count,
               (SELECT AVG(rating) FROM feedback f WHERE f.property_id = p.property_id) as avg_rating
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id
        {$where_clause}
        ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$properties = $db->getMultiple($stmt, $params);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="properties_export_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'Property ID',
    'Title',
    'Property Type',
    'Bedrooms',
    'Bathrooms',
    'Monthly Rent',
    'Security Deposit',
    'Status',
    'Review Status',
    'Owner Name',
    'Owner Email',
    'Owner Phone',
    'Location',
    'Subcity',
    'Address',
    'Image Count',
    'Request Count',
    'Average Rating',
    'Created At',
    'Review Date',
    'Reviewer',
    'Review Comments'
];

fputcsv($output, $headers);

// CSV data
foreach ($properties as $property) {
    $row = [
        $property['property_id'],
        $property['title'],
        $property['property_type'],
        $property['bedrooms'],
        $property['bathrooms'],
        $property['monthly_rent'],
        $property['security_deposit'] ?? 0,
        $property['status'],
        $property['review_status'],
        $property['owner_name'],
        $property['owner_email'],
        $property['owner_phone'] ?? 'N/A',
        $property['location_name'] ?? 'N/A',
        $property['subcity'] ?? 'N/A',
        $property['address'],
        $property['image_count'],
        $property['request_count'],
        $property['avg_rating'] ? number_format($property['avg_rating'], 1) : 'N/A',
        $property['created_at'],
        $property['review_date'] ?? 'N/A',
        $property['reviewer_name'] ?? 'N/A',
        $property['review_comments'] ?? 'N/A'
    ];
    
    fputcsv($output, $row);
}

// Close output stream
fclose($output);
exit;
?>
