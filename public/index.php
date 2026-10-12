<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $uri === '/') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>InvestCore Live Tester</title>
        <style>
            body { font-family: sans-serif; padding: 20px; background: #f4f6f9; }
            .card { background: #fff; padding: 20px; border-radius: 8px; max-width: 400px; margin: 0 auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            input, button { width: 100%; padding: 12px; margin: 8px 0; box-sizing: border-box; font-size: 16px; }
            button { background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
            .withdraw { background: #dc3545; }
            #result { background: #e9ecef; padding: 10px; border-radius: 4px; white-space: pre-wrap; word-break: break-all; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>InvestCore API Tester</h2>
            <label>User ID:</label>
            <input type="number" id="userId" value="1">
            <label>Amount ($):</label>
            <input type="number" id="amount" value="100">
            
            <button onclick="sendAction('deposit')">TEST DEPOSIT</button>
            <button class="withdraw" onclick="sendAction('withdraw')">TEST WITHDRAWAL</button>

            <h3>Response:</h3>
            <div id="result">Waiting for click...</div>
        </div>

        <script>
            async function sendAction(type) {
                const userId = document.getElementById('userId').value;
                const amount = document.getElementById('amount').value;
                const resultDiv = document.getElementById('result');
                
                resultDiv.innerText = "Processing...";

                try {
                    const response = await fetch('/' + type, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId, amount: parseFloat(amount) })
                    });
                    const data = await response.json();
                    resultDiv.innerText = JSON.stringify(data, null, 2);
                } catch (err) {
                    resultDiv.innerText = "Error: " + err.message;
                }
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if ($uri === '/deposit') {
    echo json_encode([
        'status' => 'success',
        'action' => 'deposit',
        'user_id' => $input['user_id'] ?? null,
        'amount' => $input['amount'] ?? 0,
        'message' => 'Live Deposit request successfully processed!'
    ]);
} elseif ($uri === '/withdraw') {
    echo json_encode([
        'status' => 'success',
        'action' => 'withdraw',
        'user_id' => $input['user_id'] ?? null,
        'amount' => $input['amount'] ?? 0,
        'message' => 'Live Withdrawal request successfully processed!'
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'message' => 'InvestCore API is running successfully!'
    ]);
}
