<?php

require_once '../includes/config.php';

$session->requireRole('owner');

$title = "My Properties";

$owner_id = $session->getUserId();

// Handle property actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $property_id = $_POST['property_id'] ?? 0;
                $new_status = $_POST['new_status'] ?? 'available';
                
                $sql = "UPDATE properties SET status = ? WHERE property_id = ? AND owner_id = ?";
                $stmt = $db->prepare($sql);
                $result = $db->execute($stmt, [$new_status, $property_id, $owner_id]);
                
                if ($result) {
                    $_SESSION['success'] = "Property status updated successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update property status!";
                }
                break;
                
            case 'delete_property':
                $property_id = $_POST['property_id'] ?? 0;
                
                // Check if property has active rentals
                $sql = "SELECT COUNT(*) as count FROM rental_agreements 
                        WHERE property_id = ? AND status = 'active'";
                $stmt = $db->prepare($sql);
                $result = $db->getSingle($stmt, [$property_id]);
                
                if ($result && $result['count'] > 0) {
                    $_SESSION['error'] = "Cannot delete property with active rental agreements!";
                } else {
                    // Delete property images
                    $sql = "DELETE FROM property_images WHERE property_id = ?";
                    $stmt = $db->prepare($sql);
                    $db->execute($stmt, [$property_id]);
                    
                    // Delete property
                    $sql = "DELETE FROM properties WHERE property_id = ? AND owner_id = ?";
                    $stmt = $db->prepare($sql);
                    $result = $db->execute($stmt, [$property_id, $owner_id]);
                    
                    if ($result) {
                        $_SESSION['success'] = "Property deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete property!";
                    }
                }
                break;
        }
        
        header("Location: properties.php");
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$review_filter = $_GET['review_status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build base query
$base_query = "SELECT p.*, 
               l.location_name, l.subcity,
               pi.image_url as primary_image,
               (SELECT COUNT(*) FROM rental_requests rr WHERE rr.property_id = p.property_id) as request_count,
               (SELECT COUNT(*) FROM rental_agreements ra WHERE ra.property_id = p.property_id AND ra.status = 'active') as active_rentals
               FROM properties p 
               LEFT JOIN locations l ON p.location_id = l.location_id
               LEFT JOIN property_images pi ON p.property_id = pi.property_id AND pi.is_primary = 1
               WHERE p.owner_id = ?";

$params = [$owner_id];

// Add filters
if ($status_filter !== 'all') {
    $base_query .= " AND p.status = ?";
    $params[] = $status_filter;
}

if ($review_filter !== 'all') {
    $base_query .= " AND p.review_status = ?";
    $params[] = $review_filter;
}

if (!empty($search)) {
    $base_query .= " AND (p.title LIKE ? OR p.description LIKE ? OR l.location_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$base_query .= " ORDER BY p.created_at DESC";

// Get properties
$stmt = $db->prepare($base_query);
$properties = $db->getMultiple($stmt, $params);

// Get statistics
$stats = [];
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' AND review_status = 'approved' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN review_status = 'pending' THEN 1 ELSE 0 END) as pending_review,
        SUM(CASE WHEN review_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN review_status = 'needs_revision' THEN 1 ELSE 0 END) as needs_revision,
        SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as rented
        FROM properties WHERE owner_id = ?";
$stmt = $db->prepare($sql);
$result = $db->getSingle($stmt, [$owner_id]);
$stats = $result ?: [];

include '../includes/header.php';

?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">My Properties</h1>
                            <p class="text-muted mb-0">Manage your property listings and track their performance</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="add-property.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Add New Property
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-primary bg-opacity-10 text-primary mx-auto">
                                <i class="fas fa-home fa-2x"></i>
                            </div>
                            <h4 class="fw-bold"><?php echo $stats['total'] ?? 0; ?></h4>
                            <p class="text-muted mb-0 small">Total</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h4 class="fw-bold"><?php echo $stats['available'] ?? 0; ?></h4>
                            <p class="text-muted mb-0 small">Available</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h4 class="fw-bold"><?php echo $stats['pending_review'] ?? 0; ?></h4>
                            <p class="text-muted mb-0 small">Pending Review</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-danger bg-opacity-10 text-danger mx-auto">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                            <h4 class="fw-bold"><?php echo $stats['rejected'] ?? 0; ?></h4>
                            <p class="text-muted mb-0 small">Rejected</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">
                                <i class="fas fa-edit fa-2x"></i>
                            </div>
                            <h4 class="fw-bold"><?php echo $stats['needs_revision'] ?? 0; ?></h4>
                            <p class="text-muted mb-0 small">Needs Revision</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-secondary bg-opacity-10 text-secondary mx-auto">
                                <i class="fas fa-handshake fa-2x"></i>
                            </div>
                            <h4 class="fw-bold"><?php echo $stats['rented'] ?? 0; ?></h4>
                            <p class="text-muted mb-0 small">Rented</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search properties...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Property Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="rented" <?php echo $status_filter === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Review Status</label>
                            <select name="review_status" class="form-select">
                                <option value="all" <?php echo $review_filter === 'all' ? 'selected' : ''; ?>>All Reviews</option>
                                <option value="pending" <?php echo $review_filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                <option value="approved" <?php echo $review_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $review_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="needs_revision" <?php echo $review_filter === 'needs_revision' ? 'selected' : ''; ?>>Needs Revision</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label><br>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Properties List -->
            <div class="card dashboard-card">
                <div class="card-header">
                    <h5 class="mb-0">Property Listings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($properties)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-home fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No properties found</h4>
                            <p class="text-muted">
                                <?php if (!empty($search) || $status_filter !== 'all' || $review_filter !== 'all'): ?>
                                    Try adjusting your filters or search terms.
                                <?php else: ?>
                                    Start by adding your first property listing.
                                <?php endif; ?>
                            </p>
                            <a href="add-property.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Your First Property
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($properties as $property): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 property-card">
                                        <div class="row g-0">
                                            <div class="col-md-4">
                                                <?php 
                                                $image_url = $property['primary_image'] ?: '../assets/images/default-property.jpg';
                                                ?>
                                                <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                                     class="img-fluid rounded-start h-100 object-fit-cover" 
                                                     alt="<?php echo htmlspecialchars($property['title']); ?>"
                                                     style="min-height: 200px;">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body d-flex flex-column h-100">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($property['title']); ?></h5>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="edit-property.php?id=<?php echo $property['property_id']; ?>">
                                                                        <i class="fas fa-edit me-2"></i>Edit Property
                                                                    </a>
                                                                </li>
                                                                <?php if ($property['review_status'] === 'approved'): ?>
                                                                    <li>
                                                                        <a class="dropdown-item" href="#" 
                                                                           onclick="toggleStatus(<?php echo $property['property_id']; ?>, '<?php echo $property['status'] === 'available' ? 'pending' : 'available'; ?>')">
                                                                            <i class="fas fa-power-off me-2"></i>
                                                                            <?php echo $property['status'] === 'available' ? 'Deactivate' : 'Activate'; ?>
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <?php if (($property['active_rentals'] ?? 0) == 0): ?>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li>
                                                                        <a class="dropdown-item text-danger" href="#" 
                                                                           onclick="deleteProperty(<?php echo $property['property_id']; ?>)">
                                                                            <i class="fas fa-trash me-2"></i>Delete Property
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($property['location_name'] ?? 'Unknown Location'); ?>
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <?php
                                                        // Show appropriate status badge
                                                        $review_status = $property['review_status'] ?? 'pending';
                                                        $property_status = $property['status'] ?? 'pending';
                                                        
                                                        if ($review_status === 'pending') {
                                                            $status_text = 'Waiting for employee approval';
                                                            $status_class = 'warning';
                                                        } elseif ($review_status === 'approved') {
                                                            $status_text = ucfirst($property_status);
                                                            $status_class = $property_status === 'available' ? 'success' : 
                                                                         ($property_status === 'rented' ? 'info' : 'secondary');
                                                        } elseif ($review_status === 'rejected') {
                                                            $status_text = 'Rejected';
                                                            $status_class = 'danger';
                                                        } elseif ($review_status === 'needs_revision') {
                                                            $status_text = 'Needs revision';
                                                            $status_class = 'warning';
                                                        } else {
                                                            $status_text = ucfirst($property_status);
                                                            $status_class = 'secondary';
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?php echo $status_class; ?> me-2">
                                                            <?php echo $status_text; ?>
                                                        </span>
                                                        <?php if ($property['active_rentals'] > 0): ?>
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-user me-1"></i><?php echo $property['active_rentals']; ?> Active
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <span class="text-muted small me-3">
                                                            <i class="fas fa-bed me-1"></i><?php echo $property['bedrooms']; ?> Beds
                                                        </span>
                                                        <span class="text-muted small me-3">
                                                            <i class="fas fa-bath me-1"></i><?php echo $property['bathrooms']; ?> Baths
                                                        </span>
                                                        <span class="text-muted small">
                                                            <i class="fas fa-ruler-combined me-1"></i><?php echo $property['area_sqm']; ?> sqm
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="mt-auto">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h4 class="text-primary mb-0">ETB <?php echo number_format($property['monthly_rent'], 0); ?>/mo</h4>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="text-muted small">
                                                                    <i class="fas fa-envelope me-1"></i><?php echo $property['request_count']; ?>
                                                                </span>
                                                                <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                                                    <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" 
                                                                       target="_blank" class="btn btn-sm btn-outline-info" title="Show on Map">
                                                                        <i class="fas fa-map-marked-alt"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" 
                                                                       target="_blank" class="btn btn-sm btn-outline-info" title="Search on Map">
                                                                        <i class="fas fa-search-location"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($review_status === 'rejected' && !empty($property['review_comments'])): ?>
                                                        <div class="mt-2 p-2 bg-light rounded">
                                                            <small class="text-danger">
                                                                <strong>Review Feedback:</strong> <?php echo htmlspecialchars($property['review_comments']); ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function toggleStatus(propertyId, newStatus) {
    if (confirm('Are you sure you want to ' + (newStatus === 'available' ? 'activate' : 'deactivate') + ' this property?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="property_id" value="${propertyId}">
            <input type="hidden" name="new_status" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteProperty(propertyId) {
    if (confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_property">
            <input type="hidden" name="property_id" value="${propertyId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-hide alerts
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<style>
.property-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.property-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-bottom: 10px;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.text-primary {
    color: #0d6efd !important;
}
</style>
