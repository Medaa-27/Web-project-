<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Property Deletion Center";

$employee_id = $session->getUserId();

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $property_id = $_POST['property_id'] ?? 0;
    $action = $_POST['action'];
    $reason = $_POST['reason'] ?? '';
    
    // Debug: Log the received data
    error_log("Property deletion request: ID=$property_id, Action=$action, Reason=$reason");
    
    // Validate property exists
    $sql = "SELECT * FROM properties WHERE property_id = ?";
    $stmt = $db->prepare($sql);
    $property = $db->getSingle($stmt, [$property_id]);
    
    if (!$property) {
        $_SESSION['error'] = "Property not found";
        header("Location: property-cancel.php");
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        if ($action === 'delete') {
            // Delete property images first
            $sql = "DELETE FROM property_images WHERE property_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$property_id]);
            
            // Delete related rental requests
            $sql = "DELETE FROM rental_requests WHERE property_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$property_id]);
            
            // Delete related maintenance requests
            $sql = "DELETE FROM maintenance_requests WHERE property_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$property_id]);
            
            // Delete related feedback
            $sql = "DELETE FROM feedback WHERE property_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$property_id]);
            
            // Delete related notifications
            $sql = "DELETE FROM notifications WHERE link LIKE ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, ["%property_id={$property_id}%"]);
            
            // Finally delete the property
            $sql = "DELETE FROM properties WHERE property_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$property_id]);
            
            // Create notification for property owner about deletion
            $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $message = "Your property '{$property['title']}' has been permanently deleted by an employee. Reason: {$reason}";
            $db->execute($stmt, [$property['owner_id'], 'Property Deleted', $message, 'error', '#']);
            
            $_SESSION['success'] = "Property has been permanently deleted successfully";
        }
        
        $db->commit();
        
        // Redirect to refresh the page and show the message
        header("Location: property-cancel.php");
        exit;
        
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = "Error processing property: " . $e->getMessage();
        error_log("Property processing error: " . $e->getMessage());
        header("Location: property-cancel.php");
        exit;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query conditions
$where_conditions = ["1=1"];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR u.full_name LIKE ? OR p.address LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$sql = "SELECT COUNT(*) as total 
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        {$where_clause}";
$stmt = $db->prepare($sql);
$total_result = $db->getSingle($stmt, $params);
$total_properties = $total_result['total'];
$total_pages = ceil($total_properties / $limit);

// Get properties with duplicate detection
$sql = "SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone,
               l.location_name, l.subcity,
               (SELECT COUNT(*) FROM property_images pi WHERE pi.property_id = p.property_id) as image_count,
               reviewer.full_name as reviewer_name,
               (SELECT COUNT(*) FROM rental_requests rr WHERE rr.property_id = p.property_id) as request_count,
               (SELECT AVG(rating) FROM feedback f WHERE f.property_id = p.property_id) as avg_rating,
               -- Duplicate detection: count properties with similar details
               (SELECT COUNT(*) - 1 
                FROM properties p2 
                WHERE p2.property_id != p.property_id 
                AND (
                    (p2.title = p.title AND p2.owner_id != p.owner_id) OR
                    (p2.address = p.address AND p2.owner_id != p.owner_id) OR
                    (p2.monthly_rent = p.monthly_rent AND p2.bedrooms = p.bedrooms AND p2.bathrooms = p.bathrooms AND p2.owner_id != p.owner_id)
                )
               ) as duplicate_count
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id
        {$where_clause}
        ORDER BY duplicate_count DESC, p.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$properties = $db->getMultiple($stmt, array_merge($params, [$limit, $offset]));

// Get statistics
$stats = [];
$sql = "SELECT status, COUNT(*) as count FROM properties GROUP BY status";
$stmt = $db->prepare($sql);
$status_stats = $db->getMultiple($stmt);
foreach ($status_stats as $stat) {
    $stats[$stat['status']] = $stat['count'];
}

// Get duplicate statistics
$sql = "SELECT COUNT(*) as duplicate_count 
        FROM properties p1
        JOIN properties p2 ON (
            p1.property_id < p2.property_id AND
            (
                (p1.title = p2.title AND p1.owner_id != p2.owner_id) OR
                (p1.address = p2.address AND p1.owner_id != p2.owner_id) OR
                (p1.monthly_rent = p2.monthly_rent AND p1.bedrooms = p2.bedrooms AND p1.bathrooms = p2.bathrooms AND p1.owner_id != p2.owner_id)
            )
        )";
$stmt = $db->prepare($sql);
$duplicate_result = $db->getSingle($stmt);
$total_duplicates = $duplicate_result['duplicate_count'];

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="main-container">
            <style>
/* Modern Professional Styles */
:root {
    --primary-color: #708090;
    --secondary-color: #7c3aed;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #3b82f6;
    --dark-color: #1f2937;
    --light-color: #f9fafb;
    --border-radius: 12px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.main-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    margin: 0;
    padding: 0;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.hero-section {
    background: linear-gradient(135deg, var(--danger-color), #dc2626);
    color: white;
    padding: 40px;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 30px;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
    color: white;
}

.stat-icon.duplicate { background: linear-gradient(135deg, var(--warning-color), #f97316); }
.stat-icon.available { background: linear-gradient(135deg, var(--success-color), #059669); }
.stat-icon.pending { background: linear-gradient(135deg, var(--info-color), #2563eb); }
.stat-icon.cancelled { background: linear-gradient(135deg, var(--danger-color), #dc2626); }

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
}

.filters-section {
    background: white;
    padding: 30px;
    border-bottom: 1px solid #e5e7eb;
}

.filter-form {
    display: grid;
    grid-template-columns: 1fr 2fr auto auto;
    gap: 20px;
    align-items: end;
}

.form-control, .form-select {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    transition: var(--transition);
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.btn-modern {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    transition: var(--transition);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary-modern {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
}

.btn-secondary-modern {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary-modern:hover {
    background: #e5e7eb;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
    padding: 30px;
}

.property-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.property-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.property-card.has-duplicates {
    border-left: 5px solid var(--warning-color);
}

.property-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #f8f9fa;
}

.property-image-container .card-img-top {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.property-card:hover .card-img-top {
    transform: scale(1.1);
}

.duplicate-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: var(--warning-color);
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    z-index: 10;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.property-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
    color: white;
}

.status-available { background: rgba(16, 185, 129, 0.9); }
.status-pending { background: rgba(59, 130, 246, 0.9); }
.status-cancelled { background: rgba(239, 68, 68, 0.9); }
.status-rented { background: rgba(107, 114, 128, 0.9); }

.property-content {
    padding: 25px;
}

.property-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: var(--dark-color);
    line-height: 1.3;
}

.property-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #6b7280;
}

.meta-item i {
    color: var(--primary-color);
    width: 16px;
}

.property-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--success-color);
    margin-bottom: 20px;
}

.property-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    font-size: 14px;
}

.btn-details {
    background: #f3f4f6;
    color: #374151;
}

.btn-details:hover {
    background: #e5e7eb;
}

.btn-cancel {
    background: var(--danger-color);
    color: white;
}

.btn-cancel:hover {
    background: #dc2626;
}

.btn-ignore {
    background: var(--warning-color);
    color: white;
}

.btn-ignore:hover {
    background: #d97706;
}

.duplicate-info {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #92400e;
}

.duplicate-info strong {
    color: #78350f;
}

@media (max-width: 768px) {
    .main-container {
        margin: 10px;
        border-radius: 15px;
    }
    
    .hero-section {
        padding: 25px;
    }
    
    .filter-form {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        padding: 20px;
    }
}
</style>

<!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="row align-items-center">
                <div class="col-md-12 text-center">
                    <h1 class="mb-3 fw-bold" style="font-size: 28px;">
                        <i class="fas fa-trash me-3"></i>Property Deletion Center
                    </h1>
                    <p class="mb-0" style="font-size: 14px;">Permanently delete problematic or duplicate property listings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" style="margin: 0 30px 20px 30px;">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" style="margin: 0 30px 20px 30px;">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon duplicate">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-number"><?php echo $total_duplicates; ?></div>
            <div class="stat-label">Potential Duplicates</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon available">
                <i class="fas fa-home"></i>
            </div>
            <div class="stat-number"><?php echo $stats['available'] ?? 0; ?></div>
            <div class="stat-label">Available</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon cancelled">
                <i class="fas fa-ban"></i>
            </div>
            <div class="stat-number"><?php echo $stats['cancelled'] ?? 0; ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <form method="GET" class="filter-form">
            <div>
                <label class="form-label fw-bold">Status Filter</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Properties</option>
                    <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div>
                <label class="form-label fw-bold">Search Properties</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by title, owner, or address..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn-modern btn-primary-modern">
                <i class="fas fa-search"></i> Search
            </button>
            
            <button type="button" class="btn-modern btn-secondary-modern" onclick="resetFilters()">
                <i class="fas fa-redo"></i> Reset
            </button>
        </form>
    </div>

    <!-- Properties Grid -->
    <div class="properties-grid">
        <?php if (empty($properties)): ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-home"></i>
                <h3>No Properties Found</h3>
                <p>No properties match your current filters. Try adjusting your search criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($properties as $property): ?>
                <div class="property-card <?php echo $property['duplicate_count'] > 0 ? 'has-duplicates' : ''; ?>">
                    <div class="property-image-container position-relative">
                        <?php
                        $image_url = getPropertyPrimaryImage($property['property_id']);
                        ?>
                        <img src="<?php echo $image_url; ?>" class="card-img-top" 
                             alt="<?php echo htmlspecialchars($property['title']); ?>"
                             onerror="this.src='../assets/images/default-avatar.svg'; this.onerror=null;">
                        
                        <?php if ($property['duplicate_count'] > 0): ?>
                            <div class="duplicate-badge">
                                <i class="fas fa-copy me-1"></i><?php echo $property['duplicate_count']; ?> Duplicates
                            </div>
                        <?php endif; ?>
                        
                        <span class="property-status status-<?php echo $property['status']; ?> position-absolute top-0 end-0 m-3">
                            <?php echo ucfirst($property['status']); ?>
                        </span>
                    </div>
                    
                    <div class="property-content">
                        <?php if ($property['duplicate_count'] > 0): ?>
                            <div class="duplicate-info">
                                <strong><i class="fas fa-exclamation-triangle me-1"></i>Potential Duplicates Found:</strong><br>
                                This property has <?php echo $property['duplicate_count']; ?> similar listing(s) detected. Review before taking action.
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                        
                        <div class="property-meta">
                            <div class="meta-item">
                                <i class="fas fa-home"></i>
                                <span><?php echo ucfirst($property['property_type']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-bed"></i>
                                <span><?php echo $property['bedrooms']; ?> Beds</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-bath"></i>
                                <span><?php echo $property['bathrooms']; ?> Baths</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($property['location_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        
                        <div class="property-price">
                            ETB <?php echo number_format($property['monthly_rent'], 2); ?>/mo
                        </div>
                        
                        <div class="property-actions">
                            <?php if ($property['status'] !== 'cancelled'): ?>
                                <button class="action-btn btn-delete" onclick="deleteProperty(<?php echo $property['property_id']; ?>, '<?php echo htmlspecialchars($property['title']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                    <i class="fas fa-eye-slash"></i> Ignore
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-modern">
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
        </div>
    </div>
</div>

<!-- Delete Property Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i>Delete Property
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="cancelForm">
                <div class="modal-body">
                    <input type="hidden" name="property_id" id="cancelPropertyId">
                    <input type="hidden" name="action" value="delete">
                    
                    <div class="alert alert-danger">
                        <h6 class="mb-2" id="cancelPropertyTitle"></h6>
                        <p class="mb-0"><strong>Warning:</strong> This action will <strong>permanently delete</strong> the property and all related data including:</p>
                        <ul class="mb-2">
                            <li>All property images</li>
                            <li>All rental requests</li>
                            <li>All maintenance requests</li>
                            <li>All feedback and reviews</li>
                            <li>All related notifications</li>
                        </ul>
                        <p class="mb-0"><strong>This action cannot be undone!</strong></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label fw-bold">
                            Deletion Reason <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="cancelReason" name="reason" required>
                            <option value="">Select a reason...</option>
                            <option value="Duplicate listing">Duplicate listing</option>
                            <option value="Inaccurate information">Inaccurate information</option>
                            <option value="Violation of terms">Violation of terms</option>
                            <option value="Property not available">Property not available</option>
                            <option value="Fraudulent listing">Fraudulent listing</option>
                            <option value="Other">Other (specify in comments)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Property Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white">
                <h5 class="modal-title">
                    <i class="fas fa-home me-2"></i>Property Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="propertyDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
<script>
function deleteProperty(propertyId, propertyTitle) {
    document.getElementById('cancelPropertyId').value = propertyId;
    document.getElementById('cancelPropertyTitle').textContent = propertyTitle;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

                        </span>
                        ${property.duplicate_count > 0 ? '<span class="badge bg-warning"><i class="fas fa-copy me-1"></i>' + property.duplicate_count + ' Duplicates</span>' : ''}
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <h3 class="text-success mb-0">${formatCurrency(property.monthly_rent)}</h3>
                    <small class="text-muted">per month</small>
                </div>
            </div>
            
            <!-- Duplicate Information -->
            ${property.duplicate_count > 0 ? `
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Duplicate Detection</h6>
                <p class="mb-0">This property has <strong>${property.duplicate_count}</strong> similar listing(s) detected. Similar properties are identified by matching:</p>
                <ul class="mb-0 mt-2">
                    <li>Property title (different owners)</li>
                    <li>Property address (different owners)</li>
                    <li>Price, bedrooms, and bathrooms (different owners)</li>
                </ul>
            </div>
            ` : ''}
            
            <!-- Detailed Information -->
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-info-circle me-2"></i>Property Details
                    </h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="40%"><strong>Property Type:</strong></td>
                            <td>${property.property_type}</td>
                        </tr>
                        <tr>
                            <td><strong>Bedrooms:</strong></td>
                            <td>${property.bedrooms}</td>
                        </tr>
                        <tr>
                            <td><strong>Bathrooms:</strong></td>
                            <td>${property.bathrooms}</td>
                        </tr>
                        <tr>
                            <td><strong>Area:</strong></td>
                            <td>${property.area_sqm || 'N/A'} sqm</td>
                        </tr>
                        <tr>
                            <td><strong>Monthly Rent:</strong></td>
                            <td>${formatCurrency(property.monthly_rent)}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-user me-2"></i>Owner Information
                    </h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="40%"><strong>Owner:</strong></td>
                            <td>${property.owner_name}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>${property.owner_email}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>${property.owner_phone || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>Property ID:</strong></td>
                            <td>#${property.property_id}</td>
                        </tr>
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td>${new Date(property.created_at).toLocaleDateString()}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            ${property.description ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-align-left me-2"></i>Description
                    </h6>
                    <p class="text-muted">${property.description}</p>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('propertyDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

// Form submission
document.getElementById('cancelForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('cancelReason').value;
    if (!reason) {
        e.preventDefault();
        alert('Please select a cancellation reason');
        return false;
    }
    
    // Allow form to submit normally
    return true;
});
</script>

<?php include '../includes/footer.php'; ?>
