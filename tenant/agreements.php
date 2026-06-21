<?php
require_once '../includes/config.php';
$session->requireRole('tenant');
$title = "Rental Agreements - Aksum Rental System";

$user_id = $session->getUserId();

// Get all rental agreements
$sql = "SELECT ra.*, p.title, p.monthly_rent, p.security_deposit, p.description,
               p.bedrooms, p.bathrooms, p.area_sqm, p.property_type, p.property_rules,
               l.location_name, u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        LEFT JOIN locations l ON p.location_id = l.location_id
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE ra.tenant_id = ?
        ORDER BY ra.created_at DESC";
$stmt = $db->prepare($sql);
$agreements = $db->getMultiple($stmt, [$user_id]);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <!-- Agreements Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">Rental Agreements</h1>
                            <p class="text-muted mb-0">View and manage your rental agreements</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-end">
                                <h5 class="text-primary mb-0"><?php echo count($agreements); ?></h5>
                                <small class="text-muted">Total Agreements</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agreements List -->
            <?php if (empty($agreements)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-contract fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Rental Agreements</h5>
                        <p class="text-muted mb-4">You don't have any rental agreements yet. Start by searching for properties and submitting rental requests.</p>
                        <a href="search.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Find Properties
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($agreements as $agreement): ?>
                    <div class="card mb-4" data-agreement-id="<?php echo $agreement['agreement_id']; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($agreement['title']); ?></h5>
                                <small class="text-muted">Agreement ID: #<?php echo str_pad($agreement['agreement_id'], 6, '0', STR_PAD_LEFT); ?></small>
                            </div>
                            <div>
                                <span class="badge bg-<?php echo $agreement['status'] == 'active' ? 'success' : ($agreement['status'] == 'expired' ? 'danger' : 'warning'); ?> fs-6">
                                    <?php echo ucfirst($agreement['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Property Details -->
                                <div class="col-md-8">
                                    <h6 class="text-primary mb-3">Property Details</h6>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="mb-2"><strong>Location:</strong> <?php echo htmlspecialchars($agreement['location_name']); ?></p>
                                            <p class="mb-2"><strong>Type:</strong> <?php echo ucfirst($agreement['property_type']); ?></p>
                                            <p class="mb-2"><strong>Bedrooms:</strong> <?php echo $agreement['bedrooms']; ?></p>
                                            <p class="mb-2"><strong>Bathrooms:</strong> <?php echo $agreement['bathrooms']; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2"><strong>Area:</strong> <?php echo $agreement['area_sqm']; ?> sqm</p>
                                            <p class="mb-2"><strong>Monthly Rent:</strong> <span class="text-success">ETB <?php echo number_format($agreement['monthly_rent'], 0); ?></span></p>
                                            <p class="mb-2"><strong>Security Deposit:</strong> <span class="text-info">ETB <?php echo number_format($agreement['security_deposit'], 0); ?></span></p>
                                            <p class="mb-2"><strong>Duration:</strong> 
                                                <?php 
                                                $start = new DateTime($agreement['start_date']);
                                                $end = new DateTime($agreement['end_date']);
                                                $duration = $start->diff($end);
                                                echo $duration->m + ($duration->y * 12);
                                                ?> months
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($agreement['description'])): ?>
                                        <div class="mb-3">
                                            <h6 class="text-primary">Description</h6>
                                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($agreement['description'])); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($agreement['property_rules'])): ?>
                                        <div class="mb-3">
                                            <h6 class="text-primary">Property Rules</h6>
                                            <?php
                                                $rules = json_decode($agreement['property_rules'], true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($rules)) {
                                                    echo '<div class="list-group">';
                                                    foreach ($rules as $rule) {
                                                        $title = htmlspecialchars($rule['title'] ?? 'Rule', ENT_QUOTES, 'UTF-8');
                                                        $description = htmlspecialchars($rule['description'] ?? '', ENT_QUOTES, 'UTF-8');
                                                        echo '<div class="list-group-item p-3">';
                                                        echo '<h6 class="mb-1">' . $title . '</h6>';
                                                        echo '<p class="mb-0 text-muted">' . ($description ?: 'No additional details.') . '</p>';
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<p class="text-muted">' . nl2br(htmlspecialchars($agreement['property_rules'])) . '</p>';
                                                }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Agreement Details -->
                                <div class="col-md-4">
                                    <h6 class="text-primary mb-3">Agreement Details</h6>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-2"><strong>Start Date:</strong><br><?php echo date('F d, Y', strtotime($agreement['start_date'])); ?></p>
                                        <p class="mb-2"><strong>End Date:</strong><br><?php echo date('F d, Y', strtotime($agreement['end_date'])); ?></p>
                                        <p class="mb-2"><strong>Next Payment:</strong><br><?php echo date('F d, Y', strtotime($agreement['end_date'])); ?></p>
                                        <p class="mb-2"><strong>Created:</strong><br><?php echo date('F d, Y', strtotime($agreement['created_at'])); ?></p>
                                    </div>
                                    
                                    <!-- Owner Information -->
                                    <h6 class="text-primary mb-3 mt-4">Property Owner</h6>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-1"><strong>Name:</strong><br><?php echo htmlspecialchars($agreement['owner_name']); ?></p>
                                        <p class="mb-1"><strong>Phone:</strong><br><?php echo htmlspecialchars($agreement['owner_phone']); ?></p>
                                        <p class="mb-0"><strong>Email:</strong><br><?php echo htmlspecialchars($agreement['owner_email']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Agreement Terms -->
                            <?php if (!empty($agreement['terms'])): ?>
                                <div class="mt-4">
                                    <h6 class="text-primary mb-3">Agreement Terms</h6>
                                    <div class="bg-light p-3 rounded">
                                        <pre class="mb-0 text-muted small"><?php echo htmlspecialchars($agreement['terms']); ?></pre>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="printAgreement(<?php echo $agreement['agreement_id']; ?>)">
                                        <i class="fas fa-print me-2"></i>Print
                                    </button>
                                    <a href="download-agreement.php?id=<?php echo $agreement['agreement_id']; ?>" 
                                       class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-download me-2"></i>Download
                                    </a>

                                </div>
                                <div>
                                    <small class="text-muted">
                                        Last updated: <?php echo date('M d, Y H:i', strtotime($agreement['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    /* Hide navigation and buttons */
    .sidebar, .btn, .btn-group, .card-footer, .no-print {
        display: none !important;
    }
    
    /* Layout adjustments */
    .col-lg-3 {
        display: none !important;
    }
    .col-lg-9 {
        width: 100% !important;
    }
    .container-fluid {
        padding: 0 !important;
    }
    
    /* Card styling for print */
    .card {
        border: 1px solid #000 !important;
        page-break-inside: avoid;
        margin-bottom: 20px !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #000 !important;
    }
    .card-body {
        padding: 15px !important;
    }
    
    /* Background colors */
    .bg-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Text styling */
    body {
        font-size: 12px !important;
        line-height: 1.4 !important;
        color: #000 !important;
    }
    h5, h6 {
        color: #000 !important;
        margin-bottom: 10px !important;
    }
    
    /* Ensure proper spacing */
    .row {
        margin-bottom: 15px !important;
    }
    
    /* Page breaks */
    .card {
        page-break-inside: avoid;
    }
    
    /* Footer info */
    .text-muted {
        color: #666 !important;
    }
}
</style>

<script>
function printAgreement(agreementId) {
    console.log('Printing agreement ID:', agreementId);
    
    // Find the specific agreement card
    const agreementCard = document.querySelector(`[data-agreement-id="${agreementId}"]`);
    
    if (!agreementCard) {
        alert('Agreement not found');
        return;
    }
    
    // Store original body content
    const originalContent = document.body.innerHTML;
    
    // Clone the agreement card and remove footer
    const cardClone = agreementCard.cloneNode(true);
    const footer = cardClone.querySelector('.card-footer');
    if (footer) {
        footer.remove();
    }
    
    // Create print-friendly content
    let printContent = '<div style="padding: 20px;">';
    printContent += '<div style="text-align: center; margin-bottom: 30px;">';
    printContent += '<h1>Rental Agreement</h1>';
    printContent += '<p>Printed on ' + new Date().toLocaleDateString() + '</p>';
    printContent += '</div>';
    printContent += cardClone.outerHTML;
    printContent += '</div>';
    
    // Replace body content with print content
    document.body.innerHTML = printContent;
    
    // Wait a moment for content to render, then print
    setTimeout(() => {
        window.print();
        
        // Restore original content after printing
        setTimeout(() => {
            document.body.innerHTML = originalContent;
            // Re-initialize any JavaScript that might be needed
            location.reload();
        }, 100);
    }, 500);
}
</script>

<?php include '../includes/footer.php'; ?>