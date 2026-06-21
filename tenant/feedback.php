<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Submit Feedback - Aksum Rental System";

$user_id = $session->getUserId();

// Get active rental agreements for feedback
$sql = "SELECT ra.*, p.title, p.property_id, l.location_name
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        WHERE ra.tenant_id = ? AND ra.status = 'active'
        ORDER BY ra.created_at DESC";
$stmt = $db->prepare($sql);
$active_agreements = $db->getMultiple($stmt, [$user_id]);

// Get previous feedback
$sql = "SELECT f.*, p.title as property_title, l.location_name
        FROM feedback f
        JOIN properties p ON f.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC";
$stmt = $db->prepare($sql);
$previous_feedback = $db->getMultiple($stmt, [$user_id]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = intval($_POST['property_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $feedback_type = trim($_POST['feedback_type'] ?? 'general');
    // Ensure feedback type matches DB enum
    $allowed_types = ['complaint','suggestion','review','general'];
    if (!in_array($feedback_type, $allowed_types)) $feedback_type = 'general';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if ($property_id <= 0 || $rating <= 0 || $message === '') {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Combine subject and message into the existing `comment` column
            $comment = $subject ? trim($subject . "\n\n" . $message) : $message;

            // Insert feedback using existing columns: type and comment
            $sql = "INSERT INTO feedback (property_id, user_id, rating, type, comment, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $db->getLastError());
            }

            if (!$db->execute($stmt, [$property_id, $user_id, $rating, $feedback_type, $comment])) {
                throw new Exception('Failed to save feedback: ' . $db->getLastError());
            }

            // Send notification to admin
            $sql = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $db->getLastError());
            }
            $admin = $db->getSingle($stmt);

            if ($admin) {
                $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                                    VALUES (?, 'New Feedback Submitted', 'A tenant has submitted new feedback that requires your attention.', 'info', NOW())";
                $notification_stmt = $db->prepare($notification_sql);
                if (!$notification_stmt) {
                    throw new Exception('Notification prepare failed: ' . $db->getLastError());
                }
                if (!$db->execute($notification_stmt, [$admin['user_id']])) {
                    throw new Exception('Failed to create notification: ' . $db->getLastError());
                }
            }

            $success = "Thank you for your feedback! It has been submitted successfully and will be reviewed by our team.";
        } catch (Exception $e) {
            $error = "Error submitting feedback: " . $e->getMessage();
        }
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
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-0">Submit Feedback</h1>
                    <p class="text-muted mb-0">Share your experience and help us improve our services</p>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Feedback Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">New Feedback</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Property *</label>
                                    <select name="property_id" class="form-select" required>
                                        <option value="">Select property</option>
                                        <?php foreach ($active_agreements as $agreement): ?>
                                            <option value="<?php echo $agreement['property_id']; ?>">
                                                <?php echo htmlspecialchars($agreement['title']); ?> - <?php echo htmlspecialchars($agreement['location_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Feedback Type *</label>
                                    <select name="feedback_type" class="form-select" required>
                                        <option value="">Select type</option>
                                        <option value="property">Property Condition</option>
                                        <option value="service">Service Quality</option>
                                        <option value="maintenance">Maintenance Issues</option>
                                        <option value="suggestion">Suggestion</option>
                                        <option value="complaint">Complaint</option>
                                        <option value="compliment">Compliment</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Rating *</label>
                                    <div class="rating-container">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                            <label for="star<?php echo $i; ?>" class="star-rating">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">Click to rate (1 = Poor, 5 = Excellent)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Subject *</label>
                                    <input type="text" name="subject" class="form-control" required
                                           placeholder="Brief subject for your feedback">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="5" required
                                      placeholder="Please provide detailed feedback about your experience..."></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_anonymous" id="is_anonymous" class="form-check-input">
                                <label class="form-check-label" for="is_anonymous">
                                    Submit feedback anonymously
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Feedback Guidelines</h6>
                            <ul class="mb-0">
                                <li>Be specific and constructive in your feedback</li>
                                <li>Include details about what went well or what could be improved</li>
                                <li>Your feedback helps us improve our services for all tenants</li>
                                <li>All feedback is reviewed by our team within 24-48 hours</li>
                                <li>Anonymous feedback is also welcome if you prefer privacy</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Previous Feedback -->
            <?php if (!empty($previous_feedback)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Previous Feedback</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Property</th>
                                        <th>Type</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Status</th>
                                        <th>Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previous_feedback as $feedback): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($feedback['property_title']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($feedback['location_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucfirst($feedback['type']); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-warning">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? '' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php $preview = nl2br(htmlspecialchars(substr($feedback['comment'], 0, 50))); ?>
                                                <strong><?php echo $preview; ?><?php echo strlen($feedback['comment']) > 50 ? '...' : ''; ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($feedback['comment'], 0, 50)); ?>...</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $feedback['status'] == 'resolved' ? 'success' : ($feedback['status'] == 'in_progress' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $feedback['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($feedback['admin_response']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewResponse(<?php echo $feedback['feedback_id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">No response</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="responseContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<style>
.rating-container {
    display: flex;
    gap: 5px;
}

.rating-container input[type="radio"] {
    display: none;
}

.star-rating {
    font-size: 24px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating:hover,
.star-rating:hover ~ .star-rating {
    color: #ffc107;
}

.rating-container input[type="radio"]:checked ~ .star-rating {
    color: #ffc107;
}

.rating-container input[type="radio"]:checked ~ .star-rating:hover,
.rating-container input[type="radio"]:checked ~ .star-rating:hover ~ .star-rating {
    color: #ffc107;
}
</style>

<script>
$(document).ready(function() {
    // Handle star rating
    $('.star-rating').click(function() {
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
    });
});

function viewResponse(feedbackId) {
    $.ajax({
        url: '../api/get-feedback-response.php',
        method: 'GET',
        data: { feedback_id: feedbackId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let html = `
                    <div class="mb-3">
                        <h6>Admin Response</h6>
                        <p class="text-muted"><small>Responded on: ${new Date(response.data.response_date).toLocaleDateString()}</small></p>
                        <div class="bg-light p-3 rounded">
                            ${response.data.admin_response}
                        </div>
                    </div>
                `;
                $('#responseContent').html(html);
                $('#responseModal').modal('show');
            } else {
                alert('Error loading response');
            }
        },
        error: function() {
            alert('Error loading response');
        }
    });
}
</script>
