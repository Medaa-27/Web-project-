<?php
require_once '../includes/config.php';
$title = "Create Notification - Admin";
$session->requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview') {
    $audience = $_POST['audience'] ?? 'all';
    $role = $_POST['role'] ?? '';
    $user_email = trim($_POST['user_email'] ?? '');
    $title_in = trim($_POST['title'] ?? '');
    $message_in = trim($_POST['message'] ?? '');
    $type_in = $_POST['type'] ?? 'info';
    $link_in = trim($_POST['link'] ?? '');
    $count = 0;
    if ($audience === 'all') {
        $row = $db->getSingle($db->prepare("SELECT COUNT(*) AS c FROM users"));
        $count = $row ? (int)$row['c'] : 0;
    } elseif ($audience === 'role' && $role) {
        $row = $db->getSingle($db->prepare("SELECT COUNT(*) AS c FROM users WHERE role = ?"), [$role]);
        $count = $row ? (int)$row['c'] : 0;
    } elseif ($audience === 'user' && $user_email) {
        $u = $db->getSingle($db->prepare("SELECT user_id FROM users WHERE email = ?"), [$user_email]);
        $count = $u ? 1 : 0;
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $count,
        'title' => $title_in,
        'message' => $message_in,
        'type' => $type_in,
        'link' => $link_in,
        'audience' => $audience,
        'role' => $role,
        'user_email' => $user_email
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $audience = $_POST['audience'] ?? 'all';
    $role = $_POST['role'] ?? '';
    $user_email = trim($_POST['user_email'] ?? '');
    $title_in = trim($_POST['title'] ?? '');
    $message_in = trim($_POST['message'] ?? '');
    $type_in = $_POST['type'] ?? 'info';
    $link_in = trim($_POST['link'] ?? '');

    if ($title_in === '' || $message_in === '') {
        $_SESSION['error'] = "Title and message are required";
        header("Location: notification-create.php");
        exit;
    }

    $recipients = [];
    if ($audience === 'all') {
        $recipients = $db->getMultiple($db->prepare("SELECT user_id FROM users"), []);
    } elseif ($audience === 'role' && $role) {
        $recipients = $db->getMultiple($db->prepare("SELECT user_id FROM users WHERE role = ?"), [$role]);
    } elseif ($audience === 'user' && $user_email) {
        $u = $db->getSingle($db->prepare("SELECT user_id FROM users WHERE email = ?"), [$user_email]);
        if ($u) $recipients = [$u];
    }

    if (empty($recipients)) {
        $_SESSION['error'] = "No recipients found";
        header("Location: notification-create.php");
        exit;
    }

    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
    $count = 0;
    foreach ($recipients as $r) {
        if ($db->execute($stmt, [(int)$r['user_id'], $title_in, $message_in, $type_in, $link_in])) {
            $count++;
        }
    }
    $_SESSION['success'] = "Notification sent to {$count} recipient(s)";
    header("Location: notifications.php");
    exit;
}

include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Create Notification</h1>
                <a href="notifications.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-paper-plane me-2"></i>Send Notification</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3" id="notifyForm">
                        <div class="col-md-12">
                            <label class="form-label">Audience</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="audience" id="audAll" value="all" checked>
                                    <label class="form-check-label" for="audAll">All Users</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="audience" id="audRole" value="role">
                                    <label class="form-check-label" for="audRole">By Role</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="audience" id="audUser" value="user">
                                    <label class="form-check-label" for="audUser">Individual User</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 audience-role d-none">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="">Select role</option>
                                <option value="admin">Admin</option>
                                <option value="employee">Employee</option>
                                <option value="owner">Owner</option>
                                <option value="tenant">Tenant</option>
                            </select>
                        </div>
                        <div class="col-md-4 audience-user d-none">
                            <label class="form-label">User Email</label>
                            <input type="email" class="form-control" name="user_email" placeholder="user@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="4" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Link (optional)</label>
                            <input type="text" class="form-control" name="link" placeholder="../tenant/payments.php">
                        </div>
                        <div class="col-md-12">
                            <button type="button" class="btn btn-outline-primary me-2" id="previewBtn"><i class="fas fa-eye me-2"></i>Preview</button>
                            <button type="submit" class="btn btn-primary" id="sendBtn" disabled><i class="fas fa-paper-plane me-2"></i>Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div id="previewTypeBadge" class="badge bg-info">Info</div>
                        <h6 class="mt-2" id="previewTitle"></h6>
                        <p class="mb-1" id="previewMessage"></p>
                        <div id="previewLink" class="text-muted small"></div>
                    </div>
                    <div class="text-end">
                        <div class="h4 mb-0" id="previewCount">0</div>
                        <div class="text-muted">Recipients</div>
                    </div>
                </div>
                <div id="previewAudience" class="text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSendBtn"><i class="fas fa-paper-plane me-2"></i>Confirm Send</button>
            </div>
        </div>
    </div>
</div>
<script>
function updateAudience() {
    const value = document.querySelector('input[name="audience"]:checked').value;
    document.querySelector('.audience-role').classList.toggle('d-none', value !== 'role');
    document.querySelector('.audience-user').classList.toggle('d-none', value !== 'user');
}
document.querySelectorAll('input[name="audience"]').forEach(r => r.addEventListener('change', updateAudience));
updateAudience();

function typeToBadge(type) {
    if (type === 'success') return 'bg-success';
    if (type === 'warning') return 'bg-warning';
    if (type === 'error') return 'bg-danger';
    return 'bg-info';
}

document.getElementById('previewBtn').addEventListener('click', async function() {
    const form = document.getElementById('notifyForm');
    const fd = new FormData(form);
    fd.set('action', 'preview');
    const title = fd.get('title')?.trim();
    const message = fd.get('message')?.trim();
    if (!title || !message) {
        alert('Title and message are required');
        return;
    }
    try {
        const res = await fetch('notification-create.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
            alert('Unable to preview');
            return;
        }
        document.getElementById('previewTitle').textContent = data.title || '';
        document.getElementById('previewMessage').textContent = data.message || '';
        const badge = document.getElementById('previewTypeBadge');
        badge.className = 'badge ' + typeToBadge(data.type);
        badge.textContent = (data.type || 'info').charAt(0).toUpperCase() + (data.type || 'info').slice(1);
        document.getElementById('previewCount').textContent = data.count;
        document.getElementById('previewLink').textContent = data.link ? ('Link: ' + data.link) : '';
        const audDesc = data.audience === 'all' ? 'All users' :
            (data.audience === 'role' ? ('Role: ' + (data.role || '')) :
            (data.audience === 'user' ? ('User: ' + (data.user_email || '')) : ''));
        document.getElementById('previewAudience').textContent = audDesc;
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
        document.getElementById('sendBtn').disabled = false;
    } catch (e) {
        alert('Network error');
    }
});

document.getElementById('confirmSendBtn').addEventListener('click', function() {
    document.getElementById('notifyForm').submit();
});
</script>
<?php include '../includes/footer.php'; ?>
