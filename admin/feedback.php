<?php
require_once '../includes/config.php';
$title = "Feedback Review - Admin Dashboard";

// Require admin login
$session->requireRole('admin');

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get filter parameters
$type_filter = $_GET['type'] ?? '';
$rating_filter = $_GET['rating'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($type_filter)) {
    $where_conditions[] = "f.type = ?";
    $params[] = $type_filter;
}

if (!empty($rating_filter)) {
    $where_conditions[] = "f.rating = ?";
    $params[] = $rating_filter;
}

$allowed_statuses = ['pending','reviewed','resolved'];
if (!empty($status_filter) && in_array($status_filter, $allowed_statuses, true)) {
    $where_conditions[] = "f.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM feedback f 
              LEFT JOIN users u ON f.user_id = u.user_id 
              LEFT JOIN properties p ON f.property_id = p.property_id 
              $where_clause";
$stmt = $db->prepare($count_sql);
$total_result = $db->getSingle($stmt, $params);
$total_feedback = $total_result ? (int)$total_result['total'] : 0;
$total_pages = ceil($total_feedback / $limit);

// Get feedback with pagination
$sql = "SELECT f.*, u.full_name, u.email, u.role as user_role, 
               p.title as property_title, p.property_id,
               e.full_name AS reviewed_by_name
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.user_id 
        LEFT JOIN properties p ON f.property_id = p.property_id
        LEFT JOIN users e ON f.reviewed_by = e.user_id
        $where_clause
        ORDER BY f.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$feedback_list = $db->getMultiple($stmt, $params);

// Get statistics
$stats_sql = "SELECT 
                 COUNT(*) as total,
                 AVG(rating) as avg_rating
              FROM feedback";
$stmt = $db->prepare($stats_sql);
$stats = $db->getSingle($stmt) ?: ['total' => 0, 'avg_rating' => 0];

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_sql = "SELECT f.feedback_id, f.type, f.status, f.rating, f.comment, f.created_at,
                          u.full_name AS user_name, u.email AS user_email,
                          p.title AS property_title
                   FROM feedback f
                   LEFT JOIN users u ON f.user_id = u.user_id
                   LEFT JOIN properties p ON f.property_id = p.property_id
                   $where_clause
                   ORDER BY f.created_at DESC";
    $export_stmt = $db->prepare($export_sql);
    $rows = $db->getMultiple($export_stmt, $params) ?? [];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="feedback_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Type','Status','Rating','Comment','Date','User Name','User Email','Property']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['feedback_id'],
            $r['type'],
            $r['status'],
            $r['rating'],
            $r['comment'],
            $r['created_at'],
            $r['user_name'],
            $r['user_email'],
            $r['property_title'] ?? 'General'
        ]);
    }
    fclose($out);
    exit;
}

include '../includes/header.php';
?>

<!-- Feedback Review Content -->
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Feedback Review</h1>
                <div>
                    <span class="badge bg-info">Total: <?php echo $total_feedback; ?></span>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p class="mb-0">Total Feedback</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3><?php echo number_format($stats['avg_rating'], 1); ?></h3>
                            <p class="mb-0">Average Rating</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $total_feedback; ?></h3>
                            <p class="mb-0">This Period</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Feedback
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="complaint" <?php echo $type_filter === 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                                <option value="suggestion" <?php echo $type_filter === 'suggestion' ? 'selected' : ''; ?>>Suggestion</option>
                                <option value="review" <?php echo $type_filter === 'review' ? 'selected' : ''; ?>>Review</option>
                                <option value="general" <?php echo $type_filter === 'general' ? 'selected' : ''; ?>>General</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="rating" class="form-label">Rating</label>
                            <select class="form-select" id="rating" name="rating">
                                <option value="">All Ratings</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $rating_filter == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php if (!empty($type_filter) || !empty($rating_filter) || !empty($status_filter)): ?>
                        <div class="mt-2">
                            <a href="feedback.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Feedback List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comment-dots me-2"></i>Feedback Items
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-success" onclick="exportFeedback()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="bulkRespond()">
                            <i class="fas fa-reply me-1"></i>Bulk Respond
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($feedback_list)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-dots fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No feedback found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later for new feedback.</p>
                        </div>
                    <?php else: ?>
                        <div class="feedback-list">
                            <?php foreach ($feedback_list as $feedback): ?>
                                <div class="card mb-3 feedback-item" data-feedback-id="<?php echo $feedback['feedback_id']; ?>">
                                    <div class="card-header d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($feedback['property_title'] ?: 'General Feedback'); ?>
                                                <span class="badge bg-<?php echo getStatusColor($feedback['status']); ?> ms-2">
                                                    <?php echo ucfirst($feedback['status']); ?>
                                                </span>
                                                <span class="badge bg-secondary ms-1">
                                                    <?php echo ucfirst($feedback['type']); ?>
                                                </span>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($feedback['full_name']); ?>
                                                <span class="badge bg-info ms-1"><?php echo ucfirst($feedback['user_role']); ?></span>
                                                <i class="fas fa-envelope ms-2 me-1"></i><?php echo htmlspecialchars($feedback['email']); ?>
                                                <i class="fas fa-clock ms-2 me-1"></i><?php echo formatDate($feedback['created_at'], 'M d, Y H:i'); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="viewFeedback(<?php echo $feedback['feedback_id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button type="button" class="btn btn-outline-success" onclick="respondToFeedback(<?php echo $feedback['feedback_id']; ?>)">
                                                    <i class="fas fa-reply"></i> Respond
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="updateStatus(<?php echo $feedback['feedback_id']; ?>)">
                                                    <i class="fas fa-edit"></i> Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></p>
                                        <?php if (!empty($feedback['response'])): ?>
                                            <small class="text-info d-block">
                                                <i class="fas fa-reply me-1"></i>Responded<?php echo !empty($feedback['reviewed_by_name']) ? ' by ' . htmlspecialchars($feedback['reviewed_by_name']) : ''; ?>
                                                <?php if (!empty($feedback['reviewed_at'])): ?>
                                                    <span class="text-muted ms-1"><?php echo formatDate($feedback['reviewed_at'], 'M d, Y H:i'); ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Feedback pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?><?php echo !empty($rating_filter) ? '&rating=' . urlencode($rating_filter) : ''; ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?><?php echo !empty($rating_filter) ? '&rating=' . urlencode($rating_filter) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?><?php echo !empty($rating_filter) ? '&rating=' . urlencode($rating_filter) : ''; ?>">
                                                Next
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Details Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Feedback Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="feedbackDetails"></div>
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
            <div class="modal-header">
                <h5 class="modal-title">Respond to Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="responseForm">
                    <input type="hidden" id="feedbackId" name="feedback_id">
                    <div class="mb-3">
                        <label for="responseText" class="form-label">Response</label>
                        <textarea class="form-control" id="responseText" name="response" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Update Status</label>
                        <select class="form-select" id="newStatus" name="status">
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitResponse()">Submit Response</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Feedback Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="statusForm">
                    <input type="hidden" id="statusFeedbackId" name="feedback_id">
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label">Status</label>
                        <select class="form-select" id="statusSelect" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()">Update</button>
            </div>
        </div>
    </div>
    </div>
 
<?php 
// Helper function for status colors
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'reviewed' => 'info',
        'resolved' => 'success'
    ];
    return $colors[$status] ?? 'secondary';
}
?>

<script>
function viewFeedback(feedbackId) {
    fetch('../api/feedback-details.php?feedback_id=' + feedbackId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const feedback = data.feedback;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Property:</strong> ${feedback.property_title || 'General'}<br>
                            <strong>Type:</strong> ${feedback.type}<br>
                            <strong>Status:</strong> <span class="badge bg-${getStatusColor(feedback.status)}">${feedback.status}</span><br>
                            <strong>Rating:</strong> ${getStarRating(feedback.rating)}
                        </div>
                        <div class="col-md-6">
                            <strong>User:</strong> ${feedback.full_name}<br>
                            <strong>Email:</strong> ${feedback.email}<br>
                            <strong>Role:</strong> ${feedback.user_role}<br>
                            <strong>Date:</strong> ${new Date(feedback.created_at).toLocaleString()}
                        </div>
                    </div>
                    <hr>
                    <strong>Comment:</strong><br>
                    <p>${feedback.comment.replace(/\n/g, '<br>')}</p>
                    ${feedback.responses ? '<hr><strong>Responses:</strong><br>' + feedback.responses.map(r => 
                        `<div class="border-start border-3 border-primary ps-3 mb-2">
                            <strong>Admin:</strong> ${r.admin_name}<br>
                            <small>${new Date(r.created_at).toLocaleString()}</small><br>
                            ${r.response.replace(/\n/g, '<br>')}
                        </div>`
                    ).join('') : ''}
                `;
                document.getElementById('feedbackDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('feedbackModal')).show();
            } else {
                alert('Error loading feedback details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading feedback details');
        });
}

function respondToFeedback(feedbackId) {
    document.getElementById('feedbackId').value = feedbackId;
    document.getElementById('responseText').value = '';
    document.getElementById('newStatus').value = 'reviewed';
    new bootstrap.Modal(document.getElementById('responseModal')).show();
}

function submitResponse() {
    const form = document.getElementById('responseForm');
    const formData = new FormData(form);
    
    fetch('../api/respond-feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Response submitted successfully!');
            bootstrap.Modal.getInstance(document.getElementById('responseModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting response');
    });
}

function updateStatus(feedbackId) {
    const newStatus = prompt('Enter new status (pending, reviewed, resolved):');
    if (newStatus && ['pending', 'reviewed', 'resolved'].includes(newStatus)) {
        fetch('../api/update-feedback-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                feedback_id: feedbackId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating status');
        });
    }
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'reviewed': 'info',
        'resolved': 'success'
    };
    return colors[status] || 'secondary';
}

function getStarRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<i class="fas fa-star ${i <= rating ? 'text-warning' : 'text-muted'}"></i>`;
    }
    return stars;
}

function exportFeedback() {
    window.open('feedback.php?export=csv', '_blank');
}

function bulkRespond() {
    const warn = document.createElement('div');
    warn.className = 'alert alert-warning';
    warn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Bulk response is not yet implemented. Please respond individually.';
    document.querySelector('.card-body').prepend(warn);
    setTimeout(() => warn.remove(), 4000);
}

function updateStatus(feedbackId) {
    document.getElementById('statusFeedbackId').value = feedbackId;
    document.getElementById('statusSelect').value = 'reviewed';
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function submitStatusUpdate() {
    const id = document.getElementById('statusFeedbackId').value;
    const newStatus = document.getElementById('statusSelect').value;
    fetch('../api/update-feedback-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ feedback_id: parseInt(id, 10), status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating status');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
