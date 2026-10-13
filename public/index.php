<?php
session_start();

$adminSecretPassword = 'admin123'; 
$dataFile = __DIR__ . '/../data/system_data.json';

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
$qrImageUrl = $db['settings']['qr_image_url'] ?? '';

if (isset($_GET['ref'])) {
    $_SESSION['referred_by'] = htmlspecialchars($_GET['ref']);
}

// Investment Plans styled like the screenshots
$plans = [
    'Bonduelle-1' => ['price' => 300, 'return_daily' => 50.00, 'days' => 240, 'img' => 'https://i.imgur.com/83Z0icp.png'],
    'Bonduelle-2' => ['price' => 700, 'return_daily' => 125.00, 'days' => 240, 'img' => 'https://i.imgur.com/83Z0icp.png'],
    'Bonduelle-3' => ['price' => 1500, 'return_daily' => 285.00, 'days' => 240, 'img' => 'https://i.imgur.com/83Z0icp.png'],
    'Bonduelle-4' => ['price' => 3000, 'return_daily' => 600.00, 'days' => 240, 'img' => 'https://i.imgur.com/83Z0icp.png'],
    'Bonduelle-5' => ['price' => 7000, 'return_daily' => 1500.00, 'days' => 240, 'img' => 'https://i.imgur.com/83Z0icp.png'],
    'Bonduelle-6' => ['price' => 15000, 'return_daily' => 3430.00, 'days' => 240, 'img' => 'https://i.imgur.com/83Z0icp.png'],
    'Bonduelle-7' => ['price' => 30000, 'return_daily' => 7370.00, 'days' => 240, 'img' => 'https://i.imgur.com/83Z0icp.png']
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
            'balance' => 100.00, // Free 100 sign up bonus
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
    $adminSuccess = "Matagumpay na na-update ang Admin Wallet!";
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

// USER DEPOSIT SUBMISSION
if ($currentUser && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_deposit') {
    $amount = floatval($_POST['deposit_amount'] ?? 0);
    $refNo = trim($_POST['ref_no'] ?? '');

    if ($amount < 100) {
        $error = "Ang minimum recharge ay ₱100.";
    } elseif (empty($refNo)) {
        $error = "Ilagay ang Transaction Reference No. / TXID.";
    } else {
        $db['transactions'][] = [
            'id' => 'TXN-' . time(),
            'username' => $currentUser,
            'type' => 'recharge',
            'amount' => $amount,
            'txid' => htmlspecialchars($refNo),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveData($dataFile, $db);
        $success = "Matagumpay na naisumite ang recharge request! Antayin ang beripikasyon.";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonduelle | Investment Platform</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #071913; color: #f8fafc; margin: 0; padding-bottom: 80px; }
        .container { max-width: 480px; margin: 0 auto; padding: 15px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 14px; color: #94a3b8; }
        .balance-card { background: linear-gradient(135deg, #0b2e21, #0f3d2c); padding: 20px; border-radius: 14px; border: 1px solid #145332; margin-bottom: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.4); }
        .balance-title { font-size: 13px; color: #94a3b8; }
        .balance-amount { font-size: 32px; font-weight: bold; color: #facc15; margin-top: 5px; }
        .sub-stats { display: flex; gap: 10px; margin-top: 15px; }
        .sub-box { flex: 1; background: #071c15; padding: 12px; border-radius: 10px; border: 1px solid #113829; text-align: center; }
        .sub-box-title { font-size: 11px; color: #94a3b8; }
        .sub-box-val { font-size: 16px; font-weight: bold; color: #facc15; margin-top: 4px; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 20px 0; text-align: center; }
        .menu-item { background: #0f3d2c; padding: 12px 5px; border-radius: 12px; border: 1px solid #165b41; cursor: pointer; color: #f8fafc; font-size: 11px; text-decoration: none; }
        .menu-item svg { width: 22px; height: 22px; fill: #facc15; margin-bottom: 4px; display: block; margin: 0 auto 4px auto; }
        
        .section-title { font-size: 16px; font-weight: bold; margin: 20px 0 10px 0; color: #f8fafc; display: flex; justify-content: space-between; align-items: center; }
        .section-title a { font-size: 12px; color: #facc15; text-decoration: none; }
        
        .plan-card { background: #0b2e21; border-radius: 14px; border: 1px solid #145332; overflow: hidden; margin-bottom: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .plan-img { width: 100%; height: 160px; object-fit: cover; background: #113829; }
        .plan-body { padding: 15px; }
        .yield-tag { display: inline-block; background: #facc15; color: #071913; font-weight: bold; padding: 4px 10px; border-radius: 20px; font-size: 12px; margin-bottom: 8px; }
        .plan-name { font-size: 18px; font-weight: bold; color: #fff; margin-bottom: 4px; }
        .plan-info { font-size: 12px; color: #94a3b8; margin-bottom: 12px; }
        
        .btn { display: block; width: 100%; padding: 12px; background: linear-gradient(135deg, #facc15, #eab308); color: #071913; border: none; border-radius: 10px; font-weight: bold; font-size: 15px; cursor: pointer; text-align: center; text-decoration: none; box-sizing: border-box; }
        .btn-cancel { background: #1b3a2b; color: #fff; margin-top: 8px; }
        
        /* Bottom Navigation Bar */
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: #071913; border-top: 1px solid #113829; display: grid; grid-template-columns: repeat(5, 1fr); padding: 8px 0; z-index: 1000; max-width: 480px; margin: 0 auto; }
        .nav-link { text-align: center; color: #94a3b8; text-decoration: none; font-size: 11px; }
        .nav-link.active { color: #facc15; }
        .nav-link svg { width: 20px; height: 20px; fill: currentColor; margin: 0 auto 2px auto; display: block; }
        
        .card { background: #0b2e21; padding: 15px; border-radius: 12px; border: 1px solid #145332; margin-bottom: 15px; }
        input, select { width: 100%; padding: 12px; margin: 8px 0; border-radius: 8px; border: 1px solid #165b41; background: #071913; color: white; box-sizing: border-box; }
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 13px; }
        .alert-error { background: #7f1d1d; color: #fca5a5; }
        .alert-success { background: #145332; color: #86efac; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 2000; justify-content: center; align-items: flex-end; }
        .modal-content { background: #0b2e21; width: 100%; max-width: 480px; border-radius: 20px 20px 0 0; padding: 20px; border-top: 2px solid #facc15; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="container">
    <?php if (isset($_GET['route']) && $_GET['route'] === 'admin'): ?>
        <h2>Admin Panel</h2>
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
            <div class="card">
                <h3>Admin Settings</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_admin_settings">
                    <label>Anonymous USDT Wallet Address:</label>
                    <input type="text" name="new_admin_wallet" value="<?= htmlspecialchars($adminUsdtWallet) ?>" required>
                    <button type="submit" class="btn" style="margin-top:10px;">I-save</button>
                </form>
            </div>
            <div class="card">
                <h3>Pending Recharges</h3>
                <?php foreach($db['transactions'] as $txn): if($txn['type'] === 'recharge' && $txn['status'] === 'pending'): ?>
                    <div style="background:#071913; padding:10px; border-radius:8px; margin-bottom:8px; font-size:12px;">
                        User: <b><?= $txn['username'] ?></b> | Amt: ₱<?= number_format($txn['amount'], 2) ?><br>
                        TXID: <code><?= $txn['txid'] ?></code><br>
                        <a href="?route=admin&approve_deposit=<?= $txn['id'] ?>" class="btn" style="padding:6px; font-size:12px; margin-top:6px;">Approve</a>
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
                <a href="?action=admin_logout" class="btn" style="background:#ef4444; color:#fff; margin-top:15px; text-align:center;">Logout Admin</a>
            </div>
        <?php endif; ?>

    <?php elseif (!$currentUser): ?>
        <div style="text-align:center; padding: 40px 0;">
            <h2 style="color: #facc15;">Bonduelle Portal</h2>
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
        <!-- USER DASHBOARD (Bonduelle Theme) -->
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

        <!-- Action Menu Buttons -->
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
            <div class="menu-item" onclick="alert('Account active & verified!')">
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                Claimed
            </div>
            <div class="menu-item" onclick="alert('Bonuses unlocked via team invites!')">
                <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 4H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4v-2h16v2z"/></svg>
                Treasure
            </div>
        </div>

        <div class="section-title">
            <span>Product Investment</span>
        </div>

        <!-- Products List -->
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

        <!-- RECHARGE MODAL -->
        <div id="rechargeModal" class="modal">
            <div class="modal-content">
                <h3 style="color: #facc15; margin-top: 0;">Recharge Funds (USDT)</h3>
                <p style="font-size: 12px; color: #94a3b8;">Magpadala ng USDT TRC20 sa anonymous address:</p>
                <div style="background: #071913; padding: 10px; border-radius: 8px; font-family: monospace; font-size: 11px; color: #38bdf8; word-break: break-all; text-align: center; margin-bottom: 10px;"><?= $adminUsdtWallet ?></div>
                <form method="POST">
                    <input type="hidden" name="action" value="submit_deposit">
                    <label>Amount (PHP Equivalent / USDT):</label>
                    <input type="number" name="deposit_amount" min="100" value="300" required>
                    <label>Transaction TXID / Reference No:</label>
                    <input type="text" name="ref_no" placeholder="Ilagay ang TXID mula sa Binance" required>
                    <button type="submit" class="btn" style="margin-top: 10px;">Submit Recharge</button>
                    <button type="button" class="btn btn-cancel" onclick="closeModal('rechargeModal')">Cancel</button>
                </form>
            </div>
        </div>

        <!-- WITHDRAWAL MODAL -->
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

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="/" class="nav-link active">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                Home
            </a>
            <a href="#" class="nav-link" onclick="alert('Orders & Active Plans tab')">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                Orders
            </a>
            <a href="#" class="nav-link" onclick="alert('Instructions & Rules')">
                <svg viewBox="0 0 24 24"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/></svg>
                Instructions
            </a>
            <a href="#" class="nav-link" onclick="alert('Team / Referrals: <?= $referralLink ?>')">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Team
            </a>
            <a href="#" class="nav-link" onclick="alert('Account Settings & Balance')">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                My
            </a>
        </div>

        <script>
            function openModal(id) { document.getElementById(id).style.display = 'flex'; }
            function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        </script>
    <?php endif; ?>
</div>

</body>
</html>
