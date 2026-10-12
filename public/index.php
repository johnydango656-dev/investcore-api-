<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$historyFile = __DIR__ . '/transactions.json';

function getTransactions($file) {
    if (!file_exists($file)) {
        return [];
    }
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveTransaction($file, $data) {
    $transactions = getTransactions($file);
    array_unshift($transactions, $data);
    file_put_contents($file, json_encode($transactions, JSON_PRETTY_PRINT));
}

if ($uri === '/deposit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $input['user_id'] ?? 1;
    $amount = floatval($input['amount'] ?? 0);

    $record = [
        'id' => uniqid('txn_'),
        'type' => 'DEPOSIT',
        'user_id' => $userId,
        'amount' => $amount,
        'status' => 'SUCCESS',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    saveTransaction($historyFile, $record);

    echo json_encode([
        'status' => 'success',
        'message' => 'Live deposit successful!',
        'data' => $record
    ]);
    exit;
}

if ($uri === '/withdraw' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $input['user_id'] ?? 1;
    $amount = floatval($input['amount'] ?? 0);

    $record = [
        'id' => uniqid('txn_'),
        'type' => 'WITHDRAWAL',
        'user_id' => $userId,
        'amount' => $amount,
        'status' => 'SUCCESS',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    saveTransaction($historyFile, $record);

    echo json_encode([
        'status' => 'success',
        'message' => 'Live withdrawal processed successfully!',
        'data' => $record
    ]);
    exit;
}

if ($uri === '/history' || $uri === '/transactions') {
    $transactions = getTransactions($historyFile);
    echo json_encode([
        'status' => 'success',
        'total_transactions' => count($transactions),
        'history' => $transactions
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'InvestCore API is active!',
    'endpoints_available' => [
        'POST /deposit' => 'Mag-simulate ng deposit (body: user_id, amount)',
        'POST /withdraw' => 'Mag-simulate ng withdrawal (body: user_id, amount)',
        'GET /history' => 'Tignan ang lahat ng transaction history'
    ]
]);
