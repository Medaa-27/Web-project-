<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');

$db = new Database();
$property_id = intval($_GET['id'] ?? 0);

if ($property_id <= 0) {
    header('Location: properties.php');
    exit;
}

// Get property details
$sql = "SELECT p.*, l.location_name, l.subcity, u.full_name as owner_name
        FROM properties p 
        LEFT JOIN locations l ON p.location_id = l.location_id 
        LEFT JOIN users u ON p.owner_id = u.user_id 
        WHERE p.property_id = ?";
$property = $db->getSingle($db->prepare($sql), [$property_id]);

if (!$property) {
    header('Location: properties.php');
    exit;
}

// Get locations for dropdown
$locations = $db->getMultiple($db->prepare("SELECT * FROM locations ORDER BY location_name"), []);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $location_id = intval($_POST['location_id'] ?? 0);
    $monthly_rent = floatval($_POST['monthly_rent'] ?? 0);
    $security_deposit = floatval($_POST['security_deposit'] ?? 0);
    $bedrooms = intval($_POST['bedrooms'] ?? 0);
    $bathrooms = intval($_POST['bathrooms'] ?? 0);
    $property_type = $_POST['property_type'] ?? '';
    $is_furnished = isset($_POST['is_furnished']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');

    // Validate inputs
    $errors = [];
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if ($location_id <= 0) $errors[] = "Location is required";
    if ($monthly_rent <= 0) $errors[] = "Monthly rent must be greater than 0";
    if ($bedrooms <= 0) $errors[] = "Bedrooms must be greater than 0";
    if ($bathrooms <= 0) $errors[] = "Bathrooms must be greater than 0";
    if (empty($property_type)) $errors[] = "Property type is required";

    if (empty($errors)) {
        try {
            // Update property
            $update_sql = "UPDATE properties SET title = ?, description = ?, address = ?, location_id = ?, 
                           monthly_rent = ?, security_deposit = ?, bedrooms = ?, bathrooms = ?, 
                           property_type = ?, is_furnished = ?, updated_at = NOW() 
                           WHERE property_id = ?";
            $stmt = $db->prepare($update_sql);
            $result = $db->execute($stmt, [
                $title, $description, $address, $location_id, $monthly_rent, $security_deposit,
                $bedrooms, $bathrooms, $property_type, $is_furnished, $property_id
            ]);

            if ($result) {
                // Log the edit
                $log_sql = "INSERT INTO property_activity_log (property_id, employee_id, action, old_value, new_value, notes, created_at) 
                            VALUES (?, ?, 'property_edit', ?, ?, ?, NOW())";
                $log_stmt = $db->prepare($log_sql);
                $old_data = json_encode([
                    'title' => $property['title'],
                    'description' => $property['description'],
                    'monthly_rent' => $property['monthly_rent'],
                    'bedrooms' => $property['bedrooms'],
                    'bathrooms' => $property['bathrooms'],
                    'property_type' => $property['property_type']
                ]);
                $new_data = json_encode([
                    'title' => $title,
                    'description' => $description,
                    'monthly_rent' => $monthly_rent,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms,
                    'property_type' => $property_type
                ]);
                $db->execute($log_stmt, [$property_id, $_SESSION['user_id'], $old_data, $new_data, $notes]);

                // Send notification to owner
                $notification_sql = "INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                                    VALUES (?, 'info', 'Property Updated', 
                                    'Your property \"{$title}\" has been updated by an employee. 
                                    Notes: " . htmlspecialchars($notes) . "', 
                                    '../owner/properties.php', 0, NOW())";
                $notification_stmt = $db->prepare($notification_sql);
                $db->execute($notification_stmt, [$property['owner_id']]);

                header('Location: properties.php?success=Property updated successfully');
                exit;
            } else {
                $errors[] = "Failed to update property";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Aksum Rental System</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .page-header {
            background: linear-gradient(135deg, #708090 0%, #4a5568 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <?php include '../includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2">
                                <i class="fas fa-edit me-2"></i>Edit Property
                            </h1>
                            <p class="mb-0">Update property information for: <?php echo htmlspecialchars($property['title']); ?></p>
                        </div>
                        <a href="properties.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back to Properties
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="card form-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Property Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Property Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($property['title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_type" class="form-label">Property Type *</label>
                                        <select class="form-select" id="property_type" name="property_type" required>
                                            <option value="">Select Type</option>
                                            <option value="apartment" <?php echo $property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                            <option value="house" <?php echo $property['property_type'] === 'house' ? 'selected' : ''; ?>>House</option>
                                            <option value="condo" <?php echo $property['property_type'] === 'condo' ? 'selected' : ''; ?>>Condo</option>
                                            <option value="studio" <?php echo $property['property_type'] === 'studio' ? 'selected' : ''; ?>>Studio</option>
                                            <option value="villa" <?php echo $property['property_type'] === 'villa' ? 'selected' : ''; ?>>Villa</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($property['address'] ?? ''); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="location_id" class="form-label">Location *</label>
                                        <select class="form-select" id="location_id" name="location_id" required>
                                            <option value="">Select Location</option>
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?php echo $location['location_id']; ?>" 
                                                        <?php echo $property['location_id'] == $location['location_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($location['location_name'] . ', ' . $location['subcity']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="monthly_rent" class="form-label">Monthly Rent (ETB) *</label>
                                        <input type="number" class="form-control" id="monthly_rent" name="monthly_rent" 
                                               value="<?php echo $property['monthly_rent']; ?>" min="0" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="security_deposit" class="form-label">Security Deposit (ETB)</label>
                                        <input type="number" class="form-control" id="security_deposit" name="security_deposit" 
                                               value="<?php echo $property['security_deposit']; ?>" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="bedrooms" class="form-label">Bedrooms *</label>
                                        <input type="number" class="form-control" id="bedrooms" name="bedrooms" 
                                               value="<?php echo $property['bedrooms']; ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="bathrooms" class="form-label">Bathrooms *</label>
                                        <input type="number" class="form-control" id="bathrooms" name="bathrooms" 
                                               value="<?php echo $property['bathrooms']; ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="is_furnished" name="is_furnished" 
                                                   <?php echo $property['is_furnished'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_furnished">
                                                Furnished
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Edit Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" 
                                          placeholder="Add notes about this edit..."></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="properties.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Property
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Property Info Card -->
                <div class="card form-card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Current Property Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Owner:</strong> <?php echo htmlspecialchars($property['owner_name']); ?></p>
                                <p><strong>Current Status:</strong> <span class="badge bg-<?php echo $property['status'] === 'available' ? 'success' : 'warning'; ?>"><?php echo ucfirst(str_replace('_', ' ', $property['status'])); ?></span></p>
                                <p><strong>Review Status:</strong> <span class="badge bg-<?php echo $property['review_status'] === 'approved' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($property['review_status']); ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($property['location_name'] . ', ' . $property['subcity']); ?></p>
                                <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($property['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M j, Y', strtotime($property['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
