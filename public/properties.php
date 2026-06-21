<?php
require_once '../includes/config.php';
$title = "Properties - Aksum House Rental System";

$priceValidationError = '';
$minPriceInput = $_GET['min_price'] ?? '';
$maxPriceInput = $_GET['max_price'] ?? '';
$minPrice = $minPriceInput !== '' && is_numeric($minPriceInput) ? $minPriceInput : null;
$maxPrice = $maxPriceInput !== '' && is_numeric($maxPriceInput) ? $maxPriceInput : null;

if ($minPriceInput !== '' && !is_numeric($minPriceInput)) {
    $priceValidationError = 'Minimum price must be a valid number.';
} elseif ($maxPriceInput !== '' && !is_numeric($maxPriceInput)) {
    $priceValidationError = 'Maximum price must be a valid number.';
} elseif ($minPrice !== null && $maxPrice !== null && $minPrice >= $maxPrice) {
    $priceValidationError = 'Maximum price must be greater than minimum price.';
}

include '../includes/header.php';
?>

<!-- Properties Page -->
<section class="properties-page py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-4">Available Properties in Aksum</h1>
                <p class="lead">Browse our selection of rental properties across Aksum City</p>
            </div>
        </div>

        <!-- Search Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($priceValidationError)): ?>
                            <div class="alert alert-danger mb-4">
                                <?php echo htmlspecialchars($priceValidationError); ?>
                            </div>
                        <?php endif; ?>
                        <form id="properties-search-form" action="properties.php" method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Property Type</label>
                                <select name="property_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="house">House</option>
                                    <option value="apartment">Apartment</option>
                                    <option value="villa">Villa</option>
                                    <option value="condominium">Condominium</option>
                                    <option value="commercial">Commercial</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Location</label>
                                <select name="location" class="form-select">
                                    <option value="">All Locations</option>
                                    <option value="Aksum K'Idist Maryam Hospital">K'Idist Maryam Hospital Area</option>
                                    <option value="Aksum Zion">Aksum Zion Area</option>
                                    <option value="Ezana Park">Ezana Park Area</option>
                                    <option value="Aksum University Area">Aksum University Area</option>
                                    <option value="Aksum Market">Aksum Market Area</option>
                                    <option value="Referral Hospital">Referral Hospital Area</option>
                                    <option value="Airport Street">Airport Street Area</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Bedrooms</label>
                                <select name="bedrooms" class="form-select">
                                    <option value="">Any</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4+</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Min Price</label>
                                <input type="number" name="min_price" class="form-control" min="0" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="ETB" value="<?php echo htmlspecialchars($minPriceInput); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Max Price</label>
                                <input type="number" name="max_price" class="form-control" min="0" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="ETB" value="<?php echo htmlspecialchars($maxPriceInput); ?>">
                            </div>
                            <div class="col-12">
                                <div id="properties-price-validation-message" class="text-danger small mt-2" style="display:none;"></div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search Properties
                                </button>
                                <a href="properties.php" class="btn btn-outline-secondary ms-2">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties List -->
        <div class="row">
            <?php
            // Build query with filters
            $sql = "SELECT p.*, l.location_name, u.full_name as owner_name 
                    FROM properties p 
                    LEFT JOIN locations l ON p.location_id = l.location_id
                    LEFT JOIN users u ON p.owner_id = u.user_id
                    WHERE p.status = 'available' AND p.review_status = 'approved'";
            
            $params = [];
            
            if (!empty($_GET['property_type'])) {
                $sql .= " AND p.property_type = ?";
                $params[] = $_GET['property_type'];
            }
            
            if (!empty($_GET['location'])) {
                $sql .= " AND l.location_name = ?";
                $params[] = $_GET['location'];
            }
            
            if (!empty($_GET['bedrooms'])) {
                $sql .= " AND p.bedrooms >= ?";
                $params[] = $_GET['bedrooms'];
            }
            
            if (empty($priceValidationError)) {
                if ($minPrice !== null) {
                    $sql .= " AND p.monthly_rent >= ?";
                    $params[] = $minPrice;
                }
                
                if ($maxPrice !== null) {
                    $sql .= " AND p.monthly_rent <= ?";
                    $params[] = $maxPrice;
                }
            }
            
            $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $properties = $db->getMultiple($stmt, $params);
            
            if (empty($properties)) {
                echo '<div class="col-12">';
                echo '<div class="text-center py-5">';
                echo '<i class="fas fa-home fa-3x text-muted mb-3"></i>';
                echo '<h4 class="text-muted">No properties found</h4>';
                echo '<p class="text-muted">Try adjusting your search filters</p>';
                echo '</div>';
                echo '</div>';
            } else {
                foreach ($properties as $property) {
                    // Get primary image (fallbacks to any existing image or default)
                    $image_url = getPropertyPrimaryImage($property['property_id']);
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card property-card h-100 shadow-sm">
                            <div class="property-image position-relative">
                                <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                <?php if ($property['featured']): ?>
                                    <span class="badge bg-warning position-absolute top-0 end-0 m-3">Featured</span>
                                <?php endif; ?>
                                <span class="badge bg-success position-absolute top-0 start-0 m-3">ETB <?php echo number_format($property['monthly_rent'], 0); ?>/month</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                <p class="card-text text-muted mb-2">
                                    <i class="fas fa-map-marker-alt text-primary"></i> 
                                    <?php echo htmlspecialchars($property['location_name']); ?>
                                </p>
                                <div class="property-features mb-3">
                                    <span class="badge bg-light text-dark me-2">
                                        <i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bed
                                    </span>
                                    <span class="badge bg-light text-dark me-2">
                                        <i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bath
                                    </span>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-ruler-combined"></i> <?php echo $property['area_sqm']; ?> sqm
                                    </span>
                                </div>
                                <p class="card-text"><?php echo substr(htmlspecialchars($property['description']), 0, 120); ?>...</p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                        <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" target="_blank" class="btn btn-outline-info btn-sm flex-grow-1" title="Show on Map">
                                            <i class="fas fa-map-marked-alt me-1"></i>Map
                                        </a>
                                    <?php else: ?>
                                        <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" target="_blank" class="btn btn-outline-info btn-sm flex-grow-1" title="Search on Map">
                                            <i class="fas fa-search-location me-1"></i>Map
                                        </a>
                                    <?php endif; ?>
                                    <a href="property-details.php?id=<?php echo $property['property_id']; ?>" class="btn btn-primary btn-sm flex-grow-1">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var searchForm = document.getElementById('properties-search-form');
        if (!searchForm) {
            return;
        }

        var minInput = searchForm.querySelector('input[name="min_price"]');
        var maxInput = searchForm.querySelector('input[name="max_price"]');
        var messageContainer = document.getElementById('properties-price-validation-message');

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

<?php include '../includes/footer.php'; ?>