<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$usersFile = __DIR__ . '/users.json';
$historyFile = __DIR__ . '/transactions.json';

// Admin Credentials
$adminUser = 'admin';
$adminPassword = 'admin123_secure';

// PayMongo Live Secret Key
$paymongoSecretKey = 'sk_live_W2bH6v6N13aWbCuh3Y41a5v8';

function getData($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// 1. ADMIN ROUTE
if (isset($_GET['admin']) || strpos($_SERVER['REQUEST_URI'], 'admin') !== false) {
    header('Content-Type: text/html');
    
    $enteredUser = $_POST['admin_user'] ?? '';
    $enteredPass = $_POST['admin_pass'] ?? '';
    $isAdminLoggedIn = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $enteredUser === $adminUser && $enteredPass === $adminPassword) {
        $_SESSION['is_admin'] = true;
        $isAdminLoggedIn = true;
    }

    if (isset($_GET['admin_logout'])) {
        unset($_SESSION['is_admin']);
        header('Location: /?admin=1');
        exit;
    }

    if (!$isAdminLoggedIn) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - InvestCore</title>
            <style>
                body { font-family: sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
                input, button { width: 100%; padding: 12px; margin: 10px 0; box-sizing: border-box; font-size: 16px; border-radius: 4px; border: 1px solid #ccc; }
                button { background: #007bff; color: white; border: none; font-weight: bold; cursor: pointer; }
                .error { color: red; font-size: 13px; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="card">
                <h3>Admin Portal Login</h3>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!$isAdminLoggedIn)): ?>
                    <div class="error">Mali ang ID o password!</div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="admin_user" placeholder="Admin ID / Username (admin)" required>
                    <input type="password" name="admin_pass" placeholder="Admin Password" required>
                    <button type="submit">Mag-login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    $transactions = getData($historyFile);
    $users = getData($usersFile);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
        <style>
            body { font-family: sans-serif; padding: 20px; background: #f4f6f9; color: #333; }
            .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            h2 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 30px; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
            th { background: #007bff; color: white; }
            .logout-btn { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; font-size: 12px; cursor: pointer; text-decoration: none; }
            h3 { margin-top: 20px; color: #444; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>
                InvestCore Admin Dashboard
                <a href="?admin=1&admin_logout=1" class="logout-btn">Mag-logout</a>
            </h2>

            <h3>Registered Clients</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="3" style="text-align:center;">Wala pang nakarehistrong client.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>₱<?php echo number_format($u['balance'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3>Transaction History (Deposits & Withdrawals)</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Client Email</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" style="text-align:center;">Wala pang transaksiyon.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($txn['id']); ?></td>
                                <td><?php echo htmlspecialchars($txn['type']); ?></td>
                                <td><?php echo htmlspecialchars($txn['user']); ?></td>
                                <td>₱<?php echo number_format($txn['amount'], 2); ?></td>
                                <td><b><?php echo htmlspecialchars($txn['status']); ?></b></td>
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

$message = '';
$error = '';
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

if (isset($_GET['paymongo_success']) && isset($_GET['amount']) && isset($_GET['user_email'])) {
    $amount = floatval($_GET['amount']);
    $email = $_GET['user_email'];

    $users = getData($usersFile);
    $transactions = getData($historyFile);

    foreach ($users as &$u) {
        if ($u['email'] === $email) {
            $u['balance'] += $amount;
            break;
        }
    }
    saveData($usersFile, $users);

    $record = [
        'id' => uniqid('txn_pm_'),
        'type' => 'DEPOSIT (PayMongo)',
        'user' => $email,
        'amount' => $amount,
        'status' => 'SUCCESS',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    array_unshift($transactions, $record);
    saveData($historyFile, $transactions);

    header("Location: /?dashboard=1&msg=Deposit_Success");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $users = getData($usersFile);
        foreach ($users as $u) {
            if ($u['email'] === $email) {
                $error = 'May naka-register na account gamit ang email na ito.';
                break;
            }
        }

        if (empty($error)) {
            $users[] = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'balance' => 0.00
            ];
            saveData($usersFile, $users);
            $message = 'Account created successfully! Mag-login na.';
        }
    }
    elseif ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $users = getData($usersFile);
        $found = false;
        foreach ($users as $u) {
            if ($u['email'] === $email && password_verify($password, $u['password'])) {
                $_SESSION['client_email'] = $u['email'];
                $_SESSION['client_name'] = $u['name'];
                $found = true;
                break;
            }
        }

        if ($found) {
            header('Location: /?dashboard=1');
            exit;
        } else {
            $error = 'Mali ang email o password.';
        }
    }
    elseif ($action === 'deposit' && isset($_SESSION['client_email'])) {
        $amount = floatval($_POST['amount'] ?? 0);
        if ($amount > 0) {
            $amountInCentavos = intval($amount * 100);
            $email = $_SESSION['client_email'];
            $successUrl = $currentUrl . "/?paymongo_success=1&amount=$amount&user_email=" . urlencode($email);
            $cancelUrl = $currentUrl . "/?dashboard=1&cancel=1";

            $payload = [
                'data' => [
                    'attributes' => [
                        'line_items' => [[
                            'currency' => 'PHP',
                            'amount' => $amountInCentavos,
                            'name' => 'InvestCore Deposit (' . $email . ')',
                            'quantity' => 1
                        ]],
                        'payment_method_types' => ['gcash', 'paymaya', 'card', 'dob'],
                        'success_url' => $successUrl,
                        'cancel_url' => $cancelUrl,
                        'description' => 'Client Account Deposit'
                    ]
                ]
            ];

            $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($paymongoSecretKey . ':')
            ]);

            $response = curl_exec($ch);
            curl_close($ch);
            $resData = json_decode($response, true);

            if (isset($resData['data']['attributes']['checkout_url'])) {
                header('Location: ' . $resData['data']['attributes']['checkout_url']);
                exit;
            } else {
                $error = 'May error sa PayMongo connection.';
            }
        }
    }
    elseif ($action === 'withdraw' && isset($_SESSION['client_email'])) {
        $amount = floatval($_POST['amount'] ?? 0);
        $email = $_SESSION['client_email'];

        $users = getData($usersFile);
        $userIndex = -1;
        foreach ($users as $index => $u) {
            if ($u['email'] === $email) {
                $userIndex = $index;
                break;
            }
        }

        if ($userIndex !== -1 && $users[$userIndex]['balance'] >= $amount && $amount > 0) {
            $users[$userIndex]['balance'] -= $amount;
            saveData($usersFile, $users);

            $transactions = getData($historyFile);
            $record = [
                'id' => uniqid('txn_wd_'),
                'type' => 'WITHDRAWAL REQUEST',
                'user' => $email,
                'amount' => $amount,
                'status' => 'PENDING',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            array_unshift($transactions, $record);
            saveData($historyFile, $transactions);

            $message = 'Withdrawal request sent successfully!';
        } else {
            $error = 'Hindi sapat ang balanse.';
        }
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['client_email']);
    unset($_SESSION['client_name']);
    header('Location: /');
    exit;
}

if (isset($_SESSION['client_email'])) {
    $email = $_SESSION['client_email'];
    $users = getData($usersFile);
    $currentBalance = 0;
    $clientName = $_SESSION['client_name'];

    foreach ($users as $u) {
        if ($u['email'] === $email) {
            $currentBalance = $u['balance'];
            break;
        }
    }

    if (isset($_GET['msg']) && $_GET['msg'] === 'Deposit_Success') {
        $message = 'Tagumpay ang iyong pag-deposito!';
    }
    if (isset($_GET['cancel'])) {
        $error = 'Kinansela ang pagbabayad.';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Client Dashboard - InvestCore</title>
        <style>
            body { font-family: sans-serif; padding: 20px; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
            h2 { color: #333; margin-top: 0; text-align: center; }
            .balance-box { background: #e9ecef; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 20px; font-size: 18px; font-weight: bold; color: #007bff; }
            label { font-size: 14px; font-weight: bold; color: #555; }
            input, button { width: 100%; padding: 12px; margin: 8px 0 15px 0; box-sizing: border-box; font-size: 15px; border-radius: 4px; border: 1px solid #ccc; }
            .btn-deposit { background: #28a745; color: white; border: none; font-weight: bold; cursor: pointer; }
            .btn-withdraw { background: #dc3545; color: white; border: none; font-weight: bold; cursor: pointer; }
            .logout-link { display: block; text-align: center; margin-top: 15px; color: #dc3545; text-decoration: none; font-size: 14px; }
            .msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; text-align: center; }
            .err { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>Welcome, <?php echo htmlspecialchars($clientName); ?>!</h2>
            <div class="balance-box">Balanse: ₱<?php echo number_format($currentBalance, 2); ?></div>

            <?php if (!empty($message)): ?><div class="msg"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <form method="POST">
                <label>Halaga (₱):</label>
                <input type="number" name="amount" value="500" step="any" required>
                
                <button type="submit" name="action" value="deposit" class="btn-deposit">Mag-Deposit (PayMongo)</button>
                <button type="submit" name="action" value="withdraw" class="btn-withdraw">Mag-Withdraw</button>
            </form>

            <a href="/?logout=1" class="logout-link">Mag-log out</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$view = $_GET['view'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestCore Portal</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #333; text-align: center; margin-top: 0; }
        label { font-size: 14px; font-weight: bold; color: #555; }
        input, button { width: 100%; padding: 12px; margin: 8px 0 15px 0; box-sizing: border-box; font-size: 15px; border-radius: 4px; border: 1px solid #ccc; }
        button { background: #007bff; color: white; border: none; font-weight: bold; cursor: pointer; }
        .toggle-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .toggle-link a { color: #007bff; text-decoration: none; }
        .msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; text-align: center; }
        .err { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($view === 'register'): ?>
            <h2>Mag-register</h2>
            <?php if (!empty($message)): ?><div class="msg"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <label>Pangalan:</label>
                <input type="text" name="name" required>
                <label>Email:</label>
                <input type="email" name="email" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Mag-sign up</button>
            </form>
            <div class="toggle-link">Mayroon nang account? <a href="/">Mag-login dito</a></div>
        <?php else: ?>
            <h2>Mag-login</h2>
            <?php if (!empty($message)): ?><div class="msg"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <label>Email:</label>
                <input type="email" name="email" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Mag-login</button>
            </form>
            <div class="toggle-link">Wala pang account? <a href="/?view=register">Mag-register dito</a></div>
        <?php endif; ?>
    </div>
</body>
</html>
