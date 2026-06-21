<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$session->requireRole('employee');
$title = "Feedback Management";

// Suppress warnings for cleaner display
error_reporting(E_ERROR | E_PARSE);

// Handle feedback actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_feedback'])) {
        $feedback_id = $_POST['feedback_id'];
        $status = $_POST['status'];
        $response = $_POST['response'];
        
        try {
            // Update ticket status and priority
            $sql = "UPDATE feedback SET status = ?, response = ?, reviewed_at = NOW(), reviewed_by = ? WHERE feedback_id = ?";
            $stmt = $db->prepare($sql);
            
            if ($db->execute($stmt, [$status, $response, $session->getUserId(), $feedback_id])) {
                // Get user ID for notification
                $user_sql = "SELECT user_id FROM feedback WHERE feedback_id = ?";
                $user_stmt = $db->prepare($user_sql);
                $user = $db->getSingle($user_stmt, [$feedback_id]);
                
                if ($user) {
                    $notif_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                                  VALUES (?, 'Feedback Response', ?, 'success', NOW())";
                    $notif_stmt = $db->prepare($notif_sql);
                    $db->execute($notif_stmt, [$user['user_id'], "Your feedback has been reviewed and a response has been provided."]);
                }
                
                $_SESSION['success'] = "Feedback updated successfully";
                header("Location: feedback.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Update feedback error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update feedback";
        }
    }
    
    if (isset($_POST['delete_feedback'])) {
        $feedback_id = $_POST['feedback_id'];
        
        try {
            // Delete the feedback
            $sql = "DELETE FROM feedback WHERE feedback_id = ?";
            $stmt = $db->prepare($sql);
            
            if ($db->execute($stmt, [$feedback_id])) {
                $_SESSION['success'] = "Feedback deleted successfully";
                header("Location: feedback.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to delete feedback";
            }
        } catch (Exception $e) {
            error_log("Delete feedback error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete feedback";
        }
    }
    
    if (isset($_POST['bulk_action'])) {
        $feedback_ids = $_POST['feedback_ids'] ?? [];
        $action = $_POST['bulk_action_type'];
        
        if (!empty($feedback_ids) && $action) {
            $placeholders = str_repeat('?,', count($feedback_ids) - 1) . '?';
            
            switch ($action) {
                case 'review':
                    $sql = "UPDATE feedback SET status = 'reviewed' WHERE feedback_id IN ($placeholders)";
                    $message = "Feedback marked as reviewed";
                    break;
                case 'resolve':
                    $sql = "UPDATE feedback SET status = 'resolved' WHERE feedback_id IN ($placeholders)";
                    $message = "Feedback marked as resolved";
                    break;
                case 'delete':
                    $sql = "DELETE FROM feedback WHERE feedback_id IN ($placeholders)";
                    $message = "Feedback deleted successfully";
                    break;
            }
            
            $stmt = $db->prepare($sql);
            if ($db->execute($stmt, $feedback_ids)) {
                $_SESSION['success'] = $message;
            }
        }
        header("Location: feedback.php");
        exit();
    }
}

// Get feedback with pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : '';

$sql = "SELECT f.*, 
               u.full_name as user_name, u.email as user_email, u.phone as user_phone,
               p.title as property_title, p.address as property_address,
               e.full_name as reviewed_by_name
        FROM feedback f
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN properties p ON f.property_id = p.property_id
        LEFT JOIN users e ON f.reviewed_by = e.user_id
        WHERE 1=1";
        
$params = [];
$count_params = [];

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR p.title LIKE ? OR f.comment LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $count_params = $params;
}

if ($status_filter) {
    $sql .= " AND f.status = ?";
    $params[] = $status_filter;
    $count_params[] = $status_filter;
}

if ($type_filter) {
    $sql .= " AND f.type = ?";
    $params[] = $type_filter;
    $count_params[] = $type_filter;
}

if ($rating_filter) {
    $sql .= " AND f.rating = ?";
    $params[] = $rating_filter;
    $count_params[] = $rating_filter;
}

// Get total count
$count_sql = str_replace("SELECT f.*, 
               u.full_name as user_name, u.email as user_email, u.phone as user_phone,
               p.title as property_title, p.address as property_address,
               e.full_name as reviewed_by_name",
               "SELECT COUNT(*) as total", $sql);

$count_stmt = $db->prepare($count_sql);
$db->execute($count_stmt, $count_params);
$total_feedback = $count_stmt->fetch()['total'];

// Add pagination
$sql .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$feedback_list = $db->getMultiple($stmt, $params);

$total_pages = ceil($total_feedback / $limit);

// Update feedback status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_feedback'])) {
    $feedback_id = $_POST['feedback_id'];
    $status = $_POST['status'];
    $response = $_POST['response'] ?? '';
    
    $sql = "UPDATE feedback SET status = ?, response = ?, reviewed_by = ?, reviewed_at = NOW() WHERE feedback_id = ?";
    $stmt = $db->prepare($sql);
    if ($db->execute($stmt, [$status, $response, $session->getUserId(), $feedback_id])) {
        $_SESSION['success'] = "Feedback updated successfully";
        header("Location: feedback.php");
        exit();
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
            <!-- Page Header -->
            <div class="card dashboard-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 mb-0">Feedback Management</h1>
                            <p class="text-muted mb-0">Total: <?php echo $total_feedback; ?> feedback entries</p>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="Search feedback..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <select name="status" class="form-select" onchange="window.location.href='?status='+this.value">
                                <option value="">All Status</option>
                                <option value="PENDING" <?php echo $status_filter == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                <option value="REVIEWED" <?php echo $status_filter == 'REVIEWED' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="RESOLVED" <?php echo $status_filter == 'RESOLVED' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="feedback.php" class="btn btn-outline-secondary">Clear Filters</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Table -->
            <div class="card dashboard-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Feedback ID</th>
                                    <th>User</th>
                                    <th>Property</th>
                                    <th>Rating</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedback_list)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No feedback found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($feedback_list as $feedback): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($feedback['feedback_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($feedback['user_name'] ?? 'Anonymous'); ?></strong>
                                                <div class="small text-muted"><?php echo $feedback['user_email'] ?? 'No email'; ?></div>
                                                <?php if (!empty($feedback['is_anonymous']) && $feedback['is_anonymous']): ?>
                                                    <span class="badge bg-secondary">Anonymous</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($feedback['property_title'])): ?>
                                                    <strong><?php echo htmlspecialchars($feedback['property_title']); ?></strong>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($feedback['property_address'] ?? 'No address'); ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">General Feedback</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($feedback['rating'])): ?>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                    <div class="small text-muted"><?php echo $feedback['rating']; ?>/5</div>
                                                <?php else: ?>
                                                    <span class="text-muted">No rating</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $category_colors = [
                                                    'complaint' => 'danger',
                                                    'suggestion' => 'info',
                                                    'review' => 'success',
                                                    'general' => 'secondary',
                                                    'payment' => 'success',
                                                    'other' => 'secondary'
                                                ];
                                                $color = $category_colors[strtolower($feedback['category'] ?? 'other')] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo ucfirst($feedback['category'] ?? 'other'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                                                <div class="small text-muted"><?php echo date('h:i A', strtotime($feedback['created_at'])); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'PENDING' => 'warning',
                                                    'REVIEWED' => 'info',
                                                    'RESOLVED' => 'success'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$feedback['status'] ?? 'PENDING'] ?? 'secondary'; ?>">
                                                    <?php echo $feedback['status'] ?? 'PENDING'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="console.log('View button clicked'); viewFeedback(<?php echo $feedback['feedback_id']; ?>); return false;"
                                                            data-feedback-id="<?php echo $feedback['feedback_id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($feedback['user_name'] ?? 'Anonymous'); ?>"
                                                            data-comment="<?php echo htmlspecialchars($feedback['comment'] ?? ''); ?>"
                                                            data-rating="<?php echo $feedback['rating'] ?? 0; ?>"
                                                            data-category="<?php echo htmlspecialchars($feedback['category'] ?? 'other'); ?>"
                                                            data-property-title="<?php echo htmlspecialchars($feedback['property_title'] ?? ''); ?>"
                                                            data-property-address="<?php echo htmlspecialchars($feedback['property_address'] ?? ''); ?>"
                                                            data-status="<?php echo $feedback['status'] ?? 'PENDING'; ?>">
                                                        View
                                                    </button>
                                                    <?php if (($feedback['status'] ?? 'PENDING') == 'PENDING'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                                onclick="console.log('Respond button clicked'); respondToFeedback(<?php echo $feedback['feedback_id']; ?>); return false;"
                                                                data-feedback-id="<?php echo $feedback['feedback_id']; ?>"
                                                                data-current-status="<?php echo $feedback['status'] ?? 'PENDING'; ?>">
                                                            Respond
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="console.log('Delete button clicked'); deleteFeedback(<?php echo $feedback['feedback_id']; ?>); return false;">
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                        Previous
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Feedback Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Feedback Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>User:</strong> <span id="viewUserName"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Date:</strong> <span id="viewCreated"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Category:</strong> <span id="viewCategory"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Rating:</strong> <span id="viewRating"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Comment:</strong>
                        <p class="mt-2" id="viewComment"></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Status:</strong> <span id="viewStatus"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <strong>Response:</strong>
                        <p class="mt-2" id="viewResponse">No response yet</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Respond to Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>From: <strong id="responseUserName"></strong></p>
                    <p>Comment: <em id="responseComment"></em></p>
                    <input type="hidden" name="feedback_id" id="responseFeedbackId">
                    <div class="mb-3">
                        <label class="form-label">Your Response</label>
                        <textarea name="response" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="REVIEWED">Reviewed</option>
                            <option value="RESOLVED">Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_feedback" class="btn btn-primary">Send Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function viewFeedback(feedbackId) {
    console.log('viewFeedback called with ID:', feedbackId);
    
    // Find the button with this feedback ID to get data
    const button = document.querySelector(`[data-feedback-id="${feedbackId}"]`);
    console.log('Found button:', button);
    
    if (!button) {
        console.error('Button not found for feedback ID:', feedbackId);
        alert('Error: Could not find feedback data');
        return;
    }
    
    const userName = button.dataset.userName || 'Anonymous';
    const comment = button.dataset.comment || '';
    const rating = parseInt(button.dataset.rating) || 0;
    const category = button.dataset.category || 'other';
    const status = button.dataset.status || 'PENDING';
    const propertyTitle = button.dataset.propertyTitle || '';
    const propertyAddress = button.dataset.propertyAddress || '';
    
    console.log('Data extracted:', { userName, comment, rating, category, status, propertyTitle });
    
    // Update modal content
    const viewUserName = document.getElementById('viewUserName');
    const viewComment = document.getElementById('viewComment');
    const viewCategory = document.getElementById('viewCategory');
    const viewStatus = document.getElementById('viewStatus');
    
    if (viewUserName) viewUserName.textContent = userName;
    if (viewComment) viewComment.textContent = comment;
    if (viewCategory) viewCategory.textContent = category.charAt(0).toUpperCase() + category.slice(1);
    if (viewStatus) viewStatus.textContent = status;
    
    // Property information
    const propertyElement = document.getElementById('viewProperty');
    if (propertyElement) {
        if (propertyTitle) {
            propertyElement.innerHTML = `<strong>${propertyTitle}</strong><br><small class="text-muted">${propertyAddress}</small>`;
        } else {
            propertyElement.textContent = 'General Feedback';
        }
    }
    
    // Rating display
    const ratingElement = document.getElementById('viewRating');
    if (ratingElement) {
        if (rating > 0) {
            let ratingHtml = '';
            for (let i = 1; i <= 5; i++) {
                ratingHtml += `<i class="fas fa-star ${i <= rating ? 'text-warning' : 'text-muted'}"></i>`;
            }
            ratingHtml += ` (${rating}/5)`;
            ratingElement.innerHTML = ratingHtml;
        } else {
            ratingElement.textContent = 'No rating';
        }
    }
    
    // Open modal manually
    console.log('Opening viewModal');
    openModal('viewModal');
}

function respondToFeedback(feedbackId) {
    console.log('respondToFeedback called with ID:', feedbackId);
    
    // Find the button with this feedback ID to get data
    const button = document.querySelector(`[data-feedback-id="${feedbackId}"]`);
    console.log('Found button:', button);
    
    if (!button) {
        console.error('Button not found for feedback ID:', feedbackId);
        alert('Error: Could not find feedback data');
        return;
    }
    
    const userName = button.dataset.userName || 'Anonymous';
    const comment = button.dataset.comment || '';
    
    console.log('Data extracted:', { userName, comment });
    
    // Update modal content
    const responseFeedbackId = document.getElementById('responseFeedbackId');
    const responseUserName = document.getElementById('responseUserName');
    const responseComment = document.getElementById('responseComment');
    
    if (responseFeedbackId) responseFeedbackId.value = feedbackId;
    if (responseUserName) responseUserName.textContent = userName;
    if (responseComment) responseComment.textContent = comment;
    
    // Open modal manually
    console.log('Opening respondModal');
    openModal('respondModal');
}

function deleteFeedback(feedbackId) {
    console.log('deleteFeedback called with ID:', feedbackId);
    
    if (confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
        console.log('User confirmed deletion');
        
        // Create form for deletion
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        // Add feedback ID
        const feedbackIdInput = document.createElement('input');
        feedbackIdInput.type = 'hidden';
        feedbackIdInput.name = 'feedback_id';
        feedbackIdInput.value = feedbackId;
        form.appendChild(feedbackIdInput);
        
        // Add action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'delete_feedback';
        actionInput.value = '1';
        form.appendChild(actionInput);
        
        console.log('Submitting delete form');
        // Submit form
        document.body.appendChild(form);
        form.submit();
    } else {
        console.log('User cancelled deletion');
    }
}

function openModal(modalId) {
    console.log('openModal called with:', modalId);
    
    const modal = document.getElementById(modalId);
    console.log('Modal element:', modal);
    
    if (!modal) {
        console.error('Modal not found:', modalId);
        alert('Error: Modal not found');
        return;
    }
    
    // Try to open modal with Bootstrap, fallback to manual
    try {
        console.log('Trying Bootstrap modal');
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        console.log('Bootstrap modal opened successfully');
    } catch (e) {
        console.error('Bootstrap modal error:', e);
        console.log('Falling back to manual modal opening');
        
        // Manual modal opening
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'modal-backdrop-' + modalId;
        document.body.appendChild(backdrop);
        
        console.log('Manual modal opened');
    }
}

function closeModal(modalId) {
    console.log('closeModal called with:', modalId);
    
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        const backdrop = document.getElementById('modal-backdrop-' + modalId);
        if (backdrop) {
            backdrop.remove();
        }
        
        console.log('Modal closed');
    }
}

// Add close handlers to modal close buttons
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up event handlers');
    
    // Handle close button clicks for all modals
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    console.log('Found close buttons:', closeButtons.length);
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log('Close button clicked');
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Handle backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            console.log('Backdrop clicked');
            e.target.remove();
            document.body.classList.remove('modal-open');
        }
    });
});
</script>
