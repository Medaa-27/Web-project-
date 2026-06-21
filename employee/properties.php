<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

$session->requireRole('employee');
$title = "Property Management";

$employee_id = $session->getUserId();

// Initialize database
$db = new Database();

// Get employee info
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$employee = $db->getSingle($stmt, [$employee_id]);
if (!$employee) {
    $employee = ['full_name' => 'Employee'];
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE conditions
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR l.location_name LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM properties p 
              LEFT JOIN locations l ON p.location_id = l.location_id 
              LEFT JOIN users u ON p.owner_id = u.user_id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$total_properties = $db->getSingle($stmt, $params)['total'] ?? 0;
$total_pages = ceil($total_properties / $per_page);

// Get properties with pagination
$sql = "SELECT p.*, l.location_name, l.subcity, u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email,
               COUNT(DISTINCT rr.request_id) as request_count,
               COUNT(DISTINCT ra.agreement_id) as active_rentals
        FROM properties p 
        LEFT JOIN locations l ON p.location_id = l.location_id 
        LEFT JOIN users u ON p.owner_id = u.user_id 
        LEFT JOIN rental_requests rr ON p.property_id = rr.property_id
        LEFT JOIN rental_agreements ra ON p.property_id = ra.property_id AND ra.status = 'active'
        $where_clause
        GROUP BY p.property_id 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$all_params = array_merge($params, [$per_page, $offset]);
$properties = $db->getMultiple($stmt, $all_params);

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) as requested,
                SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as rented,
                SUM(CASE WHEN status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
              FROM properties";
$stats = $db->getSingle($db->prepare($stats_sql), []);
?>

<style>
    .property-card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .property-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
    }
    .status-available { background-color: #28a745; }
    .status-requested { background-color: #ffc107; }
    .status-rented { background-color: #17a2b8; }
    .status-under_maintenance { background-color: #fd7e14; }
    .status-inactive { background-color: #6c757d; }
    
    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        margin: 0.1rem;
    }
    
    .stats-card {
        background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .property-image {
        height: 200px;
        object-fit: cover;
        width: 100%;
    }
    
    .property-info {
        font-size: 0.9rem;
    }
    
    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .activity-log {
        max-height: 300px;
        overflow-y: auto;
        font-size: 0.85rem;
    }
    
    .activity-item {
        padding: 0.5rem;
        border-left: 3px solid #667eea;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
    }
</style>

<?php include '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Welcome Banner -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">
                                <i class="fas fa-home me-2"></i>Property Management
                            </h1>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($employee['full_name']); ?>!</p>
                            <p class="text-muted mb-0">Manage all properties in the system</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="stats-card text-center">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <small>Total Properties</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center">
                        <h3><?php echo number_format($stats['available']); ?></h3>
                        <small>Available</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center">
                        <h3><?php echo number_format($stats['requested']); ?></h3>
                        <small>Requested</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center">
                        <h3><?php echo number_format($stats['rented']); ?></h3>
                        <small>Rented</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center">
                        <h3><?php echo number_format($stats['maintenance']); ?></h3>
                        <small>Maintenance</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center">
                        <h3><?php echo number_format($stats['inactive']); ?></h3>
                        <small>Inactive</small>
                    </div>
                </div>
            </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status Filter</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Properties</option>
                                <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="requested" <?php echo $status_filter === 'requested' ? 'selected' : ''; ?>>Requested</option>
                                <option value="rented" <?php echo $status_filter === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                <option value="under_maintenance" <?php echo $status_filter === 'under_maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Properties</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by title, description, location, or owner...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                            <a href="properties.php" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Properties Grid -->
                <?php if (empty($properties)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-home fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No properties found</h4>
                        <p class="text-muted">No properties match your current filters.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($properties as $property): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card property-card h-100">
                                    <img src="<?php echo getPropertyPrimaryImage($property['property_id']); ?>" 
                                         class="card-img-top property-image" alt="<?php echo htmlspecialchars($property['title']); ?>"
                                         onerror="this.src='../assets/images/default-property.svg'">
                                    
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                            <span class="badge status-badge status-<?php echo $property['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $property['status'])); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="property-info mb-2">
                                            <div class="mb-1">
                                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                <?php echo htmlspecialchars($property['location_name'] . ', ' . $property['subcity']); ?>
                                            </div>
                                            <div class="mb-1">
                                                <i class="fas fa-user text-primary me-1"></i>
                                                <?php echo htmlspecialchars($property['owner_name']); ?>
                                            </div>
                                            <div class="mb-1">
                                                <i class="fas fa-money-bill-wave text-success me-1"></i>
                                                <?php echo number_format($property['monthly_rent']); ?> ETB/month
                                            </div>
                                            <div class="mb-1">
                                                <i class="fas fa-bed text-info me-1"></i>
                                                <?php echo $property['bedrooms']; ?> Beds
                                                <i class="fas fa-bath text-info ms-2 me-1"></i>
                                                <?php echo $property['bathrooms']; ?> Baths
                                            </div>
                                        </div>
                                        
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-inbox me-1"></i> <?php echo $property['request_count']; ?> Requests
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-file-contract me-1"></i> <?php echo $property['active_rentals']; ?> Active
                                                </small>
                                            </div>
                                            
                                            <div class="action-buttons d-flex flex-wrap gap-1">
                                                <button class="btn btn-info btn-sm view-details-btn" data-property-id="<?php echo $property['property_id']; ?>" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm edit-property-btn" data-property-id="<?php echo $property['property_id']; ?>" title="Edit Property">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                                    <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" 
                                                       target="_blank" class="btn btn-outline-info btn-sm" title="Show on Map">
                                                        <i class="fas fa-map-marked-alt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" 
                                                       target="_blank" class="btn btn-outline-info btn-sm" title="Search on Map">
                                                        <i class="fas fa-search-location"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button class="btn btn-secondary btn-sm update-status-btn" data-property-id="<?php echo $property['property_id']; ?>" data-current-status="<?php echo $property['status']; ?>" title="Update Status">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <button class="btn btn-primary btn-sm view-activity-btn" data-property-id="<?php echo $property['property_id']; ?>" title="View Activity">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Property pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
        </div>
    </div>
</div>

    <!-- Property Details Modal -->
    <div class="modal fade" id="propertyDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Property Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="propertyDetailsContent">
                    <!-- Content loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Property Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="statusPropertyId">
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">New Status</label>
                            <select class="form-select" id="newStatus" required>
                                <option value="">Select Status</option>
                                <option value="available">Available</option>
                                <option value="requested">Requested</option>
                                <option value="rented">Rented</option>
                                <option value="under_maintenance">Under Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="statusNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="statusNotes" rows="3" placeholder="Add notes about this status change..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStatusBtn">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Property Activity History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="activity-log" id="activityContent">
                        <!-- Content loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Debug: Check if jQuery is loaded
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    
    // Test: Simple jQuery test
    if (typeof $ !== 'undefined') {
        console.log('jQuery version:', $.fn.jquery);
        // Test if we can select elements
        console.log('Buttons found:', $('.view-details-btn').length);
    }
    
    $(document).ready(function() {
        console.log('Document ready fired');
        
        // Test: Add a simple click handler to see if jQuery events work
        $(document).on('click', '.view-details-btn', function(e) {
            console.log('View button clicked');
            e.preventDefault();
            var propertyId = $(this).data('property-id');
            console.log('Property ID:', propertyId);
            
            // Check if modal exists
            if (!$('#propertyDetailsModal').length) {
                console.error('Property details modal not found!');
                alert('Modal not found!');
                return;
            }
            
            // Show loading state
            $('#propertyDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading property details...</p></div>');
            
            $.ajax({
                url: '../api/get-property-details.php',
                method: 'POST',
                data: { property_id: propertyId },
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        $('#propertyDetailsContent').html(response.content);
                        var modal = new bootstrap.Modal(document.getElementById('propertyDetailsModal'));
                        modal.show();
                    } else {
                        $('#propertyDetailsContent').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                        var modal = new bootstrap.Modal(document.getElementById('propertyDetailsModal'));
                        modal.show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    console.error('XHR status:', xhr.status);
                    console.error('Response text:', xhr.responseText);
                    $('#propertyDetailsContent').html('<div class="alert alert-danger">Error loading property details: ' + error + '</div>');
                    var modal = new bootstrap.Modal(document.getElementById('propertyDetailsModal'));
                    modal.show();
                }
            });
        });

        // Edit Property
        $(document).on('click', '.edit-property-btn', function(e) {
            console.log('Edit button clicked');
            e.preventDefault();
            var propertyId = $(this).data('property-id');
            console.log('Navigating to edit property:', propertyId);
            window.location.href = 'edit-property.php?id=' + propertyId;
        });

        // Update Status
        $(document).on('click', '.update-status-btn', function(e) {
            console.log('Status button clicked');
            e.preventDefault();
            var propertyId = $(this).data('property-id');
            var currentStatus = $(this).data('current-status');
            console.log('Property ID:', propertyId, 'Current Status:', currentStatus);
            
            // Check if modal exists
            if (!$('#statusModal').length) {
                console.error('Status modal not found!');
                alert('Status modal not found!');
                return;
            }
            
            $('#statusPropertyId').val(propertyId);
            $('#newStatus').val(currentStatus);
            var modal = new bootstrap.Modal(document.getElementById('statusModal'));
            modal.show();
        });

        // View Activity
        $(document).on('click', '.view-activity-btn', function(e) {
            console.log('Activity button clicked');
            e.preventDefault();
            var propertyId = $(this).data('property-id');
            console.log('Property ID:', propertyId);
            
            // Check if modal exists
            if (!$('#activityModal').length) {
                console.error('Activity modal not found!');
                alert('Activity modal not found!');
                return;
            }
            
            // Show loading state
            $('#activityContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading activity history...</p></div>');
            
            $.ajax({
                url: '../api/get-property-activity.php',
                method: 'POST',
                data: { property_id: propertyId },
                dataType: 'json',
                success: function(response) {
                    console.log('Activity AJAX success:', response);
                    if (response.success) {
                        $('#activityContent').html(response.content);
                        var modal = new bootstrap.Modal(document.getElementById('activityModal'));
                        modal.show();
                    } else {
                        $('#activityContent').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                        var modal = new bootstrap.Modal(document.getElementById('activityModal'));
                        modal.show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Activity AJAX error:', error);
                    console.error('XHR status:', xhr.status);
                    console.error('Response text:', xhr.responseText);
                    $('#activityContent').html('<div class="alert alert-danger">Error loading activity history: ' + error + '</div>');
                    var modal = new bootstrap.Modal(document.getElementById('activityModal'));
                    modal.show();
                }
            });
        });

        // Save Status
        $(document).on('click', '#saveStatusBtn', function(e) {
            console.log('Save status button clicked');
            e.preventDefault();
            var propertyId = $('#statusPropertyId').val();
            var newStatus = $('#newStatus').val();
            var notes = $('#statusNotes').val();

            console.log('Saving status:', {propertyId, newStatus, notes});

            if (!newStatus) {
                alert('Please select a status');
                return;
            }

            $.ajax({
                url: '../api/update-property-status.php',
                method: 'POST',
                data: {
                    property_id: propertyId,
                    status: newStatus,
                    notes: notes
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Status update success:', response);
                    if (response.success) {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
                        modal.hide();
                        location.reload();
                    } else {
                        alert('Error updating status: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Status update error:', error);
                    console.error('XHR status:', xhr.status);
                    console.error('Response text:', xhr.responseText);
                    alert('Error updating status: ' + error);
                }
            });
        });
        
        // Debug: Log all modal elements
        console.log('Modals found:', {
            propertyDetails: $('#propertyDetailsModal').length,
            status: $('#statusModal').length,
            activity: $('#activityModal').length
        });
    });
    </script>

<?php include '../includes/footer.php'; ?>
