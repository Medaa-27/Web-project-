<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Maintenance Request - Direct Form";

$user_id = $session->getUserId();
$success_message = '';
$error_message = '';

// Get active rental agreements for the dropdown
$active_agreements = [];
try {
    $sql = "SELECT ra.agreement_id, p.title as property_title, l.location_name
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.property_id
            LEFT JOIN locations l ON p.location_id = l.location_id
            WHERE ra.tenant_id = ? AND ra.status = 'active'
            ORDER BY p.title";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $agreements = $db->getMultiple($stmt, [$user_id]);
        foreach ($agreements as $agreement) {
            $agreement['location_name'] = $agreement['location_name'] ?? 'Addis Ababa';
            $active_agreements[] = $agreement;
        }
    }
} catch (Exception $e) {
    $active_agreements = [];
    error_log("Active agreements error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $agreement_id = $_POST['agreement_id'] ?? '';
        $issue_type = $_POST['issue_type'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $description = $_POST['description'] ?? '';
        $access_instructions = $_POST['access_instructions'] ?? '';
        
        if (empty($agreement_id) || empty($issue_type) || empty($priority) || empty($description)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            // Get property_id and owner_id from agreement_id
            $sql = "SELECT ra.property_id, p.owner_id FROM rental_agreements ra 
                    JOIN properties p ON ra.property_id = p.property_id 
                    WHERE ra.agreement_id = ? AND ra.tenant_id = ? AND ra.status = 'active'";
            $stmt = $db->prepare($sql);
            $agreement = $db->getSingle($stmt, [$agreement_id, $user_id]);

            if (!$agreement) {
                throw new Exception('Invalid rental agreement selected.');
            }

            $property_id = $agreement['property_id'];
            $owner_id = $agreement['owner_id'];
            
            // Check if maintenance_requests table exists and get its structure
            try {
                $table_check = $db->getConnection()->query("DESCRIBE maintenance_requests");
                if (!$table_check) {
                    throw new Exception('maintenance_requests table does not exist');
                }
            } catch (Exception $e) {
                throw new Exception('Maintenance requests table not found. Please contact administrator.');
            }
            
            // Insert maintenance request with basic columns first
            $sql = "INSERT INTO maintenance_requests (property_id, tenant_id, owner_id, issue_type, notes, priority, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            // Prepare the full description first
            $full_description = $description;
            if (!empty($access_instructions)) {
                $full_description .= "\n\nAccess Instructions: " . $access_instructions;
            }
            
            error_log("Maintenance SQL: " . $sql);
            error_log("Parameters: property_id=$property_id, tenant_id=$user_id, owner_id=$owner_id, issue_type=$issue_type, priority=$priority");
            
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                $error_info = $db->getConnection()->errorInfo();
                error_log("SQL Prepare Error: " . print_r($error_info, true));
                
                // Try alternative query with fewer columns
                $sql_alt = "INSERT INTO maintenance_requests (property_id, tenant_id, issue_type, notes, priority, status) 
                           VALUES (?, ?, ?, ?, ?, 'pending')";
                error_log("Trying alternative SQL: " . $sql_alt);
                $stmt = $db->prepare($sql_alt);
                if (!$stmt) {
                    $error_info_alt = $db->getConnection()->errorInfo();
                    error_log("Alternative SQL Prepare Error: " . print_r($error_info_alt, true));
                    throw new Exception('Failed to prepare maintenance request query. Original error: ' . $error_info[2] . '. Alternative error: ' . $error_info_alt[2]);
                }
                $result = $db->execute($stmt, [$property_id, $user_id, $issue_type, $full_description, $priority]);
            } else {
                $result = $db->execute($stmt, [$property_id, $user_id, $owner_id, $issue_type, $full_description, $priority]);
            }
            
            if (!$result) {
                $error_info = $stmt->errorInfo();
                error_log("SQL Execute Error: " . print_r($error_info, true));
                throw new Exception('Failed to insert maintenance request. Error: ' . $error_info[2]);
            }
            
            // Send notification to property owner
            if ($owner_id) {
                $sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                        VALUES (?, 'New Maintenance Request', 
                        'A tenant has submitted a new maintenance request. Please check your maintenance panel.', 
                        'alert', NOW())";
                $stmt = $db->prepare($sql);
                $db->execute($stmt, [$owner_id]);
            }
            
            $success_message = 'Maintenance request submitted successfully!';
        }
    } catch (Exception $e) {
        $error_message = 'Error submitting maintenance request: ' . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Maintenance Request</h3>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <h4>Submit Maintenance Request</h4>
                    <p class="text-muted">Please fill out the form below to request maintenance for your property.</p>
                    
                    <form method="POST" action="maintenance-direct.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="agreement_id" class="form-label">Select Property *</label>
                                    <select class="form-select" id="agreement_id" name="agreement_id" required>
                                        <option value="">Choose your rental property...</option>
                                        <?php if (!empty($active_agreements)): ?>
                                            <?php foreach ($active_agreements as $agreement): ?>
                                                <option value="<?php echo $agreement['agreement_id']; ?>">
                                                    <?php echo htmlspecialchars($agreement['property_title']); ?> - <?php echo htmlspecialchars($agreement['location_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <?php if (empty($active_agreements)): ?>
                                        <div class="alert alert-warning mt-2 small py-2">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            You don't have any active rental agreements. You can only request maintenance for properties you are currently renting.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="issue_type" class="form-label">Issue Type *</label>
                                    <select class="form-select" id="issue_type" name="issue_type" required>
                                        <option value="">Select issue type</option>
                                        <option value="plumbing">Plumbing Issues</option>
                                        <option value="electrical">Electrical Problems</option>
                                        <option value="hvac">HVAC/Air Conditioning</option>
                                        <option value="appliance">Appliance Repair</option>
                                        <option value="structural">Structural Issues</option>
                                        <option value="pest_control">Pest Control</option>
                                        <option value="cleaning">Cleaning Services</option>
                                        <option value="other">Other Issues</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority Level *</label>
                                    <select class="form-select" id="priority" name="priority" required>
                                        <option value="">Select priority</option>
                                        <option value="low">Low - Can wait a few days</option>
                                        <option value="medium">Medium - Needs attention soon</option>
                                        <option value="high">High - Urgent attention needed</option>
                                        <option value="urgent">Urgent - Emergency situation</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required 
                                      placeholder="Please describe the issue in detail. Include when it started, how often it occurs, and any specific details that would help the maintenance team."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="access_instructions" class="form-label">Access Instructions</label>
                            <textarea class="form-control" id="access_instructions" name="access_instructions" rows="3" 
                                      placeholder="Any special instructions for accessing the property (e.g., 'Call before arriving', 'Dog on premises', 'Use back entrance', etc.)"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Submit Request
                            </button>
                            <button type="reset" class="btn btn-secondary btn-lg">
                                <i class="fas fa-redo me-2"></i>Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
