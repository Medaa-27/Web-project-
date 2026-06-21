<?php
require_once __DIR__ . '/../includes/config.php';

$sessionId = $_GET['session_id'] ?? null;
if (!$sessionId || !isset($_SESSION['payment_sim_' . $sessionId])) {
    die("Invalid or expired payment session.");
}

$sessionData = $_SESSION['payment_sim_' . $sessionId];

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $status = ($action === 'success') ? 'completed' : 'failed';
    $transactionId = 'SIM_' . time() . '_' . rand(1000, 9999);
    
    // Construct callback data
    $callbackData = [
        'reference' => $sessionData['reference'],
        'transaction_id' => $transactionId,
        'status' => $status,
        'amount' => $sessionData['amount']
    ];
    
    // In a real scenario, this would be a webhook/S2S call.
    // For simulation, we'll redirect the user with the callback data.
    $query = http_build_query($callbackData);
    $separator = strpos($sessionData['returnUrl'], '?') !== false ? '&' : '?';
    $returnUrl = $sessionData['returnUrl'] . $separator . $query;
    
    // Clear session
    unset($_SESSION['payment_sim_' . $sessionId]);
    
    header("Location: " . $returnUrl);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Simulation Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .payment-box {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .gateway-logo {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="payment-box">
        <div class="gateway-logo">
            💳 Payment Simulator Gateway
        </div>
        
        <div class="alert alert-info">
            This is a simulation page for testing payment integrations. No real money will be deducted.
        </div>
        
        <table class="table table-bordered mt-4">
            <tbody>
                <tr>
                    <th>Customer Name</th>
                    <td><?= htmlspecialchars($sessionData['customer']['name']) ?></td>
                </tr>
                <tr>
                    <th>Account / Phone</th>
                    <td><?= htmlspecialchars($sessionData['customer']['phone']) ?></td>
                </tr>
                <tr>
                    <th>Amount to Pay</th>
                    <td class="text-success fw-bold"><?= number_format($sessionData['amount'], 2) ?> <?= htmlspecialchars($sessionData['currency']) ?></td>
                </tr>
                <tr>
                    <th>Reference</th>
                    <td><small class="text-muted"><?= htmlspecialchars($sessionData['reference']) ?></small></td>
                </tr>
            </tbody>
        </table>

        <form method="POST" class="mt-4 text-center">
            <button type="submit" name="action" value="success" class="btn btn-success btn-lg w-100 mb-3">
                Successful Deposit
            </button>
            <button type="submit" name="action" value="failure" class="btn btn-danger btn-lg w-100">
                 Canceled Deposit
            </button>
        </form>
    </div>
</div>

</body>
</html>
