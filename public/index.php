    <?php elseif (isset($_GET['route']) && $_GET['route'] === 'gateway'): ?>
        <!-- CLOUD GCEEPAY GATEWAY PAYMENT SCREEN -->
        <?>
            $gatewayAmt = $_GET['amount'] ?? 300;
            $totalPayAmt = $gatewayAmt + 4.99; 
            $gatewayMethod = $_GET['method'] ?? 'GCash';
            $txnId = $_GET['txn'] ?? 'PAY-TEST';
            $subStep = $_GET['substep'] ?? '1';
        ?>
        <div style="background: #f1f5f9; min-height: 100vh; margin: -15px; padding-bottom: 50px; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            
            <!-- Cloud Gateway Top Bar -->
            <div style="background: #1b6ef3; padding: 15px; color: #fff; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="font-size: 18px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <span>🔒</span> <?= htmlspecialchars($gatewayMethod) ?> Secure Checkout
                </div>
                <div style="font-size: 10px; opacity: 0.8; margin-top: 2px;">cloud.gceepay.com</div>
            </div>

            <?php if ($subStep === '1'): ?>
                <!-- MAIN GATEWAY PAGE -->
                <div style="max-width: 440px; margin: 20px auto; background: #ffffff; border-radius: 12px; padding: 25px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <p style="text-align: center; color: #64748b; font-size: 13px; margin-bottom: 20px; font-weight: 500;">
                        Securely complete the payment with your <?= htmlspecialchars($gatewayMethod) ?> Account
                    </p>

                    <!-- THIS GOES DIRECTLY TO THE GCASH IN-GATEWAY LOGIN / PAYMENT CONFIRMATION SCREEN -->
                    <a href="?route=gateway&txn=<?= $txnId ?>&amount=<?= $gatewayAmt ?>&method=<?= $gatewayMethod ?>&substep=2" style="display: block; background: #1b6ef3; color: #fff; text-align: center; padding: 16px; border-radius: 30px; font-weight: bold; font-size: 15px; text-decoration: none; box-shadow: 0 4px 12px rgba(27,110,243,0.3); margin-bottom: 25px;">
                        Open in <?= htmlspecialchars($gatewayMethod) ?> Login
                    </a>

                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="background: #fff; padding: 12px; display: inline-block; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode('https://cloud.gceepay.com/pay?amount='.$gatewayAmt) ?>" alt="Payment QR" style="display: block;">
                        </div>
                    </div>

                    <div style="background: #f8fafc; border-radius: 10px; padding: 15px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 8px;">
                            <span>Amount Due</span>
                            <span style="font-weight: 600; color: #1e293b;">₱<?= number_format($gatewayAmt, 2) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 12px;">
                            <span>Gateway Fee</span>
                            <span style="font-weight: 600; color: #1e293b;">₱4.99</span>
                        </div>
                        <hr style="border: none; border-top: 1px solid #e2e8f0; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #0f172a;">
                            <span>Total Amount</span>
                            <span style="color: #059669;">₱<?= number_format($totalPayAmt, 2) ?></span>
                        </div>
                    </div>

                    <a href="/" style="display: block; text-align: center; background: #e2e8f0; color: #334155; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; text-decoration: none;">
                        Cancel / Back to Dashboard
                    </a>
                </div>

            <?php elseif ($subStep === '2'): ?>
                <!-- SIMULATED GCASH IN-GATEWAY LOGIN / PAYMENT CONFIRMATION SCREEN -->
                <div style="background: #1b6ef3; padding: 25px 15px; text-align: center; color: #fff;">
                    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Merchant Transaction ID</div>
                    <div style="font-size: 22px; font-weight: bold; letter-spacing: 1px;"><?= htmlspecialchars($txnId) ?></div>
                </div>

                <div style="max-width: 440px; margin: 20px auto; background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <div style="font-size: 12px; font-weight: 800; color: #1b6ef3; margin-bottom: 12px; text-transform: uppercase;">PAY WITH</div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <div style="font-weight: bold; font-size: 15px; color: #0f172a;"><?= htmlspecialchars($gatewayMethod) ?> Balance</div>
                            <div style="font-size: 11px; color: #16a34a;">Ready to pay</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a;">PHP 1,500.00</div>
                            <div style="font-size: 11px; color: #64748b;">Available</div>
                        </div>
                    </div>

                    <div style="font-size: 12px; font-weight: 800; color: #1b6ef3; margin: 20px 0 10px 0; text-transform: uppercase;">PAYMENT SUMMARY</div>

                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #475569; margin-bottom: 10px;">
                        <span>Total Due</span>
                        <span style="font-weight: bold; color: #0f172a;">PHP <?= number_format($totalPayAmt, 2) ?></span>
                    </div>

                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 15px 0;">

                    <!-- PAY BUTTON THAT COMPLETES THE CLOUD TRANSACTION -->
                    <a href="/" onclick="alert('Payment Successful via cloud.gceepay.com! Awaiting Admin Approval.');" style="display: block; background: #1b6ef3; color: #ffffff; text-align: center; padding: 16px; border-radius: 30px; font-weight: bold; font-size: 15px; text-decoration: none; box-shadow: 0 4px 12px rgba(27,110,243,0.3);">
                        PAY PHP <?= number_format($totalPayAmt, 2) ?>
                    </a>
                    
                    <a href="?route=gateway&txn=<?= $txnId ?>&amount=<?= $gatewayAmt ?>&method=<?= $gatewayMethod ?>&substep=1" style="display: block; text-align: center; color: #64748b; font-size: 12px; margin-top: 15px; text-decoration: none;">
                        ← Back to QR & Options
                    </a>
                </div>
            <?php endif; ?>

            <!-- TIMEOUT BADGE -->
            <div style="position: fixed; bottom: 20px; right: 20px; background: #1b6ef3; color: #ffffff; padding: 10px 18px; border-radius: 20px; font-weight: bold; font-size: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999;">
                Timeout : <span id="timerCountdown">15:00</span>
            </div>
        </div>

        <script>
            var timeLeft = 15 * 60; 
            var timerElement = document.getElementById('timerCountdown');
            var interval = setInterval(function() {
                var m = Math.floor(timeLeft / 60);
                var s = timeLeft % 60;
                if (s < 10) s = '0' + s;
                if (m < 10) m = '0' + m;
                timerElement.textContent = m + ':' + s;
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    window.location.href = '/';
                }
                timeLeft--;
            }, 1000);
        </script>
    <?php endif; ?>
