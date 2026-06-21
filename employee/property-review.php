<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

$session->requireRole('employee');
$title = "Property Review Center";

$employee_id = $session->getUserId();

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $property_id = $_POST['property_id'] ?? 0;
    $action = $_POST['action'];
    $comments = $_POST['comments'] ?? '';
    
    // Validate property exists and is pending
    $sql = "SELECT * FROM properties WHERE property_id = ? AND review_status = 'pending'";
    $stmt = $db->prepare($sql);
    $property = $db->getSingle($stmt, [$property_id]);
    
    if (!$property) {
        $_SESSION['error'] = "Property not found or already reviewed";
        header("Location: property-review.php");
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Update property review status
        $review_status = match($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'request_revision' => 'needs_revision',
            default => throw new Exception("Invalid action")
        };
        
        $property_status = ($action === 'approve') ? 'available' : 'pending';
        
        $sql = "UPDATE properties 
                SET review_status = ?, status = ?, reviewed_by = ?, 
                    review_date = NOW(), review_comments = ?
                WHERE property_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [$review_status, $property_status, $employee_id, $comments, $property_id]);
        
        // Create notification for property owner
        $notification_title = match($action) {
            'approve' => 'Property Approved',
            'reject' => 'Property Rejected',
            'request_revision' => 'Property Revision Requested'
        };
        
        $notification_message = match($action) {
            'approve' => "Your property '{$property['title']}' has been approved and is now visible to tenants.",
            'reject' => "Your property '{$property['title']}' has been rejected. Reason: {$comments}",
            'request_revision' => "Your property '{$property['title']}' needs revision. Please update the information and resubmit. Comments: {$comments}"
        };
        
        $notification_type = match($action) {
            'approve' => 'success',
            'reject' => 'error',
            'request_revision' => 'warning'
        };
        
        $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $link = "../owner/edit-property.php?id={$property_id}";
        $db->execute($stmt, [$property['owner_id'], $notification_title, $notification_message, $notification_type, $link]);
        
        $db->commit();
        
        $_SESSION['success'] = "Property has been " . ucfirst($review_status) . " successfully";
        
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = "Error reviewing property: " . $e->getMessage();
    }
    
    header("Location: property-review.php");
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

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

// Get total count
$sql = "SELECT COUNT(*) as total 
        FROM properties p
        JOIN users u ON p.owner_id = u.user_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        {$where_clause}";
$stmt = $db->prepare($sql);
$total_result = $db->getSingle($stmt, $params);
$total_properties = $total_result['total'];
$total_pages = ceil($total_properties / $limit);

// Get properties with full details
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
        ORDER BY 
            CASE 
                WHEN p.review_status = 'pending' THEN 1
                WHEN p.review_status = 'needs_revision' THEN 2
                ELSE 3
            END,
            p.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$properties = $db->getMultiple($stmt, array_merge($params, [$limit, $offset]));

// Get statistics
$stats = [];
$sql = "SELECT review_status, COUNT(*) as count FROM properties GROUP BY review_status";
$stmt = $db->prepare($sql);
$review_stats = $db->getMultiple($stmt);
foreach ($review_stats as $stat) {
    $stats[$stat['review_status']] = $stat['count'];
}

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
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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

.stat-icon.pending { background: linear-gradient(135deg, var(--warning-color), #f97316); }
.stat-icon.approved { background: linear-gradient(135deg, var(--success-color), #059669); }
.stat-icon.rejected { background: linear-gradient(135deg, var(--danger-color), #dc2626); }
.stat-icon.revision { background: linear-gradient(135deg, var(--info-color), #2563eb); }

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
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
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
}

.property-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
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

.property-status {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
    color: white;
}

.status-pending { background: rgba(245, 158, 11, 0.9); }
.status-approved { background: rgba(16, 185, 129, 0.9); }
.status-rejected { background: rgba(239, 68, 68, 0.9); }
.status-needs_revision { background: rgba(59, 130, 246, 0.9); }

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

.btn-approve {
    background: var(--success-color);
    color: white;
}

.btn-approve:hover {
    background: #059669;
}

.btn-reject {
    background: var(--danger-color);
    color: white;
}

.btn-reject:hover {
    background: #dc2626;
}

.btn-revision {
    background: var(--warning-color);
    color: white;
}

.btn-revision:hover {
    background: #d97706;
}

.pagination-modern {
    padding: 30px;
    display: flex;
    justify-content: center;
}

.pagination-modern .pagination {
    display: flex;
    gap: 10px;
}

.pagination-modern .page-link {
    padding: 10px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    transition: var(--transition);
}

.pagination-modern .page-link:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.pagination-modern .page-item.active .page-link {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-color: var(--primary-color);
    color: white;
}

.modal-content {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    border: none;
    padding: 25px;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    border: none;
    padding: 25px;
    background: #f9fafb;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
}

.empty-state {
    text-align: center;
    padding: 60px 30px;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #6b7280;
    margin-bottom: 10px;
}

.empty-state p {
    color: #9ca3af;
}

.image-gallery {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

.main-image-container {
    position: relative;
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.thumbnail-item {
    transition: all 0.3s ease;
}

.thumbnail-item:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.primary-thumbnail {
    border: 2px solid var(--success-color);
}

.primary-thumbnail img {
    border-color: var(--success-color) !important;
}

.thumbnail-number {
    opacity: 0.8;
}

.modal-xl {
    max-width: 95%;
}

.modal-dark .modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
}

.modal-dark .modal-header,
.modal-dark .modal-footer {
    border-color: #333;
}

.image-navigation {
    background: rgba(0, 0, 0, 0.7);
    border-radius: 8px;
    padding: 10px;
}

.image-navigation .btn {
    border-radius: 20px;
    padding: 8px 16px;
}

.thumbnail-nav img {
    border: 2px solid transparent;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.thumbnail-nav img:hover {
    border-color: var(--primary-color);
    transform: scale(1.1);
}

.thumbnail-nav img.active {
    border-color: var(--primary-color);
    box-shadow: 0 0 10px rgba(79, 70, 229, 0.5);
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
    
    .image-gallery {
        padding: 10px;
    }
    
    .thumbnails-container .row {
        gap: 1px;
    }
    
    .thumbnails-container .col-6 {
        padding: 2px;
    }
    
    .modal-xl {
        max-width: 100%;
        margin: 0;
    }
}
</style>

<!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="row align-items-center">
                <div class="col-md-12 text-center">
                    <h1 class="mb-3 fw-bold" style="font-size: 28px;">
                        <i class="fas fa-clipboard-check me-3"></i>Property Review Center
                    </h1>
                    <p class="mb-0" style="font-size: 14px;">Streamlined property approval workflow with advanced analytics and management tools</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?php echo $stats['approved'] ?? 0; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon rejected">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-number"><?php echo $stats['rejected'] ?? 0; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon revision">
                <i class="fas fa-edit"></i>
            </div>
            <div class="stat-number"><?php echo $stats['needs_revision'] ?? 0; ?></div>
            <div class="stat-label">Needs Revision</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <form method="GET" class="filter-form">
            <div>
                <label class="form-label fw-bold">Status Filter</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Properties</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="needs_revision" <?php echo $status_filter === 'needs_revision' ? 'selected' : ''; ?>>Needs Revision</option>
                </select>
            </div>
            
            <div>
                <label class="form-label fw-bold">Search Properties</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by title, owner, or location..." 
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
                <div class="property-card">
                    <div class="property-image-container position-relative">
                        <?php
                        // Direct database query for more reliable image handling
                        $sql = "SELECT image_url FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id DESC LIMIT 1";
                        $stmt = $db->prepare($sql);
                        $image = $db->getSingle($stmt, [$property['property_id']]);
                        
                        if ($image && !empty($image['image_url'])) {
                            $filename = basename($image['image_url']);
                            $final_image_url = '../assets/uploads/properties/' . $filename;
                            
                            // Double-check file exists
                            $full_path = UPLOAD_PATH . 'properties/' . $filename;
                            if (!file_exists($full_path)) {
                                $final_image_url = '../assets/images/default-avatar.svg';
                            }
                        } else {
                            $final_image_url = '../assets/images/default-avatar.svg';
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($final_image_url); ?>" class="card-img-top" 
                             alt="<?php echo htmlspecialchars($property['title']); ?>"
                             onerror="this.src='../assets/images/default-avatar.svg'; this.onerror=null; console.log('Image failed to load:', this.src);"
                             style="width: 100%; height: 200px; object-fit: cover; background: #f8f9fa;">
                        
                        <span class="property-status status-<?php echo $property['review_status']; ?> position-absolute top-0 end-0 m-3">
                            <?php echo ucfirst(str_replace('_', ' ', $property['review_status'])); ?>
                        </span>
                    </div>
                    
                    <div class="property-content">
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
                            <button class="action-btn btn-details" onclick="showPropertyDetails(<?php echo $property['property_id']; ?>)">
                                <i class="fas fa-eye"></i> Details
                            </button>
                            
                            <?php if ($property['review_status'] === 'pending'): ?>
                                <button class="action-btn btn-approve" onclick="reviewProperty(<?php echo $property['property_id']; ?>, 'approve')">
                                    Approve
                                </button>
                                <button class="action-btn btn-reject" onclick="reviewProperty(<?php echo $property['property_id']; ?>, 'reject')">
                                    Reject
                                </button>
                                <button class="action-btn btn-revision" onclick="reviewProperty(<?php echo $property['property_id']; ?>, 'request_revision')">
                                    Revision
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

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white" id="reviewModalHeader">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2" id="reviewModalIcon"></i>
                    <span id="reviewModalTitle">Review Property</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" name="property_id" id="reviewPropertyId">
                    <input type="hidden" name="action" id="reviewAction">
                    
                    <div class="alert alert-info" id="propertyInfo">
                        <h6 class="mb-2" id="propertyTitle"></h6>
                        <p class="mb-0">Owner: <span id="propertyOwner"></span></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reviewComments" class="form-label fw-bold">
                            Review Comments <span id="commentsRequired">*</span>
                        </label>
                        <textarea class="form-control" id="reviewComments" name="comments" rows="4" required
                                  placeholder="Enter your review comments..."></textarea>
                        <div class="form-text" id="commentsHelp"></div>
                    </div>
                    
                    <div class="alert alert-warning d-none" id="reviewGuidance">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="guidanceText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn" id="reviewSubmitBtn">
                        <i class="fas fa-paper-plane me-2"></i>
                        <span id="submitBtnText">Submit Review</span>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let allProperties = <?php echo json_encode($properties, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let propertyImages = {};

// Pre-load image URLs for all properties using direct database approach
<?php foreach ($properties as $property): ?>
<?php
// Get the primary image directly for JavaScript
$sql = "SELECT image_url FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id DESC LIMIT 1";
$stmt = $db->prepare($sql);
$image = $db->getSingle($stmt, [$property['property_id']]);

if ($image && !empty($image['image_url'])) {
    $filename = basename($image['image_url']);
    $image_url = '../assets/uploads/properties/' . $filename;
    
    // Check if file exists
    $full_path = UPLOAD_PATH . 'properties/' . $filename;
    if (!file_exists($full_path)) {
        $image_url = '../assets/images/default-avatar.svg';
    }
} else {
    $image_url = '../assets/images/default-avatar.svg';
}
?>
propertyImages[<?php echo $property['property_id']; ?>] = '<?php echo $image_url; ?>';
<?php endforeach; ?>

// Utility functions
function parsePropertyRules(rulesJson) {
    if (!rulesJson) return [];
    try {
        return JSON.parse(rulesJson);
    } catch (err) {
        console.error('Failed to parse property rules:', err);
        return [];
    }
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function resetFilters() {
    // Clear all form values
    document.querySelector('select[name="status"]').value = 'all';
    document.querySelector('input[name="search"]').value = '';
    
    // Submit the form with cleared values
    window.location.href = 'property-review.php';
}

function showStatistics() {
    const total = <?php echo array_sum($stats); ?>;
    const pending = <?php echo $stats['pending'] ?? 0; ?>;
    const approved = <?php echo $stats['approved'] ?? 0; ?>;
    const rejected = <?php echo $stats['rejected'] ?? 0; ?>;
    const revision = <?php echo $stats['needs_revision'] ?? 0; ?>;
    
    alert(`Property Review Statistics:\n\nTotal Properties: ${total}\nPending Review: ${pending}\nApproved: ${approved}\nRejected: ${rejected}\nNeeds Revision: ${revision}`);
}

function showBulkActions() {
    alert('Bulk actions feature coming soon! This will allow you to review multiple properties at once.');
}

function showPropertyDetails(propertyId) {
    const property = allProperties.find(p => p.property_id == propertyId);
    if (!property) return;
    
    // Format currency
    const formatCurrency = (amount) => `ETB ${Number(amount).toLocaleString()}`;
    
    // Status badge styling
    const getStatusBadge = (status) => {
        const statusColors = {
            'pending': 'warning',
            'approved': 'success', 
            'rejected': 'danger',
            'needs_revision': 'info'
        };
        const color = statusColors[status] || 'secondary';
        return `<span class="badge bg-${color}">${status.replace('_', ' ').toUpperCase()}</span>`;
    };
    
    // Rating stars
    const getRatingStars = (rating) => {
        if (!rating) return 'N/A';
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5 ? 1 : 0;
        const emptyStars = 5 - fullStars - halfStar;
        
        let stars = '';
        for (let i = 0; i < fullStars; i++) stars += '★';
        if (halfStar) stars += '☆';
        for (let i = 0; i < emptyStars; i++) stars += '☆';
        
        return `${stars} ${rating.toFixed(1)}`;
    };
    
    const content = `
        <div class="property-details-modal">
            <!-- Property Header -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h4 class="mb-2">${property.title}</h4>
                    <p class="text-muted mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>${property.location_name || 'N/A'}
                        ${property.subcity ? `, ${property.subcity}` : ''}
                    </p>
                    <div class="d-flex align-items-center gap-3">
                        ${getStatusBadge(property.review_status)}
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-home me-1"></i>${property.property_type}
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <h3 class="text-success mb-0">${formatCurrency(property.monthly_rent)}</h3>
                    <small class="text-muted">per month</small>
                </div>
            </div>
            
            <!-- Property Images -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-images me-2"></i>Property Images (${property.image_count || 0})
                    </h6>
                    <div class="property-images-preview" id="propertyImages${propertyId}">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin"></i> Loading images...
                        </div>
                    </div>
                </div>
            </div>
            
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
                            <td>${property.bedrooms} ${property.bedrooms == 1 ? 'Bedroom' : 'Bedrooms'}</td>
                        </tr>
                        <tr>
                            <td><strong>Bathrooms:</strong></td>
                            <td>${property.bathrooms} ${property.bathrooms == 1 ? 'Bathroom' : 'Bathrooms'}</td>
                        </tr>
                        <tr>
                            <td><strong>Furnished:</strong></td>
                            <td>
                                <span class="badge ${property.is_furnished ? 'bg-success' : 'bg-secondary'}">
                                    ${property.is_furnished ? 'Furnished' : 'Unfurnished'}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Monthly Rent:</strong></td>
                            <td class="text-success fw-bold">${formatCurrency(property.monthly_rent)}</td>
                        </tr>
                        <tr>
                            <td><strong>Security Deposit:</strong></td>
                            <td>${property.security_deposit ? formatCurrency(property.security_deposit) : 'N/A'}</td>
                        </tr>
                    </table>

                    <div class="mt-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-list-ul me-2"></i>Property Rules
                        </h6>
                        ${(() => {
                            const rules = parsePropertyRules(property.property_rules);
                            if (!rules.length) {
                                return '<div class="text-muted">No property rules have been provided.</div>';
                            }
                            return `<div class="list-group">${rules.map(rule => `
                                <div class="list-group-item p-3">
                                    <h6 class="mb-1">${escapeHtml(rule.title)}</h6>
                                    <p class="mb-0 text-muted">${escapeHtml(rule.description || 'No additional details.')}</p>
                                </div>
                            `).join('')}</div>`;
                        })()}
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-user me-2"></i>Owner Information
                    </h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="40%"><strong>Name:</strong></td>
                            <td>${property.owner_name}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><a href="mailto:${property.owner_email}">${property.owner_email}</a></td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>${property.owner_phone || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Requests:</strong></td>
                            <td>${property.request_count || 0}</td>
                        </tr>
                        <tr>
                            <td><strong>Average Rating:</strong></td>
                            <td>${getRatingStars(property.avg_rating)}</td>
                        </tr>
                        <tr>
                            <td><strong>Member Since:</strong></td>
                            <td>${property.owner_created_at ? new Date(property.owner_created_at).toLocaleDateString() : 'N/A'}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Review Information -->
            <div class="row mt-4">
                <div class="col-12">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-clipboard-check me-2"></i>Review Information
                    </h6>
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Review Status</small>
                                    <div class="fw-bold">${getStatusBadge(property.review_status)}</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Property Status</small>
                                    <div class="fw-bold">
                                        <span class="badge ${property.status === 'available' ? 'bg-success' : 'bg-warning'}">
                                            ${property.status.toUpperCase()}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Reviewed By</small>
                                    <div class="fw-bold">${property.reviewer_name || 'Not reviewed yet'}</div>
                                </div>
                            </div>
                            ${property.review_date ? `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <small class="text-muted">Review Date</small>
                                        <div class="fw-bold">${new Date(property.review_date).toLocaleString()}</div>
                                    </div>
                                </div>
                            ` : ''}
                            ${property.review_comments ? `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <small class="text-muted">Review Comments</small>
                                        <div class="mt-1 p-2 bg-white rounded">${property.review_comments}</div>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            ${property.description ? `
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-align-left me-2"></i>Property Description
                        </h6>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0">${property.description}</p>
                            </div>
                        </div>
                    </div>
                </div>
            ` : ''}
            
            <!-- Timestamps -->
            <div class="row mt-4">
                <div class="col-12">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Created: ${new Date(property.created_at).toLocaleString()}
                        ${property.updated_at ? `| Updated: ${new Date(property.updated_at).toLocaleString()}` : ''}
                    </small>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('propertyDetailsContent').innerHTML = content;
    
    // Load property images
    loadPropertyImages(propertyId);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

// Function to load property images
function loadPropertyImages(propertyId) {
    const imagesContainer = document.getElementById(`propertyImages${propertyId}`);
    if (imagesContainer) {
        const property = allProperties.find(p => p.property_id == propertyId);
        
        console.log('Loading images for property', propertyId, 'Count:', property.image_count);
        
        // Show loading state
        imagesContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading images...</span>
                </div>
                <p class="mt-2 text-muted">Loading ${property.image_count || 0} images...</p>
            </div>
        `;
        
        // Fetch all property images via AJAX
        console.log('Fetching images for property:', propertyId);
        fetch(`../api/get-property-images.php?property_id=${propertyId}`)
            .then(response => {
                console.log('API Response status:', response.status);
                return response.json();
            })
            .then(images => {
                console.log('API Response data:', images);
                if (images.success && images.data.length > 0) {
                    displayImageGallery(imagesContainer, images.data, property);
                } else {
                    console.log('No images found, using fallback');
                    // Fallback to primary image if no images found
                    displayFallbackImage(imagesContainer, propertyId, property);
                }
            })
            .catch(error => {
                console.error('Error loading images:', error);
                displayFallbackImage(imagesContainer, propertyId, property);
            });
    }
}

// Function to display image gallery
function displayImageGallery(container, images, property) {
    const galleryHtml = `
        <div class="image-gallery">
            <!-- Main Image Display -->
            <div class="main-image-container mb-3">
                <div class="border rounded overflow-hidden bg-light">
                    <img id="mainImage" src="${images[0].url}" class="img-fluid w-100" 
                         style="max-height: 400px; object-fit: cover; cursor: pointer;"
                         onclick="openImageModal('${images[0].url}', '${property.title}')"
                         alt="Main property image">
                    <div class="image-overlay d-none">
                        <div class="text-white text-center">
                            <i class="fas fa-search-plus fa-2x"></i>
                            <p class="mb-0">Click to enlarge</p>
                        </div>
                    </div>
                </div>
                <div class="mt-2 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary">
                            <i class="fas fa-images me-1"></i>
                            ${images.length} total images
                        </span>
                        ${images[0].is_primary ? '<span class="badge bg-success ms-1"><i class="fas fa-star me-1"></i>Primary</span>' : ''}
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="openImageModal('${images[0].url}', '${property.title}')">
                        <i class="fas fa-expand me-1"></i>View Full Size
                    </button>
                </div>
            </div>
            
            <!-- Thumbnail Gallery -->
            <div class="thumbnails-container">
                <h6 class="text-muted mb-2">
                    <i class="fas fa-th me-1"></i>All Images (${images.length})
                </h6>
                <div class="row g-2">
                    ${images.map((image, index) => `
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="thumbnail-item ${image.is_primary ? 'primary-thumbnail' : ''}" 
                                 onclick="changeMainImage('${image.url}', ${index}, ${image.is_primary})"
                                 style="cursor: pointer; position: relative;">
                                <img src="${image.url}" class="img-fluid rounded border" 
                                     style="height: 80px; width: 100%; object-fit: cover; transition: all 0.3s;"
                                     alt="Property image ${index + 1}"
                                     onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                                ${image.is_primary ? `
                                    <div class="primary-badge">
                                        <span class="badge bg-success" style="position: absolute; top: 5px; right: 5px; font-size: 0.7rem;">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    </div>
                                ` : ''}
                                <div class="thumbnail-number" style="position: absolute; bottom: 5px; left: 5px;">
                                    <span class="badge bg-dark" style="font-size: 0.6rem;">${index + 1}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
        
        <!-- Image Modal for Full Screen View -->
        <div class="modal fade" id="imageModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-image me-2"></i>${property.title} - Image Viewer
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="text-center">
                            <img id="modalImage" src="" class="img-fluid" style="max-height: 80vh;" alt="Property image">
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-dark">
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-white">
                                    <span id="currentImageIndex">1</span> / ${images.length}
                                </div>
                                <div>
                                    <button class="btn btn-outline-light btn-sm me-2" onclick="previousImage()">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button class="btn btn-outline-light btn-sm" onclick="nextImage()">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Thumbnail Navigation -->
                            <div class="mt-3">
                                <div class="d-flex gap-2 overflow-auto">
                                    ${images.map((image, index) => `
                                        <img src="${image.url}" class="img-thumbnail" 
                                             style="height: 50px; width: 50px; object-fit: cover; cursor: pointer;"
                                             onclick="showModalImage(${index})"
                                             alt="Thumbnail ${index + 1}">
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = galleryHtml;
    
    // Add hover effect to main image
    const mainImageContainer = container.querySelector('.main-image-container');
    const mainImage = container.querySelector('#mainImage');
    const overlay = container.querySelector('.image-overlay');
    
    if (mainImageContainer && mainImage && overlay) {
        mainImageContainer.addEventListener('mouseenter', () => {
            overlay.classList.remove('d-none');
        });
        mainImageContainer.addEventListener('mouseleave', () => {
            overlay.classList.add('d-none');
        });
    }
    
    // Store images data for modal navigation
    window.currentPropertyImages = images;
    window.currentImageIndex = 0;
}

// Function to change main image
function changeMainImage(imageUrl, index, isPrimary) {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.src = imageUrl;
        mainImage.onclick = () => openImageModal(imageUrl, mainImage.alt);
        
        // Update primary badge
        const badges = mainImage.parentElement.parentElement.querySelectorAll('.badge');
        badges.forEach(badge => {
            if (badge.classList.contains('bg-success')) {
                badge.remove();
            }
        });
        
        if (isPrimary) {
            const badgeContainer = mainImage.parentElement.parentElement.querySelector('.d-flex');
            const primaryBadge = document.createElement('span');
            primaryBadge.className = 'badge bg-success ms-1';
            primaryBadge.innerHTML = '<i class="fas fa-star me-1"></i>Primary';
            badgeContainer.appendChild(primaryBadge);
        }
    }
}

// Function to open image modal
function openImageModal(imageUrl, propertyTitle) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.querySelector('#imageModal .modal-title');
    
    modalImage.src = imageUrl;
    modalTitle.innerHTML = `<i class="fas fa-image me-2"></i>${propertyTitle} - Image Viewer`;
    
    // Find current image index
    window.currentImageIndex = window.currentPropertyImages.findIndex(img => img.url === imageUrl);
    updateImageIndex();
    
    modal.show();
}

// Function to show specific image in modal
function showModalImage(index) {
    window.currentImageIndex = index;
    const modalImage = document.getElementById('modalImage');
    modalImage.src = window.currentPropertyImages[index].url;
    updateImageIndex();
}

// Function to navigate to previous image
function previousImage() {
    if (window.currentImageIndex > 0) {
        window.currentImageIndex--;
        const modalImage = document.getElementById('modalImage');
        modalImage.src = window.currentPropertyImages[window.currentImageIndex].url;
        updateImageIndex();
    }
}

// Function to navigate to next image
function nextImage() {
    if (window.currentImageIndex < window.currentPropertyImages.length - 1) {
        window.currentImageIndex++;
        const modalImage = document.getElementById('modalImage');
        modalImage.src = window.currentPropertyImages[window.currentImageIndex].url;
        updateImageIndex();
    }
}

// Function to update image index display
function updateImageIndex() {
    const indexDisplay = document.getElementById('currentImageIndex');
    if (indexDisplay) {
        indexDisplay.textContent = window.currentImageIndex + 1;
    }
}

// Function to display fallback image
function displayFallbackImage(container, propertyId, property) {
    let primaryImageUrl = propertyImages[propertyId] || '../assets/images/default-avatar.svg';
    
    // Ensure correct path format
    if (!primaryImageUrl.startsWith('../assets/')) {
        if (primaryImageUrl.startsWith('assets/')) {
            primaryImageUrl = '../' + primaryImageUrl;
        } else {
            primaryImageUrl = '../assets/images/default-avatar.svg';
        }
    }
    
    container.innerHTML = `
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>No additional images found</strong>
        </div>
        <div class="border rounded p-2 text-center bg-light">
            <img src="${primaryImageUrl}" class="img-fluid rounded shadow-sm" alt="Property" 
                 style="max-height: 300px; width: auto; object-fit: cover;"
                 onerror="this.src='../assets/images/default-avatar.svg'; this.onerror=null;">
            <div class="mt-2">
                <span class="badge bg-secondary">
                    <i class="fas fa-image me-1"></i>
                    Primary image only
                </span>
            </div>
        </div>
    `;
}

function reviewProperty(propertyId, action) {
    const property = allProperties.find(p => p.property_id == propertyId);
    if (!property) return;
    
    // Set form values
    document.getElementById('reviewPropertyId').value = propertyId;
    document.getElementById('reviewAction').value = action;
    document.getElementById('propertyTitle').textContent = property.title;
    document.getElementById('propertyOwner').textContent = property.owner_name;
    
    // Get modal elements
    const modalHeader = document.getElementById('reviewModalHeader');
    const modalIcon = document.getElementById('reviewModalIcon');
    const modalTitle = document.getElementById('reviewModalTitle');
    const submitBtn = document.getElementById('reviewSubmitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const commentsField = document.getElementById('reviewComments');
    const commentsRequired = document.getElementById('commentsRequired');
    const commentsHelp = document.getElementById('commentsHelp');
    const guidance = document.getElementById('reviewGuidance');
    const guidanceText = document.getElementById('guidanceText');
    
    // Update modal based on action
    switch(action) {
        case 'approve':
            modalHeader.className = 'modal-header bg-success text-white';
            modalIcon.className = 'fas fa-check-circle me-2';
            modalTitle.textContent = 'Approve Property';
            submitBtn.className = 'btn btn-success';
            submitBtnText.textContent = 'Approve Property';
            commentsField.required = false;
            commentsRequired.textContent = '';
            commentsHelp.textContent = 'Optional: Add comments for the property owner.';
            guidance.className = 'alert alert-success';
            guidanceText.textContent = 'This property will be approved and become visible to tenants.';
            break;
            
        case 'reject':
            modalHeader.className = 'modal-header bg-danger text-white';
            modalIcon.className = 'fas fa-times-circle me-2';
            modalTitle.textContent = 'Reject Property';
            submitBtn.className = 'btn btn-danger';
            submitBtnText.textContent = 'Reject Property';
            commentsField.required = true;
            commentsRequired.textContent = '*';
            commentsHelp.textContent = 'Please provide a clear reason for rejection.';
            guidance.className = 'alert alert-danger';
            guidanceText.textContent = 'This property will be rejected and the owner will need to make changes.';
            break;
            
        case 'request_revision':
            modalHeader.className = 'modal-header bg-warning text-white';
            modalIcon.className = 'fas fa-edit me-2';
            modalTitle.textContent = 'Request Revision';
            submitBtn.className = 'btn btn-warning';
            submitBtnText.textContent = 'Request Revision';
            commentsField.required = true;
            commentsRequired.textContent = '*';
            commentsHelp.textContent = 'Please specify what changes are needed.';
            guidance.className = 'alert alert-warning';
            guidanceText.textContent = 'The property owner will be notified about required revisions.';
            break;
    }
    
    guidance.classList.remove('d-none');
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

// Initialize tooltips and other UI elements
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle review form submission
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('reviewSubmitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Disable submit button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            
            // Submit the form via fetch
            fetch('property-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                modal.hide();
                
                // Show success message and reload page
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                alert('An error occurred while submitting the review. Please try again.');
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
