    <?php elseif (isset($_GET['route']) && $_GET['route'] === 'gateway'): ?>
        <!-- SIMULATED SECURE PAYMENT GATEWAY PAGE WITH TIMEOUT -->
        <?php 
            $gatewayAmt = $_GET['amount'] ?? 300;
            $gatewayMethod = $_GET['method'] ?? 'GCash';
            $txnId = $_GET['txn'] ?? 'PAY-TEST';
        ?>
        <div style="text-align: center; padding-top: 20px; position: relative; min-height: 85vh;">
            <div style="background: #2563eb; padding: 15px; border-radius: 10px 10px 0 0; font-size: 18px; font-weight: bold; color: #fff;">
                <?= htmlspecialchars($gatewayMethod) ?> Secure Checkout
            </div>
            <div class="card" style="border-radius: 0 0 10px 10px; padding: 25px 15px; background: #ffffff; color: #000;">
                <p style="color: #64748b; font-size: 13px; margin-bottom: 5px;">Total Payment Amount</p>
                <h1 style="color: #059669; font-size: 36px; margin-top: 0; font-weight: 800;">₱<?= number_format($gatewayAmt, 2) ?></h1>
                
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #e2e8f0;">
                    <p style="font-size: 12px; color: #64748b; margin: 0 0 10px 0;">I-scan ang QR o i-click ang button sa ibaba para magbayad nang secure:</p>
                    <div style="background: #fff; padding: 10px; display: inline-block; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode('https://gcash.com/pay?amount='.$gatewayAmt) ?>" alt="Payment QR" style="display: block;">
                    </div>
                </div>

                <a href="https://m.gcash.com" target="_blank" class="btn" style="background: #2563eb; color: #fff; margin-bottom: 10px; border-radius: 8px;">Open in <?= htmlspecialchars($gatewayMethod) ?> App</a>
                <a href="/" class="btn" style="background: #e2e8f0; color: #334155; margin-bottom: 30px;">Tapos Na / Balik sa Dashboard</a>

                <!-- COUNTDOWN TIMER FLOATING BUTTON (KATULAD NG SA ORIGINAL SCREENSHOT) -->
                <div id="paymentTimeoutBadge" style="position: fixed; bottom: 20px; right: 20px; background: #2563eb; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-weight: bold; font-size: 13px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999;">
                    Payment Timeout : <span id="timerCountdown">15:00</span>
                </div>
            </div>
        </div>

        <script>
            // 15 Minutes Countdown Timer
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
