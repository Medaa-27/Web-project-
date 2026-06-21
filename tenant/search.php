<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Search Properties";

// Handle property details view
$show_details = false;
if (isset($_GET['details']) && is_numeric($_GET['details'])) {
    $property_id = $_GET['details'];
    $sql = "SELECT p.*, l.location_name, l.subcity, u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email,
               (SELECT image_url FROM property_images WHERE property_id = p.property_id AND is_primary = 1 LIMIT 1) as primary_image
            FROM properties p
            LEFT JOIN locations l ON p.location_id = l.location_id
            LEFT JOIN users u ON p.owner_id = u.user_id
            WHERE p.property_id = ? AND p.status IN ('available', 'requested', 'rented') AND p.review_status = 'approved'";
    $stmt = $db->prepare($sql);
    $property = $db->getSingle($stmt, [$property_id]);
    if ($property) {
        // If there is an active rental agreement, keep the status consistent.
        $agreementStmt = $db->prepare("SELECT agreement_id FROM rental_agreements WHERE property_id = ? AND status = 'active' LIMIT 1");
        $agreement = $db->getSingle($agreementStmt, [$property_id]);
        if ($agreement && $property['status'] !== 'rented') {
            $property['status'] = 'rented';
            $updateStatusStmt = $db->prepare("UPDATE properties SET status = 'rented', updated_at = NOW() WHERE property_id = ?");
            $db->execute($updateStatusStmt, [$property_id]);
        }

        $show_details = true;
        // Track view
        if ($session->isLoggedIn() && $session->getUserRole() == 'tenant') {
            $log_entry = date('Y-m-d H:i:s') . " - Tenant ID: " . $session->getUserId() . " viewed Property ID: $property_id\n";
            file_put_contents('../logs/property_views.log', $log_entry, FILE_APPEND | LOCK_EX);
        }
        // Get images
        $sql = "SELECT image_url FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id ASC";
        $stmt = $db->prepare($sql);
        $images = $db->getMultiple($stmt, [$property_id]);
    }
}

// Search filters
$filters = [
    'property_type' => $_GET['property_type'] ?? '',
    'location' => $_GET['location'] ?? '',
    'bedrooms' => $_GET['bedrooms'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'is_furnished' => $_GET['is_furnished'] ?? '',
    'amenities' => $_GET['amenities'] ?? []
];

$priceValidationError = '';
$minPriceInput = $filters['min_price'];
$maxPriceInput = $filters['max_price'];
$minPrice = $minPriceInput !== '' && is_numeric($minPriceInput) ? $minPriceInput : null;
$maxPrice = $maxPriceInput !== '' && is_numeric($maxPriceInput) ? $maxPriceInput : null;

if ($minPriceInput !== '' && !is_numeric($minPriceInput)) {
    $priceValidationError = 'Minimum price must be a valid number.';
} elseif ($maxPriceInput !== '' && !is_numeric($maxPriceInput)) {
    $priceValidationError = 'Maximum price must be a valid number.';
} elseif ($minPrice !== null && $maxPrice !== null && $minPrice >= $maxPrice) {
    $priceValidationError = 'Maximum price must be greater than minimum price.';
}

// Get all locations for filter (use subcity for dropdowns)
$sql = "SELECT DISTINCT subcity FROM locations WHERE subcity IS NOT NULL AND subcity != '' ORDER BY subcity";
$stmt = $db->prepare($sql);
$locations = $db->getMultiple($stmt);

// Get property types for filter
$property_types = ['house', 'apartment', 'villa', 'condominium', 'commercial'];

// Track property views for analytics (placeholder until property_views table is created)
if (isset($_GET['viewed']) && is_numeric($_GET['viewed'])) {
    // Property view tracking will be implemented when property_views table is created
    // For now, we'll just log the view without database storage
    $property_id = $_GET['viewed'];
    $user_id = $session->getUserId();
    
    // Log view to file for debugging (temporary solution)
    $log_entry = date('Y-m-d H:i:s') . " - Tenant ID: $user_id viewed Property ID: $property_id\n";
    file_put_contents('../logs/property_views.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// Build query with filters
$select_part = "p.*, l.location_name, l.subcity, u.full_name as owner_name,
               (SELECT image_url FROM property_images WHERE property_id = p.property_id AND is_primary = 1 LIMIT 1) as primary_image";
               
$from_part = "FROM properties p
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE p.status = 'available' AND p.review_status = 'approved'";
        
$params = [];
$conditions = [];

$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR l.location_name LIKE ? OR l.subcity LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($filters['property_type'])) {
    $conditions[] = "p.property_type = ?";
    $params[] = $filters['property_type'];
}

if (!empty($filters['location'])) {
    $conditions[] = "(l.location_name LIKE ? OR l.subcity LIKE ?)";
    $params[] = "%{$filters['location']}%";
    $params[] = "%{$filters['location']}%";
}

if (!empty($filters['bedrooms']) && is_numeric($filters['bedrooms'])) {
    $conditions[] = "p.bedrooms >= ?";
    $params[] = $filters['bedrooms'];
}

if (!empty($filters['is_furnished']) && $filters['is_furnished'] === 'yes') {
    $conditions[] = "p.is_furnished = 1";
}

if (empty($priceValidationError)) {
    if ($minPrice !== null) {
        $conditions[] = "p.monthly_rent >= ?";
        $params[] = $minPrice;
    }

    if ($maxPrice !== null) {
        $conditions[] = "p.monthly_rent <= ?";
        $params[] = $maxPrice;
    }
}

$where_part = "";
if (!empty($conditions)) {
    $where_part = " AND " . implode(" AND ", $conditions);
}

$order_part = " ORDER BY p.created_at DESC";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total " . $from_part . $where_part;
$count_stmt = $db->prepare($count_sql);
if ($count_stmt) {
    $count_result = $db->getSingle($count_stmt, $params);
    $total_count = isset($count_result['total']) ? $count_result['total'] : 0;
} else {
    $total_count = 0;
}

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_count / $limit);

$sql = "SELECT " . $select_part . " " . $from_part . $where_part . $order_part . " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute query
$stmt = $db->prepare($sql);
$properties = $db->getMultiple($stmt, $params);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row content-row">
        <div class="col-lg-3 sidebar-col">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9 content-col">
            <?php if (!$show_details): ?>
            <!-- Search Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 mb-0">Find Your Dream Home</h1>
                            <p class="text-muted mb-0"><?php echo $total_count; ?> properties found in Aksum</p>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="Search by title, location..." 
                                       value="<?php echo $_GET['search'] ?? ''; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (!empty($priceValidationError)): ?>
                        <div class="alert alert-danger mb-3">
                            <?php echo htmlspecialchars($priceValidationError); ?>
                        </div>
                    <?php endif; ?>
                    <form id="tenant-search-form" method="GET" action="" class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label visually-hidden">Type</label>
                            <select name="property_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="house" <?php echo $filters['property_type'] == 'house' ? 'selected' : ''; ?>>House</option>
                                <option value="apartment" <?php echo $filters['property_type'] == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                <option value="villa" <?php echo $filters['property_type'] == 'villa' ? 'selected' : ''; ?>>Villa</option>
                                <option value="condominium" <?php echo $filters['property_type'] == 'condominium' ? 'selected' : ''; ?>>Condominium</option>
                                <option value="commercial" <?php echo $filters['property_type'] == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label visually-hidden">Location</label>
                            <select name="location" class="form-select">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location['subcity']); ?>" <?php echo $filters['location'] == $location['subcity'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($location['subcity']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label visually-hidden">Beds</label>
                            <select name="bedrooms" class="form-select">
                                <option value="">Beds</option>
                                <option value="1" <?php echo $filters['bedrooms'] == '1' ? 'selected' : ''; ?>>1+</option>
                                <option value="2" <?php echo $filters['bedrooms'] == '2' ? 'selected' : ''; ?>>2+</option>
                                <option value="3" <?php echo $filters['bedrooms'] == '3' ? 'selected' : ''; ?>>3+</option>
                                <option value="4" <?php echo $filters['bedrooms'] == '4' ? 'selected' : ''; ?>>4+</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label visually-hidden">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" min="0" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="Min" value="<?php echo htmlspecialchars($filters['min_price']); ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" min="0" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="Max" value="<?php echo htmlspecialchars($filters['max_price']); ?>">
                                </div>
                            </div>
                            <div id="tenant-price-validation-message" class="text-danger small mt-2" style="display:none;"></div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label visually-hidden">Furnished</label>
                            <select name="is_furnished" class="form-select">
                                <option value="">Any</option>
                                <option value="yes" <?php echo $filters['is_furnished'] == 'yes' ? 'selected' : ''; ?>>Furnished Only</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary text-nowrap">Apply</button>
                        </div>
                        <div class="col-md-12 mt-2 text-end d-lg-none">
                            <a href="search.php" class="btn btn-outline-secondary">Clear Filters</a>
                        </div>
                        <div class="col-md-1 d-none d-lg-block">
                            <a href="search.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Properties Grid -->
            <div class="row">
                <?php if (empty($properties)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No properties found matching your criteria. Try adjusting your filters.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($properties as $property): 
                        $image_url = getPropertyPrimaryImage($property['property_id']);
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card property-card h-100">
                                <div class="property-image position-relative">
                                    <img src="<?php echo $image_url; ?>" class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($property['title']); ?>">
                                    <span class="badge bg-success position-absolute top-0 start-0 m-3">
                                        ETB <?php echo number_format($property['monthly_rent'], 0); ?>/month
                                    </span>
                                    <span class="badge <?php echo $property['status'] === 'available' ? 'bg-success' : ($property['status'] === 'requested' ? 'bg-warning text-dark' : 'bg-danger'); ?> position-absolute top-0 end-0 m-3">
                                        <?php echo ucfirst($property['status']); ?>
                                    </span>
                                    <?php if ($property['is_furnished']): ?>
                                        <span class="badge bg-info position-absolute bottom-0 start-0 m-3">Furnished</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt text-primary"></i> 
                                        <?php echo htmlspecialchars($property['location_name']); ?>, 
                                        <?php echo htmlspecialchars($property['subcity']); ?>
                                    </p>
                                    <div class="property-features mb-3">
                                        <span class="badge bg-light text-dark me-1 mb-1">
                                            <i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bed
                                        </span>
                                        <span class="badge bg-light text-dark me-1 mb-1">
                                            <i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bath
                                        </span>
                                        <span class="badge bg-light text-dark me-1 mb-1">
                                            <i class="fas fa-ruler-combined"></i> <?php echo $property['area_sqm']; ?> sqm
                                        </span>
                                        <span class="badge bg-light text-dark mb-1">
                                            <?php echo ucfirst($property['property_type']); ?>
                                        </span>
                                    </div>
                                    <p class="card-text small text-muted">
                                        <?php echo substr(htmlspecialchars($property['description']), 0, 100); ?>...
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="search.php?details=<?php echo $property['property_id']; ?>&viewed=<?php echo $property['property_id']; ?>" 
                                           class="btn btn-primary flex-grow-1">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                        <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                            <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" 
                                               target="_blank" class="btn btn-outline-info" title="Show on Map">
                                                <i class="fas fa-map-marked-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" 
                                               target="_blank" class="btn btn-outline-info" title="Search on Map">
                                                <i class="fas fa-search-location"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($session->isLoggedIn() && $session->getUserRole() == 'tenant'): ?>
                                            <?php if ($property['status'] === 'available'): ?>
                                                <button type="button" class="btn btn-outline-primary request-btn flex-grow-1" 
                                                        data-property-id="<?php echo $property['property_id']; ?>"
                                                        data-property-title="<?php echo htmlspecialchars($property['title']); ?>">
                                                    <i class="fas fa-paper-plane me-1"></i>Rent
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-secondary flex-grow-1" disabled>
                                                    <i class="fas fa-ban me-1"></i>
                                                    <?php echo $property['status'] === 'rented' ? 'Rented' : 'Unavailable'; ?>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
    <!-- Property Details -->
    <div class="mb-3">
        <a href="search.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Search
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Property Images -->
            <div class="card mb-4">
                <div class="card-body p-0">
                    <?php if (empty($images)): ?>
                        <img src="../assets/images/default-property.jpg" class="d-block w-100" style="height: 400px; object-fit: cover;" alt="Property">
                    <?php else: ?>
                        <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php foreach ($images as $index => $image): ?>
                                    <button type="button" data-bs-target="#propertyCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                                            class="<?php echo $index === 0 ? 'active' : ''; ?>" <?php echo $index === 0 ? 'aria-current="true"' : ''; ?>></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-inner">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" style="position:relative;">
                                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" class="d-block w-100" 
                                             style="height: 400px; object-fit: cover;" alt="Property Image <?php echo $index + 1; ?>">
                                        <div class="carousel-caption">
                                            <small>Image <?php echo $index + 1; ?> of <?php echo count($images); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Property Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1 class="h3 mb-2"><?php echo htmlspecialchars($property['title']); ?></h1>
                            <div class="mb-2">
                                <span class="badge <?php echo $property['status'] === 'available' ? 'bg-success' : ($property['status'] === 'requested' ? 'bg-warning text-dark' : 'bg-danger'); ?> me-1">
                                    <?php echo ucfirst($property['status']); ?>
                                </span>
                            </div>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                                <?php echo htmlspecialchars($property['location_name']); ?>, 
                                <?php echo htmlspecialchars($property['subcity']); ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <h2 class="text-primary mb-0">ETB <?php echo number_format($property['monthly_rent'], 0); ?></h2>
                            <small class="text-muted">per month</small>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-bed fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?php echo $property['bedrooms']; ?></div>
                                <small class="text-muted">Bedrooms</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-bath fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?php echo $property['bathrooms']; ?></div>
                                <small class="text-muted">Bathrooms</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-ruler-combined fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?php echo $property['area_sqm']; ?></div>
                                <small class="text-muted">sqm</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 text-center mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-home fa-2x text-primary mb-2"></i>
                                <div class="fw-bold"><?php echo ucfirst($property['property_type']); ?></div>
                                <small class="text-muted">Type</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3">Description</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                    </div>

                    <!-- Location Section -->
                    <div class="card mb-4 border-0 bg-light">
                        <div class="card-body">
                            <h5 class="mb-3">Location</h5>
                            <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <?php echo htmlspecialchars($property['location_name']); ?>, 
                                        <?php echo htmlspecialchars($property['subcity']); ?>
                                    </p>
                                    <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" 
                                       target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-external-link-alt me-1"></i>Open in Google Maps
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <?php echo htmlspecialchars($property['location_name']); ?>, 
                                        <?php echo htmlspecialchars($property['subcity']); ?>
                                    </p>
                                    <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" 
                                       target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-search-location me-1"></i>Search on Google Maps
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($property['amenities'])): ?>
                        <div class="mb-4">
                            <h5 class="mb-3">Amenities</h5>
                            <div class="row">
                                <?php 
                                $amenities = explode(',', $property['amenities']);
                                foreach ($amenities as $amenity): 
                                    $amenity = trim($amenity);
                                    if (!empty($amenity)):
                                ?>
                                    <div class="col-md-4 col-6 mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <?php echo htmlspecialchars($amenity); ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($property['is_furnished']): ?>
                        <div class="mb-4">
                            <span class="badge bg-info fs-6">
                                <i class="fas fa-couch me-2"></i>Furnished Property
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($property['security_deposit']): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Security Deposit:</strong> ETB <?php echo number_format($property['security_deposit'], 0); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Contact Owner -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Property Owner</h5>
                    <div class="text-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <h6 class="mt-2 mb-1"><?php echo htmlspecialchars($property['owner_name']); ?></h6>
                        <small class="text-muted">Property Owner</small>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Contact Information</small>
                        <div class="mt-1">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <?php echo htmlspecialchars($property['owner_phone']); ?>
                        </div>
                        <div class="mt-1">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <?php echo htmlspecialchars($property['owner_email']); ?>
                        </div>
                    </div>

                    <?php if ($session->isLoggedIn() && $session->getUserRole() == 'tenant'): ?>
                        <?php if ($property['status'] === 'available'): ?>
                            <button type="button" class="btn btn-primary w-100" id="requestRentBtn">
                                <i class="fas fa-paper-plane me-2"></i>Request to Rent
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100" disabled>
                                <i class="fas fa-ban me-2"></i>
                                <?php echo $property['status'] === 'rented' ? 'Rented' : 'Not Available'; ?>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="../login.php" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Request
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Property Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Property Information</h5>
                    <div class="mb-2">
                        <small class="text-muted">Property ID:</small>
                        <span class="float-end">#<?php echo str_pad($property['property_id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Listed:</small>
                        <span class="float-end"><?php echo date('M d, Y', strtotime($property['created_at'])); ?></span>
                    </div>
                    <?php
                    $statusClasses = [
                        'available' => 'success',
                        'requested' => 'warning',
                        'rented' => 'danger',
                        'maintenance' => 'secondary',
                        'unavailable' => 'dark'
                    ];
                    $statusLabel = ucfirst($property['status']);
                    $statusClass = $statusClasses[$property['status']] ?? 'secondary';
                    ?>
                    <div class="mb-2">
                        <small class="text-muted">Status:</small>
                        <span class="float-end"><span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span></span>
                    </div>
                    <?php if ($property['featured']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Featured:</small>
                            <span class="float-end"><span class="badge bg-warning">Yes</span></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rental Terms -->
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Rental Terms</h5>
                    <div class="mb-2">
                        <small class="text-muted">Agreement Period:</small>
                        <span class="float-end">6 months</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Advance Payment:</small>
                        <span class="float-end">20%</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Payment Method:</small>
                        <span class="float-end">Monthly</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>


<!-- Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request to Rent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="requestForm">
                    <input type="hidden" id="property_id" name="property_id">
                    <div class="mb-3">
                        <label class="form-label">Property</label>
                        <input type="text" id="property_title" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message to Owner (Optional)</label>
                        <textarea class="form-control" name="message" rows="3" 
                                  placeholder="Tell the owner about yourself and your requirements..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        By submitting this request, you agree to the 20% advance payment and 6-month agreement terms.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitRequest">Submit Request</button>
            </div>
        </div>
    </div>
</div>

<!-- Property Details Modal for Request -->
<div class="modal fade" id="detailsRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request to Rent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="detailsRequestForm">
                    <input type="hidden" id="details_property_id" name="property_id" value="<?php echo $property['property_id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Property</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($property['title'] ?? ''); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Rent</label>
                        <input type="text" class="form-control" value="ETB <?php echo number_format($property['monthly_rent'] ?? 0, 0); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Advance Payment (20%)</label>
                        <input type="text" class="form-control" value="ETB <?php echo number_format(($property['monthly_rent'] ?? 0) * 0.2, 0); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message to Owner (Optional)</label>
                        <textarea class="form-control" name="message" rows="3" 
                                  placeholder="Tell the owner about yourself and your requirements..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        By submitting this request, you agree to the 20% advance payment and 6-month agreement terms.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitDetailsRequest">Submit Request</button>
            </div>
        </div>
    </div>
</div>

</div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Request to rent button from search
    $('.request-btn').click(function() {
        const propertyId = $(this).data('property-id');
        const propertyTitle = $(this).data('property-title');
        
        $('#property_id').val(propertyId);
        $('#property_title').val(propertyTitle);
        $('#requestModal').modal('show');
    });
    
    // Submit request from search
    $('#submitRequest').click(function() {
        const formData = {
            property_id: $('#property_id').val(),
            message: $('textarea[name="message"]').val()
        };
        
        $.ajax({
            url: '../api/submit-request.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#requestModal').modal('hide');
                    alert('Rental request submitted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Request button from details page
    $('#requestRentBtn').click(function() {
        $('#detailsRequestModal').modal('show');
    });

    // Submit request from details
    $('#submitDetailsRequest').click(function() {
        const formData = {
            property_id: $('#details_property_id').val(),
            message: $('#detailsRequestForm textarea[name="message"]').val()
        };
        
        $.ajax({
            url: '../api/submit-request.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#detailsRequestModal').modal('hide');
                    alert('Rental request submitted successfully!');
                    // Could redirect or reload
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchForm = document.getElementById('tenant-search-form');
    if (!searchForm) {
        return;
    }

    var minInput = searchForm.querySelector('input[name="min_price"]');
    var maxInput = searchForm.querySelector('input[name="max_price"]');
    var messageContainer = document.getElementById('tenant-price-validation-message');

    function setFieldState(field, isInvalid) {
        field.classList.toggle('is-invalid', isInvalid);
    }

    function validatePriceRange() {
        var minValue = minInput.value.trim();
        var maxValue = maxInput.value.trim();
        var message = '';
        var valid = true;

        setFieldState(minInput, false);
        setFieldState(maxInput, false);

        if (minValue !== '' && !/^[0-9]+$/.test(minValue)) {
            message = 'Minimum price must be a valid number.';
            setFieldState(minInput, true);
            valid = false;
        }

        if (maxValue !== '' && !/^[0-9]+$/.test(maxValue)) {
            if (message === '') {
                message = 'Maximum price must be a valid number.';
            }
            setFieldState(maxInput, true);
            valid = false;
        }

        if (valid && minValue !== '' && maxValue !== '') {
            var minNum = Number(minValue);
            var maxNum = Number(maxValue);
            if (minNum >= maxNum) {
                message = 'Maximum price must be greater than minimum price.';
                setFieldState(minInput, true);
                setFieldState(maxInput, true);
                valid = false;
            }
        }

        messageContainer.textContent = message;
        messageContainer.style.display = message ? 'block' : 'none';
        return valid;
    }

    [minInput, maxInput].forEach(function(field) {
        field.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            validatePriceRange();
        });
    });

    searchForm.addEventListener('submit', function(event) {
        if (!validatePriceRange()) {
            event.preventDefault();
            messageContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
});
</script>