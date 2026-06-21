<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Reports - Tenant Dashboard";

$user_id = $session->getUserId();

// Get all report data
$rental_history = [];
$payment_history = [];
$active_rentals = [];
$rental_requests = [];
$maintenance_requests = [];

try {
    // Rental History Report
    $sql = "SELECT ra.*, p.title as property_title, p.property_type, l.location_name, l.subcity,
                   u.full_name as owner_name
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            LEFT JOIN locations l ON p.location_id = l.location_id
            LEFT JOIN users u ON p.owner_id = u.user_id
            WHERE ra.tenant_id = ?
            ORDER BY ra.start_date DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $rental_history = $db->getMultiple($stmt, [$user_id]);
    }

    // Payment History Report
    $sql = "SELECT p.*, prop.title as property_title, l.location_name
            FROM payments p
            JOIN rental_agreements ra ON p.agreement_id = ra.agreement_id
            JOIN properties prop ON ra.property_id = prop.property_id
            LEFT JOIN locations l ON prop.location_id = l.location_id
            WHERE p.tenant_id = ?
            ORDER BY p.payment_date DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $payment_history = $db->getMultiple($stmt, [$user_id]);
    }

    // Active Rentals Report
    $sql = "SELECT ra.*, p.title as property_title, p.property_type, p.monthly_rent,
                   l.location_name, l.subcity, u.full_name as owner_name, u.phone as owner_phone
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            LEFT JOIN locations l ON p.location_id = l.location_id
            LEFT JOIN users u ON p.owner_id = u.user_id
            WHERE ra.tenant_id = ? AND ra.status = 'active'
            ORDER BY ra.start_date DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $active_rentals = $db->getMultiple($stmt, [$user_id]);
    }

    // Rental Request Status Report
    $sql = "SELECT rr.*, p.title as property_title, p.property_type, l.location_name
            FROM rental_requests rr
            JOIN properties p ON rr.property_id = p.property_id
            LEFT JOIN locations l ON p.location_id = l.location_id
            WHERE rr.tenant_id = ?
            ORDER BY rr.created_at DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $rental_requests = $db->getMultiple($stmt, [$user_id]);
    }

    // Maintenance Request Report
    $sql = "SELECT mr.*, p.title as property_title, l.location_name
            FROM maintenance_requests mr
            JOIN properties p ON mr.property_id = p.property_id
            LEFT JOIN locations l ON p.location_id = l.location_id
            WHERE mr.tenant_id = ?
            ORDER BY mr.created_at DESC";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $maintenance_requests = $db->getMultiple($stmt, [$user_id]);
    }

} catch (Exception $e) {
    error_log("Reports data error: " . $e->getMessage());
}

// Handle CSV and DOC download
if (isset($_GET['download']) && $_GET['download'] == '1' && isset($_GET['report_type']) && isset($_GET['format'])) {
    $report_type = $_GET['report_type'];
    $format = $_GET['format']; // 'csv' or 'doc'
    $filename = $report_type . '_report_' . date('Y-m-d') . '.' . $format;
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($report_type) {
            case 'rental_history':
                fputcsv($output, ['Property', 'Type', 'Location', 'Start Date', 'End Date', 'Status', 'Owner']);
                foreach ($rental_history as $item) {
                    fputcsv($output, [
                        $item['property_title'],
                        $item['property_type'],
                        ($item['location_name'] ?? '') . ', ' . ($item['subcity'] ?? ''),
                        $item['start_date'],
                        $item['end_date'],
                        ucfirst($item['status']),
                        $item['owner_name'] ?? 'N/A'
                    ]);
                }
                break;
                
            case 'payment_history':
                fputcsv($output, ['Property', 'Location', 'Amount', 'Payment Date', 'Status', 'Method']);
                foreach ($payment_history as $item) {
                    fputcsv($output, [
                        $item['property_title'],
                        $item['location_name'] ?? 'N/A',
                        $item['amount'],
                        $item['payment_date'],
                        ucfirst($item['status']),
                        $item['payment_method'] ?? 'N/A'
                    ]);
                }
                break;
                
            case 'active_rentals':
                fputcsv($output, ['Property', 'Type', 'Location', 'Owner', 'Owner Phone', 'Monthly Rent', 'Start Date', 'End Date']);
                foreach ($active_rentals as $item) {
                    fputcsv($output, [
                        $item['property_title'],
                        $item['property_type'],
                        ($item['location_name'] ?? '') . ', ' . ($item['subcity'] ?? ''),
                        $item['owner_name'] ?? 'N/A',
                        $item['owner_phone'] ?? 'N/A',
                        $item['monthly_rent'],
                        $item['start_date'],
                        $item['end_date']
                    ]);
                }
                break;
                
            case 'rental_requests':
                fputcsv($output, ['Property', 'Type', 'Location', 'Request Date', 'Status', 'Message']);
                foreach ($rental_requests as $item) {
                    fputcsv($output, [
                        $item['property_title'],
                        $item['property_type'],
                        $item['location_name'] ?? 'N/A',
                        $item['created_at'],
                        ucfirst($item['status']),
                        $item['message'] ?? ''
                    ]);
                }
                break;
                
            case 'maintenance_requests':
                fputcsv($output, ['Property', 'Location', 'Issue Type', 'Priority', 'Description', 'Date Submitted', 'Status']);
                foreach ($maintenance_requests as $item) {
                    fputcsv($output, [
                        $item['property_title'],
                        $item['location_name'] ?? 'N/A',
                        $item['issue_type'],
                        $item['priority'],
                        $item['notes'],
                        $item['created_at'],
                        ucfirst($item['status'])
                    ]);
                }
                break;
        }
        
        fclose($output);
        exit;
    } elseif ($format === 'doc') {
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $content = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
        $content .= '<head><meta charset="utf-8"><title>' . ucfirst(str_replace('_', ' ', $report_type)) . ' Report</title></head><body>';
        
        // Get report data based on type
        $report_data = [];
        $report_title = '';
        $headers = [];
        
        switch ($report_type) {
            case 'rental_history':
                $report_data = $rental_history;
                $report_title = 'Rental History Report';
                $headers = ['Property', 'Type', 'Location', 'Start Date', 'End Date', 'Status', 'Owner'];
                break;
            case 'payment_history':
                $report_data = $payment_history;
                $report_title = 'Payment History Report';
                $headers = ['Property', 'Location', 'Amount', 'Payment Date', 'Status', 'Method'];
                break;
            case 'active_rentals':
                $report_data = $active_rentals;
                $report_title = 'Active Rental Report';
                $headers = ['Property', 'Type', 'Location', 'Owner', 'Owner Phone', 'Monthly Rent', 'Start Date', 'End Date'];
                break;
            case 'rental_requests':
                $report_data = $rental_requests;
                $report_title = 'Rental Request Status Report';
                $headers = ['Property', 'Type', 'Location', 'Request Date', 'Status', 'Message'];
                break;
            case 'maintenance_requests':
                $report_data = $maintenance_requests;
                $report_title = 'Maintenance Request Report';
                $headers = ['Property', 'Location', 'Issue Type', 'Priority', 'Description', 'Date Submitted', 'Status'];
                break;
        }
        
        $content .= '<h1>' . $report_title . '</h1>';
        $content .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        $content .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
        
        // Table headers
        $content .= '<tr>';
        foreach ($headers as $header) {
            $content .= '<th style="background-color: #f2f2f2; font-weight: bold;">' . htmlspecialchars($header) . '</th>';
        }
        $content .= '</tr>';
        
        // Table data
        foreach ($report_data as $item) {
            $content .= '<tr>';
            switch ($report_type) {
                case 'rental_history':
                    $content .= '<td>' . htmlspecialchars($item['property_title']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['property_type']) . '</td>';
                    $content .= '<td>' . htmlspecialchars(($item['location_name'] ?? '') . ', ' . ($item['subcity'] ?? '')) . '</td>';
                    $content .= '<td>' . $item['start_date'] . '</td>';
                    $content .= '<td>' . ($item['end_date'] ?: 'Ongoing') . '</td>';
                    $content .= '<td>' . ucfirst($item['status']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['owner_name'] ?? 'N/A') . '</td>';
                    break;
                case 'payment_history':
                    $content .= '<td>' . htmlspecialchars($item['property_title']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['location_name'] ?? 'N/A') . '</td>';
                    $content .= '<td>' . number_format($item['amount'], 2) . ' ETB</td>';
                    $content .= '<td>' . $item['payment_date'] . '</td>';
                    $content .= '<td>' . ucfirst($item['status']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['payment_method'] ?? 'N/A') . '</td>';
                    break;
                case 'active_rentals':
                    $content .= '<td>' . htmlspecialchars($item['property_title']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['property_type']) . '</td>';
                    $content .= '<td>' . htmlspecialchars(($item['location_name'] ?? '') . ', ' . ($item['subcity'] ?? '')) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['owner_name'] ?? 'N/A') . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['owner_phone'] ?? 'N/A') . '</td>';
                    $content .= '<td>' . number_format($item['monthly_rent'], 2) . ' ETB</td>';
                    $content .= '<td>' . $item['start_date'] . '</td>';
                    $content .= '<td>' . $item['end_date'] . '</td>';
                    break;
                case 'rental_requests':
                    $content .= '<td>' . htmlspecialchars($item['property_title']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['property_type']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['location_name'] ?? 'N/A') . '</td>';
                    $content .= '<td>' . $item['created_at'] . '</td>';
                    $content .= '<td>' . ucfirst($item['status']) . '</td>';
                    $content .= '<td>' . htmlspecialchars(substr($item['message'] ?? '', 0, 100)) . '</td>';
                    break;
                case 'maintenance_requests':
                    $content .= '<td>' . htmlspecialchars($item['property_title']) . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['location_name'] ?? 'N/A') . '</td>';
                    $content .= '<td>' . htmlspecialchars($item['issue_type']) . '</td>';
                    $content .= '<td>' . ucfirst($item['priority']) . '</td>';
                    $content .= '<td>' . htmlspecialchars(substr($item['notes'], 0, 100)) . '</td>';
                    $content .= '<td>' . $item['created_at'] . '</td>';
                    $content .= '<td>' . ucfirst(str_replace('_', ' ', $item['status'])) . '</td>';
                    break;
            }
            $content .= '</tr>';
        }
        
        $content .= '</table></body></html>';
        
        echo $content;
        exit;
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">My Reports</h2>
                    <p class="text-muted mb-0">View and download your rental activity reports</p>
                </div>
            </div>

            <!-- Report Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0"><?php echo count($rental_history); ?></h5>
                                    <small>Total Rentals</small>
                                </div>
                                <i class="fas fa-home fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0"><?php echo count($payment_history); ?></h5>
                                    <small>Total Payments</small>
                                </div>
                                <i class="fas fa-credit-card fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0"><?php echo count($active_rentals); ?></h5>
                                    <small>Active Rentals</small>
                                </div>
                                <i class="fas fa-key fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0"><?php echo count($maintenance_requests); ?></h5>
                                    <small>Maintenance Requests</small>
                                </div>
                                <i class="fas fa-tools fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tabs -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="reportsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="rental-history-tab" data-bs-toggle="tab" data-bs-target="#rental-history" type="button" role="tab">
                                <i class="fas fa-history me-2"></i>Rental History
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payment-history-tab" data-bs-toggle="tab" data-bs-target="#payment-history" type="button" role="tab">
                                <i class="fas fa-money-bill-wave me-2"></i>Payment History
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="active-rentals-tab" data-bs-toggle="tab" data-bs-target="#active-rentals" type="button" role="tab">
                                <i class="fas fa-home me-2"></i>Active Rentals
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rental-requests-tab" data-bs-toggle="tab" data-bs-target="#rental-requests" type="button" role="tab">
                                <i class="fas fa-file-alt me-2"></i>Rental Requests
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="maintenance-requests-tab" data-bs-toggle="tab" data-bs-target="#maintenance-requests" type="button" role="tab">
                                <i class="fas fa-tools me-2"></i>Maintenance Requests
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="reportsTabContent">
                        <!-- Rental History Tab -->
                        <div class="tab-pane fade show active" id="rental-history" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Rental History Report</h5>
                                <div>
                                    <button onclick="printReport('rental-history')" class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-print me-2"></i>Print
                                    </button>
                                    <a href="?download=1&report_type=rental_history&format=csv" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-file-csv me-2"></i>Download CSV
                                    </a>
                                    <a href="?download=1&report_type=rental_history&format=doc" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-word me-2"></i>Download DOC
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rental_history)): ?>
                                            <?php foreach ($rental_history as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['property_type']); ?></td>
                                                    <td><?php echo htmlspecialchars(($item['location_name'] ?? '') . ', ' . ($item['subcity'] ?? '')); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($item['start_date'])); ?></td>
                                                    <td><?php echo $item['end_date'] ? date('M d, Y', strtotime($item['end_date'])) : 'Ongoing'; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : ($item['status'] === 'completed' ? 'info' : 'secondary'); ?>">
                                                            <?php echo ucfirst($item['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['owner_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No rental history found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment History Tab -->
                        <div class="tab-pane fade" id="payment-history" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Payment History Report</h5>
                                <div>
                                    <button onclick="printReport('payment-history')" class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-print me-2"></i>Print
                                    </button>
                                    <a href="?download=1&report_type=payment_history&format=csv" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-file-csv me-2"></i>Download CSV
                                    </a>
                                    <a href="?download=1&report_type=payment_history&format=doc" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-word me-2"></i>Download DOC
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Location</th>
                                            <th>Amount</th>
                                            <th>Payment Date</th>
                                            <th>Status</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($payment_history)): ?>
                                            <?php foreach ($payment_history as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo number_format($item['amount'], 2); ?> ETB</td>
                                                    <td><?php echo date('M d, Y', strtotime($item['payment_date'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $item['status'] === 'completed' ? 'success' : ($item['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                            <?php echo ucfirst($item['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['payment_method'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No payment history found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Active Rentals Tab -->
                        <div class="tab-pane fade" id="active-rentals" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Active Rental Report</h5>
                                <div>
                                    <button onclick="printReport('active-rentals')" class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-print me-2"></i>Print
                                    </button>
                                    <a href="?download=1&report_type=active_rentals&format=csv" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-file-csv me-2"></i>Download CSV
                                    </a>
                                    <a href="?download=1&report_type=active_rentals&format=doc" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-word me-2"></i>Download DOC
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Owner</th>
                                            <th>Owner Phone</th>
                                            <th>Monthly Rent</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($active_rentals)): ?>
                                            <?php foreach ($active_rentals as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['property_type']); ?></td>
                                                    <td><?php echo htmlspecialchars(($item['location_name'] ?? '') . ', ' . ($item['subcity'] ?? '')); ?></td>
                                                    <td><?php echo htmlspecialchars($item['owner_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($item['owner_phone'] ?? 'N/A'); ?></td>
                                                    <td><?php echo number_format($item['monthly_rent'], 2); ?> ETB</td>
                                                    <td><?php echo date('M d, Y', strtotime($item['start_date'])); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($item['end_date'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No active rentals found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Rental Requests Tab -->
                        <div class="tab-pane fade" id="rental-requests" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Rental Request Status Report</h5>
                                <div>
                                    <button onclick="printReport('rental-requests')" class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-print me-2"></i>Print
                                    </button>
                                    <a href="?download=1&report_type=rental_requests&format=csv" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-file-csv me-2"></i>Download CSV
                                    </a>
                                    <a href="?download=1&report_type=rental_requests&format=doc" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-word me-2"></i>Download DOC
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rental_requests)): ?>
                                            <?php foreach ($rental_requests as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['property_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $item['status'] === 'approved' ? 'success' : ($item['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                            <?php echo ucfirst($item['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(substr($item['message'] ?? '', 0, 50)) . (strlen($item['message'] ?? '') > 50 ? '...' : ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No rental requests found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Maintenance Requests Tab -->
                        <div class="tab-pane fade" id="maintenance-requests" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Maintenance Request Report</h5>
                                <div>
                                    <button onclick="printReport('maintenance-requests')" class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-print me-2"></i>Print
                                    </button>
                                    <a href="?download=1&report_type=maintenance_requests&format=csv" class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fas fa-file-csv me-2"></i>Download CSV
                                    </a>
                                    <a href="?download=1&report_type=maintenance_requests&format=doc" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-word me-2"></i>Download DOC
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Location</th>
                                            <th>Issue Type</th>
                                            <th>Priority</th>
                                            <th>Description</th>
                                            <th>Date Submitted</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($maintenance_requests)): ?>
                                            <?php foreach ($maintenance_requests as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['property_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($item['issue_type']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $item['priority'] === 'high' ? 'danger' : ($item['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                                            <?php echo ucfirst($item['priority']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(substr($item['notes'], 0, 50)) . (strlen($item['notes']) > 50 ? '...' : ''); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $item['status'] === 'completed' ? 'success' : ($item['status'] === 'in_progress' ? 'warning' : 'info'); ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No maintenance requests found</td>
                                            </tr>
                                        <?php endif; ?>
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
// Manual tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers to tab buttons
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and buttons
            document.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show corresponding tab pane
            const targetId = this.getAttribute('data-bs-target');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
});

function printReport(reportId) {
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Get the active tab content
    const activeTab = document.getElementById(reportId);
    const reportTitle = activeTab.querySelector('h5').textContent;
    const tableContent = activeTab.querySelector('.table-responsive').innerHTML;
    
    // Create the print-friendly HTML
    const printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${reportTitle}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                h1 {
                    text-align: center;
                    color: #2c3e50;
                    margin-bottom: 30px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 12px;
                    text-align: left;
                }
                th {
                    background-color: #f8f9fa;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .badge {
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                }
                .bg-success { background-color: #28a745; color: white; }
                .bg-warning { background-color: #ffc107; color: black; }
                .bg-danger { background-color: #dc3545; color: white; }
                .bg-info { background-color: #17a2b8; color: white; }
                .bg-secondary { background-color: #6c757d; color: white; }
                .text-center { text-align: center; }
                .text-muted { color: #6c757d; }
                @media print {
                    body { margin: 10px; }
                    h1 { font-size: 18px; }
                    table { font-size: 12px; }
                    th, td { padding: 8px; }
                }
            </style>
        </head>
        <body>
            <h1>${reportTitle}</h1>
            <div class="table-responsive">
                ${tableContent}
            </div>
            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                Generated on ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}
            </div>
        </body>
        </html>
    `;
    
    // Write the HTML to the new window
    printWindow.document.write(printHTML);
    printWindow.document.close();
    
    // Wait for the content to load, then print
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}
</script>

<?php include '../includes/footer.php'; ?>
