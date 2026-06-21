<?php
require_once '../includes/config.php';
$session->requireRole('owner');
$title = "Reports - Property Owner Dashboard";

// Suppress warnings for cleaner display
error_reporting(E_ERROR | E_PARSE);

$owner_id = $session->getUserId();

// Handle report generation and download
$report_type = $_GET['report_type'] ?? 'property_listing';
$download_format = $_GET['download'] ?? '';

// Get ALL data FIRST before handling downloads
// Get owner's properties
$properties = [];
try {
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM rental_requests rr WHERE rr.property_id = p.property_id AND rr.status = 'pending') as pending_requests,
            (SELECT COUNT(*) FROM rental_agreements ra WHERE ra.property_id = p.property_id AND ra.status = 'active') as active_rentals,
            (SELECT COUNT(*) FROM maintenance_requests mr WHERE mr.property_id = p.property_id AND mr.status = 'pending') as pending_maintenance
            FROM properties p 
            WHERE p.owner_id = ? 
            ORDER BY p.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $properties = $result ?? [];
} catch (Exception $e) {
    $properties = [];
}

// Get rental history
$rental_history = [];
try {
    $sql = "SELECT ra.*, p.title as property_title, u.full_name as tenant_name,
            CASE 
                WHEN ra.end_date < CURDATE() THEN 'Completed'
                WHEN ra.status = 'active' THEN 'Active'
                WHEN ra.status = 'cancelled' THEN 'Cancelled'
                ELSE 'Unknown'
            END as rental_status
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            JOIN users u ON ra.tenant_id = u.user_id
            WHERE p.owner_id = ?
            ORDER BY ra.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $rental_history = $result ?? [];
} catch (Exception $e) {
    $rental_history = [];
}

// Get payment/income data
$payment_data = [];
try {
    $sql = "SELECT p.*, pa.amount, pa.payment_date, pa.status as payment_status,
            u.full_name as tenant_name, pr.title as property_title
            FROM payments pa
            JOIN rental_agreements ra ON pa.agreement_id = ra.agreement_id
            JOIN properties pr ON ra.property_id = pr.property_id
            JOIN users u ON pa.tenant_id = u.user_id
            JOIN properties p ON pr.property_id = p.property_id
            WHERE pr.owner_id = ?
            ORDER BY pa.payment_date DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $payment_data = $result ?? [];
} catch (Exception $e) {
    $payment_data = [];
}

// Get pending requests
$pending_requests = [];
try {
    $sql = "SELECT rr.*, p.title as property_title, u.full_name as tenant_name
            FROM rental_requests rr
            JOIN properties p ON rr.property_id = p.property_id
            JOIN users u ON rr.tenant_id = u.user_id
            WHERE p.owner_id = ? AND rr.status = 'pending'
            ORDER BY rr.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $pending_requests = $result ?? [];
} catch (Exception $e) {
    $pending_requests = [];
}

// Get maintenance data
$maintenance_data = [];
try {
    $sql = "SELECT mr.*, p.title as property_title, u.full_name as tenant_name
            FROM maintenance_requests mr
            JOIN properties p ON mr.property_id = p.property_id
            JOIN users u ON mr.tenant_id = u.user_id
            WHERE p.owner_id = ?
            ORDER BY mr.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $maintenance_data = $result ?? [];
} catch (Exception $e) {
    $maintenance_data = [];
}

// NOW handle downloads after data is loaded
if ($download_format) {
    $filename = 'Report_' . date('Y-m-d_H-i-s');
    
    if ($download_format === 'csv') {
        // Very simple CSV with actual data
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        // Add CSV content
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, ['Report Data']);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, ['Report Type: ' . $report_type]);
        
        // Add actual data based on report type
        if ($report_type === 'property_listing' && !empty($properties)) {
            fputcsv($output, ['Properties:']);
            foreach ($properties as $p) {
                fputcsv($output, [
                    ($p['title'] ?? 'Unknown'),
                    'ETB ' . number_format(($p['monthly_rent'] ?? 0), 2),
                    ucfirst(($p['status'] ?? 'unknown'))
                ]);
            }
        } elseif ($report_type === 'rental_history' && !empty($rental_history)) {
            fputcsv($output, ['Rental History:']);
            foreach ($rental_history as $r) {
                fputcsv($output, [
                    ($r['property_title'] ?? 'Unknown'),
                    ($r['tenant_name'] ?? 'Unknown'),
                    'ETB ' . number_format(($r['monthly_rent'] ?? 0), 2)
                ]);
            }
        } else {
            fputcsv($output, ['No data available']);
        }
        
        fclose($output);
        exit;
        
    } elseif ($download_format === 'doc') {
        // Download as DOC format
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="' . $filename . '.doc"');
        
        // Create HTML content for Word document
        $content = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
        $content .= '<head><meta charset="utf-8"><title>' . strtoupper(str_replace('_', ' ', $report_type)) . ' REPORT</title></head><body>';
        
        $content .= '<h1>' . strtoupper(str_replace('_', ' ', $report_type)) . ' REPORT</h1>';
        $content .= '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        $content .= '<hr>';
        
        // Add actual data based on report type
        if ($report_type === 'property_listing' && !empty($properties)) {
            $content .= '<h2>PROPERTIES LISTING</h2>';
            $content .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $content .= '<tr style="background-color: #f2f2f2; font-weight: bold;">';
            $content .= '<th>Property</th><th>Rent</th><th>Status</th><th>Type</th><th>Bedrooms</th><th>Bathrooms</th>';
            $content .= '</tr>';
            
            foreach ($properties as $p) {
                $content .= '<tr>';
                $content .= '<td>' . htmlspecialchars($p['title'] ?? 'Unknown') . '</td>';
                $content .= '<td>ETB ' . number_format(($p['monthly_rent'] ?? 0), 2) . '</td>';
                $content .= '<td>' . ucfirst(($p['status'] ?? 'unknown')) . '</td>';
                $content .= '<td>' . htmlspecialchars($p['property_type'] ?? 'Unknown') . '</td>';
                $content .= '<td>' . ($p['bedrooms'] ?? 'N/A') . '</td>';
                $content .= '<td>' . ($p['bathrooms'] ?? 'N/A') . '</td>';
                $content .= '</tr>';
            }
            $content .= '</table>';
            
        } elseif ($report_type === 'rental_history' && !empty($rental_history)) {
            $content .= '<h2>RENTAL HISTORY</h2>';
            $content .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $content .= '<tr style="background-color: #f2f2f2; font-weight: bold;">';
            $content .= '<th>Property</th><th>Tenant</th><th>Rent</th><th>Start Date</th><th>End Date</th><th>Status</th>';
            $content .= '</tr>';
            
            foreach ($rental_history as $r) {
                $content .= '<tr>';
                $content .= '<td>' . htmlspecialchars($r['property_title'] ?? 'Unknown') . '</td>';
                $content .= '<td>' . htmlspecialchars($r['tenant_name'] ?? 'Unknown') . '</td>';
                $content .= '<td>ETB ' . number_format(($r['monthly_rent'] ?? 0), 2) . '</td>';
                $content .= '<td>' . ($r['start_date'] ?? 'N/A') . '</td>';
                $content .= '<td>' . ($r['end_date'] ?? 'N/A') . '</td>';
                $content .= '<td>' . ($r['rental_status'] ?? 'Unknown') . '</td>';
                $content .= '</tr>';
            }
            $content .= '</table>';
            
        } elseif ($report_type === 'income' && !empty($payment_data)) {
            $content .= '<h2>INCOME REPORT</h2>';
            $content .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $content .= '<tr style="background-color: #f2f2f2; font-weight: bold;">';
            $content .= '<th>Property</th><th>Tenant</th><th>Amount</th><th>Payment Date</th><th>Status</th>';
            $content .= '</tr>';
            
            foreach ($payment_data as $p) {
                $content .= '<tr>';
                $content .= '<td>' . htmlspecialchars($p['property_title'] ?? 'Unknown') . '</td>';
                $content .= '<td>' . htmlspecialchars($p['tenant_name'] ?? 'Unknown') . '</td>';
                $content .= '<td>ETB ' . number_format(($p['amount'] ?? 0), 2) . '</td>';
                $content .= '<td>' . ($p['payment_date'] ?? 'N/A') . '</td>';
                $content .= '<td>' . ucfirst(($p['payment_status'] ?? 'unknown')) . '</td>';
                $content .= '</tr>';
            }
            $content .= '</table>';
            
        } else {
            $content .= '<p>No data available for this report type.</p>';
        }
        
        $content .= '</body></html>';
        
        echo $content;
        exit;
    }
}

// Add HTML structure for page display
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reports-container {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .report-header {
            background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
            color: white;
            padding: 30px;
            min-height: 140px;
        }
        .report-header h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .report-header p {
            margin-bottom: 0;
            font-size: 1.1rem;
            opacity: 0.95;
            line-height: 1.4;
        }
        .report-header .d-flex {
            flex-wrap: wrap;
            gap: 25px;
            align-items: center;
        }
        @media (max-width: 768px) {
            .report-header .d-flex {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .report-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .report-table table {
            margin: 0;
        }
        .report-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .report-tabs {
            border-bottom: 2px solid #dee2e6;
            background: white;
            border-radius: 8px 8px 0 0;
        }
        .report-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 15px 25px;
            font-weight: 500;
        }
        .report-tabs .nav-link.active {
            color: #667eea;
            background: transparent;
            border-bottom: 3px solid #667eea;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-available { background: #d4edda; color: #155724; }
        .status-rented { background: #cce5ff; color: #004085; }
        .status-maintenance { background: #fff3cd; color: #856404; }
        .status-inactive { background: #f8d7da; color: #721c24; }

        /* Print-specific styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .print-area .report-card {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
            .print-area .stat-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .print-area .report-tabs {
                display: none;
            }
            .print-area .tab-pane {
                display: block !important;
                opacity: 1 !important;
            }
            .print-area .table {
                font-size: 12px;
            }
            .print-area h2, .print-area h3, .print-area h4, .print-area h5 {
                color: #000 !important;
            }
        }
    </style>
</head>
<body>';

// Get owner's properties
$properties = [];
try {
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM rental_requests rr WHERE rr.property_id = p.property_id AND rr.status = 'pending') as pending_requests,
            (SELECT COUNT(*) FROM rental_agreements ra WHERE ra.property_id = p.property_id AND ra.status = 'active') as active_rentals,
            (SELECT COUNT(*) FROM maintenance_requests mr WHERE mr.property_id = p.property_id AND mr.status = 'pending') as pending_maintenance
            FROM properties p 
            WHERE p.owner_id = ? 
            ORDER BY p.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $properties = $result ?? [];
} catch (Exception $e) {
    $properties = [];
}

// Get rental history
$rental_history = [];
try {
    $sql = "SELECT ra.*, p.title as property_title, u.full_name as tenant_name,
            CASE 
                WHEN ra.end_date < CURDATE() THEN 'Completed'
                WHEN ra.status = 'active' THEN 'Active'
                WHEN ra.status = 'cancelled' THEN 'Cancelled'
                ELSE 'Unknown'
            END as rental_status
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            JOIN users u ON ra.tenant_id = u.user_id
            WHERE p.owner_id = ?
            ORDER BY ra.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $rental_history = $result ?? [];
} catch (Exception $e) {
    $rental_history = [];
}

// Get payment/income data
$payment_data = [];
try {
    $sql = "SELECT p.*, pa.amount, pa.payment_date, pa.status as payment_status,
            u.full_name as tenant_name, pr.title as property_title
            FROM payments pa
            JOIN rental_agreements ra ON pa.agreement_id = ra.agreement_id
            JOIN properties pr ON ra.property_id = pr.property_id
            JOIN users u ON pa.tenant_id = u.user_id
            JOIN properties p ON pr.property_id = p.property_id
            WHERE pr.owner_id = ?
            ORDER BY pa.payment_date DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $payment_data = $result ?? [];
} catch (Exception $e) {
    $payment_data = [];
}

// Get pending requests
$pending_requests = [];
try {
    $sql = "SELECT rr.*, p.title as property_title, u.full_name as tenant_name
            FROM rental_requests rr
            JOIN properties p ON rr.property_id = p.property_id
            JOIN users u ON rr.tenant_id = u.user_id
            WHERE p.owner_id = ? AND rr.status = 'pending'
            ORDER BY rr.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $pending_requests = $result ?? [];
} catch (Exception $e) {
    $pending_requests = [];
}

// Get maintenance data
$maintenance_data = [];
try {
    $sql = "SELECT mr.*, p.title as property_title, u.full_name as tenant_name
            FROM maintenance_requests mr
            JOIN properties p ON mr.property_id = p.property_id
            JOIN users u ON mr.tenant_id = u.user_id
            WHERE p.owner_id = ?
            ORDER BY mr.created_at DESC";
    $stmt = $db->prepare($sql);
    $result = $db->getMultiple($stmt, [$owner_id]);
    $maintenance_data = $result ?? [];
} catch (Exception $e) {
    $maintenance_data = [];
}

// Calculate summary statistics
$verified_payments = array_filter($payment_data, fn($p) => $p['payment_status'] == 'verified');
$total_income = array_sum(array_column($verified_payments, 'amount'));

$summary_stats = [
    'total_properties' => count($properties),
    'active_properties' => count(array_filter($properties, fn($p) => $p['status'] == 'available')),
    'rented_properties' => count(array_filter($properties, fn($p) => $p['status'] == 'rented')),
    'total_income' => $total_income,
    'pending_requests' => count($pending_requests),
    'pending_maintenance' => count(array_filter($maintenance_data, fn($m) => $m['status'] == 'pending'))
];

include '../includes/header.php';
?>

<div class="main-content">

<style>
.reports-container {
    background: #f8f9fa;
    min-height: 100vh;
}
.report-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
}
.report-header {
    background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
    color: white;
    padding: 30px;
    min-height: 140px;
}
.report-header h2 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 8px;
}
.report-header p {
    margin-bottom: 0;
    font-size: 1.1rem;
    opacity: 0.95;
    line-height: 1.4;
}
.report-header .d-flex {
    flex-wrap: wrap;
    gap: 25px;
    align-items: center;
}
@media (max-width: 768px) {
    .report-header .d-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
}
.report-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}
.report-table table {
    margin: 0;
}
.report-table th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}
.report-tabs {
    border-bottom: 2px solid #dee2e6;
    background: white;
    border-radius: 8px 8px 0 0;
}
.report-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 15px 25px;
    font-weight: 500;
}
.report-tabs .nav-link.active {
    color: #667eea;
    background: transparent;
    border-bottom: 3px solid #667eea;
}
.download-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
}
.download-btn:hover {
    background: #218838;
}
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-available { background: #d4edda; color: #155724; }
.status-rented { background: #cce5ff; color: #004085; }
.status-maintenance { background: #fff3cd; color: #856404; }
.status-inactive { background: #f8d7da; color: #721c24; }

/* Print-specific styles */
@media print {
    body * {
        visibility: hidden;
    }
    .print-area, .print-area * {
        visibility: visible;
    }
    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
    .print-area .report-card {
        box-shadow: none;
        border: 1px solid #ddd;
        page-break-inside: avoid;
    }
    .print-area .stat-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    .print-area .report-tabs {
        display: none;
    }
    .print-area .tab-pane {
        display: block !important;
        opacity: 1 !important;
    }
    .print-area .table {
        font-size: 12px;
    }
    .print-area h2, .print-area h3, .print-area h4, .print-area h5 {
        color: #000 !important;
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
                            <h2 class="mb-1">Reports & Analytics</h2>
                            <p class="mb-0 opacity-90">Comprehensive reports for your property portfolio</p>
                        </div>
                        <div>
                            <button class="btn btn-light me-2 no-print" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            
                            <a href="?report_type=property_listing&download=csv" class="btn btn-success me-2 no-print" download>
                                <i class="fas fa-file-csv me-2"></i>Download CSV
                            </a>
                            
                            <a href="?report_type=property_listing&download=doc" class="btn btn-primary me-2 no-print" download>
                                <i class="fas fa-file-word me-2"></i>Download DOC
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="row mb-4 print-area">
                <div class="col-md-2 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $summary_stats['total_properties']; ?></div>
                        <div class="text-muted">Total Properties</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $summary_stats['rented_properties']; ?></div>
                        <div class="text-muted">Rented</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $summary_stats['pending_requests']; ?></div>
                        <div class="text-muted">Pending Requests</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $summary_stats['pending_maintenance']; ?></div>
                        <div class="text-muted">Maintenance</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($rental_history); ?></div>
                        <div class="text-muted">Total Rentals</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($payment_data); ?></div>
                        <div class="text-muted">Payments</div>
                    </div>
                </div>
            </div>

            <!-- Report Tabs -->
            <div class="report-tabs no-print">
                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="property-tab" data-bs-toggle="tab" data-bs-target="#property" type="button" role="tab">
                            <i class="fas fa-home me-2"></i>Property Listing
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rental-tab" data-bs-toggle="tab" data-bs-target="#rental" type="button" role="tab">
                            <i class="fas fa-history me-2"></i>Rental History
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab">
                            <i class="fas fa-money-bill-wave me-2"></i>Income/Payments
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
                            <i class="fas fa-clock me-2"></i>Pending Requests
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                            <i class="fas fa-tools me-2"></i>Maintenance
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content print-area" id="reportTabContent">
                <!-- Property Listing Report -->
                <div class="tab-pane fade show active" id="property" role="tabpanel">
                    <div class="report-table">
                        <div class="p-3">
                            <h5>Property Listing Report</h5>
                            <p class="text-muted">Complete list of all properties owned by you</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property Title</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Monthly Rent</th>
                                        <th>Status</th>
                                        <th>Pending Requests</th>
                                        <th>Active Rentals</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($properties as $property): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($property['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($property['description']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($property['property_type']); ?></td>
                                            <td><?php echo htmlspecialchars($property['location']); ?></td>
                                            <td>ETB <?php echo number_format($property['monthly_rent'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $property['status']; ?>">
                                                    <?php echo ucfirst($property['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($property['pending_requests'] > 0): ?>
                                                    <span class="badge bg-warning"><?php echo $property['pending_requests']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($property['active_rentals'] > 0): ?>
                                                    <span class="badge bg-success"><?php echo $property['active_rentals']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Rental History Report -->
                <div class="tab-pane fade" id="rental" role="tabpanel">
                    <div class="report-table">
                        <div class="p-3">
                            <h5>Rental History Report</h5>
                            <p class="text-muted">Complete rental history for all your properties</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Tenant Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Monthly Rent</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rental_history as $rental): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rental['property_title']); ?></td>
                                            <td><?php echo htmlspecialchars($rental['tenant_name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($rental['start_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($rental['end_date'])); ?></td>
                                            <td>ETB <?php echo number_format($rental['monthly_rent'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $rental['rental_status']; ?>">
                                                    <?php echo $rental['rental_status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Income/Payment Report -->
                <div class="tab-pane fade" id="income" role="tabpanel">
                    <div class="report-table">
                        <div class="p-3">
                            <h5>Income & Payment Report</h5>
                            <p class="text-muted">All payments received for your properties</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Tenant Name</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Payment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payment_data as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['property_title']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['tenant_name']); ?></td>
                                            <td><strong>ETB <?php echo number_format($payment['amount'], 2); ?></strong></td>
                                            <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                                    <?php echo ucfirst($payment['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests Report -->
                <div class="tab-pane fade" id="requests" role="tabpanel">
                    <div class="report-table">
                        <div class="p-3">
                            <h5>Pending Requests Report</h5>
                            <p class="text-muted">Rental requests waiting for your approval</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tenant Name</th>
                                        <th>Property</th>
                                        <th>Request Date</th>
                                        <th>Monthly Rent</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['tenant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['property_title']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                            <td>ETB <?php echo number_format($request['monthly_rent'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-pending">
                                                    Pending
                                                </span>
                                            </td>
                                            <td>
                                                <a href="requests.php" class="btn btn-sm btn-primary">Review</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Report -->
                <div class="tab-pane fade" id="maintenance" role="tabpanel">
                    <div class="report-table">
                        <div class="p-3">
                            <h5>Maintenance Report</h5>
                            <p class="text-muted">Maintenance requests for your properties</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Tenant Name</th>
                                        <th>Issue Type</th>
                                        <th>Description</th>
                                        <th>Date Reported</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenance_data as $maintenance): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($maintenance['property_title']); ?></td>
                                            <td><?php echo htmlspecialchars($maintenance['tenant_name']); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($maintenance['issue_type'])); ?></td>
                                            <td>
                                                <?php 
                                                $description = substr($maintenance['notes'], 0, 50);
                                                echo htmlspecialchars($description);
                                                if (strlen($maintenance['notes']) > 50) echo '...';
                                                ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($maintenance['created_at'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $maintenance['status']; ?>">
                                                    <?php echo ucfirst($maintenance['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $maintenance['priority'] == 'urgent' ? 'danger' : 
                                                        ($maintenance['priority'] == 'high' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($maintenance['priority']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
// Initialize Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'))
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl)
    });
    
    // Initialize tooltips if needed
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/footer.php'; ?>
