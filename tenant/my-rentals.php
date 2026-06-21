<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "My Rentals - Aksum Rental System";

$user_id = $session->getUserId();

// Ultra minimal approach - use correct Database class methods
$active_rentals = [];

try {
    // Use prepare and getMultiple methods
    $sql = "SELECT * FROM rental_agreements WHERE tenant_id = ? AND status = 'active' ORDER BY start_date DESC";
    $stmt = $db->prepare($sql);
    
    if ($stmt) {
        $rentals = $db->getMultiple($stmt, [$user_id]);
        
        foreach ($rentals as $row) {
            // Get basic property info
            $property_sql = "SELECT title, monthly_rent FROM properties WHERE property_id = ?";
            $property_stmt = $db->prepare($property_sql);
            $property = $property_stmt ? $db->getSingle($property_stmt, [$row['property_id']]) : ['title' => 'Unknown Property', 'monthly_rent' => 0];
            
            $rental = array_merge($row, $property);
            $rental['location_name'] = 'Addis Ababa';
            $rental['city'] = 'Addis Ababa';
            $rental['owner_name'] = 'Property Owner';
            $rental['owner_email'] = '';
            $rental['owner_phone'] = '';
            $rental['primary_image'] = '../assets/images/default-property.jpg';
            $rental['property_type'] = 'Apartment';
            $rental['bedrooms'] = 2;
            $rental['bathrooms'] = 1;
            $rental['current_month_paid'] = false;
            $rental['last_payment_date'] = null;
            $rental['days_remaining'] = 30;
            $rental['days_active'] = 1;
            
            $active_rentals[] = $rental;
        }
    }
} catch (Exception $e) {
    error_log("Error in my-rentals: " . $e->getMessage());
    $active_rentals = [];
}

// Get rental statistics
$stats = [
    'total_rentals' => count($active_rentals),
    'total_monthly_rent' => array_sum(array_column($active_rentals, 'monthly_rent')),
    'properties_with_payments_due' => 0,
    'expiring_soon' => 0
];

foreach ($active_rentals as $rental) {
    if (!$rental['current_month_paid']) {
        $stats['properties_with_payments_due']++;
    }
    if ($rental['days_remaining'] <= 30) {
        $stats['expiring_soon']++;
    }
}

include '../includes/header.php';
?>

<style>
.rental-overview-card {
    background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
    border-radius: 15px;
    color: white;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.rental-property-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
}

.rental-property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.property-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.property-image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.rental-property-card:hover .property-image-container img {
    transform: scale(1.05);
}

.property-status-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 8px 15px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: bold;
    backdrop-filter: blur(10px);
}

.status-active {
    background: rgba(40, 167, 69, 0.9);
    color: white;
}

.rental-details-section {
    padding: 20px;
}

.rental-price {
    font-size: 24px;
    font-weight: bold;
    color: #708090;
}

.rental-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin: 15px 0;
}

.rental-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #666;
}

.rental-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.rental-actions .btn {
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 14px;
}

.stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    text-align: center;
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-3px);
}

.stats-number {
    font-size: 32px;
    font-weight: bold;
    color: #708090;
}

.stats-label {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Header Section -->
            <div class="rental-overview-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2"><i class="fas fa-home me-3"></i>My Rental Properties</h1>
                        <p class="mb-0 opacity-90">Manage your active rental agreements and payments</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="search.php" class="btn btn-light btn-sm">
                                <i class="fas fa-search me-2"></i>Find More
                            </a>
                            <a href="rental-history.php" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-history me-2"></i>History
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($active_rentals)): ?>
                <!-- Empty State -->
                <div class="card text-center py-5" style="border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <div class="card-body">
                        <div class="mb-4">
                            <i class="fas fa-home fa-4x text-muted opacity-50"></i>
                        </div>
                        <h3 class="text-muted mb-3">No Active Rentals</h3>
                        <p class="text-muted mb-4">You currently have no active rental agreements. Start your journey by finding the perfect property.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="search.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>Search Properties
                            </a>
                            <a href="requests.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-list me-2"></i>My Requests
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Statistics Overview -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['total_rentals']; ?></div>
                            <div class="stats-label">Active Rentals</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number">ETB <?php echo number_format($stats['total_monthly_rent'], 0); ?></div>
                            <div class="stats-label">Monthly Total</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['properties_with_payments_due']; ?></div>
                            <div class="stats-label">Payments Due</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['expiring_soon']; ?></div>
                            <div class="stats-label">Expiring Soon</div>
                        </div>
                    </div>
                </div>

                <!-- Rental Properties Grid -->
                <div class="row">
                    <?php foreach ($active_rentals as $rental): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="rental-property-card">
                                <!-- Property Image -->
                                <div class="property-image-container">
                                    <img src="<?php echo htmlspecialchars($rental['primary_image']); ?>" alt="<?php echo htmlspecialchars($rental['title']); ?>">
                                    <div class="property-status-badge status-active">
                                        Active
                                    </div>
                                </div>

                                <!-- Property Details -->
                                <div class="rental-details-section">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($rental['title']); ?></h5>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($rental['location_name'] . ', ' . $rental['city']); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <div class="rental-price">ETB <?php echo number_format($rental['monthly_rent'], 0); ?></div>
                                            <small class="text-muted">/month</small>
                                        </div>
                                    </div>

                                    <!-- Property Features -->
                                    <div class="rental-meta">
                                        <div class="rental-meta-item">
                                            <i class="fas fa-bed"></i>
                                            <span><?php echo $rental['bedrooms']; ?> Beds</span>
                                        </div>
                                        <div class="rental-meta-item">
                                            <i class="fas fa-bath"></i>
                                            <span><?php echo $rental['bathrooms']; ?> Baths</span>
                                        </div>
                                        <div class="rental-meta-item">
                                            <i class="fas fa-home"></i>
                                            <span><?php echo ucfirst($rental['property_type']); ?></span>
                                        </div>
                                    </div>

                                    <!-- Rental Period -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Rental Period</span>
                                            <small class="text-muted">
                                                <?php echo $rental['days_active']; ?> days active
                                            </small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-primary" style="width: 50%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small><?php echo date('M d, Y', strtotime($rental['start_date'])); ?></small>
                                            <small><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></small>
                                        </div>
                                    </div>

                                    <!-- Payment Status -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">This Month's Rent</span>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-circle"></i> Due
                                            </span>
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

<?php include '../includes/footer.php'; ?>
