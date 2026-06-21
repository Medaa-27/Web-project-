<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');

$db = new Database();
$format = $_GET['format'] ?? 'csv';

// Get all properties with comprehensive data
$sql = "SELECT p.property_id, p.title, p.description, p.monthly_rent, p.bedrooms, p.bathrooms, 
               p.property_type, p.is_furnished, p.status, p.review_status, p.created_at, p.updated_at,
               l.location_name, l.subcity,
               u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email,
               e.full_name as reviewed_by_name, p.review_date,
               COUNT(DISTINCT rr.request_id) as total_requests,
               COUNT(DISTINCT ra.agreement_id) as active_agreements
        FROM properties p 
        LEFT JOIN locations l ON p.location_id = l.location_id 
        LEFT JOIN users u ON p.owner_id = u.user_id 
        LEFT JOIN users e ON p.reviewed_by = e.user_id
        LEFT JOIN rental_requests rr ON p.property_id = rr.property_id
        LEFT JOIN rental_agreements ra ON p.property_id = ra.property_id AND ra.status = 'active'
        GROUP BY p.property_id 
        ORDER BY p.created_at DESC";

$properties = $db->getMultiple($db->prepare($sql), []);

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="properties_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($output, [
        'Property ID', 'Title', 'Description', 'Monthly Rent', 'Bedrooms', 'Bathrooms',
        'Property Type', 'Furnished', 'Status', 'Review Status', 'Location', 'Subcity',
        'Owner Name', 'Owner Phone', 'Owner Email', 'Reviewed By', 'Review Date',
        'Total Requests', 'Active Agreements', 'Created At', 'Updated At'
    ]);
    
    // CSV Data
    foreach ($properties as $property) {
        fputcsv($output, [
            $property['property_id'],
            $property['title'],
            strip_tags($property['description']),
            $property['monthly_rent'],
            $property['bedrooms'],
            $property['bathrooms'],
            $property['property_type'],
            $property['is_furnished'] ? 'Yes' : 'No',
            ucfirst(str_replace('_', ' ', $property['status'])),
            ucfirst($property['review_status']),
            $property['location_name'],
            $property['subcity'],
            $property['owner_name'],
            $property['owner_phone'],
            $property['owner_email'],
            $property['reviewed_by_name'] ?? 'Not Reviewed',
            $property['review_date'] ?? 'N/A',
            $property['total_requests'],
            $property['active_agreements'],
            $property['created_at'],
            $property['updated_at']
        ]);
    }
    
    fclose($output);
    
} elseif ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="properties_export_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>";
    echo "<th>Property ID</th><th>Title</th><th>Description</th><th>Monthly Rent</th><th>Bedrooms</th><th>Bathrooms</th>";
    echo "<th>Property Type</th><th>Furnished</th><th>Status</th><th>Review Status</th><th>Location</th><th>Subcity</th>";
    echo "<th>Owner Name</th><th>Owner Phone</th><th>Owner Email</th><th>Reviewed By</th><th>Review Date</th>";
    echo "<th>Total Requests</th><th>Active Agreements</th><th>Created At</th><th>Updated At</th>";
    echo "</tr>";
    
    foreach ($properties as $property) {
        echo "<tr>";
        echo "<td>" . $property['property_id'] . "</td>";
        echo "<td>" . htmlspecialchars($property['title']) . "</td>";
        echo "<td>" . htmlspecialchars(strip_tags($property['description'])) . "</td>";
        echo "<td>" . $property['monthly_rent'] . "</td>";
        echo "<td>" . $property['bedrooms'] . "</td>";
        echo "<td>" . $property['bathrooms'] . "</td>";
        echo "<td>" . htmlspecialchars($property['property_type']) . "</td>";
        echo "<td>" . ($property['is_furnished'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ucfirst(str_replace('_', ' ', $property['status'])) . "</td>";
        echo "<td>" . ucfirst($property['review_status']) . "</td>";
        echo "<td>" . htmlspecialchars($property['location_name']) . "</td>";
        echo "<td>" . htmlspecialchars($property['subcity']) . "</td>";
        echo "<td>" . htmlspecialchars($property['owner_name']) . "</td>";
        echo "<td>" . htmlspecialchars($property['owner_phone']) . "</td>";
        echo "<td>" . htmlspecialchars($property['owner_email']) . "</td>";
        echo "<td>" . htmlspecialchars($property['reviewed_by_name'] ?? 'Not Reviewed') . "</td>";
        echo "<td>" . ($property['review_date'] ?? 'N/A') . "</td>";
        echo "<td>" . $property['total_requests'] . "</td>";
        echo "<td>" . $property['active_agreements'] . "</td>";
        echo "<td>" . $property['created_at'] . "</td>";
        echo "<td>" . $property['updated_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} elseif ($format === 'pdf') {
    // For PDF, we'll create a simple HTML table that can be saved as PDF
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="properties_export_' . date('Y-m-d') . '.html"');
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Properties Export - Aksum Rental System</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .header-info { margin-bottom: 20px; color: #666; }
        </style>
    </head>
    <body>
        <h1>Properties Export Report</h1>
        <div class='header-info'>
            <p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Total Properties:</strong> " . count($properties) . "</p>
            <p><strong>Generated by:</strong> " . $_SESSION['user_name'] . " (Employee)</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Property ID</th><th>Title</th><th>Monthly Rent</th><th>Bedrooms</th><th>Bathrooms</th>
                    <th>Property Type</th><th>Status</th><th>Location</th><th>Owner</th>
                    <th>Requests</th><th>Agreements</th><th>Created</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($properties as $property) {
        echo "<tr>
            <td>" . $property['property_id'] . "</td>
            <td>" . htmlspecialchars($property['title']) . "</td>
            <td>" . number_format($property['monthly_rent']) . " ETB</td>
            <td>" . $property['bedrooms'] . "</td>
            <td>" . $property['bathrooms'] . "</td>
            <td>" . htmlspecialchars($property['property_type']) . "</td>
            <td>" . ucfirst(str_replace('_', ' ', $property['status'])) . "</td>
            <td>" . htmlspecialchars($property['location_name']) . "</td>
            <td>" . htmlspecialchars($property['owner_name']) . "</td>
            <td>" . $property['total_requests'] . "</td>
            <td>" . $property['active_agreements'] . "</td>
            <td>" . date('M j, Y', strtotime($property['created_at'])) . "</td>
        </tr>";
    }
    
    echo "</tbody></table>
        
        <div style='margin-top: 30px; page-break-inside: avoid;'>
            <p><em>This report was generated from the Aksum Rental Management System.</em></p>
        </div>
    </body>
    </html>";
}

exit;
?>
