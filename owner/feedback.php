<?php
require_once '../includes/config.php';

$session->requireRole('owner');
$title = 'Feedback & Ratings - Aksum Rental System';

$owner_id = $session->getUserId();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $feedback_id = $_POST['feedback_id'] ?? null;
    $new_status = $_POST['status'] ?? '';
    
    if (is_numeric($feedback_id) && in_array($new_status, ['pending', 'reviewed', 'resolved'], true)) {
        // Verify this feedback belongs to owner's property
        $sql = "SELECT f.*, p.owner_id 
                FROM feedback f
                JOIN properties p ON f.property_id = p.property_id
                WHERE f.feedback_id = ? AND p.owner_id = ?";
        $stmt = $db->prepare($sql);
        $feedback = $db->getSingle($stmt, [$feedback_id, $owner_id]);
        
        if ($feedback) {
            $sql = "UPDATE feedback SET status = ? WHERE feedback_id = ?";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$new_status, $feedback_id]);
            
            // Notify tenant about status update
            $sql = "INSERT INTO notifications (user_id, title, message, type, created_at)
                    VALUES (?, 'Feedback Status Updated', 
                    'Your feedback status has been updated to: " . ucfirst($new_status) . ".', 
                    'info', NOW())";
            $stmt = $db->prepare($sql);
            $db->execute($stmt, [$feedback['user_id']]);
            
            $_SESSION['success'] = 'Feedback status updated successfully.';
        }
    }
    
    header('Location: feedback.php');
    exit;
}

// Get feedback for owner's properties
$sql = "SELECT f.*, p.title as property_title, p.address, l.location_name,
               u.full_name as user_name, u.email as user_email, u.phone as user_phone
        FROM feedback f
        JOIN properties p ON f.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON f.user_id = u.user_id
        WHERE p.owner_id = ?
        ORDER BY f.created_at DESC";
$stmt = $db->prepare($sql);
$feedback_list = $db->getMultiple($stmt, [$owner_id]);

// Get statistics
$stats = [
    'total' => count($feedback_list),
    'pending' => 0,
    'reviewed' => 0,
    'resolved' => 0,
    'average_rating' => 0
];

$total_rating = 0;
$rating_count = 0;

foreach ($feedback_list as $feedback) {
    switch ($feedback['status']) {
        case 'pending':
            $stats['pending']++;
            break;
        case 'reviewed':
            $stats['reviewed']++;
            break;
        case 'resolved':
            $stats['resolved']++;
            break;
    }
    
    if ($feedback['rating'] && $feedback['rating'] > 0) {
        $total_rating += $feedback['rating'];
        $rating_count++;
    }
}

$stats['average_rating'] = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;

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
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">Feedback & Ratings</h1>
                            <p class="text-muted mb-0">Review tenant feedback for your properties</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-end">
                                <h5 class="text-warning mb-0"><?php echo $stats['average_rating']; ?> ⭐</h5>
                                <small class="text-muted">Average Rating</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-primary bg-opacity-10 text-primary mx-auto">
                                <i class="fas fa-star fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['total']; ?></h3>
                            <p class="text-muted mb-0">Total Feedback</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-warning bg-opacity-10 text-warning mx-auto">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['pending']; ?></h3>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-info bg-opacity-10 text-info mx-auto">
                                <i class="fas fa-eye fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['reviewed']; ?></h3>
                            <p class="text-muted mb-0">Reviewed</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h3 class="display-6 fw-bold"><?php echo $stats['resolved']; ?></h3>
                            <p class="text-muted mb-0">Resolved</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Feedback</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($feedback_list)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-star fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Feedback Yet</h5>
                            <p class="text-muted">Tenants haven't submitted any feedback for your properties yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row" id="feedbackList">
                            <?php foreach ($feedback_list as $feedback): 
                                $status_color = $feedback['status'] == 'pending' ? 'warning' : 
                                              ($feedback['status'] == 'reviewed' ? 'info' : 'success');
                                $status_icon = $feedback['status'] == 'pending' ? 'clock' : 
                                             ($feedback['status'] == 'reviewed' ? 'eye' : 'check-circle');
                            ?>
                                <div class="col-md-6 mb-4 feedback-item" data-status="<?php echo $feedback['status']; ?>">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <!-- Header with Rating and Status -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars(substr($feedback['comment'], 0, 50)); ?>...</h6>
                                                    <p class="text-muted small mb-0">
                                                        <i class="fas fa-home"></i> <?php echo htmlspecialchars($feedback['property_title']); ?>
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <div class="text-warning mb-1">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? '' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="badge bg-<?php echo $status_color; ?>">
                                                        <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                                        <?php echo ucfirst($feedback['status']); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Feedback Type -->
                                            <div class="mb-2">
                                                <span class="badge bg-info"><?php echo ucfirst($feedback['type']); ?></span>
                                            </div>

                                            <!-- Message -->
                                            <div class="mb-3">
                                                <p class="small text-muted mb-1">Message:</p>
                                                <p class="small"><?php echo htmlspecialchars(substr($feedback['comment'], 0, 150)); ?>...</p>
                                            </div>

                                            <!-- Feedback Details -->
                                            <div class="bg-light p-2 rounded mb-3">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Feedback ID</small>
                                                        <p class="mb-0 small">#<?php echo str_pad($feedback['feedback_id'], 6, '0', STR_PAD_LEFT); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Submitted</small>
                                                        <p class="mb-0 small"><?php echo date('M d, Y H:i', strtotime($feedback['created_at'])); ?></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- User Info & Actions -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="small">
                                                    <strong>From:</strong> <?php echo htmlspecialchars($feedback['user_name']); ?>
                                                    <?php if ($feedback['user_email']): ?>
                                                        <br><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($feedback['user_email']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-secondary" onclick="viewDetails(<?php echo $feedback['feedback_id']; ?>)">
                                                        <i class="fas fa-eye"></i> Details
                                                    </button>
                                                    <?php if ($feedback['status'] !== 'resolved'): ?>
                                                        <button class="btn btn-outline-primary" onclick="updateStatus(<?php echo $feedback['feedback_id']; ?>, '<?php echo $feedback['status'] === 'pending' ? 'reviewed' : 'resolved'; ?>')">
                                                            <i class="fas fa-<?php echo $feedback['status'] === 'pending' ? 'eye' : 'check'; ?>"></i> 
                                                            <?php echo $feedback['status'] === 'pending' ? 'Mark Reviewed' : 'Mark Resolved'; ?>
                                                        </button>
                                                    <?php endif; ?>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Status filter
document.getElementById('statusFilter').addEventListener('change', function() {
    const status = this.value;
    const items = document.querySelectorAll('.feedback-item');
    
    items.forEach(item => {
        if (!status || item.dataset.status === status) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// View details
function viewDetails(feedbackId) {
    // This would open a modal with full details
    alert('View details for feedback #' + feedbackId + ' - Feature coming soon!');
}

// Update status
function updateStatus(feedbackId, newStatus) {
    if (!confirm('Update status to ' + newStatus + '?')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="feedback_id" value="${feedbackId}">
        <input type="hidden" name="status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
