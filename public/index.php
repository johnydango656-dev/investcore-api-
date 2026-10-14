<?php
// index.php - Payment Gateway UI
$transactionId = $_GET['tx'] ?? 'PAY-' . rand(1000000000, 9999999999);
$amount = $_GET['amount'] ?? '300.00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment Gateway</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; padding: 24px; text-align: center; }
        .header { background: #007DF4; color: white; padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; }
        .btn { background: #007DF4; color: white; border: none; width: 100%; padding: 14px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .qr-container { margin: 20px 0; border: 1px solid #eee; border-radius: 12px; padding: 10px; }
        .qr-container img { width: 100%; max-width: 220px; height: auto; border-radius: 8px; }
        .details { background: #f8f9fa; border-radius: 8px; padding: 12px; text-align: left; font-size: 14px; margin-bottom: 15px; }
        .details div { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .total { font-weight: bold; font-size: 16px; border-top: 1px solid #ddd; padding-top: 6px; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">GCash Secure Payment</div>
        
        <!-- Smart GCash Button -->
        <a href="gcash://" class="btn">Open in GCash</a>
        
        <div style="color: #666; font-size: 13px; margin-bottom: 15px;">or Scan/Upload this QR code in GCash App</div>
        
        <div class="qr-container">
            <img src="qr.jpg" alt="InstaPay Payment QR">
        </div>

        <div class="details">
            <div><span>Transaction ID</span> <span><?php echo htmlspecialchars($transactionId); ?></span></div>
            <div><span>Amount Due</span> <span>₱<?php echo htmlspecialchars($amount); ?></span></div>
            <div class="total"><span>Total Amount</span> <span style="color: #007DF4;">₱<?php echo htmlspecialchars($amount); ?></span></div>
        </div>

        <a href="/dashboard.php" class="btn" style="background: #e2e8f0; color: #333; margin-bottom: 0;">Tapos Na / Balik sa Dashboard</a>
    </div>
</body>
</html>
