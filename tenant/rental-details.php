<?php
require_once '../includes/config.php';
$session->requireRole('tenant');

$agreement_id = $_GET['id'] ?? 0;
if (!is_numeric($agreement_id) || $agreement_id <= 0) {
    header('Location: my-rentals.php');
    exit;
}

$user_id = $session->getUserId();

// Fetch agreement and ensure it belongs to tenant
$sql = "SELECT ra.*, p.title, p.property_type, p.monthly_rent, p.security_deposit, p.property_id, p.description, p.location_id,
               l.location_name, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone,
               (SELECT image_url FROM property_images WHERE property_id = p.property_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE ra.agreement_id = ? AND ra.tenant_id = ?";
$stmt = $db->prepare($sql);
$agreement = $db->getSingle($stmt, [$agreement_id, $user_id]);

if (!$agreement) {
    header('Location: my-rentals.php');
    exit;
}

// Get payments for this agreement
$sql = "SELECT * FROM payments WHERE agreement_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$payments = $db->getMultiple($stmt, [$agreement_id]);

$title = 'Rental Details - ' . htmlspecialchars($agreement['title']) . ' - Aksum Rental System';
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 mb-1"><?php echo htmlspecialchars($agreement['title']); ?></h1>
                        <small class="text-muted">Agreement #<?php echo str_pad($agreement['agreement_id'], 6, '0', STR_PAD_LEFT); ?></small>
                    </div>
                    <div>
                        <?php if ($agreement['status'] == 'active'): ?>
                            <a href="../tenant/payments.php?agreement=<?php echo $agreement['agreement_id']; ?>" class="btn btn-success">Pay Rent</a>
                        <?php endif; ?>
                        <a href="../tenant/download-agreement.php?id=<?php echo $agreement['agreement_id']; ?>" class="btn btn-outline-secondary ms-2">Download Agreement</a>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-4">
                            <img src="<?php echo htmlspecialchars($agreement['primary_image'] ?: '../assets/images/default-property.jpg'); ?>" class="img-fluid rounded" alt="Property">
                        </div>
                        <div class="col-md-8">
                            <p class="mb-1"><strong>Owner:</strong> <?php echo htmlspecialchars($agreement['owner_name']); ?></p>
                            <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($agreement['location_name']); ?></p>
                            <p class="mb-1"><strong>Type:</strong> <?php echo ucfirst($agreement['property_type']); ?></p>
                            <p class="mb-1"><strong>Monthly Rent:</strong> ETB <?php echo number_format($agreement['monthly_rent'],0); ?></p>
                            <p class="mb-1"><strong>Security Deposit:</strong> ETB <?php echo number_format($agreement['security_deposit'],0); ?></p>
                            <p class="mb-1"><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($agreement['start_date'])); ?></p>
                            <p class="mb-1"><strong>End Date:</strong> <?php echo date('M d, Y', strtotime($agreement['end_date'])); ?></p>
                            <div class="mt-3">
                                <a href="payments.php?agreement=<?php echo $agreement['agreement_id']; ?>" class="btn btn-success me-2">Pay Now</a>
                                <button class="btn btn-outline-info" id="contactOwnerBtn">Contact Owner</button>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-2">Agreement Summary</h5>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($agreement['terms'] ?? 'No terms recorded.')); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Payment History</h5>
                    <?php if (empty($payments)): ?>
                        <div class="text-center text-muted py-3">No payments recorded.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                            <td>ETB <?php echo number_format($p['amount'],0); ?></td>
                                            <td><?php echo htmlspecialchars($p['method']); ?></td>
                                            <td><?php echo htmlspecialchars($p['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <div class="col-lg-4">
            <?php include '../includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<!-- Contact Owner Modal -->
<div class="modal fade" id="contactOwnerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Contact Owner</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="contactOwnerInfo"></p>
        <div class="d-grid gap-2">
            <a href="#" id="contactCall" class="btn btn-outline-primary">Call Owner</a>
            <a href="#" id="contactEmail" class="btn btn-outline-secondary">Send Email</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('contactOwnerBtn').addEventListener('click', function(){
    var phone = '<?php echo htmlspecialchars($agreement['owner_phone']); ?>';
    var email = '<?php echo htmlspecialchars($agreement['owner_email']); ?>';
    var info = '';
    if (phone) info += 'Phone: ' + phone + '<br>';
    if (email) info += 'Email: ' + email + '<br>';
    document.getElementById('contactOwnerInfo').innerHTML = info;
    document.getElementById('contactCall').setAttribute('href', phone ? 'tel:' + phone : '#');
    document.getElementById('contactEmail').setAttribute('href', email ? 'mailto:' + email + '?subject=Regarding your property listing' : '#');
    if (typeof bootstrap !== 'undefined') {
        var modalEl = document.getElementById('contactOwnerModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    } else {
        alert('Owner contact:\n' + (phone ? ('Phone: ' + phone + '\n') : '') + (email ? ('Email: ' + email) : ''));
    }
});
</script>

<?php include '../includes/footer.php'; ?>