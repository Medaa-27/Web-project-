<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review-history.php');
    exit;
}

$format = $_POST['format'] ?? 'csv';
$status_filter = $_POST['status'] ?? 'all';
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';

$employee_id = $session->getUserId();

// Build query conditions
$where_conditions = ["p.reviewed_by = ?"];
$params = [$employee_id];

if ($status_filter !== 'all') {
    $where_conditions[] = "p.review_status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(p.review_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(p.review_date) <= ?";
    $params[] = $date_to;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get review data
$sql = "SELECT p.property_id, p.title, p.property_type, p.monthly_rent,
               p.review_status, p.review_date, p.review_comments,
               u.full_name as owner_name, u.email as owner_email,
               l.location_name, l.subcity,
               reviewer.full_name as reviewer_name
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id
        {$where_clause}
        ORDER BY p.review_date DESC";
$stmt = $db->prepare($sql);
$reviews = $db->getMultiple($stmt, $params);

if (empty($reviews)) {
    $_SESSION['error'] = "No data found for export";
    header('Location: review-history.php');
    exit;
}

// Generate filename
$filename = "property_reviews_" . date('Y-m-d_H-i-s');

switch ($format) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV header
        fputcsv($output, [
            'Property ID',
            'Title',
            'Type',
            'Monthly Rent',
            'Owner Name',
            'Owner Email',
            'Location',
            'Subcity',
            'Review Status',
            'Review Date',
            'Review Comments',
            'Reviewed By'
        ]);
        
        // CSV data
        foreach ($reviews as $review) {
            fputcsv($output, [
                $review['property_id'],
                $review['title'],
                $review['property_type'],
                $review['monthly_rent'],
                $review['owner_name'],
                $review['owner_email'],
                $review['location_name'] ?? '',
                $review['subcity'] ?? '',
                $review['review_status'],
                $review['review_date'] ?? '',
                $review['review_comments'] ?? '',
                $review['reviewer_name'] ?? ''
            ]);
        }
        
        fclose($output);
        break;
        
    case 'excel':
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>Property ID</th>";
        echo "<th>Title</th>";
        echo "<th>Type</th>";
        echo "<th>Monthly Rent</th>";
        echo "<th>Owner Name</th>";
        echo "<th>Owner Email</th>";
        echo "<th>Location</th>";
        echo "<th>Subcity</th>";
        echo "<th>Review Status</th>";
        echo "<th>Review Date</th>";
        echo "<th>Review Comments</th>";
        echo "<th>Reviewed By</th>";
        echo "</tr>";
        
        foreach ($reviews as $review) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($review['property_id']) . "</td>";
            echo "<td>" . htmlspecialchars($review['title']) . "</td>";
            echo "<td>" . htmlspecialchars($review['property_type']) . "</td>";
            echo "<td>" . $review['monthly_rent'] . "</td>";
            echo "<td>" . htmlspecialchars($review['owner_name']) . "</td>";
            echo "<td>" . htmlspecialchars($review['owner_email']) . "</td>";
            echo "<td>" . htmlspecialchars($review['location_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($review['subcity'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($review['review_status']) . "</td>";
            echo "<td>" . htmlspecialchars($review['review_date'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($review['review_comments'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($review['reviewer_name'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        break;
        
    case 'pdf':
        // Simple PDF generation
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Property Review History</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Property Review History</h1>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
        <p>Employee: ' . htmlspecialchars($_SESSION['full_name']) . '</p>
    </div>
    
    <table>
        <tr>
            <th>Property ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Monthly Rent</th>
            <th>Owner Name</th>
            <th>Review Status</th>
            <th>Review Date</th>
        </tr>';
        
        foreach ($reviews as $review) {
            $html .= '
        <tr>
            <td>' . htmlspecialchars($review['property_id']) . '</td>
            <td>' . htmlspecialchars($review['title']) . '</td>
            <td>' . htmlspecialchars($review['property_type']) . '</td>
            <td>ETB ' . number_format($review['monthly_rent'], 2) . '</td>
            <td>' . htmlspecialchars($review['owner_name']) . '</td>
            <td>' . htmlspecialchars($review['review_status']) . '</td>
            <td>' . htmlspecialchars($review['review_date'] ?? '') . '</td>
        </tr>';
        }
        
        $html .= '
    </table>
    
    <div class="footer">
        <p>End of Report</p>
    </div>
</body>
</html>';
        
        // Convert HTML to PDF (you might want to use a library like TCPDF or DomPDF in production)
        echo $html;
        break;
        
    default:
        $_SESSION['error'] = "Invalid export format";
        header('Location: review-history.php');
        exit;
}

// Log export action
$sql = "INSERT INTO audit_log (user_id, action, table_name, old_value, new_value) 
        VALUES (?, 'export_reviews', 'properties', ?, ?)";
$stmt = $db->prepare($sql);
$old_value = "format: {$format}, filters: status={$status_filter}, date_from={$date_from}, date_to={$date_to}";
$new_value = "exported " . count($reviews) . " records";
$db->execute($stmt, [$employee_id, $old_value, $new_value]);

exit;
?>
