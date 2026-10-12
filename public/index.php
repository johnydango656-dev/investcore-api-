<?php
session_start();

// Configuration
$paymongoSecretKey = 'sk_live_vWpTVPFntQuGqMqGTi5JiDtc';
$dataFile = __DIR__ . '/../data/system_data.json';

// Utility functions for JSON storage (Auto-creates folder if missing)
function loadData($filePath) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
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
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

$db = loadData($dataFile);

// Plans definition
$plans = [
    'Starter Plan' => ['price' => 500, 'return_daily' => 62, 'days' => 15],
    'Growth Plan' => ['price' => 2500, 'return_daily' => 320, 'days' => 15],
    'Pro Plan' => ['price' => 10000, 'return_daily' => 1350, 'days' => 15]
];

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
                        'name' => 'Investment Platform Payment',
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

// Action: Handle Deposit / Top-up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $amount = floatval($_POST['deposit_amount'] ?? 0);

    if ($amount < 100) {
        $error = "Ang minimum deposit ay ₱100.";
    } else {
        $result = createPaymongoCheckoutSession($amount, "Wallet Deposit / Top-up", $paymongoSecretKey);

        if (isset($result['data']['attributes']['checkout_url'])) {
            $checkoutUrl = $result['data']['attributes']['checkout_url'];
            
            $db['transactions'][] = [
                'id' => 'TXN-' . time(),
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveData($dataFile, $db);

            header("Location: " . $checkoutUrl);
            exit();
        } else {
            $apiError = $result['errors'][0]['detail'] ?? 'Hindi maikonekta sa PayMongo gateway.';
            $error = "PayMongo API Error: " . $apiError;
        }
    }
}

// Action: Handle Plan Subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe') {
    $selectedPlanKey = $_POST['plan_name'] ?? 'Starter Plan';
    
    if (!isset($plans[$selectedPlanKey])) {
        $error = "Invalid investment plan selected.";
    } else {
        $plan = $plans[$selectedPlanKey];
        $amount = $plan['price'];
        
        $result = createPaymongoCheckoutSession($amount, "Subscription: $selectedPlanKey (15-Day Cycle)", $paymongoSecretKey);

        if (isset($result['data']['attributes']['checkout_url'])) {
            $checkoutUrl = $result['data']['attributes']['checkout_url'];
            
            $db['transactions'][] = [
                'id' => 'TXN-' . time(),
                'type' => 'subscription',
                'plan' => $selectedPlanKey,
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveData($dataFile, $db);

            header("Location: " . $checkoutUrl);
            exit();
        } else {
            $apiError = $result['errors'][0]['detail'] ?? 'Hindi maikonekta sa PayMongo gateway.';
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
    <title>Investment Platform Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #1e293b; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        h2 { text-align: center; color: #38bdf8; }
        .balance-box { background: #334155; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-size: 18px; font-weight: bold; }
        .card { background: #334155; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .btn-deposit { display: block; width: 100%; padding: 12px; background: #22c55e; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; margin-top: 10px; }
        .btn-deposit:hover { background: #16a34a; }
        .btn-subscribe { display: block; width: 100%; padding: 12px; background: #d97706; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; margin-top: 10px; }
        .btn-subscribe:hover { background: #b45309; }
        select, input[type="number"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; box-sizing: border-box; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .alert-error { background: #ef4444; color: white; }
        .alert-success { background: #22c55e; color: white; }
        .logout-link { display: block; text-align: center; margin-top: 15px; color: #f87171; text-decoration: none; font-size: 14px; }
        .logout-link:hover { text-decoration: underline; }
        hr { border: 0; border-top: 1px solid #475569; margin: 20px 0; }
    </style>
</head>
<body>

<div class="container">
    <h2>Investment Dashboard</h2>

    <div class="balance-box">
        Balance: ₱0.00
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert alert-success">Salamat! Nareceived ang iyong payment request. Ia-update ang iyong account pagka-verify.</div>
    <?php endif; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'cancelled'): ?>
        <div class="alert alert-error">Nacancel ang iyong payment request.</div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Deposit Section -->
    <div class="card">
        <h3>Mag-deposit (PayMongo)</h3>
        <form method="POST">
            <input type="hidden" name="action" value="deposit">
            <label for="deposit_amount">Halaga ng Deposit (PHP):</label>
            <input type="number" id="deposit_amount" name="deposit_amount" min="100" value="500" required>
            <button type="submit" class="btn-deposit">Mag-deposit sa PayMongo</button>
        </form>
    </div>

    <hr>

    <!-- Subscription Section -->
    <div class="card">
        <h3>Mag-subscribe sa Plan</h3>
        <form method="POST">
            <input type="hidden" name="action" value="subscribe">
            <label for="plan_name">Pumili ng Investment Plan:</label>
            <select name="plan_name" id="plan_name">
                <?php foreach ($plans as $name => $details): ?>
                    <option value="<?= htmlspecialchars($name) ?>">
                        <?= htmlspecialchars($name) ?> (Dep: ₱<?= number_format($details['price']) ?> | Return: ₱<?= number_format($details['return_daily']) ?>/day)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-subscribe">Mag-subscribe sa Plan</button>
        </form>
    </div>

    <a href="#" class="logout-link">Mag-log out</a>
</div>

</body>
</html>
