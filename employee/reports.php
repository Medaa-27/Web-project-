<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Reports - Employee Dashboard";

// Suppress warnings for cleaner display
error_reporting(E_ERROR | E_PARSE);

$employee_id = $session->getUserId();

// Get employee info
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$employee = $db->getSingle($stmt, [$employee_id]);
if (!$employee) {
    $employee = ['full_name' => 'Employee'];
}

// Handle report generation and download
$report_type = $_GET['report_type'] ?? 'property_status';
$download_format = $_GET['download'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get ALL data FIRST before handling downloads
$report_data = [];
$report_title = '';

switch($report_type) {
    case 'property_status':
        $report_title = 'Property Status Report';
        try {
            $sql = "SELECT 
                        p.property_id, p.title, p.monthly_rent, p.property_type, p.status, p.created_at,
                        l.location_name, l.subcity,
                        u.full_name as owner_name,
                        COUNT(ra.agreement_id) as total_rentals,
                        COUNT(CASE WHEN ra.status = 'active' THEN 1 END) as active_rentals,
                        SUM(CASE WHEN ra.status = 'active' THEN ra.monthly_rent ELSE 0 END) as monthly_income,
                        CASE 
                            WHEN p.status = 'available' THEN 'Available'
                            WHEN p.status = 'rented' THEN 'Rented'
                            WHEN p.status = 'maintenance' THEN 'Under Maintenance'
                            WHEN p.status = 'unavailable' THEN 'Inactive'
                            ELSE p.status
                        END as status_text,
                        (COUNT(CASE WHEN ra.status = 'active' THEN 1 END) / NULLIF(COUNT(ra.agreement_id), 0) * 100) as occupancy_rate
                     FROM properties p
                     LEFT JOIN users u ON p.owner_id = u.user_id
                     LEFT JOIN locations l ON p.location_id = l.location_id
                     LEFT JOIN rental_agreements ra ON p.property_id = ra.property_id
                     GROUP BY p.property_id, p.title, p.monthly_rent, p.property_type, p.status, l.location_name, l.subcity, u.full_name, p.created_at
                     ORDER BY p.created_at DESC";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $report_data = $db->getMultiple($stmt);
            } else {
                $report_data = [];
            }
        } catch (Exception $e) {
            $error_message = "Error generating property status report: " . $e->getMessage();
            $report_data = [];
        }
        break;
        
    case 'rental_activity':
        $report_title = 'Rental Activity Report';
        try {
            $sql = "SELECT ra.*, p.title as property_title, p.property_type, p.monthly_rent,
                            u.full_name as tenant_name, l.location_name,
                            CASE 
                                WHEN ra.status = 'active' THEN 'Active'
                                WHEN ra.status = 'expired' THEN 'Completed'
                                WHEN ra.status = 'terminated' THEN 'Terminated'
                                ELSE ra.status
                            END as status_text
                     FROM rental_agreements ra
                     JOIN properties p ON ra.property_id = p.property_id
                     JOIN users u ON ra.tenant_id = u.user_id
                     LEFT JOIN locations l ON p.location_id = l.location_id";
            if ($date_from && $date_to) {
                $sql .= " WHERE ra.start_date BETWEEN ? AND ?";
                $stmt = $db->prepare($sql);
                if ($stmt) {
                    $report_data = $db->getMultiple($stmt, [$date_from, $date_to]);
                } else {
                    $report_data = [];
                }
            } else {
                $stmt = $db->prepare($sql);
                if ($stmt) {
                    $report_data = $db->getMultiple($stmt);
                } else {
                    $report_data = [];
                }
            }
        } catch (Exception $e) {
            $error_message = "Error generating rental activity report: " . $e->getMessage();
            $report_data = [];
        }
        break;
        
    case 'pending_requests':
        $report_title = 'Pending Rental Requests Report';
        try {
            // Test 1: Simple table existence check
            $check_sql = "SHOW TABLES LIKE 'rental_requests'";
            $stmt = $db->prepare($check_sql);
            if ($stmt) {
                $table_exists = $db->getMultiple($stmt);
                if (empty($table_exists)) {
                    $error_message = "rental_requests table does not exist in database";
                    $report_data = [];
                } else {
                    // Test 2: Simple count query
                    $count_sql = "SELECT COUNT(*) as count FROM rental_requests";
                    $stmt = $db->prepare($count_sql);
                    if ($stmt) {
                        $count_result = $db->getSingle($stmt);
                        $total_count = $count_result['count'] ?? 0;
                        
                        if ($total_count == 0) {
                            $error_message = "rental_requests table exists but is empty (0 records)";
                            $report_data = [];
                        } else {
                            // Test 3: Simple query without joins
                            $simple_sql = "SELECT * FROM rental_requests LIMIT 5";
                            $stmt = $db->prepare($simple_sql);
                            if ($stmt) {
                                $simple_data = $db->getMultiple($stmt);
                                if (!empty($simple_data)) {
                                    // Test 4: Now try the full query
                                    $sql = "SELECT rr.*, p.title as property_title, p.property_type, p.monthly_rent,
                                                    u.full_name as tenant_name, u.email as tenant_email, u.phone as tenant_phone,
                                                    CASE 
                                                        WHEN rr.status = 'pending' THEN 'Pending'
                                                        WHEN rr.status = 'approved' THEN 'Approved'
                                                        WHEN rr.status = 'rejected' THEN 'Rejected'
                                                        ELSE rr.status
                                                    END as status_text
                                             FROM rental_requests rr
                                             JOIN properties p ON rr.property_id = p.property_id
                                             JOIN users u ON rr.tenant_id = u.user_id
                                             WHERE rr.status = 'pending'";
                                    if ($date_from && $date_to) {
                                        $sql .= " AND rr.created_at BETWEEN ? AND ?";
                                        $stmt = $db->prepare($sql);
                                        if ($stmt) {
                                            $report_data = $db->getMultiple($stmt, [$date_from, $date_to]);
                                        } else {
                                            $error_message = "SQL prepare error with date filters: " . $db->getLastError();
                                            $report_data = [];
                                        }
                                    } else {
                                        $stmt = $db->prepare($sql);
                                        if ($stmt) {
                                            $report_data = $db->getMultiple($stmt);
                                        } else {
                                            $error_message = "SQL prepare error without filters: " . $db->getLastError();
                                            $report_data = [];
                                        }
                                    }
                                } else {
                                    $error_message = "Simple query returned no data";
                                    $report_data = [];
                                }
                            } else {
                                $error_message = "Simple query prepare failed";
                                $report_data = [];
                            }
                        }
                    } else {
                        $error_message = "Count query failed";
                        $report_data = [];
                    }
                }
            } else {
                $error_message = "Database error checking rental_requests table";
                $report_data = [];
            }
        } catch (Exception $e) {
            $error_message = "Exception in pending requests: " . $e->getMessage();
            $report_data = [];
        }
        break;
        
    case 'payment_verification':
        $report_title = 'Payment Verification Report';
        try {
            // Test 1: Simple table existence check
            $check_sql = "SHOW TABLES LIKE 'payments'";
            $stmt = $db->prepare($check_sql);
            if ($stmt) {
                $table_exists = $db->getMultiple($stmt);
                if (empty($table_exists)) {
                    $error_message = "payments table does not exist in database";
                    $report_data = [];
                } else {
                    // Test 2: Simple count query
                    $count_sql = "SELECT COUNT(*) as count FROM payments";
                    $stmt = $db->prepare($count_sql);
                    if ($stmt) {
                        $count_result = $db->getSingle($stmt);
                        $total_count = $count_result['count'] ?? 0;
                        
                        if ($total_count == 0) {
                            $error_message = "payments table exists but is empty (0 records)";
                            $report_data = [];
                        } else {
                            // Test 3: Simple query without joins
                            $simple_sql = "SELECT * FROM payments LIMIT 5";
                            $stmt = $db->prepare($simple_sql);
                            if ($stmt) {
                                $simple_data = $db->getMultiple($stmt);
                                if (!empty($simple_data)) {
                                    // Test 4: Now try the full query
                                    $sql = "SELECT pm.*, ra.agreement_id, p.title as property_title, p.monthly_rent,
                                                    u.full_name as tenant_name,
                                                    CASE 
                                                        WHEN pm.status = 'pending' THEN 'Pending'
                                                        WHEN pm.status = 'completed' THEN 'Completed'
                                                        WHEN pm.status = 'failed' THEN 'Failed'
                                                        ELSE pm.status
                                                    END as status_text
                                             FROM payments pm
                                             JOIN rental_agreements ra ON pm.agreement_id = ra.agreement_id
                                             JOIN properties p ON ra.property_id = p.property_id
                                             JOIN users u ON ra.tenant_id = u.user_id
                                             WHERE pm.status = 'pending'";
                                    if ($date_from && $date_to) {
                                        $sql .= " AND pm.created_at BETWEEN ? AND ?";
                                        $stmt = $db->prepare($sql);
                                        if ($stmt) {
                                            $report_data = $db->getMultiple($stmt, [$date_from, $date_to]);
                                        } else {
                                            $error_message = "SQL prepare error with date filters: " . $db->getLastError();
                                            $report_data = [];
                                        }
                                    } else {
                                        $stmt = $db->prepare($sql);
                                        if ($stmt) {
                                            $report_data = $db->getMultiple($stmt);
                                        } else {
                                            $error_message = "SQL prepare error without filters: " . $db->getLastError();
                                            $report_data = [];
                                        }
                                    }
                                } else {
                                    $error_message = "Simple query returned no data";
                                    $report_data = [];
                                }
                            } else {
                                $error_message = "Simple query prepare failed";
                                $report_data = [];
                            }
                        }
                    } else {
                        $error_message = "Count query failed";
                        $report_data = [];
                    }
                }
            } else {
                $error_message = "Database error checking payments table";
                $report_data = [];
            }
        } catch (Exception $e) {
            $error_message = "Exception in payment verification: " . $e->getMessage();
            $report_data = [];
        }
        break;
        
    case 'maintenance_issues':
        $report_title = 'Maintenance and Issue Report';
        try {
            // First check if maintenance_requests table exists
            $check_sql = "SHOW TABLES LIKE 'maintenance_requests'";
            $stmt = $db->prepare($check_sql);
            if ($stmt) {
                $table_exists = $db->getMultiple($stmt);
                if (empty($table_exists)) {
                    $error_message = "maintenance_requests table does not exist in database";
                    $report_data = [];
                } else {
                    // Table exists, proceed with query
                    $sql = "SELECT mr.*, p.title as property_title, p.monthly_rent,
                                    u.full_name as tenant_name,
                                    CASE 
                                        WHEN mr.status = 'pending' THEN 'Pending'
                                        WHEN mr.status = 'in_progress' THEN 'In Progress'
                                        WHEN mr.status = 'resolved' THEN 'Resolved'
                                        ELSE mr.status
                                    END as status_text
                             FROM maintenance_requests mr
                             JOIN properties p ON mr.property_id = p.property_id
                             JOIN users u ON mr.tenant_id = u.user_id";
                    if ($date_from && $date_to) {
                        $sql .= " WHERE mr.created_at BETWEEN ? AND ?";
                        $stmt = $db->prepare($sql);
                        if ($stmt) {
                            $report_data = $db->getMultiple($stmt, [$date_from, $date_to]);
                        } else {
                            $error_message = "SQL prepare error: " . $db->getLastError();
                            $report_data = [];
                        }
                    } elseif ($status_filter) {
                        $sql .= " WHERE mr.status = ?";
                        $stmt = $db->prepare($sql);
                        if ($stmt) {
                            $report_data = $db->getMultiple($stmt, [$status_filter]);
                        } else {
                            $error_message = "SQL prepare error: " . $db->getLastError();
                            $report_data = [];
                        }
                    } else {
                        $stmt = $db->prepare($sql);
                        if ($stmt) {
                            $report_data = $db->getMultiple($stmt);
                        } else {
                            $error_message = "SQL prepare error: " . $db->getLastError();
                            $report_data = [];
                        }
                    }
                }
            } else {
                $error_message = "Database error checking maintenance_requests table";
                $report_data = [];
            }
        } catch (Exception $e) {
            $error_message = "Error generating maintenance issues report: " . $e->getMessage();
            $report_data = [];
        }
        break;
        
    case 'user_activity':
        $report_title = 'User Activity Summary Report';
        try {
            $report_data = [];
            
            // Get user statistics
            $sql = "SELECT 
                        COUNT(CASE WHEN role = 'tenant' THEN 1 END) as total_tenants,
                        COUNT(CASE WHEN role = 'owner' THEN 1 END) as total_owners,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_users
                     FROM users";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $user_stats = $db->getSingle($stmt);
            } else {
                $user_stats = ['total_tenants' => 0, 'total_owners' => 0, 'active_users' => 0, 'inactive_users' => 0];
            }
            
            // Get requests count for selected period
            $requests_sql = "SELECT COUNT(*) as total_requests FROM rental_requests";
            if ($date_from && $date_to) {
                $requests_sql .= " WHERE created_at BETWEEN ? AND ?";
                $stmt = $db->prepare($requests_sql);
                if ($stmt) {
                    $requests_stats = $db->getSingle($stmt, [$date_from, $date_to]);
                } else {
                    $requests_stats = ['total_requests' => 0];
                }
            } else {
                $stmt = $db->prepare($requests_sql);
                if ($stmt) {
                    $requests_stats = $db->getSingle($stmt);
                } else {
                    $requests_stats = ['total_requests' => 0];
                }
            }
            
            $report_data = [
                'user_stats' => $user_stats,
                'requests_stats' => $requests_stats
            ];
        } catch (Exception $e) {
            $error_message = "Error generating user activity report: " . $e->getMessage();
            $report_data = [
                'user_stats' => ['total_tenants' => 0, 'total_owners' => 0, 'active_users' => 0, 'inactive_users' => 0],
                'requests_stats' => ['total_requests' => 0]
            ];
        }
        break;
        
    default:
        $report_title = 'Select a Report Type';
        break;
}

// Handle export requests
if (isset($_GET['download'])) {
    $export_format = $_GET['download'];
    
    // Check if we have valid data to export
    if (!empty($report_data)) {
        if ($export_format === 'csv') {
            exportToCSV($report_data, $report_title, $report_type);
        } elseif ($export_format === 'doc') {
            exportToDOC($report_data, $report_title, $report_type);
        }
    } else {
        // Show error message if no data
        $_SESSION['error'] = 'No data available to export for this report type. Error: ' . ($error_message ?? 'Unknown error');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?report_type=' . $report_type);
        exit;
    }
}

function exportToCSV($data, $title, $type) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $title . '.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");
    
    // Write headers based on report type
    switch($type) {
        case 'property_status':
            fputcsv($output, ['Property ID', 'Title', 'Owner Name', 'Location', 'Property Type', 'Status', 'Monthly Rent', 'Total Rentals', 'Active Rentals', 'Occupancy Rate (%)']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['property_id'],
                    $row['title'],
                    $row['owner_name'] ?? 'N/A',
                    ($row['location_name'] ?? '') . ', ' . ($row['subcity'] ?? ''),
                    $row['property_type'],
                    $row['status_text'],
                    $row['monthly_rent'],
                    $row['total_rentals'],
                    $row['active_rentals'],
                    round($row['occupancy_rate'], 2)
                ]);
            }
            break;
            
        case 'rental_activity':
            fputcsv($output, ['Agreement ID', 'Tenant Name', 'Property Title', 'Property Type', 'Start Date', 'End Date', 'Status', 'Monthly Rent']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['agreement_id'],
                    $row['tenant_name'],
                    $row['property_title'],
                    $row['property_type'],
                    $row['start_date'],
                    $row['end_date'],
                    $row['status_text'],
                    $row['monthly_rent']
                ]);
            }
            break;
            
        case 'pending_requests':
            fputcsv($output, ['Request ID', 'Tenant Name', 'Tenant Email', 'Tenant Phone', 'Property Title', 'Property Type', 'Request Date', 'Status', 'Message']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['request_id'],
                    $row['tenant_name'],
                    $row['tenant_email'],
                    $row['tenant_phone'],
                    $row['property_title'],
                    $row['property_type'],
                    $row['created_at'],
                    $row['status_text'],
                    $row['message']
                ]);
            }
            break;
            
        case 'payment_verification':
            fputcsv($output, ['Payment ID', 'Agreement ID', 'Tenant Name', 'Property Title', 'Amount', 'Payment Method', 'Reference Code', 'Status', 'Payment Date']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['payment_id'],
                    $row['agreement_id'],
                    $row['tenant_name'],
                    $row['property_title'],
                    $row['amount'],
                    $row['payment_method'],
                    $row['transaction_id'],
                    $row['status_text'],
                    $row['created_at']
                ]);
            }
            break;
            
        case 'maintenance_issues':
            fputcsv($output, ['Maintenance ID', 'Tenant Name', 'Property Title', 'Issue Type', 'Priority', 'Description', 'Reported Date', 'Status']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['maintenance_id'],
                    $row['tenant_name'],
                    $row['property_title'],
                    $row['issue_type'],
                    $row['priority'],
                    $row['description'],
                    $row['created_at'],
                    $row['status_text']
                ]);
            }
            break;
            
        case 'user_activity':
            fputcsv($output, ['User Activity Summary Report']);
            fputcsv($output, []);
            fputcsv($output, ['Total Tenants', $data['user_stats']['total_tenants']]);
            fputcsv($output, ['Total Owners', $data['user_stats']['total_owners']]);
            fputcsv($output, ['Active Users', $data['user_stats']['active_users']]);
            fputcsv($output, ['Inactive Users', $data['user_stats']['inactive_users']]);
            fputcsv($output, []);
            fputcsv($output, ['Total Requests (Selected Period)', $data['requests_stats']['total_requests']]);
            break;
    }
    
    fclose($output);
    exit;
}

function exportToDOC($data, $title, $type) {
    // DOC export using HTML format compatible with Microsoft Word
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '.doc"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Pragma: no-cache');
    
    $html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <meta name=ProgId content=Word.Document>
    <meta name=Generator content="Microsoft Word">
    <meta name=Originator content="Microsoft Word">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        @page Section1 {
            size:8.5in 11.0in;
            margin:1.0in 1.0in 1.0in 1.0in;
        }
        div.Section1 {page:Section1;}
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; font-size: 18px; font-weight: bold; }
        h2 { color: #34495e; margin-top: 20px; font-size: 16px; font-weight: bold; }
        h3 { color: #2c3e50; margin-top: 15px; font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; border: 1px solid #000; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .summary-box { background-color: #f8f9fa; border: 1px solid #000; padding: 15px; margin: 10px 0; }
        .summary-item { margin: 10px 0; display: flex; justify-content: space-between; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .no-data { text-align: center; padding: 40px; color: #666; font-style: italic; }
        .header-info { text-align: center; margin-bottom: 30px; }
        .footer-info { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="Section1">
        <div class="header-info">
            <h1>' . htmlspecialchars($title) . '</h1>
            <p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Generated by:</strong> Aksum House Rental Management System</p>
        </div>';
    
    switch($type) {
        case 'property_status':
            if (is_array($data) && !empty($data)) {
                $html .= '<h2>Property Status Report</h2>
                    <table>
                        <tr>
                            <th>Property ID</th>
                            <th>Title</th>
                            <th>Owner Name</th>
                            <th>Location</th>
                            <th>Property Type</th>
                            <th>Status</th>
                            <th>Monthly Rent</th>
                            <th>Total Rentals</th>
                            <th>Active Rentals</th>
                            <th>Occupancy Rate</th>
                        </tr>';
                foreach ($data as $row) {
                    $html .= '<tr>
                        <td>' . $row['property_id'] . '</td>
                        <td>' . htmlspecialchars($row['title']) . '</td>
                        <td>' . htmlspecialchars($row['owner_name'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($row['location_name'] ?? '') . ', ' . htmlspecialchars($row['subcity'] ?? '') . '</td>
                        <td>' . htmlspecialchars(ucfirst($row['property_type'])) . '</td>
                        <td>' . htmlspecialchars($row['status_text']) . '</td>
                        <td class="text-right">ETB ' . number_format($row['monthly_rent'], 0) . '</td>
                        <td class="text-center">' . $row['total_rentals'] . '</td>
                        <td class="text-center">' . $row['active_rentals'] . '</td>
                        <td class="text-center">' . round($row['occupancy_rate'], 1) . '%</td>
                    </tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<div class="no-data">No data available for Property Status Report.</div>';
            }
            break;
            
        case 'rental_activity':
            if (is_array($data) && !empty($data)) {
                $html .= '<h2>Rental Activity Report</h2>
                    <table>
                        <tr>
                            <th>Agreement ID</th>
                            <th>Tenant Name</th>
                            <th>Property Title</th>
                            <th>Property Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Monthly Rent</th>
                        </tr>';
                foreach ($data as $row) {
                    $html .= '<tr>
                        <td>' . $row['agreement_id'] . '</td>
                        <td>' . htmlspecialchars($row['tenant_name']) . '</td>
                        <td>' . htmlspecialchars($row['property_title']) . '</td>
                        <td>' . htmlspecialchars($row['property_type']) . '</td>
                        <td>' . $row['start_date'] . '</td>
                        <td>' . $row['end_date'] . '</td>
                        <td>' . htmlspecialchars($row['status_text']) . '</td>
                        <td class="text-right">ETB ' . number_format($row['monthly_rent'], 0) . '</td>
                    </tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<div class="no-data">No data available for Rental Activity Report.</div>';
            }
            break;
            
        case 'pending_requests':
            if (is_array($data) && !empty($data)) {
                $html .= '<h2>Pending Rental Requests Report</h2>
                    <table>
                        <tr>
                            <th>Request ID</th>
                            <th>Tenant Name</th>
                            <th>Tenant Email</th>
                            <th>Tenant Phone</th>
                            <th>Property Title</th>
                            <th>Property Type</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>';
                foreach ($data as $row) {
                    $html .= '<tr>
                        <td>' . $row['request_id'] . '</td>
                        <td>' . htmlspecialchars($row['tenant_name']) . '</td>
                        <td>' . htmlspecialchars($row['tenant_email']) . '</td>
                        <td>' . htmlspecialchars($row['tenant_phone']) . '</td>
                        <td>' . htmlspecialchars($row['property_title']) . '</td>
                        <td>' . htmlspecialchars($row['property_type']) . '</td>
                        <td>' . $row['created_at'] . '</td>
                        <td>' . htmlspecialchars($row['status_text']) . '</td>
                        <td>' . htmlspecialchars(substr($row['message'], 0, 100)) . '...</td>
                    </tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<div class="no-data">No data available for Pending Rental Requests Report.</div>';
            }
            break;
            
        case 'payment_verification':
            if (is_array($data) && !empty($data)) {
                $html .= '<h2>Payment Verification Report</h2>
                    <table>
                        <tr>
                            <th>Payment ID</th>
                            <th>Agreement ID</th>
                            <th>Tenant Name</th>
                            <th>Property Title</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Reference Code</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                        </tr>';
                foreach ($data as $row) {
                    $html .= '<tr>
                        <td>' . $row['payment_id'] . '</td>
                        <td>' . $row['agreement_id'] . '</td>
                        <td>' . htmlspecialchars($row['tenant_name']) . '</td>
                        <td>' . htmlspecialchars($row['property_title']) . '</td>
                        <td class="text-right">ETB ' . number_format($row['amount'], 0) . '</td>
                        <td>' . htmlspecialchars($row['payment_method']) . '</td>
                        <td>' . htmlspecialchars($row['transaction_id']) . '</td>
                        <td>' . htmlspecialchars($row['status_text']) . '</td>
                        <td>' . $row['created_at'] . '</td>
                    </tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<div class="no-data">No data available for Payment Verification Report.</div>';
            }
            break;
            
        case 'maintenance_issues':
            if (is_array($data) && !empty($data)) {
                $html .= '<h2>Maintenance and Issue Report</h2>
                    <table>
                        <tr>
                            <th>Maintenance ID</th>
                            <th>Tenant Name</th>
                            <th>Property Title</th>
                            <th>Issue Type</th>
                            <th>Priority</th>
                            <th>Description</th>
                            <th>Reported Date</th>
                            <th>Status</th>
                        </tr>';
                foreach ($data as $row) {
                    $html .= '<tr>
                        <td>' . $row['maintenance_id'] . '</td>
                        <td>' . htmlspecialchars($row['tenant_name']) . '</td>
                        <td>' . htmlspecialchars($row['property_title']) . '</td>
                        <td>' . htmlspecialchars($row['issue_type']) . '</td>
                        <td>' . htmlspecialchars($row['priority']) . '</td>
                        <td>' . htmlspecialchars(substr($row['description'], 0, 100)) . '...</td>
                        <td>' . $row['created_at'] . '</td>
                        <td>' . htmlspecialchars($row['status_text']) . '</td>
                    </tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<div class="no-data">No data available for Maintenance and Issue Report.</div>';
            }
            break;
            
        case 'user_activity':
            if (is_array($data) && isset($data['user_stats']) && isset($data['requests_stats'])) {
                $html .= '<h2>User Activity Summary Report</h2>
                    <div class="summary-box">
                        <h3>User Statistics</h3>
                        <div class="summary-item"><strong>Total Tenants:</strong> ' . $data['user_stats']['total_tenants'] . '</div>
                        <div class="summary-item"><strong>Total Owners:</strong> ' . $data['user_stats']['total_owners'] . '</div>
                        <div class="summary-item"><strong>Active Users:</strong> ' . $data['user_stats']['active_users'] . '</div>
                        <div class="summary-item"><strong>Inactive Users:</strong> ' . $data['user_stats']['inactive_users'] . '</div>
                    </div>
                    <div class="summary-box">
                        <h3>Request Statistics</h3>
                        <div class="summary-item"><strong>Total Requests:</strong> ' . $data['requests_stats']['total_requests'] . '</div>
                    </div>';
            } else {
                $html .= '<div class="no-data">No data available for User Activity Summary Report.</div>';
            }
            break;
            
        default:
            $html .= '<div class="no-data">No data available for this report type.</div>';
            break;
    }
    
    $html .= '
        <div class="footer-info">
            <p>This report was generated by Aksum House Rental Management System</p>
            <p>For more information, please contact the system administrator</p>
        </div>
    </div>
</body>
</html>';
    
    echo $html;
    exit;
}

include '../includes/header.php';
?>

<style>
    .reports-container {
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        min-height: 100vh;
        padding: 20px 0;
    }
    .report-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 25px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    }
    .report-header {
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        color: white;
        padding: 25px 30px;
        border-radius: 20px 20px 0 0;
        border: none;
    }
    .report-tabs {
        background: #f8f9fa;
        padding: 20px 30px 0;
        border-radius: 0;
        border-bottom: 2px solid #e9ecef;
    }
    .nav-tabs .nav-link {
        color: #708090;
        border: none;
        padding: 12px 24px;
        margin-right: 10px;
        border-radius: 10px 10px 0 0;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    .nav-tabs .nav-link:hover {
        background: rgba(112, 128, 144, 0.1);
        color: #708090;
        transform: translateY(-2px);
    }
    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 15px rgba(112, 128, 144, 0.3);
    }
    .report-table {
        padding: 30px;
    }
    .table-responsive {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    .table {
        margin-bottom: 0;
        background: white;
    }
    .table thead th {
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        color: white;
        font-weight: 600;
        padding: 15px;
        border: none;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
        transition: background-color 0.3s ease;
    }
    .table tbody tr:hover td {
        background-color: rgba(112, 128, 144, 0.05);
    }
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-available { background-color: #d4edda; color: #155724; }
    .status-rented { background-color: #cce5ff; color: #004085; }
    .status-maintenance { background-color: #fff3cd; color: #856404; }
    .status-unavailable { background-color: #f8d7da; color: #721c24; }
    .status-active { background-color: #d4edda; color: #155724; }
    .status-expired { background-color: #f8d7da; color: #721c24; }
    .status-terminated { background-color: #e2e3e5; color: #383d41; }
    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-approved { background-color: #d4edda; color: #155724; }
    .status-rejected { background-color: #f8d7da; color: #721c24; }
    .status-completed { background-color: #d1ecf1; color: #0c5460; }
    .status-in_progress { background-color: #cce5ff; color: #004085; }
    .status-resolved { background-color: #d4edda; color: #155724; }
    
    .priority-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .priority-low { background-color: #d4edda; color: #155724; }
    .priority-medium { background-color: #fff3cd; color: #856404; }
    .priority-high { background-color: #f8d7da; color: #721c24; }
    .priority-emergency { background-color: #721c24; color: #ffffff; }
    
    .summary-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .summary-card h6 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 8px;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }
    .summary-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    .summary-value {
        font-weight: 600;
        font-size: 14px;
    }
    .btn-export {
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        margin-right: 10px;
    }
    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(112, 128, 144, 0.3);
        color: white;
    }
    .filter-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease;
        border: 1px solid #e9ecef;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .stat-label {
        color: #708090;
        font-size: 0.9rem;
        margin-top: 5px;
    }
    .report-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .overview-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    .overview-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    .overview-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .overview-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    .overview-desc {
        color: #708090;
        font-size: 0.9rem;
    }
    
    /* Print-specific styles */
    @media print {
        body {
            margin: 0;
            padding: 0;
        }
        .container-fluid {
            max-width: none;
            padding: 0;
        }
        .row {
            margin: 0;
        }
        .col-lg-3 {
            display: none !important;
        }
        .col-lg-9 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        .reports-container {
            background: white !important;
            padding: 0 !important;
        }
        .report-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            margin-bottom: 20px !important;
            border-radius: 0 !important;
        }
        .report-header {
            background: white !important;
            color: black !important;
            border-bottom: 2px solid #ddd !important;
        }
        .report-header h2, .report-header p {
            color: black !important;
        }
        .btn, .nav-tabs, .filter-section {
            display: none !important;
        }
        .report-table {
            padding: 20px !important;
        }
        .table {
            font-size: 12px;
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 8px;
            border: 1px solid #000;
            background: white !important;
            color: black !important;
        }
        .table th {
            background: #f5f5f5 !important;
            color: black !important;
            font-weight: bold;
        }
        .status-badge {
            border: 1px solid #000 !important;
            background: white !important;
            color: black !important;
            padding: 2px 6px;
            font-size: 10px;
        }
        .alert {
            display: none !important;
        }
    }
</style>

<div class="container-fluid reports-container py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="report-card">
                <div class="report-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Operational Reports</h2>
                            <p class="mb-0 opacity-90">Comprehensive reports for property management and monitoring</p>
                        </div>
                        <div>
                            <a href="?report_type=<?php echo $report_type; ?>&download=csv" class="btn btn-success btn-sm me-2">
                                <i class="fas fa-download me-1"></i> download CSV
                            </a>
                            <a href="?report_type=<?php echo $report_type; ?>&download=doc" class="btn btn-danger btn-sm me-2">
                                <i class="fas fa-file-word me-1"></i> download DOC
                            </a>
                            <button class="btn btn-light btn-sm" onclick="printReport()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            
            <!-- Report Tabs -->
            <div class="report-card">
                <div class="report-tabs">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'property_status' ? 'active' : ''; ?>" 
                               href="?report_type=property_status">
                                <i class="fas fa-home me-2"></i>Property Status
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'rental_activity' ? 'active' : ''; ?>" 
                               href="?report_type=rental_activity">
                                <i class="fas fa-chart-line me-2"></i>Rental Activity
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'pending_requests' ? 'active' : ''; ?>" 
                               href="?report_type=pending_requests">
                                <i class="fas fa-inbox me-2"></i>Pending Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'payment_verification' ? 'active' : ''; ?>" 
                               href="?report_type=payment_verification">
                                <i class="fas fa-credit-card me-2"></i>Payment Verification
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'maintenance_issues' ? 'active' : ''; ?>" 
                               href="?report_type=maintenance_issues">
                                <i class="fas fa-tools me-2"></i>Maintenance Issues
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'user_activity' ? 'active' : ''; ?>" 
                               href="?report_type=user_activity">
                                <i class="fas fa-users me-2"></i>User Activity
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Report Content -->
                <div class="tab-content">
                    <div class="tab-pane fade <?php echo $report_type === 'property_status' ? 'show active' : ''; ?>" 
                         id="property_status" role="tabpanel">
                        <div class="report-table">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php 
                                    echo htmlspecialchars($_SESSION['error']);
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <h5>Property Status Report</h5>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="report_type" value="property_status">
                                    <div class="col-md-4">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter me-1"></i> Apply Filters
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($report_data)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Property ID</th>
                                                <th>Title</th>
                                                <th>Owner Name</th>
                                                <th>Location</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Monthly Rent</th>
                                                <th>Total Rentals</th>
                                                <th>Active Rentals</th>
                                                <th>Occupancy Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                                <tr>
                                                    <td>#<?php echo $row['property_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['owner_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['location_name'] ?? '') . ', ' . htmlspecialchars($row['subcity'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($row['property_type'])); ?></td>
                                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status_text']; ?></span></td>
                                                    <td>ETB <?php echo number_format($row['monthly_rent'], 0); ?></td>
                                                    <td><?php echo $row['total_rentals']; ?></td>
                                                    <td><?php echo $row['active_rentals']; ?></td>
                                                    <td><?php echo round($row['occupancy_rate'], 1); ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No data found</h5>
                                    <p class="text-muted">Try adjusting your filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Other report tabs would go here with similar structure -->
                    <div class="tab-pane fade <?php echo $report_type === 'rental_activity' ? 'show active' : ''; ?>" 
                         id="rental_activity" role="tabpanel">
                        <div class="report-table">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php 
                                    echo htmlspecialchars($_SESSION['error']);
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <h5>Rental Activity Report</h5>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="report_type" value="rental_activity">
                                    <div class="col-md-4">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Report Data -->
                            <?php if (!empty($report_data)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Agreement ID</th>
                                                <th>Tenant Name</th>
                                                <th>Property Title</th>
                                                <th>Property Type</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Status</th>
                                                <th>Monthly Rent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                                <tr>
                                                    <td><?php echo $row['agreement_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($row['property_type'])); ?></td>
                                                    <td><?php echo $row['start_date']; ?></td>
                                                    <td><?php echo $row['end_date']; ?></td>
                                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status_text']; ?></span></td>
                                                    <td>ETB <?php echo number_format($row['monthly_rent'], 0); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No rental activity found</h5>
                                    <p class="text-muted">Try adjusting your filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo $report_type === 'pending_requests' ? 'show active' : ''; ?>" 
                         id="pending_requests" role="tabpanel">
                        <div class="report-table">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php 
                                    echo htmlspecialchars($_SESSION['error']);
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <h5>Pending Rental Requests Report</h5>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="report_type" value="pending_requests">
                                    <div class="col-md-4">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Report Data -->
                            <?php if (!empty($report_data)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Tenant Name</th>
                                                <th>Tenant Email</th>
                                                <th>Tenant Phone</th>
                                                <th>Property Title</th>
                                                <th>Property Type</th>
                                                <th>Request Date</th>
                                                <th>Status</th>
                                                <th>Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                                <tr>
                                                    <td><?php echo $row['request_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['tenant_email']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['tenant_phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($row['property_type'])); ?></td>
                                                    <td><?php echo $row['created_at']; ?></td>
                                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status_text']; ?></span></td>
                                                    <td><?php echo htmlspecialchars(substr($row['message'], 0, 50)) . '...'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No pending requests found</h5>
                                    <p class="text-muted">Try adjusting your filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo $report_type === 'payment_verification' ? 'show active' : ''; ?>" 
                         id="payment_verification" role="tabpanel">
                        <div class="report-table">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php 
                                    echo htmlspecialchars($_SESSION['error']);
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <h5>Payment Verification Report</h5>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="report_type" value="payment_verification">
                                    <div class="col-md-4">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Report Data -->
                            <?php if (!empty($report_data)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Agreement ID</th>
                                                <th>Tenant Name</th>
                                                <th>Property Title</th>
                                                <th>Amount</th>
                                                <th>Payment Method</th>
                                                <th>Reference Code</th>
                                                <th>Status</th>
                                                <th>Payment Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                                <tr>
                                                    <td><?php echo $row['payment_id']; ?></td>
                                                    <td><?php echo $row['agreement_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['property_title']); ?></td>
                                                    <td>ETB <?php echo number_format($row['amount'], 0); ?></td>
                                                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status_text']; ?></span></td>
                                                    <td><?php echo $row['created_at']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No payments pending verification</h5>
                                    <p class="text-muted">Try adjusting your filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo $report_type === 'maintenance_issues' ? 'show active' : ''; ?>" 
                         id="maintenance_issues" role="tabpanel">
                        <div class="report-table">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php 
                                    echo htmlspecialchars($_SESSION['error']);
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <h5>Maintenance and Issue Report</h5>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="report_type" value="maintenance_issues">
                                    <div class="col-md-3">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="">All Status</option>
                                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Report Data -->
                            <?php if (!empty($report_data)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Maintenance ID</th>
                                                <th>Tenant Name</th>
                                                <th>Property Title</th>
                                                <th>Issue Type</th>
                                                <th>Priority</th>
                                                <th>Description</th>
                                                <th>Reported Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                                <tr>
                                                    <td><?php echo $row['maintenance_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['issue_type']); ?></td>
                                                    <td><span class="priority-badge priority-<?php echo $row['priority']; ?>"><?php echo htmlspecialchars($row['priority']); ?></span></td>
                                                    <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                                                    <td><?php echo $row['created_at']; ?></td>
                                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status_text']; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No maintenance issues found</h5>
                                    <p class="text-muted">Try adjusting your filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo $report_type === 'user_activity' ? 'show active' : ''; ?>" 
                         id="user_activity" role="tabpanel">
                        <div class="report-table">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php 
                                    echo htmlspecialchars($_SESSION['error']);
                                    unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <h5>User Activity Summary Report</h5>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="report_type" value="user_activity">
                                    <div class="col-md-4">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Report Data -->
                            <?php if (!empty($report_data) && isset($report_data['user_stats'])): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="summary-card">
                                            <h6><i class="fas fa-users me-2"></i>User Statistics</h6>
                                            <div class="summary-item">
                                                <span>Total Tenants:</span>
                                                <span class="summary-value"><?php echo $report_data['user_stats']['total_tenants']; ?></span>
                                            </div>
                                            <div class="summary-item">
                                                <span>Total Owners:</span>
                                                <span class="summary-value"><?php echo $report_data['user_stats']['total_owners']; ?></span>
                                            </div>
                                            <div class="summary-item">
                                                <span>Active Users:</span>
                                                <span class="summary-value text-success"><?php echo $report_data['user_stats']['active_users']; ?></span>
                                            </div>
                                            <div class="summary-item">
                                                <span>Inactive Users:</span>
                                                <span class="summary-value text-muted"><?php echo $report_data['user_stats']['inactive_users']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="summary-card">
                                            <h6><i class="fas fa-inbox me-2"></i>Request Statistics</h6>
                                            <div class="summary-item">
                                                <span>Total Requests:</span>
                                                <span class="summary-value"><?php echo $report_data['requests_stats']['total_requests']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No user activity data found</h5>
                                    <p class="text-muted">Try adjusting your filters</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($report_type === 'default'): ?>
                        <div class="tab-pane fade show active" role="tabpanel">
                            <div class="report-table">
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Select a Report Type</h5>
                                    <p class="text-muted">Choose a report from the tabs above to view data</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Bootstrap tabs
document.addEventListener('DOMContentLoaded', function() {
    var triggerTabList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'))
    triggerTabList.forEach(function (triggerEl) {
        new bootstrap.Tab(triggerEl)
    });
});

// Print report using CSS print styles
function printReport() {
    window.print();
}
</script>

<?php include '../includes/footer.php'; ?>
