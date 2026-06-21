<?php
require_once '../includes/config.php';

$session->requireRole('owner');

$title = "Add Property";
$owner_id = $session->getUserId();

// Ensure database schema is up to date
$required_columns = [
    'contact_preferences' => "TEXT NULL AFTER longitude",
    'contact_phone' => "VARCHAR(255) NULL AFTER contact_preferences",
    'contact_email' => "VARCHAR(255) NULL AFTER contact_phone",
    'property_rules' => "TEXT NULL AFTER contact_email",
    'latitude' => "DECIMAL(10, 8) NULL AFTER featured",
    'longitude' => "DECIMAL(11, 8) NULL AFTER latitude"
];

foreach ($required_columns as $column => $definition) {
    if (!$db->columnExists('properties', $column)) {
        try {
            $conn->exec("ALTER TABLE properties ADD COLUMN $column $definition");
        } catch (Exception $e) {
            error_log("Migration failed for $column: " . $e->getMessage());
        }
    }
}

// Get locations for dropdown
$desiredLocations = [
    ['location_name' => "Aksum K'Idist Maryam Hospital", 'subcity' => "K'Idist Maryam Hospital Area"],
    ['location_name' => 'Aksum Zion', 'subcity' => 'Aksum Zion Area'],
    ['location_name' => 'Ezana Park', 'subcity' => 'Ezana Park Area'],
    ['location_name' => 'Aksum University Area', 'subcity' => 'Aksum University Area'],
    ['location_name' => 'Aksum Market', 'subcity' => 'Aksum Market Area'],
    ['location_name' => 'Referral Hospital', 'subcity' => 'Referral Hospital Area'],
    ['location_name' => 'Airport Street', 'subcity' => 'Airport Street Area'],
];

$locations = [];
try {
    foreach ($desiredLocations as $desiredLocation) {
        $checkSql = "SELECT location_id FROM locations WHERE location_name = ? LIMIT 1";
        $checkStmt = $db->prepare($checkSql);
        $existing = $db->getSingle($checkStmt, [$desiredLocation['location_name']]);

        if (!$existing) {
            $insertSql = "INSERT INTO locations (location_name, subcity) VALUES (?, ?)";
            $insertStmt = $db->prepare($insertSql);
            $db->execute($insertStmt, [$desiredLocation['location_name'], $desiredLocation['subcity']]);
        }
    }

    $placeholders = implode(',', array_fill(0, count($desiredLocations), '?'));
    $sql = "SELECT location_id, location_name, subcity FROM locations WHERE location_name IN ($placeholders) ORDER BY location_name";
    $stmt = $db->prepare($sql);
    $params = array_column($desiredLocations, 'location_name');
    $locations = $db->getMultiple($stmt, $params);
} catch (Exception $e) {
    $locations = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['title', 'property_type', 'address', 'location_id', 'monthly_rent'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Prepare and validate numeric data
        $title = trim($_POST['title']);
        $property_type = trim($_POST['property_type']);
        $address = trim($_POST['address']);
        $location_id = (int)$_POST['location_id'];
        $description = trim($_POST['description'] ?? '');
        
        $bedrooms_raw = $_POST['bedrooms'] ?? '';
        $bathrooms_raw = $_POST['bathrooms'] ?? '';
        $area_sqm_raw = $_POST['area_sqm'] ?? '';
        $monthly_rent_raw = $_POST['monthly_rent'] ?? '';
        $security_deposit_raw = $_POST['security_deposit'] ?? '';

        if ($bedrooms_raw !== '' && (!is_numeric($bedrooms_raw) || $bedrooms_raw < 1 || $bedrooms_raw > 9)) {
            throw new Exception('Bedrooms must be a whole number between 1 and 9');
        }
        if ($bathrooms_raw !== '' && (!is_numeric($bathrooms_raw) || $bathrooms_raw < 0 || $bathrooms_raw > 9)) {
            throw new Exception('Bathrooms must be a whole number between 0 and 9');
        }
        if (!is_numeric($monthly_rent_raw) || $monthly_rent_raw <= 0) {
            throw new Exception('Monthly rent must be a valid number greater than 0');
        }

        $bedrooms = $bedrooms_raw !== '' ? (int)$bedrooms_raw : 0;
        $bathrooms = $bathrooms_raw !== '' ? (int)$bathrooms_raw : 0;
        $area_sqm = $area_sqm_raw !== '' ? (int)$area_sqm_raw : 0;
        $monthly_rent = (int)$monthly_rent_raw;
        $security_deposit = $security_deposit_raw !== '' ? (int)$security_deposit_raw : 0;
        $amenities = trim($_POST['amenities'] ?? '');
        $is_furnished = isset($_POST['is_furnished']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $latitude = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        $status = $_POST['status'] ?? 'available';

        // Property rules handling
        $property_rules = [];
        if (isset($_POST['rule_title']) && is_array($_POST['rule_title'])) {
            foreach ($_POST['rule_title'] as $index => $rule_title) {
                $rule_title = trim($rule_title);
                $rule_description = trim($_POST['rule_description'][$index] ?? '');
                
                if (!empty($rule_title)) {
                    $property_rules[] = [
                        'title' => $rule_title,
                        'description' => $rule_description
                    ];
                }
            }
        }
        $property_rules_data = !empty($property_rules) ? json_encode($property_rules) : null;

        // Insert property
        $sql = "INSERT INTO properties (
            owner_id, location_id, title, description, property_type, address, 
            bedrooms, bathrooms, area_sqm, monthly_rent, security_deposit, 
            is_furnished, amenities, status, review_status, featured,
            latitude, longitude,
            property_rules,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare property insert query: " . $db->getLastError());
        }
        
        $db->execute($stmt, [
            $owner_id, $location_id, $title, $description, $property_type, $address,
            $bedrooms, $bathrooms, $area_sqm, $monthly_rent, $security_deposit, 
            $is_furnished, $amenities, $status, 'pending', $is_featured,
            $latitude, $longitude,
            $property_rules_data
        ]);

        $property_id = $db->lastInsertId();

        // Handle image uploads with enhanced options
        $uploaded_images = [];
        $image_errors = [];
        
        if (!empty($_FILES['property_images']['name'][0])) {
            $upload_dir = '../assets/uploads/properties/';
            $thumbnail_dir = '../assets/uploads/properties/thumbnails/';
            
            // Create directories if they don't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (!file_exists($thumbnail_dir)) {
                mkdir($thumbnail_dir, 0755, true);
            }

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            $max_images = 10;
            $make_first_primary = isset($_POST['make_first_primary']);
            $auto_resize = isset($_POST['auto_resize']);
            $generate_thumbnails = isset($_POST['generate_thumbnails']);
            $is_primary_set = false;

            // Limit number of images
            $image_files = array_filter($_FILES['property_images']['name']);
            if (count($image_files) > $max_images) {
                throw new Exception("Maximum {$max_images} images allowed per property.");
            }

            foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['property_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['property_images']['name'][$key];
                    $file_size = $_FILES['property_images']['size'][$key];
                    $file_tmp = $_FILES['property_images']['tmp_name'][$key];
                    
                    // Validate file
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $image_errors[] = "Invalid file type: $file_name. Only JPG, PNG, GIF, and WEBP files are allowed.";
                        continue;
                    }
                    
                    if ($file_size > $max_file_size) {
                        $image_errors[] = "File too large: $file_name. Maximum size is 5MB.";
                        continue;
                    }

                    // Generate unique filename
                    $unique_name = 'property_' . $property_id . '_' . time() . '_' . $key . '.' . $file_extension;
                    $upload_path = $upload_dir . $unique_name;

                    // Process image if auto-resize is enabled
                    if ($auto_resize) {
                        $result = resizeImage($file_tmp, $upload_path, $unique_name, $file_extension);
                        if (!$result) {
                            $image_errors[] = "Failed to resize image: $file_name. Trying to upload without resizing.";
                            // Try to move without resizing
                            if (!move_uploaded_file($file_tmp, $upload_path)) {
                                $image_errors[] = "Failed to upload file: $file_name";
                                continue;
                            }
                        }
                    } else {
                        // Move file without resizing
                        if (!move_uploaded_file($file_tmp, $upload_path)) {
                            $image_errors[] = "Failed to upload file: $file_name";
                            continue;
                        }
                    }

                    // Generate thumbnail if enabled
                    if ($generate_thumbnails) {
                        $thumbnail_path = $thumbnail_dir . 'thumb_' . $unique_name;
                        if (!createThumbnail($upload_path, $thumbnail_path, $file_extension)) {
                            // Log error but don't fail the upload
                            error_log("Failed to create thumbnail for: $unique_name");
                        }
                    }

                    // Set primary image based on checkbox
                    $is_primary = 0;
                    if ($make_first_primary && !$is_primary_set && $key === 0) {
                        $is_primary = 1;
                        $is_primary_set = true;
                    }

                    // Insert into database with full path to match existing format
                    $image_url = '../assets/uploads/properties/' . $unique_name;
                    $sql = "INSERT INTO property_images (property_id, image_url, is_primary) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    if ($db->execute($stmt, [$property_id, $image_url, $is_primary])) {
                        $uploaded_images[] = $unique_name;
                    } else {
                        $image_errors[] = "Failed to save image record: $file_name";
                    }
                } elseif ($_FILES['property_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                    $image_errors[] = "Upload error for file " . ($key + 1) . ": " . $_FILES['property_images']['error'][$key];
                }
            }
            
            // If no images were successfully uploaded, show warning
            if (empty($uploaded_images) && !empty($image_files)) {
                throw new Exception("No images were successfully uploaded. Errors: " . implode('; ', $image_errors));
            }
        }

        // Handle additional images if any
        if (!empty($_FILES['additional_images']['name'][0])) {
            // Process additional images the same way
            // This would be similar to the above processing
        }

        // Create notification for employees
        $message = "New property '{$title}' requires review";
        $employeeIdsSql = "SELECT user_id FROM users WHERE role = 'employee'";
        $employeeIdsStmt = $db->prepare($employeeIdsSql);
        $employeeIds = $db->getMultiple($employeeIdsStmt);
        foreach ($employeeIds as $employeeRow) {
            createNotification($employeeRow['user_id'], 'New Property Added', $message, 'info', null, 15);
        }

        // Send email notification to employees
        try {
            require_once __DIR__ . '/../includes/functions.php';
            $employeeStmt = $db->prepare("SELECT full_name, email FROM users WHERE role = 'employee' AND is_active = 1");
            $employees = $db->getMultiple($employeeStmt);
            
            if (!empty($employees)) {
                $ownerStmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
                $owner = $db->getSingle($ownerStmt, [$owner_id]);
                
                $locationStmt = $db->prepare("SELECT location_name FROM locations WHERE location_id = ?");
                $locationRow = $db->getSingle($locationStmt, [$location_id]);
                $location_name = $locationRow['location_name'] ?? 'Unknown Location';

                foreach ($employees as $employee) {
                    if (!empty($employee['email'])) {
                        sendEmailTemplate($employee['email'], "New Property Pending Review - " . SITE_NAME, 'property_added', [
                            'employee_name' => $employee['full_name'],
                            'property_title' => $title,
                            'property_location' => $location_name,
                            'owner_name' => $owner['full_name'] ?? 'Unknown Owner',
                            'date_added' => date('F d, Y'),
                            'review_link' => rtrim(SITE_URL, '/') . '/employee/property-review.php',
                            'site_name' => SITE_NAME
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to send property added email to employees: " . $e->getMessage());
        }

        // Create success message with image information
        $success_message = "Property added successfully! It will be reviewed by an employee before being published.";
        if (!empty($uploaded_images)) {
            $success_message .= " " . count($uploaded_images) . " image(s) uploaded successfully.";
        }
        if (!empty($image_errors)) {
            $success_message .= " Some images had issues: " . implode('; ', array_slice($image_errors, 0, 3));
            if (count($image_errors) > 3) {
                $success_message .= " ...";
            }
        }

        $_SESSION['success'] = $success_message;
        header('Location: properties.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../includes/header.php';
$addPropertyGoogleMapsKey = (defined('GOOGLE_MAPS_API_KEY') && GOOGLE_MAPS_API_KEY !== 'YOUR_GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
?>
<script>
    window.addPropertyGoogleMapsKey = '<?php echo htmlspecialchars($addPropertyGoogleMapsKey, ENT_QUOTES); ?>';
    window.loadAddPropertyGoogleMaps = function() {
        if (!window.addPropertyGoogleMapsKey) {
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(window.addPropertyGoogleMapsKey) + '&libraries=places&callback=initializeAddPropertyMap';
        script.async = true;
        script.defer = true;
        script.onerror = function() {
            window.handleGoogleMapsLoadError && window.handleGoogleMapsLoadError();
        };
        document.head.appendChild(script);
    };
</script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Add Property</h1>
                    <p class="text-muted mb-0">Create a new property listing</p>
                </div>
                <a href="properties.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Add Property Form -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-0 py-4">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-home me-2"></i>Property Details</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" id="addPropertyForm" enctype="multipart/form-data">
                        <div class="row g-4">
                            <!-- Left Column - Property Details -->
                            <div class="col-md-8">
                                <!-- Title -->
                                <div class="mb-4">
                                    <label for="title" class="form-label fw-semibold text-dark mb-2">Property Title</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-tag text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 ps-0" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                               placeholder="Enter an attractive title for your property" required>
                                    </div>
                                </div>

                                <!-- Property Type -->
                                <div class="mb-4">
                                    <label for="property_type" class="form-label fw-semibold text-dark mb-2">Property Type</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-building text-muted"></i></span>
                                        <select class="form-select border-start-0 ps-0" id="property_type" name="property_type" required>
                                            <option value="">Select Property Type</option>
                                            <option value="house" <?php echo (($_POST['property_type'] ?? '') === 'house') ? 'selected' : ''; ?>>🏠 House</option>
                                            <option value="apartment" <?php echo (($_POST['property_type'] ?? '') === 'apartment') ? 'selected' : ''; ?>>🏢 Apartment</option>
                                            <option value="villa" <?php echo (($_POST['property_type'] ?? '') === 'villa') ? 'selected' : ''; ?>>🏡 Villa</option>
                                            <option value="condominium" <?php echo (($_POST['property_type'] ?? '') === 'condominium') ? 'selected' : ''; ?>>🏙️ Condominium</option>
                                            <option value="commercial" <?php echo (($_POST['property_type'] ?? '') === 'commercial') ? 'selected' : ''; ?>>🏪 Commercial</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="mb-4">
                                    <label for="address" class="form-label fw-semibold text-dark mb-2">Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-map-marker-alt text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 ps-0" id="address" name="address" 
                                               value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" 
                                               placeholder="Full address of the property" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-outline-primary rounded-pill px-3" id="geocodeAddressBtn">
                                            <i class="fas fa-search-location me-2"></i>Locate on Map
                                        </button>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted">Click to auto-fill coordinates</small>
                                    </div>
                                </div>

                                <div id="addPropertyMap" class="rounded-3 shadow-sm mb-4" style="height:250px; width:100%;"></div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="latitude" class="form-label fw-semibold text-dark mb-2">Latitude</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-arrows-alt-v text-muted"></i></span>
                                            <input type="number" step="any" class="form-control border-start-0 ps-0" id="latitude" name="latitude" 
                                                   value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>" 
                                                   placeholder="e.g. 14.1211">
                                        </div>
                                        <small class="text-muted">Enter manually or use "Locate on Map"</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="longitude" class="form-label fw-semibold text-dark mb-2">Longitude</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-arrows-alt-h text-muted"></i></span>
                                            <input type="number" step="any" class="form-control border-start-0 ps-0" id="longitude" name="longitude" 
                                                   value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>" 
                                                   placeholder="e.g. 38.7241">
                                        </div>
                                        <small class="text-muted">Enter manually or use "Locate on Map"</small>
                                    </div>
                                </div>

                                <!-- Location -->
                                <div class="mb-4">
                                    <label for="location_id" class="form-label fw-semibold text-dark mb-2">Location</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-map-pin text-muted"></i></span>
                                        <select class="form-select border-start-0 ps-0" id="location_id" name="location_id" required>
                                            <option value="">Choose Location</option>
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?php echo $location['location_id']; ?>" 
                                                        <?php echo (($_POST['location_id'] ?? '') == $location['location_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($location['location_name'] . ' (' . $location['subcity'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="mb-4">
                                    <label for="description" class="form-label fw-semibold text-dark mb-2">Description</label>
                                    <textarea class="form-control rounded-3" id="description" name="description" rows="4" 
                                              placeholder="Describe your property's features, amenities, and what makes it special..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>

                                <!-- Property Details Row -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <!-- Bedrooms -->
                                        <label for="bedrooms" class="form-label fw-semibold text-dark mb-2">Bedrooms</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-bed text-muted"></i></span>
                                            <input type="number" class="form-control border-start-0 ps-0 integer-input" id="bedrooms" name="bedrooms" 
                                                   value="<?php echo htmlspecialchars($_POST['bedrooms'] ?? ''); ?>" 
                                                   step="1" min="1" placeholder="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Bathrooms -->
                                        <label for="bathrooms" class="form-label fw-semibold text-dark mb-2">Bathrooms</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-bath text-muted"></i></span>
                                            <input type="number" class="form-control border-start-0 ps-0 integer-input" id="bathrooms" name="bathrooms" 
                                                   value="<?php echo htmlspecialchars($_POST['bathrooms'] ?? ''); ?>" 
                                                   step="1" min="0" placeholder="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <!-- Area -->
                                        <label for="area_sqm" class="form-label fw-semibold text-dark mb-2">Area (sqm)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-ruler-combined text-muted"></i></span>
                                            <input type="number" class="form-control border-start-0 ps-0" id="area_sqm" name="area_sqm" 
                                                   value="<?php echo htmlspecialchars($_POST['area_sqm'] ?? ''); ?>" 
                                                   step="1" placeholder="">
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Details Row -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <!-- Monthly Rent -->
                                        <label for="monthly_rent" class="form-label fw-semibold text-dark mb-2">Monthly Rent</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-dollar-sign text-muted"></i></span>
                                            <input type="number" class="form-control border-start-0 ps-0 currency-input" id="monthly_rent" name="monthly_rent" 
                                                   value="<?php echo htmlspecialchars($_POST['monthly_rent'] ?? ''); ?>" 
                                                   step="0.01" min="0" placeholder="0.00" required>
                                            <span class="input-group-text bg-light border-start-0">ETB</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Security Deposit -->
                                        <label for="security_deposit" class="form-label fw-semibold text-dark mb-2">Security Deposit</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-shield-alt text-muted"></i></span>
                                            <input type="number" class="form-control border-start-0 ps-0 currency-input" id="security_deposit" name="security_deposit" 
                                                   value="<?php echo htmlspecialchars($_POST['security_deposit'] ?? ''); ?>" 
                                                   step="0.01" min="0" placeholder="0.00">
                                            <span class="input-group-text bg-light border-start-0">ETB</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Amenities -->
                                <div class="mb-4">
                                    <label for="amenities" class="form-label fw-semibold text-dark mb-2">Amenities</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-concierge-bell text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 ps-0" id="amenities" name="amenities" 
                                               value="<?php echo htmlspecialchars($_POST['amenities'] ?? ''); ?>" 
                                               placeholder="e.g. WiFi, Parking, Security, Water, Electricity">
                                    </div>
                                    <small class="text-muted">Separate amenities with commas</small>
                                </div>

                                <!-- Property Rules -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold text-dark mb-3">Property Rules</label>
                                    <p class="text-muted small mb-3">Set guidelines for tenants to ensure a smooth rental experience.</p>
                                    
                                    <div id="rulesContainer">
                                        <!-- Rules will be added here dynamically -->
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline-primary rounded-pill px-4 py-2" id="addRuleBtn">
                                        <i class="fas fa-plus me-2"></i>Add Rule
                                    </button>
                                </div>

                                <!-- Status and Options Row -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <!-- Status -->
                                        <label for="status" class="form-label fw-semibold text-dark mb-2">Property Status</label>
                                        <select class="form-select rounded-3" id="status" name="status">
                                            <option value="available">🟢 Available</option>
                                            <option value="maintenance">🔧 Under Maintenance</option>
                                            <option value="unavailable">🔴 Unavailable</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Property Options -->
                                        <label class="form-label fw-semibold text-dark mb-2">Options</label>
                                        <div class="d-flex flex-column gap-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_furnished" name="is_furnished" 
                                                       <?php echo isset($_POST['is_furnished']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="is_furnished">
                                                    <i class="fas fa-couch text-warning me-2"></i>Furnished
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                                       <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-semibold" for="is_featured">
                                                    <i class="fas fa-star text-warning me-2"></i>Featured Listing
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Images -->
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-header bg-gradient-primary text-white border-0 rounded-top-3">
                                        <h5 class="card-title mb-0"><i class="fas fa-images me-2"></i>Property Images</h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <div id="currentImages" class="mb-4 text-center">
                                            <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-2">No images selected yet</p>
                                        </div>
                                        
                                        <!-- Upload New Images -->
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-dark mb-3">Upload Images</label>
                                            <div class="border border-dashed border-2 rounded-3 p-4 text-center bg-light upload-zone" 
                                                 ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                                <i class="fas fa-cloud-upload-alt text-primary" style="font-size: 2rem;"></i>
                                                <p class="text-muted mb-3">Drag & drop images here or click to browse</p>
                                                <input type="file" class="form-control d-none" id="property_images" name="property_images[]" 
                                                       multiple accept="image/*" onchange="handleImageSelection(this.files)">
                                                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="document.getElementById('property_images').click()">
                                                    <i class="fas fa-folder-open me-2"></i>Choose Files
                                                </button>
                                                <small class="text-muted d-block mt-3" id="fileText">No files selected</small>
                                            </div>
                                        </div>
                                        
                                        <!-- Image Options -->
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-dark mb-3">Upload Options</label>
                                            <div class="d-flex flex-column gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="make_first_primary" name="make_first_primary" checked>
                                                    <label class="form-check-label small" for="make_first_primary">
                                                        <i class="fas fa-star text-warning me-1"></i>Make first image primary
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="auto_resize" name="auto_resize" checked>
                                                    <label class="form-check-label small" for="auto_resize">
                                                        <i class="fas fa-compress text-info me-1"></i>Auto-resize large images
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="generate_thumbnails" name="generate_thumbnails" checked>
                                                    <label class="form-check-label small" for="generate_thumbnails">
                                                        <i class="fas fa-th text-success me-1"></i>Generate thumbnails
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Upload Progress -->
                                        <div class="mb-4" id="uploadProgress" style="display: none;">
                                            <label class="form-label fw-semibold text-dark">Upload Progress</label>
                                            <div class="progress rounded-pill mt-2">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%" id="progressBar">
                                                    0%
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-4">
                                        
                                        <!-- Guidelines -->
                                        <div class="alert alert-light border rounded-3 small">
                                            <i class="fas fa-lightbulb text-warning me-2"></i>
                                            <strong>Tips:</strong><br>
                                            • Max 5MB per image<br>
                                            • JPG, PNG, GIF, WEBP<br>
                                            • Min 800x600px recommended<br>
                                            • Up to 10 images
                                        </div>
                                        
                                        <!-- Quick Actions -->
                                        <div class="d-grid gap-2 mt-4">
                                            <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="clearSelectedImages()">
                                                <i class="fas fa-times me-1"></i>Clear All
                                            </button>
                                            <button type="button" class="btn btn-outline-info rounded-pill" onclick="document.getElementById('additionalImages').click()">
                                                <i class="fas fa-plus me-1"></i>Add More
                                            </button>
                                            <input type="file" id="additionalImages" name="additional_images[]" 
                                                   multiple accept="image/*" onchange="addMoreImages(this)" style="display: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-3">
                                    <a href="properties.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                                        <i class="fas fa-save me-2"></i>Save Property
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<style>
/* Modern Airbnb-style design */
body {
    background-color: #f8f9fa;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.form-label {
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #4299e1;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    outline: none;
}

.input-group-text {
    background-color: #f7fafc;
    border: 2px solid #e2e8f0;
    border-right: none;
    color: #718096;
}

.input-group .form-control {
    border-left: none;
}

.input-group .form-control:focus {
    border-left: none;
    box-shadow: none;
}

.input-group .input-group-text + .form-control {
    border-left: none;
}

.btn {
    border-radius: 25px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    border: none;
    box-shadow: 0 2px 4px rgba(66, 153, 225, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(66, 153, 225, 0.4);
}

.btn-outline-primary {
    border: 2px solid #4299e1;
    color: #4299e1;
}

.btn-outline-primary:hover {
    background-color: #4299e1;
    border-color: #4299e1;
    transform: translateY(-1px);
}

.btn-outline-secondary {
    border: 2px solid #a0aec0;
    color: #a0aec0;
}

.btn-outline-secondary:hover {
    background-color: #a0aec0;
    border-color: #a0aec0;
}

.upload-zone {
    border: 2px dashed #cbd5e0 !important;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-zone:hover {
    border-color: #4299e1 !important;
    background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
    transform: translateY(-2px);
}

.hover-shadow {
    transition: all 0.2s ease;
}

.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.form-check {
    background: white;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.form-check:hover {
    background: #f7fafc;
}

.alert {
    border-radius: 8px;
    border: none;
}

.alert-light {
    background: #f7fafc;
    color: #4a5568;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
}

.text-primary {
    color: #4299e1 !important;
}

.shadow-sm {
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24) !important;
}

.rounded-3 {
    border-radius: 0.75rem !important;
}

.rounded-pill {
    border-radius: 50rem !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem;
    }
    
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    
    .d-flex.justify-content-end.gap-3 {
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .d-flex.justify-content-end.gap-3 .btn {
        margin-bottom: 0.5rem;
    }
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>

<script>
    // Flag to prevent multiple simultaneous image processing calls
    var isProcessingImages = false;

    $(document).ready(function() {
        // Enhanced form validation
        $('#addPropertyForm').on('submit', function(e) {
            var title = $('#title').val().trim();
            var propertyType = $('#property_type').val();
            var address = $('#address').val().trim();
            var location = $('#location_id').val();
            var monthlyRent = $('#monthly_rent').val();

            // Clear previous alerts
            $('.alert-danger').remove();

            if (!title) {
                e.preventDefault();
                showValidationError('Property title is required', '#title');
                return false;
            }

            if (title.length < 3) {
                e.preventDefault();
                showValidationError('Property title must be at least 3 characters long', '#title');
                return false;
            }

            if (!propertyType) {
                e.preventDefault();
                showValidationError('Property type is required', '#property_type');
                return false;
            }

            if (!address) {
                e.preventDefault();
                showValidationError('Property address is required', '#address');
                return false;
            }

            if (address.length < 5) {
                e.preventDefault();
                showValidationError('Please provide a complete address', '#address');
                return false;
            }

            if (!location) {
                e.preventDefault();
                showValidationError('Location is required', '#location_id');
                return false;
            }

            if (!monthlyRent || monthlyRent <= 0 || isNaN(monthlyRent)) {
                e.preventDefault();
                showValidationError('Monthly rent must be a valid number greater than 0', '#monthly_rent');
                return false;
            }

            // Validate other numeric fields
            var bedrooms = $('#bedrooms').val();
            var bathrooms = $('#bathrooms').val();
            var area = $('#area_sqm').val();
            var deposit = $('#security_deposit').val();

            if (bedrooms !== "" && (isNaN(bedrooms) || bedrooms < 1 || bedrooms > 9 || !Number.isInteger(Number(bedrooms)))) {
                e.preventDefault();
                showValidationError('Bedrooms must be a whole number between 1 and 9', '#bedrooms');
                return false;
            }

            if (bathrooms !== "" && (isNaN(bathrooms) || bathrooms < 0 || bathrooms > 9 || !Number.isInteger(Number(bathrooms)))) {
                e.preventDefault();
                showValidationError('Bathrooms must be a whole number between 0 and 9', '#bathrooms');
                return false;
            }

            if (area !== "" && (isNaN(area) || area < 0)) {
                e.preventDefault();
                showValidationError('Area must be a valid non-negative number', '#area_sqm');
                return false;
            }

            if (deposit !== "" && (isNaN(deposit) || deposit < 0)) {
                e.preventDefault();
                showValidationError('Security deposit must be a valid non-negative number', '#security_deposit');
                return false;
            }

            // Show loading state
            $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
        });

        // Character counter for description
    $('#description').on('input', function() {
        var charCount = $(this).val().length;
        var maxLength = 1000;
        
        if (charCount > maxLength) {
            $(this).val($(this).val().substring(0, maxLength));
            charCount = maxLength;
        }
        
        // Update character counter if it exists
        if ($('#charCount').length === 0) {
            $(this).after('<small class="text-muted" id="charCount">' + charCount + '/' + maxLength + ' characters</small>');
        } else {
            $('#charCount').text(charCount + '/' + maxLength + ' characters');
        }
    });

    // Currency input restriction (only numbers and dot)
    $('.currency-input').on('keydown', function(e) {
        // Allow: backspace, delete, tab, escape, enter, . (decimal point)
        // Key codes: 46 (Delete), 8 (Backspace), 9 (Tab), 27 (Escape), 13 (Enter), 110 (Decimal point on numpad), 190 (Period)
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
             // Allow: Ctrl+A, Command+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
            (e.keyCode === 67 && (e.ctrlKey === true || e.metaKey === true)) || 
            (e.keyCode === 86 && (e.ctrlKey === true || e.metaKey === true)) || 
            (e.keyCode === 88 && (e.ctrlKey === true || e.metaKey === true)) || 
             // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
                 
                 // If the user typed a dot, check if one already exists
                 if (e.keyCode === 190 || e.keyCode === 110) {
                     if ($(this).val().indexOf('.') !== -1) {
                         e.preventDefault();
                     }
                 }
                 return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    // Extra safety: clean non-numeric characters on blur or input
    $('.currency-input').on('input', function() {
        var val = $(this).val();
        if (/[^0-9.]/g.test(val)) {
            $(this).val(val.replace(/[^0-9.]/g, ''));
        }
        // Ensure only one dot
        var parts = $(this).val().split('.');
        if (parts.length > 2) {
            $(this).val(parts[0] + '.' + parts.slice(1).join(''));
        }
    });

    // Integer input restriction (only numbers, no dots)
    $('.integer-input').on('keydown', function(e) {
        // Allow: backspace, delete, tab, escape, enter
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13]) !== -1 ||
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

    $('.integer-input').on('input', function() {
        var val = $(this).val();
        // Remove anything that isn't a digit
        if (/[^0-9]/g.test(val)) {
            $(this).val(val.replace(/[^0-9]/g, ''));
        }
        
        // Special case for bedrooms: block "0"
        if ($(this).attr('id') === 'bedrooms' && $(this).val() === '0') {
            $(this).val('1');
            alert('Property must have at least 1 bedroom.');
        }
    });

    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Preview images before upload with drag and drop
    $('#property_images').on('change', function(e) {
        handleImageSelection(e.target.files);
    });

    // Handle additional images
    $('#additionalImages').on('change', function(e) {
        var existingFiles = $('#property_images')[0].files;
        var newFiles = e.target.files;
        var combinedFiles = new DataTransfer();
        
        // Add existing files
        for (var i = 0; i < existingFiles.length; i++) {
            combinedFiles.items.add(existingFiles[i]);
        }
        
        // Add new files (check limit)
        if (existingFiles.length + newFiles.length > 10) {
            alert('Maximum 10 images allowed per property. You can add ' + (10 - existingFiles.length) + ' more images.');
            return;
        }
        
        for (var i = 0; i < newFiles.length; i++) {
            combinedFiles.items.add(newFiles[i]);
        }
        
        // Update the main file input
        $('#property_images')[0].files = combinedFiles.files;
        handleImageSelection(combinedFiles.files);
    });

    // Drag and drop functionality
    $('.upload-zone').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('border-primary').css('background', 'linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%)');
    });

    $('.upload-zone').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('border-primary').css('background', 'linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%)');
    });

    $('.upload-zone').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('border-primary').css('background', 'linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%)');
        
        var files = e.originalEvent.dataTransfer.files;
        var existingFiles = $('#property_images')[0].files;
        
        if (existingFiles.length + files.length > 10) {
            alert('Maximum 10 images allowed per property.');
            return;
        }
        
        var combinedFiles = new DataTransfer();
        
        // Add existing files
        for (var i = 0; i < existingFiles.length; i++) {
            combinedFiles.items.add(existingFiles[i]);
        }
        
        // Add new files
        for (var i = 0; i < files.length; i++) {
            if (files[i].type.startsWith('image/')) {
                combinedFiles.items.add(files[i]);
            }
        }
        
        $('#property_images')[0].files = combinedFiles.files;
        handleImageSelection(combinedFiles.files);
    });
});

// Drag and drop functions for inline handlers
function handleDragOver(event) {
    event.preventDefault();
    event.currentTarget.classList.add('border-primary');
    event.currentTarget.style.background = 'linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%)';
}

function handleDragLeave(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-primary');
    event.currentTarget.style.background = 'linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%)';
}

function handleDrop(event) {
    event.preventDefault();
    event.currentTarget.classList.remove('border-primary');
    event.currentTarget.style.background = 'linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%)';
    
    var files = event.dataTransfer.files;
    var existingFiles = document.getElementById('property_images').files;
    
    if (existingFiles.length + files.length > 10) {
        alert('Maximum 10 images allowed per property.');
        return;
    }
    
    var combinedFiles = new DataTransfer();
    
    // Add existing files
    for (var i = 0; i < existingFiles.length; i++) {
        combinedFiles.items.add(existingFiles[i]);
    }
    
    // Add new files
    for (var i = 0; i < files.length; i++) {
        if (files[i].type.startsWith('image/')) {
            combinedFiles.items.add(files[i]);
        }
    }
    
    document.getElementById('property_images').files = combinedFiles.files;
    handleImageSelection(combinedFiles.files);
}

// Handle image selection and preview
function handleImageSelection(files) {
    // Prevent multiple simultaneous calls
    if (isProcessingImages) {
        console.log('Already processing images, skipping...');
        return;
    }
    
    isProcessingImages = true;
    
    var preview = $('#currentImages');
    var fileText = $('#fileText');
    
    console.log('Processing images:', files.length, 'files');
    
    if (files && files.length > 0) {
        preview.empty();
        
        // Show progress
        $('#uploadProgress').show();
        updateProgress(0);
        
        var loadedCount = 0;
        var validFiles = 0;
        
        // First, count valid files
        for (var i = 0; i < files.length; i++) {
            if (files[i].type.startsWith('image/')) {
                validFiles++;
            }
        }
        
        console.log('Valid files:', validFiles);
        
        if (validFiles === 0) {
            preview.html('<i class="fas fa-image text-muted" style="font-size: 3rem;"></i><p class="text-muted mt-2">No valid images selected.</p>').removeClass('row');
            fileText.text('No valid files selected.');
            $('#uploadProgress').hide();
            isProcessingImages = false;
            return;
        }
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            
            // Validate file
            if (!file.type.startsWith('image/')) {
                continue;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert('File ' + file.name + ' is too large. Maximum size is 5MB.');
                continue;
            }
            
            var reader = new FileReader();
            
            reader.onload = (function(file, index) {
                return function(e) {
                    console.log('Loading image:', file.name, 'index:', index);
                    var imageHtml = '<div class="col-md-6 mb-2 position-relative">' +
                                   '<img src="' + e.target.result + '" class="img-fluid img-thumbnail" style="max-height: 100px; object-fit: cover;" alt="' + file.name + '">' +
                                   '<button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" onclick="removeImage(' + index + ')" title="Remove image">' +
                                   '<i class="fas fa-times"></i>' +
                                   '</button>' +
                                   '<small class="d-block text-muted mt-1">' + file.name + '</small>' +
                                   '</div>';
                    preview.append(imageHtml);
                    
                    loadedCount++;
                    updateProgress((loadedCount / validFiles) * 100);
                    
                    if (loadedCount === validFiles) {
                        setTimeout(function() {
                            $('#uploadProgress').hide();
                            isProcessingImages = false; // Reset flag when done
                            console.log('Image processing complete');
                        }, 1000);
                    }
                };
            })(file, i);
            
            reader.readAsDataURL(file);
        }
        
        if (validFiles > 5) {
            preview.append('<div class="col-12 text-center text-muted"><small>+' + (validFiles - 5) + ' more images</small></div>');
        }
        
        preview.addClass('row');
        
        // Update file text
        if (files.length === 1) {
            fileText.text('1 file selected: ' + files[0].name);
        } else {
            fileText.text(files.length + ' files selected');
        }
        
        // Also call updateFileText for consistency
        updateFileText(document.getElementById('property_images'));
    } else {
        preview.html('<i class="fas fa-image text-muted" style="font-size: 3rem;"></i><p class="text-muted mt-2">No images yet.</p>').removeClass('row');
        fileText.text('No files selected.');
        $('#uploadProgress').hide();
        isProcessingImages = false;
    }
}

// Update progress bar
function updateProgress(percent) {
    $('#progressBar').css('width', percent + '%').text(Math.round(percent) + '%');
}

// Remove specific image
function removeImage(index) {
    var files = $('#property_images')[0].files;
    var newFiles = new DataTransfer();
    
    for (var i = 0; i < files.length; i++) {
        if (i !== index) {
            newFiles.items.add(files[i]);
        }
    }
    
    $('#property_images')[0].files = newFiles.files;
    isProcessingImages = false; // Reset flag before calling handleImageSelection
    handleImageSelection(newFiles.files);
}

// Clear all selected images
function clearSelectedImages() {
    $('#property_images')[0].files = new DataTransfer().files;
    $('#currentImages').html('<i class="fas fa-image text-muted" style="font-size: 3rem;"></i><p class="text-muted mt-2">No images yet.</p>').removeClass('row');
    $('#fileText').text('No files selected.');
    $('#uploadProgress').hide();
    isProcessingImages = false; // Reset flag
}

// Add more images (separate function for clarity)
function addMoreImages(input) {
    var files = input.files;
    var existingFiles = $('#property_images')[0].files;
    
    if (existingFiles.length + files.length > 10) {
        alert('Maximum 10 images allowed per property. You can add ' + (10 - existingFiles.length) + ' more images.');
        return;
    }
    
    var combinedFiles = new DataTransfer();
    
    // Add existing files
    for (var i = 0; i < existingFiles.length; i++) {
        combinedFiles.items.add(existingFiles[i]);
    }
    
    // Add new files
    for (var i = 0; i < files.length; i++) {
        combinedFiles.items.add(files[i]);
    }
    
    $('#property_images')[0].files = combinedFiles.files;
    isProcessingImages = false; // Reset flag before calling handleImageSelection
    handleImageSelection(combinedFiles.files);
    
    // Clear the additional images input
    input.value = '';
}

// Show validation error
function showValidationError(message, fieldSelector) {
    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                   '<i class="fas fa-exclamation-triangle me-2"></i>' + message +
                   '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                   '</div>';
    
    $('.card-body').prepend(alertHtml);
    $(fieldSelector).focus().addClass('is-invalid');
    
    // Remove invalid class on input
    $(fieldSelector).on('input', function() {
        $(this).removeClass('is-invalid');
    });
}

// Update file selection text
function updateFileText(input) {
    const fileText = document.getElementById('fileText');
    if (input && input.files && input.files.length > 0) {
        const fileCount = input.files.length;
        const fileNames = Array.from(input.files).map(file => file.name).join(', ');
        if (fileCount === 1) {
            fileText.textContent = fileNames;
        } else {
            fileText.textContent = fileCount + ' files selected';
        }
    } else {
        fileText.textContent = 'No files selected.';
    }
}

// Map integration: geocode address and update hidden latitude/longitude
var addPropertyMap = null;
var addPropertyMarker = null;
var addPropertyGeocoder = null;
var addPropertyAutocomplete = null;

function showMapError(mapId, message) {
    var container = document.getElementById(mapId);
    if (!container) return;
    container.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 border border-danger rounded-3 bg-light text-danger p-3 text-center"><div><i class="fas fa-exclamation-circle fa-2x mb-2"></i><div>' + message + '</div></div></div>';
}

function setLeafletFallbackMarker(lat, lng) {
    if (!addPropertyMap || !window.L) return;

    var position = [parseFloat(lat), parseFloat(lng)];

    if (!addPropertyMarker) {
        addPropertyMarker = L.marker(position, { draggable: true }).addTo(addPropertyMap);
        addPropertyMarker.on('dragend', function(e) {
            var pos = e.target.getLatLng();
            setLeafletFallbackMarker(pos.lat, pos.lng);
        });
    } else {
        addPropertyMarker.setLatLng(position);
    }

    addPropertyMap.panTo(position);
    document.getElementById('latitude').value = position[0].toFixed(8);
    document.getElementById('longitude').value = position[1].toFixed(8);
}

function initLeafletFallbackMap() {
    var mapContainer = document.getElementById('addPropertyMap');
    if (!mapContainer || !window.L) {
        showMapError('addPropertyMap', 'Unable to initialize fallback map.');
        return;
    }

    mapContainer.innerHTML = '';
    var lat = parseFloat(document.getElementById('latitude').value) || 14.1211;
    var lng = parseFloat(document.getElementById('longitude').value) || 38.7241;
    var zoom = document.getElementById('latitude').value && document.getElementById('longitude').value ? 15 : 8;

    addPropertyMap = L.map('addPropertyMap').setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(addPropertyMap);

    addPropertyMap.on('click', function(e) {
        setLeafletFallbackMarker(e.latlng.lat, e.latlng.lng);
    });

    setLeafletFallbackMarker(lat, lng);
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
        showMapError('addPropertyMap', 'Fallback map failed to load.');
    };
    document.head.appendChild(script);
}

window.handleGoogleMapsLoadError = function() {
    loadLeafletFallback();
};

function gm_authFailure() {
    loadLeafletFallback();
}

function initializeAddPropertyMap() {
    if (!document.getElementById('addPropertyMap')) return;
    if (!window.google || !google.maps || !window.addPropertyGoogleMapsKey) {
        loadLeafletFallback();
        return;
    }

    var lat = parseFloat(document.getElementById('latitude').value) || null;
    var lng = parseFloat(document.getElementById('longitude').value) || null;
    var center = { lat: lat || 14.1211, lng: lng || 38.7241 };

    try {
        addPropertyMap = new google.maps.Map(document.getElementById('addPropertyMap'), {
            center: center,
            zoom: lat && lng ? 15 : 8,
            mapTypeControl: false
        });

        addPropertyGeocoder = new google.maps.Geocoder();
        addPropertyAutocomplete = new google.maps.places.Autocomplete(document.getElementById('address'), {
            componentRestrictions: { country: 'et' },
            fields: ['formatted_address', 'geometry']
        });

        addPropertyAutocomplete.addListener('place_changed', function() {
            var place = addPropertyAutocomplete.getPlace();
            if (!place.geometry) {
                alert('Unable to locate the selected address. Please choose a more specific location.');
                return;
            }
            setMapMarker(place.geometry.location.lat(), place.geometry.location.lng(), place.formatted_address || document.getElementById('address').value);
        });

        addPropertyMap.addListener('click', function(e) {
            setMapMarker(e.latLng.lat(), e.latLng.lng());
        });

        if (lat && lng) {
            setMapMarker(lat, lng);
        }

        setTimeout(function() {
            var container = document.getElementById('addPropertyMap');
            if (container && (container.querySelector('.gm-err-container') || /Oops! Something went wrong/i.test(container.textContent || ''))) {
                loadLeafletFallback();
            }
        }, 1500);
    } catch (e) {
        console.error('Google Maps initialization failed:', e);
        loadLeafletFallback();
    }
}

function setMapMarker(lat, lng, address) {
    if (!document.getElementById('addPropertyMap')) return;

    var position = { lat: parseFloat(lat), lng: parseFloat(lng) };

    if (!addPropertyMarker) {
        addPropertyMarker = new google.maps.Marker({
            position: position,
            map: addPropertyMap,
            draggable: true
        });
        addPropertyMarker.addListener('dragend', function() {
            var pos = addPropertyMarker.getPosition();
            setMapMarker(pos.lat(), pos.lng());
        });
    } else {
        addPropertyMarker.setPosition(position);
    }

    addPropertyMap.panTo(position);
    addPropertyMap.setZoom(15);

    document.getElementById('latitude').value = position.lat.toFixed(8);
    document.getElementById('longitude').value = position.lng.toFixed(8);
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
    if (window.google && google.maps && addPropertyGeocoder) {
        addPropertyGeocoder.geocode({ address: address }, function(results, status) {
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
                
                if (window.google && google.maps && addPropertyMap instanceof google.maps.Map) {
                    setMapMarker(lat, lng, display_name);
                } else if (window.L && addPropertyMap instanceof L.Map) {
                    setLeafletFallbackMarker(lat, lng);
                    document.getElementById('address').value = display_name;
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

document.addEventListener('DOMContentLoaded', function() {
    // Add listeners for manual coordinate entry
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    function updateMapFromInputs() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            if (window.google && google.maps && addPropertyMap instanceof google.maps.Map) {
                setMapMarker(lat, lng);
            } else if (window.L && addPropertyMap instanceof L.Map) {
                setLeafletFallbackMarker(lat, lng);
            }
        }
    }

    if (latInput) latInput.addEventListener('change', updateMapFromInputs);
    if (lngInput) lngInput.addEventListener('change', updateMapFromInputs);
    if (window.addPropertyGoogleMapsKey) {
        if (window.google && google.maps) {
            initializeAddPropertyMap();
        } else {
            window.loadAddPropertyGoogleMaps();
            setTimeout(function() {
                if (!window.google || !google.maps) {
                    loadLeafletFallback();
                }
            }, 3000);
        }
    } else {
        loadLeafletFallback();
    }

    document.getElementById('geocodeAddressBtn').addEventListener('click', function(e) {
        e.preventDefault();
        geocodeAddress(document.getElementById('address').value);
    });

    // Property Rules Management
    let ruleCount = 0;

    function addRule(title = '', description = '') {
        const ruleId = 'rule_' + ruleCount;
        const ruleHtml = `
            <div class="rule-item border rounded p-3 mb-3" id="${ruleId}">
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">Rule Title</label>
                        <input type="text" class="form-control rule-title" name="rule_title[]" 
                               value="${title}" placeholder="e.g., No smoking, Quiet hours" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description (Optional)</label>
                        <input type="text" class="form-control rule-description" name="rule_description[]" 
                               value="${description}" placeholder="Additional details about this rule">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-rule" 
                                data-rule-id="${ruleId}" title="Remove this rule">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('rulesContainer').insertAdjacentHTML('beforeend', ruleHtml);
        ruleCount++;
        
        // Add event listener to the remove button
        document.querySelector(`#${ruleId} .remove-rule`).addEventListener('click', function() {
            removeRule(ruleId);
        });
    }

    function removeRule(ruleId) {
        const ruleElement = document.getElementById(ruleId);
        if (ruleElement) {
            ruleElement.remove();
        }
    }

    // Add Rule button event listener
    document.getElementById('addRuleBtn').addEventListener('click', function() {
        addRule();
    });

    // Initialize with one empty rule
    addRule();
});
</script>

<?php
// Image processing functions
function resizeImage($source_path, $destination_path, $filename, $extension) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        error_log('GD library is not available for image resizing');
        // Just move the file without resizing
        return move_uploaded_file($source_path, $destination_path) ? $destination_path : false;
    }
    
    $max_width = 1200;
    $max_height = 1200;
    
    // Get image dimensions
    $image_info = getimagesize($source_path);
    if (!$image_info) {
        error_log('Unable to get image dimensions for: ' . $source_path);
        return move_uploaded_file($source_path, $destination_path) ? $destination_path : false;
    }
    
    list($width, $height) = $image_info;
    
    // Check if resizing is needed
    if ($width <= $max_width && $height <= $max_height) {
        // No resizing needed, just move the file
        return move_uploaded_file($source_path, $destination_path) ? $destination_path : false;
    }
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    if (!$new_image) {
        error_log('Unable to create new image resource');
        return move_uploaded_file($source_path, $destination_path) ? $destination_path : false;
    }
    
    // Load original image based on extension
    $source = null;
    switch (strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($source_path);
            break;
        case 'png':
            $source = imagecreatefrompng($source_path);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case 'gif':
            $source = imagecreatefromgif($source_path);
            break;
        case 'webp':
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            error_log('Unsupported image format: ' . $extension);
            return move_uploaded_file($source_path, $destination_path) ? $destination_path : false;
    }
    
    if (!$source) {
        error_log('Unable to load source image: ' . $source_path);
        imagedestroy($new_image);
        return move_uploaded_file($source_path, $destination_path) ? $destination_path : false;
    }
    
    // Resize and save
    $result = imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    if (!$result) {
        error_log('Unable to resize image: ' . $source_path);
        imagedestroy($source);
        imagedestroy($new_image);
        return move_uploaded_file($source_path, $destination_path) ? $destination_path : false;
    }
    
    // Save resized image
    $saved = false;
    switch (strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            $saved = imagejpeg($new_image, $destination_path, 85);
            break;
        case 'png':
            $saved = imagepng($new_image, $destination_path, 8);
            break;
        case 'gif':
            $saved = imagegif($new_image, $destination_path);
            break;
        case 'webp':
            $saved = imagewebp($new_image, $destination_path, 85);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($new_image);
    
    return $saved ? $destination_path : false;
}

function createThumbnail($source_path, $thumbnail_path, $extension) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        error_log('GD library is not available for thumbnail creation');
        return false;
    }
    
    $thumb_width = 300;
    $thumb_height = 200;
    
    // Get image dimensions
    $image_info = getimagesize($source_path);
    if (!$image_info) {
        error_log('Unable to get image dimensions for thumbnail: ' . $source_path);
        return false;
    }
    
    list($width, $height) = $image_info;
    
    // Calculate thumbnail dimensions (maintain aspect ratio)
    $ratio = min($thumb_width / $width, $thumb_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($new_width, $new_height);
    if (!$thumbnail) {
        error_log('Unable to create thumbnail resource');
        return false;
    }
    
    // Load original image
    $source = null;
    switch (strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($source_path);
            break;
        case 'png':
            $source = imagecreatefrompng($source_path);
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            break;
        case 'gif':
            $source = imagecreatefromgif($source_path);
            break;
        case 'webp':
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            error_log('Unsupported image format for thumbnail: ' . $extension);
            return false;
    }
    
    if (!$source) {
        error_log('Unable to load source image for thumbnail: ' . $source_path);
        imagedestroy($thumbnail);
        return false;
    }
    
    // Create thumbnail
    $result = imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    if (!$result) {
        error_log('Unable to create thumbnail: ' . $source_path);
        imagedestroy($source);
        imagedestroy($thumbnail);
        return false;
    }
    
    // Save thumbnail
    $saved = false;
    switch (strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            $saved = imagejpeg($thumbnail, $thumbnail_path, 75);
            break;
        case 'png':
            $saved = imagepng($thumbnail, $thumbnail_path, 7);
            break;
        case 'gif':
            $saved = imagegif($thumbnail, $thumbnail_path);
            break;
        case 'webp':
            $saved = imagewebp($thumbnail, $thumbnail_path, 75);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $saved;
}
?>
