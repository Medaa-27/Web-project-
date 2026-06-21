<?php
require_once '../includes/config.php';

// Get property ID from URL
$property_id = $_GET['id'] ?? 0;

if (!is_numeric($property_id) || $property_id <= 0) {
    header('Location: ../tenant/search.php');
    exit;
}

// Get property details with owner information
$sql = "SELECT p.*, l.location_name, l.subcity, u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email,
               (SELECT image_url FROM property_images WHERE property_id = p.property_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE p.property_id = ? AND p.status IN ('available', 'requested', 'rented') AND p.review_status = 'approved'";
$stmt = $db->prepare($sql);
$property = $db->getSingle($stmt, [$property_id]);

if (!$property) {
    header('Location: ../tenant/search.php');
    exit;
}

// Get all property images
$sql = "SELECT image_url FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id ASC";
$stmt = $db->prepare($sql);
$images = $db->getMultiple($stmt, [$property_id]);

// Debug: Check if images exist
error_log("Property ID: $property_id, Images found: " . count($images));
if (!empty($images)) {
    foreach ($images as $index => $image) {
        error_log("Image $index: " . $image['image_url']);
    }
}

// Track property view if tenant is logged in
if ($session->isLoggedIn() && $session->getUserRole() == 'tenant') {
    $tenant_id = $session->getUserId();
    
    // Log view to file (temporary solution until property_views table is created)
    $log_entry = date('Y-m-d H:i:s') . " - Tenant ID: $tenant_id viewed Property ID: $property_id\n";
    file_put_contents('../logs/property_views.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// Get similar properties
$sql = "SELECT p.*, l.location_name,
               (SELECT image_url FROM property_images WHERE property_id = p.property_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p
        LEFT JOIN locations l ON p.location_id = l.location_id
        WHERE p.property_id != ? AND p.status = 'available' AND p.review_status = 'approved'
        AND p.property_type = ? AND p.monthly_rent BETWEEN ? AND ?
        ORDER BY p.created_at DESC
        LIMIT 3";
$stmt = $db->prepare($sql);
$similar_properties = $db->getMultiple($stmt, [
    $property_id, 
    $property['property_type'],
    $property['monthly_rent'] * 0.8,
    $property['monthly_rent'] * 1.2
]);

$title = $property['title'] . ' - Aksum Rental System';
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Property Images -->
            <div class="card mb-4">
                <div class="card-body p-0">
                    <!-- Debug Info -->
                    <div class="alert alert-info m-2">
                        <small><strong>Debug:</strong> Property ID: <?php echo $property_id; ?> | Images found: <?php echo count($images); ?></small>
                    </div>
                    
                    <?php if (empty($images)): ?>
                        <div class="alert alert-warning m-2">
                            <small><strong>No images found</strong> - Showing default image</small>
                        </div>
                        <img src="../assets/images/default-property.jpg" class="d-block w-100" style="height: 400px; object-fit: cover;" alt="Property">
                    <?php else: ?>
                        <div class="alert alert-success m-2">
                            <small><strong>Images found:</strong> <?php echo count($images); ?> image(s)</small>
                        </div>
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
                                        <button type="button" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 show-more-images-btn" data-index="<?php echo $index; ?>" title="Show more images" style="z-index:1050;">
                                            <i class="fas fa-images me-1"></i>More images
                                        </button>
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

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Location</h5>
                            <?php if (!empty($property['latitude']) && !empty($property['longitude'])): ?>
                                <div id="propertyDetailsMap" class="rounded-3 shadow-sm mb-3" style="height:250px;"></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        Coordinates: <?php echo htmlspecialchars($property['latitude']); ?>, <?php echo htmlspecialchars($property['longitude']); ?>
                                    </p>
                                    <a href="https://www.google.com/maps?q=<?php echo $property['latitude']; ?>,<?php echo $property['longitude']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-external-link-alt me-1"></i>Open in Google Maps
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary mb-3">Location coordinates are not available for this property.</div>
                                <a href="https://www.google.com/maps?q=<?php echo urlencode($property['title'] . ', ' . $property['location_name']); ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-search-location me-1"></i>Search Location on Google Maps
                                </a>
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

            <!-- Similar Properties -->
            <?php if (!empty($similar_properties)): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-4">Similar Properties</h5>
                        <div class="row">
                            <?php foreach ($similar_properties as $similar): 
                                $image_url = $similar['primary_image'] ?: '../assets/images/default-property.jpg';
                            ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <img src="<?php echo $image_url; ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Property">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($similar['title']); ?></h6>
                                            <p class="text-muted small mb-2">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($similar['location_name']); ?>
                                            </p>
                                            <div class="fw-bold text-primary">ETB <?php echo number_format($similar['monthly_rent'], 0); ?>/month</div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <a href="property-details.php?id=<?php echo $similar['property_id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
</div>

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
                    <input type="hidden" id="property_id" name="property_id" value="<?php echo $property['property_id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Property</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($property['title']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Rent</label>
                        <input type="text" class="form-control" value="ETB <?php echo number_format($property['monthly_rent'], 0); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Advance Payment (20%)</label>
                        <input type="text" class="form-control" value="ETB <?php echo number_format($property['monthly_rent'] * 0.2, 0); ?>" readonly>
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

<!-- Image Modal / Gallery -->
<div class="modal fade" id="imageModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body text-center p-0 position-relative">
        <button type="button" class="btn btn-sm btn-light position-absolute top-50 start-0 translate-middle-y ms-2" id="imgPrev" style="z-index:1060;">
            <i class="fas fa-chevron-left"></i>
        </button>
        <img src="" id="lightboxImage" class="img-fluid rounded" style="max-height:80vh;" alt="">
        <button type="button" class="btn btn-sm btn-light position-absolute top-50 end-0 translate-middle-y me-2" id="imgNext" style="z-index:1060;">
            <i class="fas fa-chevron-right"></i>
        </button>
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
      </div>
    </div>
  </div>
</div>

<script>
window.onload = function() {
    console.log('Page loaded, initializing...');
    
    // Test if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded!');
    } else {
        console.log('Bootstrap is loaded');
    }
    
    // Rental request functionality
    var requestBtn = document.getElementById('requestRentBtn');
    var submitBtn = document.getElementById('submitRequest');
    
    if (requestBtn) {
        console.log('Request button found');
        requestBtn.onclick = function() {
            console.log('Request button clicked');
            var modal = document.getElementById('requestModal');
            if (modal) {
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
        };
    }
    
    if (submitBtn) {
        console.log('Submit button found');
        submitBtn.onclick = function() {
            console.log('Submit button clicked');
            
            var propertyId = document.getElementById('property_id').value;
            var message = document.querySelector('textarea[name="message"]').value;
            
            console.log('Submitting request for property:', propertyId);
            
            // Simple form submission
            var formData = new FormData();
            formData.append('property_id', propertyId);
            formData.append('message', message);
            
            fetch('../api/submit-request.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('Response:', data);
                if (data.success) {
                    alert('Rental request submitted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        };
    }
    
    // Close modal functionality
    var closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(function(button) {
        button.onclick = function() {
            var modal = document.getElementById('requestModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        };
    });

    // Gallery / More images handlers
    (function(){
        var images = Array.from(document.querySelectorAll('#propertyCarousel .carousel-item img')).map(function(i){ return i.getAttribute('src'); });
        console.log('[gallery] images', images);
        if (!images || images.length === 0) return;
        var currentIndex = 0;
        var imageModalEl = document.getElementById('imageModal');
        var lightboxImage = document.getElementById('lightboxImage');
        var lightboxModal = null;

        function setLightboxSrc(url){
            if (!lightboxImage) return;
            var cb = (url || '') + '?_=' + Date.now();
            console.log('[gallery] set src', cb);
            lightboxImage.setAttribute('src', cb);
        }

        function openLightbox(idx){
            currentIndex = (!isNaN(idx) && idx >= 0) ? idx : 0;
            setLightboxSrc(images[currentIndex]);
            if (typeof bootstrap !== 'undefined' && imageModalEl) {
                if (!lightboxModal) lightboxModal = new bootstrap.Modal(imageModalEl);
                lightboxModal.show();
            } else if (imageModalEl) {
                imageModalEl.classList.add('show');
                imageModalEl.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        }

        function showNext(){ currentIndex = (currentIndex + 1) % images.length; setLightboxSrc(images[currentIndex]); }
        function showPrev(){ currentIndex = (currentIndex - 1 + images.length) % images.length; setLightboxSrc(images[currentIndex]); }

        // Global button: open gallery at current carousel slide
        var showMoreBtn = document.getElementById('showMoreImages');
        if (showMoreBtn) showMoreBtn.addEventListener('click', function(){
            var active = document.querySelector('#propertyCarousel .carousel-item.active');
            var idx = Array.prototype.indexOf.call(document.querySelectorAll('#propertyCarousel .carousel-item'), active);
            openLightbox(idx >= 0 ? idx : 0);
        });

        // Overlay per-slide buttons
        Array.prototype.forEach.call(document.querySelectorAll('.show-more-images-btn'), function(btn){
            btn.addEventListener('click', function(e){ e.stopPropagation(); openLightbox(parseInt(btn.dataset.index, 10) || 0); });
        });

        // clicking an image opens lightbox for that image
        Array.prototype.forEach.call(document.querySelectorAll('#propertyCarousel .carousel-item img'), function(img, idx){
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', function(){ openLightbox(idx); });
        });

        var imgNext = document.getElementById('imgNext'), imgPrev = document.getElementById('imgPrev');
        if (imgNext) imgNext.addEventListener('click', function(e){ e.preventDefault(); showNext(); });
        if (imgPrev) imgPrev.addEventListener('click', function(e){ e.preventDefault(); showPrev(); });

        // close modal behavior for fallback or bootstrap close buttons
        Array.prototype.forEach.call(document.querySelectorAll('[data-bs-dismiss="modal"]'), function(btn){
            btn.addEventListener('click', function(){
                if (lightboxModal) lightboxModal.hide();
                else if (imageModalEl) { imageModalEl.classList.remove('show'); imageModalEl.style.display = 'none'; document.body.classList.remove('modal-open'); }
            });
        });

    })();
};
</script>

<script defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initializePropertyDetailsMap" onerror="window.handleGoogleMapsLoadError && window.handleGoogleMapsLoadError()"></script>
<script>
function showMapError(mapId, message) {
    var container = document.getElementById(mapId);
    if (!container) return;
    container.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 border border-danger rounded-3 bg-light text-danger p-3 text-center"><div><i class="fas fa-exclamation-circle fa-2x mb-2"></i><div>' + message + '</div></div></div>';
}

window.handleGoogleMapsLoadError = function() {
    showMapError('propertyDetailsMap', 'Google Maps failed to load. Check your API key and host restrictions.');
};

function gm_authFailure() {
    window.handleGoogleMapsLoadError && window.handleGoogleMapsLoadError();
}

function initializePropertyDetailsMap() {
    var mapContainer = document.getElementById('propertyDetailsMap');
    if (!mapContainer || !window.google || !google.maps) return;

    var latitude = parseFloat(<?php echo json_encode($property['latitude'] ?? ''); ?>);
    var longitude = parseFloat(<?php echo json_encode($property['longitude'] ?? ''); ?>);
    if (!latitude || !longitude) return;

    var map = new google.maps.Map(mapContainer, {
        center: { lat: latitude, lng: longitude },
        zoom: 15,
        mapTypeControl: false
    });

    var marker = new google.maps.Marker({
        position: { lat: latitude, lng: longitude },
        map: map,
        title: <?php echo json_encode($property['title']); ?>
    });

    var infoWindow = new google.maps.InfoWindow({
        content: '<strong>' + <?php echo json_encode($property['title']); ?> + '</strong><br>' + <?php echo json_encode($property['location_name'] . ', ' . $property['subcity']); ?>
    });

    marker.addListener('click', function() {
        infoWindow.open(map, marker);
    });
    infoWindow.open(map, marker);
}

document.addEventListener('DOMContentLoaded', function() {
    initializePropertyDetailsMap();
});
</script>

<?php include '../includes/footer.php'; ?>