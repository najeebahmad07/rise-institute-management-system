<?php
/**
 * RISE - Razorpay Payment Handler
 * ==================================
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn() || isSuperAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate CSRF
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$paymentId = sanitize($_POST['razorpay_payment_id'] ?? '');
$amount = (float) ($_POST['amount'] ?? 0);

if (empty($paymentId) || $amount < MIN_RECHARGE_AMOUNT) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
    exit;
}

$db = getDB();
$userId = getCurrentUserId();

// Verify payment with Razorpay API (basic verification)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/payments/' . $paymentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$paymentVerified = false;
$razorpayOrderId = null;

if ($httpCode === 200 && $response) {
    $paymentData = json_decode($response, true);
    if ($paymentData && $paymentData['status'] === 'captured' &&
        ($paymentData['amount'] / 100) == $amount &&
        $paymentData['currency'] === CURRENCY) {
        $paymentVerified = true;
        $razorpayOrderId = $paymentData['order_id'] ?? null;
    }
}

// For testing/development: auto-verify if Razorpay API is not available
// Remove this in production!
if (!$paymentVerified && strpos(RAZORPAY_KEY_ID, 'rzp_test_') === 0) {
    $paymentVerified = true; // Allow test payments
}

if (!$paymentVerified) {
    // Record failed transaction
    $stmt = $db->prepare("INSERT INTO wallet_transactions (admin_id, amount, type, transaction_type, razorpay_payment_id, status, description) VALUES (:admin_id, :amount, 'credit', 'recharge', :payment_id, 'failed', 'Payment verification failed')");
    $stmt->execute([':admin_id' => $userId, ':amount' => $amount, ':payment_id' => $paymentId]);

    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
    exit;
}

// Check for duplicate payment
$stmt = $db->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE razorpay_payment_id = :pid AND status = 'success'");
$stmt->execute([':pid' => $paymentId]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Payment already processed']);
    exit;
}

// Process recharge
$db->beginTransaction();
try {
    // Credit wallet
    $stmt = $db->prepare("UPDATE admins SET wallet_balance = wallet_balance + :amount WHERE id = :id");
    $stmt->execute([':amount' => $amount, ':id' => $userId]);

    // Record transaction
    $stmt = $db->prepare("INSERT INTO wallet_transactions (admin_id, amount, type, transaction_type, razorpay_payment_id, razorpay_order_id, status, description) VALUES (:admin_id, :amount, 'credit', 'recharge', :payment_id, :order_id, 'success', :desc)");
    $stmt->execute([
        ':admin_id' => $userId,
        ':amount' => $amount,
        ':payment_id' => $paymentId,
        ':order_id' => $razorpayOrderId,
        ':desc' => 'Wallet recharge of ' . CURRENCY_SYMBOL . number_format($amount, 2),
    ]);

    $db->commit();

    $newBalance = getWalletBalance($userId);
    echo json_encode([
        'success' => true,
        'message' => 'Recharge successful',
        'new_balance' => $newBalance,
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Recharge error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Processing failed. Contact support.']);
}