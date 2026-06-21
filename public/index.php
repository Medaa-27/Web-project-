<?php
require_once '../includes/config.php';
$title = t('homepage_title');

$propertyCount = 0;
$ownerCount = 0;
$tenantCount = 0;

$propertyRow = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM properties"));
$propertyCount = $propertyRow['total'] ?? 0;

$ownerRow = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'owner'"));
$ownerCount = $ownerRow['total'] ?? 0;

$tenantRow = $db->getSingle($db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'tenant'"));
$tenantCount = $tenantRow['total'] ?? 0;

include '../includes/header.php';
?>

<style>
@media (max-width: 768px) {
    .hero-section {
        min-height: auto !important;
        padding: 2rem 1rem 2rem !important;
        overflow: visible !important;
    }
    .hero-section .container {
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .hero-section .row {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        margin: 0 !important;
    }
    .hero-section .col-lg-6 {
        width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 1.5rem !important;
        padding: 0 !important;
    }
    .hero-content {
        position: relative !important;
        z-index: 3 !important;
        padding: 0 !important;
    }
    .hero-section h1 {
        font-size: 2rem !important;
        line-height: 1.2 !important;
        margin-bottom: 1rem !important;
    }
    .hero-section .lead {
        font-size: 1rem !important;
        line-height: 1.6 !important;
        margin-bottom: 1.5rem !important;
    }
    .hero-image {
        width: 100% !important;
        max-width: 100% !important;
        margin-top: 1.5rem !important;
    }
    .hero-image img {
        width: 100% !important;
        max-height: 320px !important;
        object-fit: cover !important;
        display: block !important;
    }
    .hero-section .stat-box {
        width: 100% !important;
    }
}
</style>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 order-lg-1 order-1">
                <div class="hero-content">
                    <h1 class="display-3 fw-bold mb-4 animate-fade-in"><?php echo t('find_your_perfect_home'); ?></h1>
                    <p class="lead mb-4 animate-fade-in-delay">
                        <?php echo t('homepage_hero_description'); ?>
                    </p>
                    <div class="d-flex gap-3 flex-wrap animate-fade-in-delay-2">
                        <a href="#search" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-search me-2"></i><?php echo t('search_properties'); ?>
                        </a>
                        <a href="../register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i><?php echo t('sign_up_free'); ?>
                        </a>
                    </div>
                    <div class="mt-4 animate-fade-in-delay-3">
                        <div class="row g-3 text-center">
                            <!-- make these stats collapse to two per row on very small screens
                                 so the third box doesn't get pushed offscreen by overflow:hidden -->
                            <div class="col-6 col-md-4">
                                <div class="stat-box">
                                    <h3 class="h2 text-white fw-bold"><?php echo number_format($propertyCount); ?></h3>
                                    <p class="small mb-0"><?php echo __('property'); ?></p>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="stat-box">
                                    <h3 class="h2 text-white fw-bold"><?php echo number_format($ownerCount); ?></h3>
                                    <p class="small mb-0"><?php echo __('property_owner'); ?></p>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="stat-box">
                                    <h3 class="h2 text-white fw-bold"><?php echo number_format($tenantCount); ?></h3>
                                    <p class="small mb-0"><?php echo __('tenant'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-2 order-2">
                <div class="hero-image animate-slide-in">
                    <img src="../assets/images/ax.jpg" alt="Aksum City Properties" class="img-fluid rounded-3 shadow-lg">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Search Section -->
<section id="search" class="search-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-lg border-0 search-card">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4 fw-bold">
                            <i class="fas fa-home text-primary me-2"></i><?php echo t('find_your_dream_property'); ?>
                        </h2>
                        <form id="property-search-form" action="properties.php" method="GET" class="row g-3">
                            <div class="col-md-3">
                                    <label class="form-label small fw-semibold"><?php echo t('property_type_label'); ?></label>
                                <select name="property_type" class="form-select form-select-lg">
                                        <option value=""><?php echo t('all_types_option'); ?></option>
                                    <option value="house"><?php echo t('type_house'); ?></option>
                                    <option value="apartment"><?php echo t('type_apartment'); ?></option>
                                    <option value="villa"><?php echo t('type_villa'); ?></option>
                                    <option value="condominium"><?php echo t('type_condominium'); ?></option>
                                    <option value="commercial"><?php echo t('type_commercial'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold"><?php echo t('location_label'); ?></label>
                                <select name="location" class="form-select form-select-lg">
                                    <option value=""><?php echo t('all_locations_option'); ?></option>
                                    <option value="Aksum K'Idist Maryam Hospital">📍 K'Idist Maryam Hospital Area</option>
                                    <option value="Aksum Zion">📍 Aksum Zion Area</option>
                                    <option value="Ezana Park">📍 Ezana Park Area</option>
                                    <option value="Aksum University Area">📍 Aksum University Area</option>
                                    <option value="Referral Hospital">📍 Referral Hospital Area</option>
                                    <option value="Aksum Market">📍 Aksum Market Area</option>
                                    <option value="Airport Street">📍 Airport Street Area</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold"><?php echo t('bedrooms_label'); ?></label>
                                <select name="bedrooms" class="form-select form-select-lg">
                                    <option value=""><?php echo t('any_option'); ?></option>
                                    <option value="1"><?php echo t('bed_option_1'); ?></option>
                                    <option value="2"><?php echo t('bed_option_2'); ?></option>
                                    <option value="3"><?php echo t('bed_option_3'); ?></option>
                                    <option value="4"><?php echo t('bed_option_4_plus'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold"><?php echo t('price_range_label'); ?></label>
                                <div class="input-group">
                                    <input type="number" name="min_price" class="form-control form-control-lg" min="0" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="<?php echo t('min_price_placeholder'); ?>">
                                    <span class="input-group-text"><?php echo t('price_range_to'); ?></span>
                                    <input type="number" name="max_price" class="form-control form-control-lg" min="0" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="<?php echo t('max_price_placeholder'); ?>">
                                </div>
                                <div id="home-price-validation-message" class="text-danger small mt-2" style="display:none;"></div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i><?php echo t('search_properties'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-5 fw-bold mb-4"><?php echo t('why_choose_title'); ?></h2>
                <p class="lead text-muted"><?php echo t('why_choose_description'); ?></p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shield-alt fa-3x text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo t('feature_verified_properties'); ?></h4>
                    <p class="text-muted"><?php echo t('feature_verified_properties_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-dollar-sign fa-3x text-success"></i>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo t('feature_best_prices'); ?></h4>
                    <p class="text-muted"><?php echo t('feature_best_prices_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-headset fa-3x text-info"></i>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo t('feature_support_247'); ?></h4>
                    <p class="text-muted"><?php echo t('feature_support_247_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-lock fa-3x text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo t('feature_secure_payments'); ?></h4>
                    <p class="text-muted"><?php echo t('feature_secure_payments_desc'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties -->
<section class="featured-properties py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-5 fw-bold mb-4"><?php echo t('featured_properties'); ?></h2>
                <p class="lead text-muted"><?php echo t('featured_properties_description'); ?></p>
            </div>
        </div>
        <div class="row">
            <?php
            // Fetch featured properties
            $sql = "SELECT p.*, l.location_name, u.full_name as owner_name 
                    FROM properties p 
                    LEFT JOIN locations l ON p.location_id = l.location_id
                    LEFT JOIN users u ON p.owner_id = u.user_id
                    WHERE p.status = 'available' AND p.review_status = 'approved' AND p.featured = 1 
                    ORDER BY p.created_at DESC LIMIT 6";
            $stmt = $db->prepare($sql);
            $properties = $db->getMultiple($stmt);
            
            if (empty($properties)) {
                echo '<div class="col-12 text-center"><p class="text-muted">No featured properties available at the moment.</p></div>';
            } else {
                foreach ($properties as $property) {
                    // Get primary image (fallbacks to any existing image or default)
                    $image_url = getPropertyPrimaryImage($property['property_id']);
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card property-card h-100 shadow-sm border-0">
                            <div class="property-image position-relative overflow-hidden">
                                <img src="<?php echo $image_url; ?>" class="card-img-top property-img" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                <div class="property-overlay">
                                    <span class="badge bg-primary position-absolute top-0 end-0 m-3">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                    <span class="badge bg-success position-absolute top-0 start-0 m-3">
                                        ETB <?php echo number_format($property['monthly_rent'], 0); ?>/month
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($property['title']); ?></h5>
                                <p class="card-text text-muted mb-3">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i> 
                                    <?php echo htmlspecialchars($property['location_name']); ?>
                                </p>
                                <div class="property-features mb-3">
                                    <span class="badge bg-light text-dark me-2">
                                        <i class="fas fa-bed me-1"></i> <?php echo $property['bedrooms']; ?> Bed
                                    </span>
                                    <span class="badge bg-light text-dark me-2">
                                        <i class="fas fa-bath me-1"></i> <?php echo $property['bathrooms']; ?> Bath
                                    </span>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-ruler-combined me-1"></i> <?php echo $property['area_sqm']; ?> sqm
                                    </span>
                                </div>
                                <p class="card-text small text-muted"><?php echo substr(htmlspecialchars($property['description']), 0, 80); ?>...</p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0 d-flex gap-2">
                                <a href="property-details.php?id=<?php echo $property['property_id']; ?>" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-eye me-2"></i><?php echo t('view_details'); ?>
                                </a>
                                <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                    <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" target="_blank" class="btn btn-outline-info" title="Show on Map">
                                        <i class="fas fa-map-marked-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" target="_blank" class="btn btn-outline-info" title="Search on Map">
                                        <i class="fas fa-search-location"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="properties.php" class="btn btn-outline-primary btn-lg px-4">
                <i class="fas fa-th me-2"></i><?php echo t('view_all_properties'); ?>
            </a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="how-it-works py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-5 fw-bold mb-4"><?php echo t('how_it_works'); ?></h2>
                <p class="lead text-muted"><?php echo t('how_it_works_description'); ?></p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card text-center p-4 h-100">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                        <span class="h3 mb-0 fw-bold">1</span>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo t('step_create_account_title'); ?></h4>
                    <p class="text-muted"><?php echo t('step_create_account_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card text-center p-4 h-100">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                        <span class="h3 mb-0 fw-bold">2</span>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo t('step_search_select_title'); ?></h4>
                    <p class="text-muted"><?php echo t('step_search_select_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card text-center p-4 h-100">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                        <span class="h3 mb-0 fw-bold">3</span>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo t('step_rent_move_in_title'); ?></h4>
                    <p class="text-muted"><?php echo t('step_rent_move_in_desc'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics -->
<section class="statistics py-5 bg-primary text-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <i class="fas fa-home fa-2x mb-3"></i>
                    <h2 class="display-4 fw-bold"><?php 
                        $sql = "SELECT COUNT(*) as total FROM properties WHERE status = 'available' AND review_status = 'approved'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt);
                        echo $result['total'] ?? '0';
                    ?></h2>
                    <p class="h5">Properties Available</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <h2 class="display-4 fw-bold"><?php 
                        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'owner'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt);
                        echo $result['total'] ?? '0';
                    ?></h2>
                    <p class="h5">Property Owners</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <i class="fas fa-handshake fa-2x mb-3"></i>
                    <h2 class="display-4 fw-bold"><?php 
                        $sql = "SELECT COUNT(*) as total FROM rental_agreements WHERE status = 'active'";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt);
                        echo $result['total'] ?? '0';
                    ?></h2>
                    <p class="h5">Active Rentals</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <i class="fas fa-map-marker-alt fa-2x mb-3"></i>
                    <h2 class="display-4 fw-bold"><?php 
                        $sql = "SELECT COUNT(DISTINCT location_name) as total FROM locations";
                        $stmt = $db->prepare($sql);
                        $result = $db->getSingle($stmt);
                        echo $result['total'] ?? '0';
                    ?></h2>
                    <p class="h5">Locations in Aksum</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-4"><?php echo t('cta_title'); ?></h2>
<p class="lead mb-4 text-muted">
<?php echo t('cta_description'); ?>
</p>                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="../register.php" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i><?php echo t('cta_get_started'); ?>
                    </a>
                    <a href="properties.php" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-search me-2"></i><?php echo t('cta_browse_properties'); ?>

                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchForm = document.getElementById('property-search-form');
    if (!searchForm) {
        return;
    }

    var minInput = searchForm.querySelector('input[name="min_price"]');
    var maxInput = searchForm.querySelector('input[name="max_price"]');
    var messageContainer = document.getElementById('home-price-validation-message');

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