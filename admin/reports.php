<?php
require_once '../includes/config.php';
$title = "Reports - Admin Dashboard";

// Require admin login
$session->requireRole('admin');

// Get report parameters
$report_type = $_GET['type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$format = $_GET['format'] ?? 'web';

// Validate date range
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get report data based on type
$report_data = [];
switch ($report_type) {
    case 'overview':
        $report_data = getOverviewReport($start_date, $end_date);
        break;
    case 'users':
        $report_data = getUsersReport($start_date, $end_date);
        break;
    case 'properties':
        $report_data = getPropertiesReport($start_date, $end_date);
        break;
    case 'rentals':
        $report_data = getRentalsReport($start_date, $end_date);
        break;
    case 'payments':
        $report_data = getPaymentsReport($start_date, $end_date);
        break;
    case 'feedback':
        $report_data = getFeedbackReport($start_date, $end_date);
        break;
    default:
        $report_data = getOverviewReport($start_date, $end_date);
}

// Handle export formats
if ($format === 'csv' || $format === 'doc') {
    exportReport($report_type, $report_data, $format);
    exit;
}

include '../includes/header.php';
?>

<!-- Reports Content -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Reports & Analytics</h1>
                <div>
                    <span class="badge bg-info">Report Generator</span>
                    <span class="text-muted">Period: <?php echo formatDate($start_date, 'M d, Y'); ?> - <?php echo formatDate($end_date, 'M d, Y'); ?></span>
                </div>
            </div>
            
            <!-- Report Controls -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Report Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Report Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>System Overview</option>
                                <option value="users" <?php echo $report_type === 'users' ? 'selected' : ''; ?>>User Analytics</option>
                                <option value="properties" <?php echo $report_type === 'properties' ? 'selected' : ''; ?>>Property Statistics</option>
                                <option value="rentals" <?php echo $report_type === 'rentals' ? 'selected' : ''; ?>>Rental Activity</option>
                                <option value="payments" <?php echo $report_type === 'payments' ? 'selected' : ''; ?>>Payment Reports</option>
                                <option value="feedback" <?php echo $report_type === 'feedback' ? 'selected' : ''; ?>>Feedback Analysis</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync me-2"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Quick Date Ranges -->
                    <div class="mt-3">
                        <div class="btn-group btn-group-sm">
                            <a href="?type=<?php echo $report_type; ?>&start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary">Today</a>
                            <a href="?type=<?php echo $report_type; ?>&start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary">Last 7 Days</a>
                            <a href="?type=<?php echo $report_type; ?>&start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-t'); ?>" class="btn btn-outline-secondary">This Month</a>
                            <a href="?type=<?php echo $report_type; ?>&start_date=<?php echo date('Y-m-01', strtotime('-1 month')); ?>&end_date=<?php echo date('Y-m-t', strtotime('-1 month')); ?>" class="btn btn-outline-secondary">Last Month</a>
                            <a href="?type=<?php echo $report_type; ?>&start_date=<?php echo date('Y-01-01'); ?>&end_date=<?php echo date('Y-12-31'); ?>" class="btn btn-outline-secondary">This Year</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Export Options -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="exportCurrentReport('csv')">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </button>
                        <button type="button" class="btn btn-primary" onclick="exportCurrentReport('doc')">
                            <i class="fas fa-file-word me-2"></i>Export DOC
                        </button>
                        <button type="button" class="btn btn-info" onclick="printReport()">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                        <button type="button" class="btn btn-primary" onclick="scheduleReport()">
                            <i class="fas fa-clock me-2"></i>Schedule Report
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Report Content -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i><?php echo getReportTitle($report_type); ?>
                    </h5>
                </div>
                <div class="card-body" id="reportContent">
                    <?php include 'reports/' . $report_type . '.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Report Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm">
                    <div class="mb-3">
                        <label for="scheduleName" class="form-label">Report Name</label>
                        <input type="text" class="form-control" id="scheduleName" required>
                    </div>
                    <div class="mb-3">
                        <label for="scheduleFrequency" class="form-label">Frequency</label>
                        <select class="form-select" id="scheduleFrequency" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="scheduleEmail" class="form-label">Email Recipients</label>
                        <input type="email" class="form-control" id="scheduleEmail" placeholder="admin@example.com" required>
                        <small class="form-text text-muted">Separate multiple emails with commas</small>
                    </div>
                    <div class="mb-3">
                        <label for="scheduleFormat" class="form-label">Export Format</label>
                        <select class="form-select" id="scheduleFormat">
                            <option value="csv">CSV</option>
                            <option value="doc">DOC</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSchedule()">Save Schedule</button>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions
function getReportTitle($type) {
    $titles = [
        'overview' => 'System Overview Report',
        'users' => 'User Analytics Report',
        'properties' => 'Property Statistics Report',
        'rentals' => 'Rental Activity Report',
        'payments' => 'Payment Reports',
        'feedback' => 'Feedback Analysis Report'
    ];
    return $titles[$type] ?? 'Report';
}

function getOverviewReport($start_date, $end_date) {
    global $db;
    
    $stats = [];
    
    // User statistics
    $sql = "SELECT COUNT(*) as total_users,
                   COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_users
            FROM users";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$start_date, $end_date]);
    $stats['users'] = $result;
    
    // Property statistics
    $sql = "SELECT COUNT(*) as total_properties
            FROM properties";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt);
    $stats['properties'] = $result;
    
    // Rental statistics
    $sql = "SELECT COUNT(*) as total_rentals,
                   COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_rentals
            FROM rental_agreements";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$start_date, $end_date]);
    $stats['rentals'] = $result;
    
    // Payment statistics
    $sql = "SELECT COUNT(*) as total_payments,
                   SUM(amount) as total_revenue,
                   COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as recent_payments
            FROM payments WHERE status = 'completed'";
    $stmt = $db->prepare($sql);
    $result = $db->getSingle($stmt, [$start_date, $end_date]);
    $stats['payments'] = $result;
    
    return $stats;
}

function getUsersReport($start_date, $end_date) {
    global $db;
    
    $sql = "SELECT role, COUNT(*) as count,
                   COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_users
            FROM users 
            GROUP BY role";
    $stmt = $db->prepare($sql);
    return $db->getMultiple($stmt, [$start_date, $end_date]);
}

function getPropertiesReport($start_date, $end_date) {
    global $db;
    
    $sql = "SELECT p.property_type, COUNT(*) as count,
                   AVG(p.monthly_rent) as avg_rent
            FROM properties p
            GROUP BY p.property_type";
    $stmt = $db->prepare($sql);
    return $db->getMultiple($stmt);
}

function getRentalsReport($start_date, $end_date) {
    global $db;
    
    $sql = "SELECT DATE(ra.created_at) as date, COUNT(*) as rentals
            FROM rental_agreements ra
            WHERE ra.created_at BETWEEN ? AND ?
            GROUP BY DATE(ra.created_at)
            ORDER BY date";
    $stmt = $db->prepare($sql);
    return $db->getMultiple($stmt, [$start_date, $end_date]);
}

function getPaymentsReport($start_date, $end_date) {
    global $db;
    
    $sql = "SELECT DATE(p.created_at) as date, 
                   COUNT(*) as payments,
                   SUM(p.amount) as total_amount
            FROM payments p
            WHERE p.status = 'completed' AND p.created_at BETWEEN ? AND ?
            GROUP BY DATE(p.created_at)
            ORDER BY date";
    $stmt = $db->prepare($sql);
    return $db->getMultiple($stmt, [$start_date, $end_date]);
}

function getFeedbackReport($start_date, $end_date) {
    global $db;
    
    $sql = "SELECT f.type, COUNT(*) as count,
                   AVG(f.rating) as avg_rating
            FROM feedback f
            WHERE f.created_at BETWEEN ? AND ?
            GROUP BY f.type";
    $stmt = $db->prepare($sql);
    return $db->getMultiple($stmt, [$start_date, $end_date]);
}

function exportReport($type, $data, $format) {
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV header
        fputcsv($output, ['Report Type: ' . ucfirst($type)]);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Data based on report type
        switch ($type) {
            case 'overview':
                fputcsv($output, ['Metric', 'Value']);
                foreach ($data as $category => $stats) {
                    foreach ($stats as $key => $value) {
                        fputcsv($output, [ucfirst($category) . ' - ' . ucfirst(str_replace('_', ' ', $key)), $value]);
                    }
                }
                break;
            // Add other report types as needed
        }
        
        fclose($output);
    } elseif ($format === 'doc') {
        $title = getReportTitle($type);
        $html = '<style>body{font-family:Arial,sans-serif}h1{margin:0 0 10px}h2{margin:20px 0 10px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}small{color:#666}</style>';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<small>Generated: ' . date('Y-m-d H:i:s') . '</small>';
        switch ($type) {
            case 'overview':
                foreach ($data as $category => $stats) {
                    $html .= '<h2>' . ucfirst($category) . '</h2>';
                    $html .= '<table><thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>';
                    foreach ($stats as $key => $value) {
                        $html .= '<tr><td>' . ucfirst(str_replace('_', ' ', $key)) . '</td><td>' . htmlspecialchars((string)$value) . '</td></tr>';
                    }
                    $html .= '</tbody></table>';
                }
                break;
            case 'users':
                $html .= '<table><thead><tr><th>Role</th><th>Count</th><th>New Users</th></tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= '<tr><td>' . ucfirst($row['role']) . '</td><td>' . (int)$row['count'] . '</td><td>' . (int)$row['new_users'] . '</td></tr>';
                }
                $html .= '</tbody></table>';
                break;
            case 'properties':
                $html .= '<table><thead><tr><th>Property Type</th><th>Count</th><th>Average Rent (ETB)</th></tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= '<tr><td>' . ucfirst($row['property_type']) . '</td><td>' . (int)$row['count'] . '</td><td>' . number_format((float)$row['avg_rent'], 2) . '</td></tr>';
                }
                $html .= '</tbody></table>';
                break;
            case 'rentals':
                $html .= '<table><thead><tr><th>Date</th><th>Rentals</th></tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= '<tr><td>' . htmlspecialchars($row['date']) . '</td><td>' . (int)$row['rentals'] . '</td></tr>';
                }
                $html .= '</tbody></table>';
                break;
            case 'payments':
                $html .= '<table><thead><tr><th>Date</th><th>Payments</th><th>Total Amount (ETB)</th></tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= '<tr><td>' . htmlspecialchars($row['date']) . '</td><td>' . (int)$row['payments'] . '</td><td>' . number_format((float)$row['total_amount'], 2) . '</td></tr>';
                }
                $html .= '</tbody></table>';
                break;
            case 'feedback':
                $html .= '<table><thead><tr><th>Type</th><th>Count</th><th>Average Rating</th></tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= '<tr><td>' . ucfirst($row['type']) . '</td><td>' . (int)$row['count'] . '</td><td>' . number_format((float)$row['avg_rating'], 2) . '</td></tr>';
                }
                $html .= '</tbody></table>';
                break;
            default:
                $html .= '<p>No data</p>';
        }
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.doc"');
        echo $html;
        exit;
    }
}
?>

<script>
function exportCurrentReport(format) {
    const url = new URL(window.location);
    url.searchParams.set('format', format);
    window.open(url.toString(), '_blank');
}

function printReport() {
    window.print();
}

function scheduleReport() {
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    modal.show();
}

function saveSchedule() {
    const formData = new FormData(document.getElementById('scheduleForm'));
    
    fetch('../api/schedule-report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Report scheduled successfully!');
            bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error scheduling report');
    });
}
</script>

<style>
@media print {
    .sidebar, .btn, .card-header, .modal {
        display: none !important;
    }
    
    .container-fluid {
        max-width: 100%;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
