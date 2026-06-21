<?php

require_once '../includes/config.php';

$session->requireRole('tenant');

$title = "My Rental Requests - Aksum Rental System";



$user_id = $session->getUserId();



// Get all rental requests with property details

$sql = "SELECT rr.*, p.title as property_title, p.monthly_rent, p.status as property_status,
               p.bedrooms, p.bathrooms, p.is_furnished, p.property_type,
               l.location_name, l.subcity, u.full_name as owner_name, u.phone as owner_phone,
               u.email as owner_email, pi.image_url as property_image,
               ra.agreement_id, ra.agreement_status, ra.payment_deadline, ra.balance_remaining, ra.amount_paid
        FROM rental_requests rr
        JOIN properties p ON rr.property_id = p.property_id
        LEFT JOIN (
            SELECT ra1.agreement_id, ra1.property_id, ra1.tenant_id, ra1.status as agreement_status, 
                   ra1.payment_deadline, 
                   (SELECT balance_remaining FROM payments WHERE agreement_id = ra1.agreement_id AND status = 'completed' ORDER BY created_at DESC LIMIT 1) as balance_remaining,
                   (SELECT amount_paid FROM payments WHERE agreement_id = ra1.agreement_id AND status = 'completed' ORDER BY created_at DESC LIMIT 1) as amount_paid
            FROM rental_agreements ra1
            WHERE ra1.status IN ('active', 'partially_paid', 'pending')
        ) ra ON rr.property_id = ra.property_id AND rr.tenant_id = ra.tenant_id
        LEFT JOIN locations l ON p.location_id = l.location_id

        LEFT JOIN users u ON p.owner_id = u.user_id

        LEFT JOIN property_images pi ON p.property_id = pi.property_id AND pi.is_primary = 1

        WHERE rr.tenant_id = ?

        ORDER BY rr.created_at DESC";

$stmt = $db->prepare($sql);

$requests = $db->getMultiple($stmt, [$user_id]);



// Get statistics

$stats = [];

$stats['total'] = count($requests);

$stats['pending'] = 0;

$stats['approved'] = 0;

$stats['rejected'] = 0;



foreach ($requests as $request) {

    switch ($request['status']) {

        case 'pending':

            $stats['pending']++;

            break;

        case 'approved':

            $stats['approved']++;

            break;

        case 'rejected':

            $stats['rejected']++;

            break;

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

            <div class="card mb-4">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h1 class="h3 mb-0">My Rental Requests</h1>

                            <p class="text-muted mb-0">Track and manage your property rental requests</p>

                        </div>

                        <div class="col-md-4 text-end">

                            <a href="search.php" class="btn btn-primary">

                                <i class="fas fa-search me-2"></i>Search for Property

                            </a>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Statistics Cards -->

            <div class="row mb-4">

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-primary bg-opacity-10 text-primary mx-auto">

                                <i class="fas fa-list fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold"><?php echo $stats['total']; ?></h3>

                            <p class="text-muted mb-0">Total Requests</p>

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

                            <div class="card-icon bg-success bg-opacity-10 text-success mx-auto">

                                <i class="fas fa-check-circle fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold"><?php echo $stats['approved']; ?></h3>

                            <p class="text-muted mb-0">Approved</p>

                        </div>

                    </div>

                </div>

                

                <div class="col-md-3 mb-3">

                    <div class="card dashboard-card h-100">

                        <div class="card-body text-center">

                            <div class="card-icon bg-danger bg-opacity-10 text-danger mx-auto">

                                <i class="fas fa-times-circle fa-2x"></i>

                            </div>

                            <h3 class="display-6 fw-bold"><?php echo $stats['rejected']; ?></h3>

                            <p class="text-muted mb-0">Rejected</p>

                        </div>

                    </div>

                </div>

            </div>



            <!-- Requests List -->

            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">Rental Requests History</h5>

                    <div class="d-flex gap-2">

                        <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">

                            <option value="">All Status</option>

                            <option value="pending">Pending</option>

                            <option value="approved">Approved</option>

                            <option value="rejected">Rejected</option>

                        </select>

                    </div>

                </div>

                <div class="card-body">

                    <?php if (empty($requests)): ?>

                        <div class="text-center py-5">

                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>

                            <h5 class="text-muted">No Rental Requests Yet</h5>

                            <p class="text-muted">You haven't submitted any rental requests yet.</p>

                            <a href="search.php" class="btn btn-primary">

                                <i class="fas fa-search me-2"></i>Search for Properties

                            </a>

                        </div>

                    <?php else: ?>

                        <div class="row" id="requestsList">

                            <?php foreach ($requests as $request): 

                                $image_url = $request['property_image'] ?: '../assets/images/default-property.jpg';

                                $status_color = $request['status'] == 'pending' ? 'warning' : 

                                              ($request['status'] == 'approved' ? 'success' : 'danger');

                                $status_icon = $request['status'] == 'pending' ? 'clock' : 

                                             ($request['status'] == 'approved' ? 'check-circle' : 'times-circle');

                            ?>

                                <div class="col-md-6 mb-4 request-item" data-status="<?php echo $request['status']; ?>">

                                    <div class="card border h-100">

                                        <div class="card-body">

                                            <!-- Property Image and Status -->

                                            <div class="row mb-3">

                                                <div class="col-4">

                                                    <img src="<?php echo $image_url; ?>" class="img-fluid rounded" alt="Property">

                                                </div>

                                                <div class="col-8">

                                                    <div class="d-flex justify-content-between align-items-start mb-2">

                                                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($request['property_title']); ?></h6>

                                                        <span class="badge bg-<?php echo $status_color; ?>">

                                                            <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>

                                                            <?php echo ucfirst($request['status']); ?>

                                                        </span>

                                                    </div>

                                                    <p class="text-muted small mb-2">

                                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($request['location_name']); ?>

                                                    </p>

                                                    <p class="mb-0">

                                                        <strong>ETB <?php echo number_format($request['monthly_rent'], 0); ?>/month</strong>

                                                    </p>

                                                </div>
                                            </div>

                                            <?php if ($request['status'] == 'approved' && !empty($request['agreement_id'])): ?>
                                                <div class="alert alert-info py-2 mb-3">
                                                    <div class="row align-items-center">
                                                        <div class="col-8">
                                                            <div class="small">
                                                                <strong><?php echo t('payment_status'); ?>:</strong> 
                                                                <?php if ($request['agreement_status'] == 'partially_paid'): ?>
                                                                    <span class="text-warning fw-bold"><?php echo t('partially_paid'); ?></span>
                                                                    <br><strong><?php echo t('remaining_balance'); ?>:</strong> ETB <?php echo number_format($request['balance_remaining'], 2); ?>
                                                                    <br><strong><?php echo t('deadline'); ?>:</strong> <?php echo date('M d, Y', strtotime($request['payment_deadline'])); ?>
                                                                <?php else: ?>
                                                                    <span class="text-success fw-bold"><?php echo t('fully_paid'); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 text-end">
                                                            <?php if ($request['agreement_status'] == 'partially_paid'): ?>
                                                                <button class="btn btn-primary btn-sm pay-balance-btn" 
                                                                        data-agreement-id="<?php echo $request['agreement_id']; ?>"
                                                                        data-balance="<?php echo $request['balance_remaining']; ?>"
                                                                        data-property-title="<?php echo htmlspecialchars($request['property_title']); ?>">
                                                                    <?php echo t('pay_remaining_balance'); ?>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Property Details -->

                                            <div class="row text-center mb-3">

                                                <div class="col-4">

                                                    <small class="text-muted d-block">Bedrooms</small>

                                                    <strong><?php echo $request['bedrooms']; ?></strong>

                                                </div>

                                                <div class="col-4">

                                                    <small class="text-muted d-block">Bathrooms</small>

                                                    <strong><?php echo $request['bathrooms']; ?></strong>

                                                </div>

                                                <div class="col-4">

                                                    <small class="text-muted d-block">Type</small>

                                                    <strong><?php echo ucfirst($request['property_type']); ?></strong>

                                                </div>

                                            </div>



                                            <!-- Request Details -->

                                            <div class="bg-light p-2 rounded mb-3">

                                                <div class="row">

                                                    <div class="col-md-6">

                                                        <small class="text-muted">Request Date</small>

                                                        <p class="mb-0"><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></p>

                                                    </div>

                                                    <div class="col-md-6">

                                                        <small class="text-muted">Request ID</small>

                                                        <p class="mb-0">#<?php echo str_pad($request['request_id'], 6, '0', STR_PAD_LEFT); ?></p>

                                                    </div>

                                                </div>

                                                <?php if (!empty($request['message'])): ?>

                                                    <div class="mt-2">

                                                        <small class="text-muted">Your Message</small>

                                                        <p class="mb-0 small"><?php echo htmlspecialchars($request['message']); ?></p>

                                                    </div>

                                                <?php endif; ?>

                                            </div>



                                            <!-- Owner Info & Actions -->

                                            <div class="d-flex justify-content-between align-items-center">

                                                <div class="small">

                                                    <strong>Owner:</strong> <?php echo htmlspecialchars($request['owner_name']); ?>

                                                    <?php if ($request['owner_phone']): ?>

                                                        <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['owner_phone']); ?>

                                                    <?php endif; ?>

                                                </div>

                                                <div class="btn-group btn-group-sm">

                                                    <?php if ($request['status'] == 'approved'): ?>

                                                        <a href="agreements.php" class="btn btn-outline-primary">

                                                            <i class="fas fa-file-contract"></i> View Agreement

                                                        </a>

                                                    <?php endif; ?>

                                                    <?php if ($request['status'] == 'pending'): ?>

                                                        <button class="btn btn-outline-danger" onclick="cancelRequest(<?php echo $request['request_id']; ?>)">

                                                            <i class="fas fa-times"></i> Cancel

                                                        </button>

                                                    <?php endif; ?>

                                                    <button class="btn btn-outline-secondary" onclick="viewDetails(<?php echo $request['request_id']; ?>)">

                                                        <i class="fas fa-eye"></i> Details

                                                    </button>

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



<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo t('pay_remaining_balance'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" id="agreement_id" name="agreement_id">
                <input type="hidden" name="payment_type" value="FULL">
                <input type="hidden" name="payment_method" value="wallet">
                <div class="modal-body">
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Payment Summary</h6>
                            <p class="mb-1"><strong>Property:</strong> <span id="payment_property_title"></span></p>
                            <p class="mb-1"><strong>Amount to Pay:</strong> <span class="text-success fw-bold" id="payment_amount_display">ETB 0</span></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any additional information..."></textarea>
                    </div>

                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Wallet Payment:</strong><br>
                        • Amount will be deducted from your wallet balance<br>
                        • Payment is processed immediately<br>
                        • Status will be updated to Fully Paid
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitPaymentBtn">
                        <i class="fas fa-credit-card me-2"></i>Pay Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Request Details Modal -->

<div class="modal fade" id="detailsModal" tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">Request Details</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body" id="detailsContent">

                <!-- Content will be loaded via JavaScript -->

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

            </div>

        </div>

    </div>

</div>



<?php include '../includes/footer.php'; ?>



<script>

document.addEventListener('DOMContentLoaded', function() {

    console.log('Requests page loaded');

    

    // Status filter

    const statusFilter = document.getElementById('statusFilter');

    if (statusFilter) {

        statusFilter.addEventListener('change', function() {

            const status = this.value;

            const requestItems = document.querySelectorAll('.request-item');

            

            requestItems.forEach(function(item) {

                if (status === '' || item.dataset.status === status) {

                    item.style.display = 'block';

                } else {

                    item.style.display = 'none';

                }

            });

        });

    }



    // View request details

    window.viewDetails = function(requestId) {

        const requestData = <?php echo json_encode($requests); ?>;

        const request = requestData.find(r => r.request_id == requestId);

        

        if (request) {

            const content = `

                <div class="row">

                    <div class="col-md-6">

                        <h6>Property Information</h6>

                        <p><strong>Title:</strong> ${request.property_title}</p>

                        <p><strong>Location:</strong> ${request.location_name}</p>

                        <p><strong>Monthly Rent:</strong> ETB ${Number(request.monthly_rent).toLocaleString()}</p>

                        <p><strong>Bedrooms:</strong> ${request.bedrooms}</p>

                        <p><strong>Bathrooms:</strong> ${request.bathrooms}</p>

                        <p><strong>Type:</strong> ${request.property_type}</p>

                        <p><strong>Furnished:</strong> ${request.is_furnished ? 'Yes' : 'No'}</p>

                    </div>

                    <div class="col-md-6">

                        <h6>Request Information</h6>

                        <p><strong>Request ID:</strong> #${String(request.request_id).padStart(6, '0')}</p>

                        <p><strong>Status:</strong> <span class="badge bg-${request.status === 'pending' ? 'warning' : request.status === 'approved' ? 'success' : 'danger'}">${request.status}</span></p>

                        <p><strong>Submitted:</strong> ${new Date(request.created_at).toLocaleString()}</p>

                        <h6>Owner Information</h6>

                        <p><strong>Name:</strong> ${request.owner_name}</p>

                        <p><strong>Phone:</strong> ${request.owner_phone || 'N/A'}</p>

                        <p><strong>Email:</strong> ${request.owner_email || 'N/A'}</p>

                    </div>

                </div>

                ${request.message ? `

                    <div class="mt-3">

                        <h6>Your Message</h6>

                        <div class="bg-light p-3 rounded">

                            ${request.message}

                        </div>

                    </div>

                ` : ''}

                ${request.admin_response ? `

                    <div class="mt-3">

                        <h6>Response</h6>

                        <div class="bg-light p-3 rounded">

                            ${request.admin_response}

                        </div>

                    </div>

                ` : ''}

            `;

            

            const detailsContent = document.getElementById('detailsContent');

            if (detailsContent) {

                detailsContent.innerHTML = content;

            }

            

            // Show modal manually

            const modal = document.getElementById('detailsModal');

            if (modal) {

                modal.style.display = 'block';

                modal.classList.add('show');

                document.body.classList.add('modal-open');

            }

        }

    };



    // Cancel request

    window.cancelRequest = function(requestId) {

        console.log('Cancel request called for ID:', requestId);

        

        if (confirm('Are you sure you want to cancel this rental request?')) {

            console.log('User confirmed cancellation');

            

            const formData = new FormData();

            formData.append('request_id', requestId);

            

            console.log('Sending request to API...');

            

            fetch('../api/cancel-request.php', {

                method: 'POST',

                body: formData

            })

            .then(response => {

                console.log('Response received:', response);

                return response.json();

            })

            .then(data => {

                console.log('Response data:', data);

                if (data.success) {

                    alert('Request cancelled successfully!');

                    console.log('Reloading page...');

                    location.reload();

                } else {

                    console.error('Cancellation failed:', data.message);

                    alert('Error: ' + data.message);

                }

            })

            .catch(error => {

                console.error('Network error:', error);

                alert('Error cancelling request. Please try again.');

            });

        } else {

            console.log('User cancelled the operation');

        }

    };

    

    // Close modal functionality

    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');

    closeButtons.forEach(function(button) {

        button.addEventListener('click', function() {

            const modal = document.getElementById('detailsModal');

            if (modal) {

                modal.style.display = 'none';

                modal.classList.remove('show');

                document.body.classList.remove('modal-open');

            }

        });

    });

    // Pay Remaining Balance
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const paymentForm = document.getElementById('paymentForm');

    document.querySelectorAll('.pay-balance-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const agreementId = this.dataset.agreementId;
            const balance = this.dataset.balance;
            const propertyTitle = this.dataset.propertyTitle;

            document.getElementById('agreement_id').value = agreementId;
            document.getElementById('payment_property_title').textContent = propertyTitle;
            document.getElementById('payment_amount_display').textContent = 'ETB ' + Number(balance).toLocaleString();
            
            paymentModal.show();
        });
    });

    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitPaymentBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            const formData = new FormData(this);

            fetch('../api/process-payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment successful! Your agreement is now fully paid and active.');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay Now';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your payment.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay Now';
            });
        });
    }

});

</script>