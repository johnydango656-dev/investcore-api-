<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
$historyFile = __DIR__ . '/transactions.json';
$adminPassword = 'admin123_secure';

function getTransactions($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveTransaction($file, $data) {
    $transactions = getTransactions($file);
    array_unshift($transactions, $data);
    file_put_contents($file, json_encode($transactions, JSON_PRETTY_PRINT));
}

// 1. KUNG /deposit ang hiningi
if (strpos($requestUri, 'deposit') !== false && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $record = [
        'id' => uniqid('txn_'),
        'type' => 'DEPOSIT',
        'user_id' => $input['user_id'] ?? 1,
        'amount' => floatval($input['amount'] ?? 0),
        'status' => 'SUCCESS',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    saveTransaction($historyFile, $record);
    echo json_encode(['status' => 'success', 'message' => 'Deposit successful', 'data' => $record]);
    exit;
}

// 2. KUNG /withdraw ang hiningi
if (strpos($requestUri, 'withdraw') !== false && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $record = [
        'id' => uniqid('txn_'),
        'type' => 'WITHDRAWAL',
        'user_id' => $input['user_id'] ?? 1,
        'amount' => floatval($input['amount'] ?? 0),
        'status' => 'SUCCESS',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    saveTransaction($historyFile, $record);
    echo json_encode(['status' => 'success', 'message' => 'Withdrawal successful', 'data' => $record]);
    exit;
}

// 3. KUNG /admin ang hiningi
if (strpos($requestUri, 'admin') !== false) {
    header('Content-Type: text/html');
    $enteredPassword = $_POST['admin_pass'] ?? '';
    $isLoggedIn = ($_SERVER['REQUEST_METHOD'] === 'POST' && $enteredPassword === $adminPassword);

    if (!$isLoggedIn) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login</title>
            <style>
                body { font-family: sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .login-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
                input, button { width: 100%; padding: 12px; margin: 10px 0; box-sizing: border-box; font-size: 16px; border-radius: 4px; border: 1px solid #ccc; }
                button { background: #007bff; color: white; border: none; font-weight: bold; cursor: pointer; }
                .error { color: red; font-size: 13px; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="login-card">
                <h3>Admin Portal Login</h3>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $enteredPassword !== $adminPassword): ?>
                    <div class="error">Mali ang password!</div>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="admin_pass" placeholder="Admin Password" required>
                    <button type="submit">Mag-login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    $transactions = getTransactions($historyFile);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
        <style>
            body { font-family: sans-serif; padding: 20px; background: #f4f6f9; color: #333; }
            .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            h2 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
            th { background: #007bff; color: white; }
            .deposit { color: green; font-weight: bold; }
            .withdrawal { color: red; font-weight: bold; }
            .logout-btn { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; font-size: 12px; cursor: pointer; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>
                InvestCore Admin Dashboard
                <a href="/admin" class="logout-btn">Mag-logout</a>
            </h2>
            <p>Lahat ng Client Deposit at Withdrawal Transactions:</p>
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Type</th>
                        <th>User ID</th>
                        <th>Amount</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" style="text-align:center;">Wala pang transaksiyon.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($txn['id']); ?></td>
                                <td class="<?php echo strtolower($txn['type']); ?>"><?php echo htmlspecialchars($txn['type']); ?></td>
                                <td><?php echo htmlspecialchars($txn['user_id']); ?></td>
                                <td>$<?php echo number_format($txn['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($txn['timestamp']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. DEFAULT: CLIENT PORTAL WEBSITE (GET /)
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestCore Client Portal</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; padding: 25px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; text-align: center; margin-bottom: 20px; }
        label { font-size: 14px; font-weight: bold; color: #555; }
        input, button { width: 100%; padding: 12px; margin: 8px 0 15px 0; box-sizing: border-box; font-size: 15px; border-radius: 4px; border: 1px solid #ccc; }
        .btn-deposit { background: #28a745; color: white; border: none; font-weight: bold; cursor: pointer; }
        .btn-withdraw { background: #dc3545; color: white; border: none; font-weight: bold; cursor: pointer; }
        #result { background: #e9ecef; padding: 10px; border-radius: 4px; font-size: 13px; white-space: pre-wrap; word-break: break-all; margin-top: 10px; display: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>InvestCore Client Portal</h2>
        <label>User ID:</label>
        <input type="number" id="userId" value="1">
        
        <label>Amount ($):</label>
        <input type="number" id="amount" value="100">
        
        <button class="btn-deposit" onclick="sendAction('deposit')">Mag-Deposit</button>
        <button class="btn-withdraw" onclick="sendAction('withdraw')">Mag-Withdraw</button>

        <div id="result"></div>
    </div>

    <script>
        async function sendAction(type) {
            const userId = document.getElementById('userId').value;
            const amount = document.getElementById('amount').value;
            const resultDiv = document.getElementById('result');
            
            resultDiv.style.display = "block";
            resultDiv.innerText = "Nagpoproseso...";

            try {
                const response = await fetch('/' + type, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, amount: parseFloat(amount) })
                });
                const data = await response.json();
                resultDiv.innerText = "Status: " + data.message + "\nUri: " + data.data.type + "\nHalaga: $" + data.data.amount;
            } catch (err) {
                resultDiv.innerText = "May error: " + err.message;
            }
        }
    </script>
</body>
</html>
