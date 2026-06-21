<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = 'Edit Property - Aksum House Rental System';

$owner_id = $session->getUserId();
$property_id = $_GET['id'] ?? null;

if (!is_numeric($property_id) || !isPropertyOwner($owner_id, (int)$property_id)) {
    header('Location: properties.php');
    exit;
}

$locations_stmt = $db->prepare("SELECT location_id, location_name, subcity FROM locations ORDER BY location_name ASC");
$locations = $db->getMultiple($locations_stmt);

$stmt = $db->prepare('SELECT * FROM properties WHERE property_id = ?');
$property = $db->getSingle($stmt, [$property_id]);

if (!$property) {
    header('Location: properties.php');
    exit;
}

// Check if this is a rejected property being updated
$is_rejected_property = isset($_GET['rejected']) && $_GET['rejected'] == '1' && 
                       ($property['review_status'] ?? '') === 'rejected';

// Set primary image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_primary'])) {
    $image_id = $_POST['image_id'] ?? null;

    if (is_numeric($image_id)) {
        $stmt = $db->prepare('SELECT image_id FROM property_images WHERE image_id = ? AND property_id = ?');
        $img = $db->getSingle($stmt, [$image_id, $property_id]);
        if ($img) {
            $stmt = $db->prepare('UPDATE property_images SET is_primary = 0 WHERE property_id = ?');
            $db->execute($stmt, [$property_id]);
            $stmt = $db->prepare('UPDATE property_images SET is_primary = 1 WHERE image_id = ?');
            $db->execute($stmt, [$image_id]);
            $_SESSION['success'] = 'Primary image updated.';
        }
    }

    header('Location: edit-property.php?id=' . (int)$property_id);
    exit;
}

// Delete image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $image_id = $_POST['image_id'] ?? null;

    if (is_numeric($image_id)) {
        // Verify image belongs to property and owner
        $stmt = $db->prepare('SELECT image_id, image_url, is_primary FROM property_images WHERE image_id = ? AND property_id = ?');
        $img = $db->getSingle($stmt, [$image_id, $property_id]);
        
        if ($img) {
            // Check if we're deleting the primary image
            $was_primary = (int)$img['is_primary'] === 1;
            
            // Delete from filesystem
            $file_path = dirname(__DIR__) . '/' . ltrim($img['image_url'], '/');
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $stmt = $db->prepare('DELETE FROM property_images WHERE image_id = ?');
            $db->execute($stmt, [$image_id]);
            
            // If we deleted the primary image, set a new one if available
            if ($was_primary) {
                $stmt = $db->prepare('SELECT image_id FROM property_images WHERE property_id = ? LIMIT 1');
                $next_img = $db->getSingle($stmt, [$property_id]);
                if ($next_img) {
                    $stmt = $db->prepare('UPDATE property_images SET is_primary = 1 WHERE image_id = ?');
                    $db->execute($stmt, [$next_img['image_id']]);
                }
            }
            
            $_SESSION['success'] = 'Image deleted successfully.';
        }
    }

    header('Location: edit-property.php?id=' . (int)$property_id);
    exit;
}

// Update property
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_property'])) {
    $title_in = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $property_type = $_POST['property_type'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $location_id = $_POST['location_id'] ?? null;
    $bedrooms = $_POST['bedrooms'] ?? null;
    $bathrooms = $_POST['bathrooms'] ?? null;
    $area_sqm = $_POST['area_sqm'] ?? null;
    $monthly_rent = $_POST['monthly_rent'] ?? null;
    $security_deposit = $_POST['security_deposit'] ?? null;

    $errors = [];
    if ($bedrooms !== '' && (!is_numeric($bedrooms) || $bedrooms < 1 || $bedrooms > 9)) {
        $errors[] = 'Bedrooms must be between 1 and 9.';
    }
    if ($bathrooms !== '' && (!is_numeric($bathrooms) || $bathrooms < 0 || $bathrooms > 9)) {
        $errors[] = 'Bathrooms must be between 0 and 9.';
    }
    if (!is_numeric($monthly_rent) || (float)$monthly_rent <= 0) {
        $errors[] = 'Monthly rent must be a valid amount greater than 0.';
    }

    $is_furnished = isset($_POST['is_furnished']) ? 1 : 0;
    $amenities = trim($_POST['amenities'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $latitude = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $status = $_POST['status'] ?? $property['status'];

    if ($title_in === '') {
        $errors[] = 'Title is required.';
    }
    if ($property_type === '' || !in_array($property_type, ['house', 'apartment', 'villa', 'condominium', 'commercial'], true)) {
        $errors[] = 'Property type is required.';
    }
    if ($address === '') {
        $errors[] = 'Address is required.';
    }
    if (!is_numeric($location_id)) {
        $errors[] = 'Please select a valid location.';
    }
    if (!is_numeric($monthly_rent) || (float)$monthly_rent <= 0) {
        $errors[] = 'Monthly rent must be a valid amount.';
    }
    if (!in_array($status, ['available', 'maintenance', 'unavailable', 'requested', 'rented'], true)) {
        $errors[] = 'Invalid status.';
    }

    $subcity = null;
    if (is_numeric($location_id)) {
        $loc_stmt = $db->prepare('SELECT subcity FROM locations WHERE location_id = ?');
        $loc = $db->getSingle($loc_stmt, [$location_id]);
        $subcity = $loc['subcity'] ?? null;
    }

    if (empty($errors)) {
        // For rejected properties being resubmitted, reset review status
        $review_status = $is_rejected_property ? 'pending' : ($property['review_status'] ?? 'pending');
        
        $sql = "UPDATE properties
                SET title = ?, description = ?, property_type = ?, address = ?, location_id = ?, subcity = ?,
                    bedrooms = ?, bathrooms = ?, area_sqm = ?, monthly_rent = ?, security_deposit = ?,
                    is_furnished = ?, amenities = ?, featured = ?, status = ?, review_status = ?, 
                    latitude = ?, longitude = ?,
                    review_comments = NULL, reviewed_by = NULL, review_date = NULL, updated_at = NOW()
                WHERE property_id = ? AND owner_id = ?";
        $stmt = $db->prepare($sql);
        $db->execute($stmt, [
            $title_in,
            ($description === '' ? null : $description),
            $property_type,
            $address,
            $location_id,
            $subcity,
            ($bedrooms === '' ? null : (int)$bedrooms),
            ($bathrooms === '' ? null : (int)$bathrooms),
            ($area_sqm === '' ? null : (int)$area_sqm),
            (int)$monthly_rent,
            ($security_deposit === '' ? null : (int)$security_deposit),
            $is_furnished,
            ($amenities === '' ? null : $amenities),
            $featured,
            $status,
            $review_status,
            $latitude,
            $longitude,
            $property_id,
            $owner_id
        ]);

        // Upload new images (optional)
        $upload_dir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'properties' . DIRECTORY_SEPARATOR;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $make_primary = isset($_POST['make_primary_new']) ? 1 : 0;
        $first_uploaded = true;

        if (isset($_FILES['property_images']) && is_array($_FILES['property_images']['name'])) {
            $count = count($_FILES['property_images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if (($_FILES['property_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }

                $orig = $_FILES['property_images']['name'][$i];
                $tmp = $_FILES['property_images']['tmp_name'][$i];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_ext, true)) {
                    continue;
                }

                $file_name = 'property_' . $property_id . '_' . ($i + 1) . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $file_name;
                if (!move_uploaded_file($tmp, $dest)) {
                    continue;
                }

                if ($make_primary && $first_uploaded) {
                    $stmt = $db->prepare('UPDATE property_images SET is_primary = 0 WHERE property_id = ?');
                    $db->execute($stmt, [$property_id]);
                }

                $relative_url = '../' . PROPERTY_IMG_PATH . $file_name;
                $is_primary = ($make_primary && $first_uploaded) ? 1 : 0;
                $first_uploaded = false;

                $img_stmt = $db->prepare('INSERT INTO property_images (property_id, image_url, is_primary) VALUES (?, ?, ?)');
                $db->execute($img_stmt, [$property_id, $relative_url, $is_primary]);
            }
        }

        if ($is_rejected_property) {
            $_SESSION['success'] = 'Property updated and resubmitted for review. It will be reviewed by an employee.';
        } else {
            $_SESSION['success'] = 'Property updated successfully.';
        }
        header('Location: edit-property.php?id=' . (int)$property_id);
        exit;
    }
}

// Refresh property and images
$stmt = $db->prepare('SELECT * FROM properties WHERE property_id = ?');
$property = $db->getSingle($stmt, [$property_id]);

$img_stmt = $db->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id DESC');
$images = $db->getMultiple($img_stmt, [$property_id]);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">
                                <?php echo $is_rejected_property ? 'Update & Resubmit Property' : 'Edit Property'; ?>
                            </h1>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($property['title']); ?></p>
                        </div>
                        <div>
                            <a href="properties.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($is_rejected_property && !empty($property['review_comments'])): ?>
                <!-- Rejection Reason Alert -->
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Property Rejected - Please Address the Following Issues:
                    </h5>
                    <div class="mb-2">
                        <strong>Rejection Reason:</strong>
                        <p class="mb-2 mt-1"><?php echo htmlspecialchars($property['review_comments']); ?></p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Reviewed on: <?php echo date('M d, Y H:i', strtotime($property['review_date'] ?? 'now')); ?>
                        </small>
                    </div>
                    <hr>
                    <p class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Please update your property information to address the issues mentioned above.</strong><br>
                        After making the necessary changes, submit the property for review again.
                    </p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Property Details</h5></div>
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Title *</label>
                                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Property Type *</label>
                                        <select name="property_type" class="form-select" required>
                                            <option value="house" <?php echo $property['property_type'] === 'house' ? 'selected' : ''; ?>>House</option>
                                            <option value="apartment" <?php echo $property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                            <option value="villa" <?php echo $property['property_type'] === 'villa' ? 'selected' : ''; ?>>Villa</option>
                                            <option value="condominium" <?php echo $property['property_type'] === 'condominium' ? 'selected' : ''; ?>>Condominium</option>
                                            <option value="commercial" <?php echo $property['property_type'] === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                        </select>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Address *</label>
                                        <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($property['address']); ?>" required>
                                    </div>

                                    <div class="col-md-4 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-outline-primary flex-fill" id="geocodeAddressBtn">
                                            <i class="fas fa-map-marker-alt me-1"></i> Locate on map
                                        </button>
                                        <button type="button" class="btn btn-outline-success flex-fill" id="showOnMapBtn">
                                            <i class="fas fa-external-link-alt me-1"></i> Show on map
                                        </button>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <div id="editPropertyMap" class="rounded-3 shadow-sm border border-secondary" style="min-height:320px; background:#f8f9fa; overflow:hidden;"></div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label for="latitude" class="form-label small mb-1">Latitude</label>
                                                <input type="number" step="any" class="form-control form-control-sm" id="latitude" name="latitude" value="<?php echo htmlspecialchars($property['latitude'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="longitude" class="form-label small mb-1">Longitude</label>
                                                <input type="number" step="any" class="form-control form-control-sm" id="longitude" name="longitude" value="<?php echo htmlspecialchars($property['longitude'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <small id="coordsDisplay" class="form-text text-muted">Enter coordinates manually or use "Locate Address"</small>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Location *</label>
                                        <select name="location_id" class="form-select" required>
                                            <option value="">Select Location</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?php echo (int)$loc['location_id']; ?>" <?php echo ((string)$loc['location_id'] === (string)$property['location_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($loc['location_name']); ?>
                                                    <?php if (!empty($loc['subcity'])): ?>
                                                        (<?php echo htmlspecialchars($loc['subcity']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($property['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Bedrooms</label>
                                        <input type="number" id="bedrooms" name="bedrooms" class="form-control integer-input" value="<?php echo (!empty($property['bedrooms']) || $property['bedrooms'] === '0') ? (int)$property['bedrooms'] : ''; ?>" step="1" min="1" placeholder="1">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Bathrooms</label>
                                        <input type="number" id="bathrooms" name="bathrooms" class="form-control integer-input" value="<?php echo (!empty($property['bathrooms']) || $property['bathrooms'] === '0') ? (int)$property['bathrooms'] : ''; ?>" step="1" min="0" placeholder="1">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Area (sqm)</label>
                                        <input type="number" id="area_sqm" name="area_sqm" class="form-control" value="<?php echo (!empty($property['area_sqm']) || $property['area_sqm'] === '0') ? (int)$property['area_sqm'] : ''; ?>" step="1" placeholder="">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Monthly Rent (ETB) *</label>
                                        <input type="number" id="monthly_rent" name="monthly_rent" class="form-control currency-input" value="<?php echo (!empty($property['monthly_rent']) || $property['monthly_rent'] === '0') ? (float)$property['monthly_rent'] : ''; ?>" required step="0.01" min="0" placeholder="0.00">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Security Deposit (ETB)</label>
                                        <input type="number" id="security_deposit" name="security_deposit" class="form-control currency-input" value="<?php echo (!empty($property['security_deposit']) || $property['security_deposit'] === '0') ? (float)$property['security_deposit'] : ''; ?>" step="0.01" min="0" placeholder="0.00">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="available" <?php echo $property['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="maintenance" <?php echo $property['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="unavailable" <?php echo $property['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                            <option value="requested" <?php echo $property['status'] === 'requested' ? 'selected' : ''; ?>>Requested</option>
                                            <option value="rented" <?php echo $property['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Amenities</label>
                                        <input type="text" name="amenities" class="form-control" value="<?php echo htmlspecialchars($property['amenities'] ?? ''); ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_furnished" id="is_furnished" <?php echo !empty($property['is_furnished']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_furnished">Furnished</label>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="featured" id="featured" <?php echo !empty($property['featured']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="featured">Featured</label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Upload New Images</label>
                                        <div id="imagePreviewContainer" class="row g-2 mb-2"></div>
                                        <input type="file" id="property_images" name="property_images[]" class="form-control" accept="image/*" multiple onchange="previewImages(this)">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="make_primary_new" id="make_primary_new">
                                            <label class="form-check-label" for="make_primary_new">Make first uploaded image primary</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex justify-content-end gap-2">
                                    <a href="properties.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" name="update_property" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $is_rejected_property ? 'Update & Resubmit for Review' : 'Save Changes'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Images</h5></div>
                        <div class="card-body">
                            <?php if (empty($images)): ?>
                                <div class="text-center text-muted py-4">
                                    No images yet.
                                </div>
                            <?php else: ?>
                                <div class="row g-2">
                                    <?php foreach ($images as $img): ?>
                                        <div class="col-6">
                                            <div class="border rounded p-2">
                                                <img src="<?php echo htmlspecialchars($img['image_url']); ?>" class="img-fluid rounded" alt="Image">
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <?php if ((int)$img['is_primary'] === 1): ?>
                                                        <span class="badge bg-success">Primary</span>
                                                    <?php else: ?>
                                                        <div class="d-flex gap-1">
                                                            <form method="POST" action="" class="m-0">
                                                                <input type="hidden" name="image_id" value="<?php echo (int)$img['image_id']; ?>">
                                                                <button type="submit" name="set_primary" class="btn btn-sm btn-outline-success" title="Set as primary">
                                                                    <i class="fas fa-star"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="" class="m-0" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                                                <input type="hidden" name="image_id" value="<?php echo (int)$img['image_id']; ?>">
                                                                <button type="submit" name="delete_image" class="btn btn-sm btn-outline-danger" title="Delete image">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
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
    </div>
</div>

<script>
var editPropertyMap = null;
var editPropertyMarker = null;
var editPropertyGeocoder = null;
var editPropertyAutocomplete = null;

function showEditPropertyMapError(message) {
    var container = document.getElementById('editPropertyMap');
    if (!container) return;
    container.innerHTML = '<div class="d-flex flex-column align-items-center justify-content-center h-100 text-center text-danger p-4">' +
        '<div class="display-6 mb-3"><i class="fas fa-exclamation-circle"></i></div>' +
        '<div class="fw-bold mb-2">Map unavailable</div>' +
        '<div class="small">' + message + '</div>' +
        '</div>';
    container.style.backgroundColor = '#fff5f5';
    container.style.borderColor = '#dc3545';
}

function initLeafletFallbackMap() {
    if (!document.getElementById('editPropertyMap') || !window.L) {
        showEditPropertyMapError('Unable to initialize fallback map.');
        return;
    }

    var lat = parseFloat(document.getElementById('latitude').value) || 14.1211;
    var lng = parseFloat(document.getElementById('longitude').value) || 38.7241;
    var zoom = document.getElementById('latitude').value && document.getElementById('longitude').value ? 15 : 8;

    var map = L.map('editPropertyMap').setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    var marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', function(e) {
        var pos = e.target.getLatLng();
        document.getElementById('latitude').value = pos.lat.toFixed(8);
        document.getElementById('longitude').value = pos.lng.toFixed(8);
        document.getElementById('coordsDisplay').innerText = 'Selected coordinates: ' + pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6);
    });

    document.getElementById('coordsDisplay').innerText = 'Selected coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
}

function loadLeafletFallback() {
    if (window.L) {
        initLeafletFallbackMap();
        return;
    }

    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet/dist/leaflet.css';
    document.head.appendChild(css);

    var script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet/dist/leaflet.js';
    script.async = true;
    script.defer = true;
    script.onload = initLeafletFallbackMap;
    script.onerror = function() {
        showEditPropertyMapError('Fallback map failed to load.');
    };
    document.head.appendChild(script);
}

window.handleGoogleMapsLoadError = function() {
    loadLeafletFallback();
};

window.gm_authFailure = function() {
    loadLeafletFallback();
};

function initEditPropertyMap() {
    if (!document.getElementById('editPropertyMap') || !window.google || !google.maps) {
        window.handleGoogleMapsLoadError();
        return;
    }

    var lat = parseFloat(document.getElementById('latitude').value) || null;
    var lng = parseFloat(document.getElementById('longitude').value) || null;
    var center = { lat: lat || 14.1211, lng: lng || 38.7241 };

    editPropertyMap = new google.maps.Map(document.getElementById('editPropertyMap'), {
        center: center,
        zoom: lat && lng ? 15 : 8,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        styles: [
            { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
            { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
            { featureType: 'administrative', elementType: 'geometry.stroke', stylers: [{ color: '#c9c9c9' }] },
            { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#eeeeee' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#c9e3f2' }] }
        ]
    });

    editPropertyGeocoder = new google.maps.Geocoder();
    editPropertyAutocomplete = new google.maps.places.Autocomplete(document.getElementById('address'), {
        componentRestrictions: { country: 'et' },
        fields: ['formatted_address', 'geometry']
    });

    editPropertyAutocomplete.addListener('place_changed', function() {
        var place = editPropertyAutocomplete.getPlace();
        if (!place.geometry) {
            alert('Unable to locate the selected address. Please choose a more specific location.');
            return;
        }
        setMapMarker(place.geometry.location.lat(), place.geometry.location.lng(), place.formatted_address || document.getElementById('address').value);
    });

    editPropertyMap.addListener('click', function(e) {
        setMapMarker(e.latLng.lat(), e.latLng.lng());
    });

    if (lat && lng) {
        setMapMarker(lat, lng);
    }
}

function setMapMarker(lat, lng, address) {
    if (!document.getElementById('editPropertyMap')) return;

    var position = { lat: parseFloat(lat), lng: parseFloat(lng) };

    if (!editPropertyMarker) {
        editPropertyMarker = new google.maps.Marker({
            position: position,
            map: editPropertyMap,
            draggable: true
        });
        editPropertyMarker.addListener('dragend', function() {
            var pos = editPropertyMarker.getPosition();
            setMapMarker(pos.lat(), pos.lng());
        });
    } else {
        editPropertyMarker.setPosition(position);
    }

    editPropertyMap.panTo(position);
    editPropertyMap.setZoom(15);

    document.getElementById('latitude').value = position.lat.toFixed(8);
    document.getElementById('longitude').value = position.lng.toFixed(8);
    document.getElementById('coordsDisplay').innerText = 'Selected coordinates: ' + position.lat.toFixed(6) + ', ' + position.lng.toFixed(6);
    if (address) {
        document.getElementById('address').value = address;
    }
}

function geocodeAddress(address) {
    if (!address || address.trim() === '') {
        alert('Please enter an address to locate');
        return;
    }

    // Try Google Maps first
    if (window.google && google.maps) {
        if (!editPropertyGeocoder) {
            editPropertyGeocoder = new google.maps.Geocoder();
        }
        editPropertyGeocoder.geocode({ address: address }, function(results, status) {
            if (status === 'OK' && results && results.length) {
                var location = results[0].geometry.location;
                setMapMarker(location.lat(), location.lng(), results[0].formatted_address);
            } else {
                // Fallback to Nominatim if Google fails
                geocodeWithNominatim(address);
            }
        });
    } else {
        // Use Nominatim if Google Maps is not loaded
        geocodeWithNominatim(address);
    }
}

function geocodeWithNominatim(address) {
    var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + '&limit=1';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                var lat = parseFloat(data[0].lat);
                var lng = parseFloat(data[0].lon);
                var display_name = data[0].display_name;
                
                // Update based on which map is active
                if (window.google && google.maps && editPropertyMap instanceof google.maps.Map) {
                    setMapMarker(lat, lng, display_name);
                } else if (window.L) {
                    // Update inputs
                    document.getElementById('latitude').value = lat.toFixed(8);
                    document.getElementById('longitude').value = lng.toFixed(8);
                    document.getElementById('address').value = display_name;
                    document.getElementById('coordsDisplay').innerText = 'Selected coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                }
            } else {
                alert('Address not found. Please refine the location text or enter coordinates manually.');
            }
        })
        .catch(error => {
            console.error('Nominatim geocoding error:', error);
            alert('Geocoding service unavailable. Please enter coordinates manually.');
        });
}

function loadEditPropertyMapScript() {
    var apiKey = '<?php echo GOOGLE_MAPS_API_KEY; ?>';
    if (!apiKey || apiKey === 'YOUR_GOOGLE_MAPS_API_KEY') {
        loadLeafletFallback();
        return;
    }

    var script = document.createElement('script');
    script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + '&libraries=places&callback=initEditPropertyMap';
    script.async = true;
    script.defer = true;
    script.onerror = window.handleGoogleMapsLoadError;
    document.head.appendChild(script);
}

function loadLeafletFallback() {
    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet/dist/leaflet.css';
    document.head.appendChild(css);

    var script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet/dist/leaflet.js';
    script.async = true;
    script.defer = true;
    script.onload = initLeafletFallbackMap;
    script.onerror = function() {
        showEditPropertyMapError('Fallback map failed to load.');
    };
    document.head.appendChild(script);
}

function initLeafletFallbackMap() {
    if (!document.getElementById('editPropertyMap') || !window.L) {
        showEditPropertyMapError('Unable to initialize fallback map.');
        return;
    }

    var lat = parseFloat(document.getElementById('latitude').value) || 14.1211;
    var lng = parseFloat(document.getElementById('longitude').value) || 38.7241;
    var zoom = document.getElementById('latitude').value && document.getElementById('longitude').value ? 15 : 8;

    var map = L.map('editPropertyMap').setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    var marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', function(e) {
        var pos = e.target.getLatLng();
        document.getElementById('latitude').value = pos.lat.toFixed(8);
        document.getElementById('longitude').value = pos.lng.toFixed(8);
        document.getElementById('coordsDisplay').innerText = 'Selected coordinates: ' + pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6);
    });

    document.getElementById('coordsDisplay').innerText = 'Selected coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
    var fallbackNotice = document.createElement('div');
    fallbackNotice.className = 'alert alert-warning py-2 mt-3';
    fallbackNotice.innerHTML = '<strong>Google Maps unavailable.</strong> Using OpenStreetMap fallback for a fully functional map.';
    var parent = document.getElementById('editPropertyMap').parentNode;
    if (parent && !parent.querySelector('.alert')) {
        parent.insertBefore(fallbackNotice, document.getElementById('editPropertyMap').nextSibling);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadEditPropertyMapScript();

    // Add listeners for manual coordinate entry
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    function updateMapFromInputs() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            if (window.google && google.maps && editPropertyMap instanceof google.maps.Map) {
                setMapMarker(lat, lng);
            } else if (window.L) {
                document.getElementById('coordsDisplay').innerText = 'Selected coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
            }
        }
    }

    if (latInput) latInput.addEventListener('change', updateMapFromInputs);
    if (lngInput) lngInput.addEventListener('change', updateMapFromInputs);

    // Currency input restriction (only numbers and dot)
    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter, . (decimal point)
            if ([46, 8, 9, 27, 13, 110, 190].indexOf(e.keyCode) !== -1 ||
                (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode === 67 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode === 86 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode === 88 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                
                if (e.keyCode === 190 || e.keyCode === 110) {
                    if (this.value.indexOf('.') !== -1) {
                        e.preventDefault();
                    }
                }
                return;
            }
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function() {
            let val = this.value;
            if (/[^0-9.]/g.test(val)) {
                this.value = val.replace(/[^0-9.]/g, '');
            }
            let parts = this.value.split('.');
            if (parts.length > 2) {
                this.value = parts[0] + '.' + parts.slice(1).join('');
            }
        });
    });

    // Integer input restriction (only numbers, no dots)
    const integerInputs = document.querySelectorAll('.integer-input');
    integerInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter
            if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode === 67 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode === 86 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode === 88 && (e.ctrlKey === true || e.metaKey === true)) || 
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                return;
            }
            // Block dot/decimal point (110, 190)
            if (e.keyCode === 110 || e.keyCode === 190) {
                e.preventDefault();
            }
            // Ensure that it is a number
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function() {
            let val = this.value;
            // Remove anything that isn't a digit
            if (/[^0-9]/g.test(val)) {
                this.value = val.replace(/[^0-9]/g, '');
            }
            
            // Special case for bedrooms: block "0"
            if (this.id === 'bedrooms' && this.value === '0') {
                this.value = '1';
                alert('Property must have at least 1 bedroom.');
            }
        });
    });

    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const br = document.getElementById('bedrooms').value;
            const ba = document.getElementById('bathrooms').value;
            const rent = document.getElementById('monthly_rent').value;

            if (br !== "" && (isNaN(br) || br < 1 || br > 9)) {
                e.preventDefault();
                alert('Bedrooms must be between 1 and 9');
                return;
            }
            if (ba !== "" && (isNaN(ba) || ba < 0 || ba > 9)) {
                e.preventDefault();
                alert('Bathrooms must be between 0 and 9');
                return;
            }
            if (!rent || isNaN(rent) || rent <= 0) {
                e.preventDefault();
                alert('Monthly rent must be a valid number greater than 0');
                return;
            }
        });
    }

    document.getElementById('geocodeAddressBtn').addEventListener('click', function() {
        var address = document.getElementById('address').value;
        geocodeAddress(address);
    });

    document.getElementById('showOnMapBtn').addEventListener('click', function() {
        var lat = document.getElementById('latitude').value;
        var lng = document.getElementById('longitude').value;

        if (!lat || !lng) {
            alert('Please set a location on the map first by using "Locate on map" or dragging the marker.');
            return;
        }

        var googleMapsUrl = 'https://www.google.com/maps/@' + encodeURIComponent(lat) + ',' + encodeURIComponent(lng) + ',15z/data=!3m1!1e3';
        window.open(googleMapsUrl, '_blank');
    });

    document.getElementById('address').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            geocodeAddress(this.value);
        }
    });
});

function previewImages(input) {
    const container = document.getElementById('imagePreviewContainer');
    container.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'col-md-3 col-6 position-relative mb-2';
                div.innerHTML = `
                    <div class="border rounded p-1 h-100">
                        <img src="${e.target.result}" class="img-fluid rounded" style="height: 80px; width: 100%; object-fit: cover;">
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 d-flex align-items-center justify-content-center" 
                                style="width: 20px; height: 20px; border-radius: 50%;" 
                                onclick="removePreviewImage(${index})">
                            <i class="fas fa-times" style="font-size: 10px;"></i>
                        </button>
                    </div>
                `;
                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }
}

function removePreviewImage(index) {
    const input = document.getElementById('property_images');
    const dt = new DataTransfer();
    const { files } = input;
    
    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }
    
    input.files = dt.files;
    previewImages(input);
}
</script>

<?php include '../includes/footer.php'; ?>
