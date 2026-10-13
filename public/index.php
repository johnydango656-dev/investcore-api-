    <?php elseif (isset($_GET['route']) && $_GET['route'] === 'gateway'): ?>
        <?php 
            $gatewayAmt = $_GET['amount'] ?? 300;
            $totalPayAmt = $gatewayAmt + 4.99; 
            $gatewayMethod = $_GET['method'] ?? 'GCash';
            $txnId = $_GET['txn'] ?? 'PAY-TEST';
            $step = $_GET['step'] ?? '1';
        ?>
        <div style="background: #f1f5f9; min-height: 100vh; margin: -15px; padding-bottom: 50px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            
            <?php if ($step === '1'): ?>
                <!-- STEP 1: INITIAL CHECKOUT / QR SCREEN -->
                <div style="background: #1b6ef3; padding: 20px 15px; color: #fff; text-align: center;">
                    <div style="font-size: 20px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span style="background: #fff; color: #1b6ef3; padding: 2px 8px; border-radius: 4px; font-size: 14px;">G</span> <?= htmlspecialchars($gatewayMethod) ?>
                    </div>
                </div>

                <div style="max-width: 440px; margin: 20px auto; background: #ffffff; border-radius: 12px; padding: 25px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); color: #1e293b;">
                    <p style="text-align: center; color: #64748b; font-size: 13px; margin-bottom: 20px; font-weight: 500;">
                        Securely complete the payment with your <?= htmlspecialchars($gatewayMethod) ?> App
                    </p>

                    <!-- CLICKING THIS GOES DIRECTLY TO STEP 2 (SIMULATED GCASH PAYMENT PAGE) -->
                    <a href="?route=gateway&txn=<?= $txnId ?>&amount=<?= $gatewayAmt ?>&method=<?= $gatewayMethod ?>&step=2" style="display: block; background: #1b6ef3; color: #fff; text-align: center; padding: 16px; border-radius: 30px; font-weight: bold; font-size: 15px; text-decoration: none; box-shadow: 0 4px 12px rgba(27,110,243,0.3); margin-bottom: 25px;">
                        Open in <?= htmlspecialchars($gatewayMethod) ?>
                    </a>

                    <div style="text-align: center; color: #64748b; font-size: 12px; margin-bottom: 25px;">
                        or Login to <?= htmlspecialchars($gatewayMethod) ?> and scan this QR with the QR Scanner
                    </div>

                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="background: #fff; padding: 12px; display: inline-block; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode('https://gcash.com/pay?amount='.$gatewayAmt) ?>" alt="Payment QR" style="display: block;">
                        </div>
                    </div>

                    <a href="/" style="display: block; text-align: center; background: #e2e8f0; color: #334155; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; text-decoration: none;">
                        Cancel Payment
                    </a>
                </div>

            <?php elseif ($step === '2'): ?>
                <!-- STEP 2: EXACT GCASH PAYMENT CONFIRMATION SCREEN (PARAS IN YOUR SCREENSHOT) -->
                <div style="background: #1b6ef3; padding: 15px; color: #fff; text-align: center; position: relative;">
                    <a href="?route=gateway&txn=<?= $txnId ?>&amount=<?= $gatewayAmt ?>&method=<?= $gatewayMethod ?>&step=1" style="position: absolute; left: 15px; top: 15px; color: #fff; text-decoration: none; font-size: 18px; font-weight: bold;">←</a>
                    <div style="font-size: 18px; font-weight: bold;">Payment</div>
                </div>

                <div style="background: #1b6ef3; padding: 30px 15px 40px 15px; text-align: center; color: #fff;">
                    <div style="width: 60px; height: 60px; background: #e2e8f0; border-radius: 50%; margin: 0 auto 10px auto; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 22px; font-weight: bold;">
                        1
                    </div>
                    <div style="font-size: 20px; font-weight: 700; letter-spacing: 1px;">173488510</div>
                </div>

                <div style="max-width: 480px; margin: 0 auto; background: #ffffff; padding: 20px; color: #1e293b;">
                    <div style="font-size: 12px; font-weight: 800; color: #1b6ef3; margin-bottom: 12px; text-transform: uppercase;">PAY WITH</div>

                    <!-- GCash Balance Option -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <div style="font-weight: bold; font-size: 15px; color: #0f172a;">GCash</div>
                            <div style="font-size: 11px; color: #ef4444; margin-top: 2px;">Pay now</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 14px; font-weight: bold; color: #0f172a;">PHP 1,500.00</div>
                            <div style="font-size: 11px; color: #64748b;">Available Balance</div>
                        </div>
                    </div>

                    <!-- Card Option -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <div style="font-weight: bold; font-size: 14px; color: #0f172a;">Card</div>
                            <div style="font-size: 11px; color: #1b6ef3;">Pay with debit or credit</div>
                        </div>
                        <div style="color: #1b6ef3; font-weight: bold; font-size: 14px;">+ Add</div>
                    </div>

                    <div style="font-size: 12px; font-weight: 800; color: #1b6ef3; margin: 25px 0 15px 0; text-transform: uppercase;">YOU ARE ABOUT TO PAY</div>

                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #475569; margin-bottom: 10px;">
                        <span>Amount Due</span>
                        <span style="font-weight: 600; color: #0f172a;">PHP <?= number_format($totalPayAmt, 2) ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: #475569; margin-bottom: 20px;">
                        <span>Discount</span>
                        <span style="color: #64748b;">No available voucher ›</span>
                    </div>

                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin-bottom: 15px;">

                    <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 30px;">
                        <span>Total Amount</span>
                        <span>PHP <?= number_format($totalPayAmt, 2) ?></span>
                    </div>

                    <p style="text-align: center; font-size: 12px; color: #64748b; margin-bottom: 15px;">
                        Please review to ensure that the details are correct before you proceed.
                    </p>

                    <!-- PAY BUTTON THAT FINISHES PAYMENT AND DIRECTS BACK TO DASHBOARD -->
                    <a href="/" onclick="alert('Payment Submitted! Awaiting Admin Approval.')" style="display: block; background: #1b6ef3; color: #ffffff; text-align: center; padding: 16px; border-radius: 30px; font-weight: bold; font-size: 15px; text-decoration: none; box-shadow: 0 4px 12px rgba(27,110,243,0.3);">
                        PAY PHP <?= number_format($totalPayAmt, 2) ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- COUNTDOWN TIMER FLOATING BADGE -->
            <div id="paymentTimeoutBadge" style="position: fixed; bottom: 20px; right: 20px; background: #1b6ef3; color: #ffffff; padding: 10px 18px; border-radius: 20px; font-weight: bold; font-size: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999;">
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
