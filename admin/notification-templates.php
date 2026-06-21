<?php
require_once '../includes/config.php';
$title = "Notification Templates - Admin";
$session->requireRole('admin');

// Ensure table exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS notification_templates (
        template_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(20) NOT NULL DEFAULT 'info',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $t = trim($_POST['title'] ?? '');
        $m = trim($_POST['message'] ?? '');
        $type = $_POST['type'] ?? 'info';
        if ($name && $t && $m) {
            $stmt = $db->prepare("INSERT INTO notification_templates (name, title, message, type) VALUES (?, ?, ?, ?)");
            if ($db->execute($stmt, [$name, $t, $m, $type])) {
                $_SESSION['success'] = "Template created";
            } else {
                $_SESSION['error'] = "Failed to create template";
            }
        } else {
            $_SESSION['error'] = "Name, title, and message are required";
        }
        header("Location: notification-templates.php");
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['template_id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM notification_templates WHERE template_id = ?");
            if ($db->execute($stmt, [$id])) {
                $_SESSION['success'] = "Template deleted";
            } else {
                $_SESSION['error'] = "Failed to delete template";
            }
        }
        header("Location: notification-templates.php");
        exit;
    }
}

$templates = $db->getMultiple($db->prepare("SELECT * FROM notification_templates ORDER BY created_at DESC"));

include '../includes/header.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-3"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Notification Templates</h1>
                <a href="notification-create.php" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i>Use Template</a>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Create Template</h5></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required placeholder="Payment Reminder">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required placeholder="Your payment has been received">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="4" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Templates</h5></div>
                <div class="card-body">
                    <?php if (empty($templates)): ?>
                        <div class="text-muted text-center py-4">No templates created</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($templates as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                                        <td><span class="badge bg-<?php echo $t['type']==='success'?'success':($t['type']==='warning'?'warning':($t['type']==='error'?'danger':'info')); ?>"><?php echo ucfirst($t['type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($t['title']); ?></td>
                                        <td><small class="text-muted"><?php echo formatDate($t['created_at'], 'M d, Y H:i'); ?></small></td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="template_id" value="<?php echo (int)$t['template_id']; ?>">
                                                <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
