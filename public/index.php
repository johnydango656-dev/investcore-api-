<?php
session_start();

// Configuration
$paymongoSecretKey = 'sk_live_vWpTVPFntQuGqMqGTi5JiDtc';
$dataFile = __DIR__ . '/../data/system_data.json';

// Utility functions for JSON storage
function loadData($filePath) {
    if (!file_exists($filePath)) {
        $initialData = [
            'users' => [],
            'subscriptions' => [],
            'transactions' => []
        ];
        file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));
        return $initialData;
    }
    $content = file_get_contents($filePath);
    return json_decode($content, true) ?? ['users' => [], 'subscriptions' => [], 'transactions' => []];
}

function saveData($filePath, $data) {
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

$db = loadData($dataFile);

// Helper function to call PayMongo API
function createPaymongoCheckoutSession($amount, $description, $secretKey) {
    $url = 'https://api.paymongo.com/v1/checkout_sessions';
    
    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => [
                    [
                        'currency' => 'PHP',
                        'amount' => intval($amount * 100), // Convert PHP to centavos
                        'description' => $description,
                        'name' => 'Investment Plan Subscription',
                        'quantity' => 1
                    ]
                ],
                'payment_method_types' => ['gcash', 'paymaya', 'card', 'dob'],
                'description' => $description,
                'send_email_receipt' => false,
                'show_description' => true,
                'show_line_items' => true,
                'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/?status=success',
                'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/?status=cancelled'
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($secretKey . ':')
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['error' => $err];
    }

    return json_decode($response, true);
}

// Action: Initiate Deposit / Checkout Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $amount = floatval($_POST['amount'] ?? 0);
    $planName = $_POST['plan_name'] ?? 'Standard Plan';

    if ($amount < 100) {
        $error = "Ang minimum deposit ay ₱100.";
    } else {
        $result = createPaymongoCheckoutSession($amount, "Subscription: $planName", $paymongoSecretKey);

        if (isset($result['data']['attributes']['checkout_url'])) {
            $checkoutUrl = $result['data']['attributes']['checkout_url'];
            
            // Record pending transaction
            $db['transactions'][] = [
                'id' => 'TXN-' . time(),
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveData($dataFile, $db);

            // Redirect user directly to PayMongo payment page
            header("Location: " . $checkoutUrl);
            exit();
        } else {
            $apiError = $result['errors'][0]['detail'] ?? 'Hindi maikonekta sa PayMongo gateway. Subukan muli.';
            $error = "PayMongo API Error: " . $apiError;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Platform</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #1e293b; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        h2 { text-align: center; color: #38bdf8; }
        .card { background: #334155; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .btn { display: block; width: 100%; padding: 12px; background: #22c55e; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; }
        .btn:hover { background: #16a34a; }
        input[type="number"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; box-sizing: border-box; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .alert-error { background: #ef4444; color: white; }
        .alert-success { background: #22c55e; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h2>Investment Dashboard</h2>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert alert-success">Salamat! Nareceived ang iyong deposit request. Ilalagay ang pondo sa iyong balance matapos ma-verify.</div>
    <?php endif; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'cancelled'): ?>
        <div class="alert alert-error">Nacancel ang iyong payment request.</div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Mag-subscribe sa Plan</h3>
        <p>15-Day Cycle Platform</p>
        <form method="POST">
            <input type="hidden" name="action" value="deposit">
            <input type="hidden" name="plan_name" value="15-Day Growth Plan">
            <label for="amount">Deposit Amount (PHP):</label>
            <input type="number" id="amount" name="amount" min="100" value="500" required>
            <button type="submit" class="btn">Mag-deposit sa GCash / Maya</button>
        </form>
    </div>
</div>

</body>
</html>
