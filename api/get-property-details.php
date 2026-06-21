<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employee') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$db = new Database();
$property_id = intval($_POST['property_id'] ?? 0);

if ($property_id <= 0) {
    exit(json_encode(['success' => false, 'message' => 'Invalid property ID']));
}

try {
    // Get comprehensive property details
    $sql = "SELECT p.*, l.location_name, l.subcity, u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email,
               e.full_name as reviewed_by_name,
               GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.is_primary DESC, pi.image_id) as images
        FROM properties p 
        LEFT JOIN locations l ON p.location_id = l.location_id 
        LEFT JOIN users u ON p.owner_id = u.user_id 
        LEFT JOIN users e ON p.reviewed_by = e.user_id
        LEFT JOIN property_images pi ON p.property_id = pi.property_id
        WHERE p.property_id = ?
        GROUP BY p.property_id";

    $stmt = $db->prepare($sql);
    $property = $db->getSingle($stmt, [$property_id]);

    if (!$property) {
        exit(json_encode(['success' => false, 'message' => 'Property not found']));
    }

    // Get rental requests
    $requests_sql = "SELECT rr.*, u.full_name as tenant_name, u.phone as tenant_phone, u.email as tenant_email
                     FROM rental_requests rr
                     LEFT JOIN users u ON rr.tenant_id = u.user_id
                     WHERE rr.property_id = ?
                     ORDER BY rr.created_at DESC";
    $requests = $db->getMultiple($db->prepare($requests_sql), [$property_id]);

    // Get rental agreements
    $agreements_sql = "SELECT ra.*, u.full_name as tenant_name, u.phone as tenant_phone, u.email as tenant_email
                       FROM rental_agreements ra
                       LEFT JOIN users u ON ra.tenant_id = u.user_id
                       WHERE ra.property_id = ?
                       ORDER BY ra.created_at DESC";
    $agreements = $db->getMultiple($db->prepare($agreements_sql), [$property_id]);

    // Get maintenance requests
    $maintenance_sql = "SELECT mr.*, u.full_name as tenant_name, u.phone as tenant_phone
                        FROM maintenance_requests mr
                        LEFT JOIN users u ON mr.tenant_id = u.user_id
                        WHERE mr.property_id = ?
                        ORDER BY mr.created_at DESC";
    $maintenance = $db->getMultiple($db->prepare($maintenance_sql), [$property_id]);

    // Get feedback
    $feedback_sql = "SELECT f.*, u.full_name as user_name, u.user_role
                      FROM feedback f
                      LEFT JOIN users u ON f.user_id = u.user_id
                      WHERE f.property_id = ?
                      ORDER BY f.created_at DESC";
    $feedback = $db->getMultiple($db->prepare($feedback_sql), [$property_id]);

    $images = !empty($property['images']) ? explode(',', $property['images']) : [];

    ob_start();
    ?>
    <div class="row">
        <div class="col-md-6">
            <h6>Property Information</h6>
            <table class="table table-sm">
                <tr><td><strong>Title:</strong></td><td><?php echo htmlspecialchars($property['title']); ?></td></tr>
                <tr><td><strong>Description:</strong></td><td><?php echo nl2br(htmlspecialchars($property['description'])); ?></td></tr>
                <tr><td><strong>Location:</strong></td><td><?php echo htmlspecialchars($property['location_name'] . ', ' . $property['subcity']); ?></td></tr>
                <tr><td><strong>Price:</strong></td><td><?php echo number_format($property['monthly_rent']); ?> ETB/month</td></tr>
                <tr><td><strong>Bedrooms:</strong></td><td><?php echo $property['bedrooms']; ?></td></tr>
                <tr><td><strong>Bathrooms:</strong></td><td><?php echo $property['bathrooms']; ?></td></tr>
                <tr><td><strong>Type:</strong></td><td><?php echo htmlspecialchars($property['property_type']); ?></td></tr>
                <tr><td><strong>Furnished:</strong></td><td><?php echo $property['is_furnished'] ? 'Yes' : 'No'; ?></td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="badge status-badge status-<?php echo $property['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $property['status'])); ?></span></td></tr>
                <tr><td><strong>Review Status:</strong></td><td><?php echo ucfirst($property['review_status']); ?></td></tr>
                <?php if ($property['reviewed_by_name']): ?>
                <tr><td><strong>Reviewed By:</strong></td><td><?php echo htmlspecialchars($property['reviewed_by_name']); ?></td></tr>
                <?php endif; ?>
                <?php if ($property['review_date']): ?>
                <tr><td><strong>Review Date:</strong></td><td><?php echo date('M j, Y', strtotime($property['review_date'])); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="col-md-6">
            <h6>Owner Information</h6>
            <table class="table table-sm">
                <tr><td><strong>Name:</strong></td><td><?php echo htmlspecialchars($property['owner_name']); ?></td></tr>
                <tr><td><strong>Phone:</strong></td><td><?php echo htmlspecialchars($property['owner_phone']); ?></td></tr>
                <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($property['owner_email']); ?></td></tr>
            </table>
            
            <h6 class="mt-3">Property Images</h6>
            <div class="row">
                <?php foreach ($images as $image): ?>
                    <div class="col-6 mb-2">
                        <img src="../assets/uploads/properties/<?php echo htmlspecialchars($image); ?>" class="img-fluid rounded" alt="Property Image" style="max-height: 150px; object-fit: cover; width: 100%;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <h6>Rental Requests (<?php echo count($requests); ?>)</h6>
            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['tenant_name']); ?></td>
                                <td><span class="badge bg-<?php echo $req['status'] === 'pending' ? 'warning' : ($req['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($req['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <h6>Rental Agreements (<?php echo count($agreements); ?>)</h6>
            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Status</th>
                            <th>Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agreements as $agreement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agreement['tenant_name']); ?></td>
                                <td><span class="badge bg-<?php echo $agreement['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($agreement['status']); ?></span></td>
                                <td><?php echo date('M j', strtotime($agreement['start_date'])) . ' - ' . date('M j, Y', strtotime($agreement['end_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <h6>Maintenance Requests (<?php echo count($maintenance); ?>)</h6>
            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Issue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenance as $main): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($main['tenant_name']); ?></td>
                                <td><?php echo htmlspecialchars($main['issue_type']); ?></td>
                                <td><span class="badge bg-<?php echo $main['status'] === 'pending' ? 'warning' : ($main['status'] === 'in_progress' ? 'info' : 'success'); ?>"><?php echo ucfirst(str_replace('_', ' ', $main['status'])); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <h6>Feedback (<?php echo count($feedback); ?>)</h6>
            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback as $fb): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fb['user_name']); ?> (<?php echo ucfirst($fb['user_role']); ?>)</td>
                                <td><?php echo str_repeat('⭐', $fb['rating']); ?></td>
                                <td><?php echo ucfirst($fb['type']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    echo json_encode(['success' => true, 'content' => $content]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
