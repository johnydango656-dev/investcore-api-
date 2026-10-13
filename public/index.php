    <?php elseif (isset($_GET['route']) && $_GET['route'] === 'gateway'): ?>
        <!-- PRO GCASH / MAYA SECURE CHECKOUT GATEWAY -->
        <?>
            $gatewayAmt = $_GET['amount'] ?? 300;
            // Magdagdag ng kaunting simulation fee o tax tulad sa totoong gateway kung gusto mo, o exact amount:
            $totalPayAmt = $gatewayAmt + 4.99; 
            $gatewayMethod = $_GET['method'] ?? 'GCash';
            $txnId = $_GET['txn'] ?? 'PAY-TEST';
        ?>
        <div style="background: #f1f5f9; min-height: 100vh; margin: -15px; padding-bottom: 50px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <!-- Blue Top Header -->
            <div style="background: #1b6ef3; padding: 20px 15px; color: #fff; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="font-size: 20px; font-weight: 800; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span style="background: #fff; color: #1b6ef3; padding: 2px 8px; border-radius: 4px; font-size: 14px;">G</span> <?= htmlspecialchars($gatewayMethod) ?>
                </div>
            </div>

            <div style="max-width: 440px; margin: 20px auto; background: #ffffff; border-radius: 12px; padding: 25px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); color: #1e293b;">
                <p style="text-align: center; color: #64748b; font-size: 13px; margin-bottom: 20px; font-weight: 500;">
                    Securely complete the payment with your <?= htmlspecialchars($gatewayMethod) ?> App
                </p>

                <!-- Open in App Button -->
                <a href="https://m.gcash.com" target="_blank" style="display: block; background: #1b6ef3; color: #fff; text-align: center; padding: 16px; border-radius: 30px; font-weight: bold; font-size: 15px; text-decoration: none; box-shadow: 0 4px 12px rgba(27,110,243,0.3); margin-bottom: 25px;">
                    Open in <?= htmlspecialchars($gatewayMethod) ?>
                </a>

                <div style="text-align: center; color: #64748b; font-size: 12px; margin-bottom: 25px; position: relative;">
                    <span style="background: #fff; padding: 0 10px; position: relative; z-index: 1;">or Login to <?= htmlspecialchars($gatewayMethod) ?> and scan this QR with the QR Scanner</span>
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin-top: -8px;">
                </div>

                <!-- QR Code Box -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="background: #fff; padding: 12px; display: inline-block; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode('https://gcash.com/pay?amount='.$gatewayAmt) ?>" alt="Payment QR" style="display: block; border-radius: 6px;">
                    </div>
                </div>

                <!-- Payment Breakdown Box -->
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
                    Tapos Na / Balik sa Dashboard
                </a>
            </div>

            <!-- COUNTDOWN TIMER FLOATING BADGE -->
            <div id="paymentTimeoutBadge" style="position: fixed; bottom: 20px; right: 20px; background: #1b6ef3; color: #ffffff; padding: 12px 20px; border-radius: 30px; font-weight: bold; font-size: 13px; box-shadow: 0 4px 15px rgba(27,110,243,0.4); z-index: 9999;">
                Payment Timeout : <span id="timerCountdown">15:00</span>
            </div>
        </div>

        <script>
            var timeLeft = 15 * 60; 
            var timerElement = document.getElementById('timerCountdown');

            var countdownInterval = setInterval(function() {
                var minutes = Math.floor(timeLeft / 60);
                var seconds = timeLeft % 60;

                if (seconds < 10) { seconds = '0' + seconds; }
                if (minutes < 10) { minutes = '0' + minutes; }

                timerElement.textContent = minutes + ':' + seconds;

                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    alert('Nalampasan na ang 15-minute time limit. Mangyaring subukang muli.');
                    window.location.href = '/';
                }
                timeLeft--;
            }, 1000);
        </script>
    <?php endif; ?>
