<?php
require_once '../includes/config.php';

$session->requireRole('tenant');

$agreement_id = $_GET['id'] ?? 0;

if (!is_numeric($agreement_id) || $agreement_id <= 0) {
    header('Location: agreements.php');
    exit;
}

// Get agreement details with tenant verification
$sql = "SELECT ra.*, p.title, p.description, p.monthly_rent, p.security_deposit,
               p.bedrooms, p.bathrooms, p.area_sqm, p.property_type, p.is_furnished,
               l.location_name, l.subcity, u.full_name as owner_name, u.phone as owner_phone,
               u.email as owner_email, t.full_name as tenant_name, t.phone as tenant_phone,
               t.email as tenant_email
        FROM rental_agreements ra
        JOIN properties p ON ra.property_id = p.property_id
        JOIN locations l ON p.location_id = l.location_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN users t ON ra.tenant_id = t.user_id
        WHERE ra.agreement_id = ? AND ra.tenant_id = ?";
$stmt = $db->prepare($sql);
$agreement = $db->getSingle($stmt, [$agreement_id, $session->getUserId()]);

if (!$agreement) {
    header('Location: agreements.php');
    exit;
}

// Set headers for HTML download
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="rental-agreement-' . $agreement['agreement_id'] . '.html"');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rental Agreement - <?php echo htmlspecialchars($agreement['title']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .two-column {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .column {
            width: 48%;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .signature-section {
            margin-top: 50px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            margin-right: 5%;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin: 30px 0 10px 0;
            height: 50px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body { margin: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">RENTAL AGREEMENT</div>
        <div>Agreement ID: #<?php echo str_pad($agreement['agreement_id'], 6, '0', STR_PAD_LEFT); ?></div>
        <div>Date: <?php echo date('F j, Y', strtotime($agreement['created_at'])); ?></div>
    </div>

    <div class="section">
        <div class="section-title">PROPERTY DETAILS</div>
        <div class="info-row">
            <span class="label">Property Title:</span> <?php echo htmlspecialchars($agreement['title']); ?>
        </div>
        <div class="info-row">
            <span class="label">Location:</span> <?php echo htmlspecialchars($agreement['location_name']); ?>, <?php echo htmlspecialchars($agreement['subcity']); ?>
        </div>
        <div class="info-row">
            <span class="label">Type:</span> <?php echo ucfirst($agreement['property_type']); ?>
        </div>
        <div class="info-row">
            <span class="label">Bedrooms:</span> <?php echo $agreement['bedrooms']; ?>
        </div>
        <div class="info-row">
            <span class="label">Bathrooms:</span> <?php echo $agreement['bathrooms']; ?>
        </div>
        <div class="info-row">
            <span class="label">Area:</span> <?php echo $agreement['area_sqm']; ?> sqm
        </div>
        <div class="info-row">
            <span class="label">Furnished:</span> <?php echo $agreement['is_furnished'] ? 'Yes' : 'No'; ?>
        </div>
        <?php if ($agreement['description']): ?>
        <div class="info-row">
            <span class="label">Description:</span> <?php echo nl2br(htmlspecialchars($agreement['description'])); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-title">FINANCIAL TERMS</div>
        <div class="info-row">
            <span class="label">Monthly Rent:</span> ETB <?php echo number_format($agreement['monthly_rent'], 0); ?>
        </div>
        <div class="info-row">
            <span class="label">Security Deposit:</span> ETB <?php echo number_format($agreement['security_deposit'], 0); ?>
        </div>
        <div class="info-row">
            <span class="label">Advance Payment:</span> ETB <?php echo number_format($agreement['advance_payment'], 0); ?>
        </div>
    </div>

    <div class="section">
        <div class="section-title">AGREEMENT PERIOD</div>
        <div class="info-row">
            <span class="label">Start Date:</span> <?php echo date('F j, Y', strtotime($agreement['start_date'])); ?>
        </div>
        <div class="info-row">
            <span class="label">End Date:</span> <?php echo date('F j, Y', strtotime($agreement['end_date'])); ?>
        </div>
        <div class="info-row">
            <span class="label">Duration:</span> <?php 
            $start = new DateTime($agreement['start_date']);
            $end = new DateTime($agreement['end_date']);
            $duration = $start->diff($end);
            echo $duration->m + ($duration->y * 12); ?> months
        </div>
    </div>

    <div class="section">
        <div class="section-title">PARTIES INVOLVED</div>
        <div class="two-column">
            <div class="column">
                <h4>PROPERTY OWNER</h4>
                <div class="info-row">
                    <span class="label">Name:</span> <?php echo htmlspecialchars($agreement['owner_name']); ?>
                </div>
                <div class="info-row">
                    <span class="label">Phone:</span> <?php echo htmlspecialchars($agreement['owner_phone']); ?>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span> <?php echo htmlspecialchars($agreement['owner_email']); ?>
                </div>
            </div>
            <div class="column">
                <h4>TENANT</h4>
                <div class="info-row">
                    <span class="label">Name:</span> <?php echo htmlspecialchars($agreement['tenant_name']); ?>
                </div>
                <div class="info-row">
                    <span class="label">Phone:</span> <?php echo htmlspecialchars($agreement['tenant_phone']); ?>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span> <?php echo htmlspecialchars($agreement['tenant_email']); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">TERMS AND CONDITIONS</div>
        <ol>
            <li>The tenant agrees to pay the monthly rent on or before the 5th day of each month.</li>
            <li>The security deposit will be refunded at the end of the agreement period, subject to property condition.</li>
            <li>The tenant shall maintain the property in good condition and is responsible for any damages.</li>
            <li>The property shall be used for residential purposes only.</li>
            <li>The owner has the right to inspect the property with reasonable notice.</li>
            <li>Either party may terminate this agreement with one month written notice.</li>
            <li>This agreement is governed by the laws of Ethiopia.</li>
        </ol>
    </div>

    <div class="signature-section">
        <div class="section-title">SIGNATURES</div>
        <div class="two-column">
            <div class="signature-box">
                <div class="info-row">
                    <span class="label">Owner:</span> <?php echo htmlspecialchars($agreement['owner_name']); ?>
                </div>
                <div class="signature-line"></div>
                <div>Signature & Date</div>
            </div>
            <div class="signature-box">
                <div class="info-row">
                    <span class="label">Tenant:</span> <?php echo htmlspecialchars($agreement['tenant_name']); ?>
                </div>
                <div class="signature-line"></div>
                <div>Signature & Date</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This is a legally binding rental agreement. Please keep a copy for your records.</p>
        <p>Generated on <?php echo date('F j, Y g:i A'); ?> | Aksum Rental System</p>
        <p class="no-print"><strong>Instructions:</strong> Use your browser's "Print to PDF" function to save this as a PDF file.</p>
    </div>
</body>
</html>
