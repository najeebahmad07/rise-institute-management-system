<?php
/**
 * RISE - Approve Student (Wallet Debit)
 * ========================================
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAdmin();

if (isSuperAdmin()) {
    setFlashMessage('error', 'Use an admin account.');
    header('Location: students.php');
    exit;
}

$db = getDB();
$studentId = (int) ($_GET['id'] ?? 0);
$userId = getCurrentUserId();

if ($studentId <= 0) {
    setFlashMessage('error', 'Invalid student.');
    header('Location: students.php');
    exit;
}

verifyStudentOwnership($studentId);

// Check student status
$stmt = $db->prepare("SELECT * FROM students WHERE id = :id AND admin_id = :admin_id");
$stmt->execute([':id' => $studentId, ':admin_id' => $userId]);
$student = $stmt->fetch();

if (!$student) {
    setFlashMessage('error', 'Student not found.');
    header('Location: students.php');
    exit;
}

if ($student['status'] === 'Approved') {
    setFlashMessage('warning', 'Student is already approved.');
    header('Location: view_student.php?id=' . $studentId);
    exit;
}

// Check wallet balance
$balance = getWalletBalance($userId);
$fee = APPROVAL_FEE;

if ($balance < $fee) {
    setFlashMessage('error', 'Insufficient wallet balance. Required: ' . CURRENCY_SYMBOL . number_format($fee, 2) . '. Current balance: ' . CURRENCY_SYMBOL . number_format($balance, 2) . '. Please recharge.');
    header('Location: wallet.php');
    exit;
}

// Begin transaction
$db->beginTransaction();

try {
    // Debit wallet
    $newBalance = $balance - $fee;

    $stmt = $db->prepare("UPDATE admins SET wallet_balance = :balance WHERE id = :id");
    $stmt->execute([':balance' => $newBalance, ':id' => $userId]);

    // Record transaction
    $stmt = $db->prepare("INSERT INTO wallet_transactions (admin_id, amount, type, transaction_type, description, status) VALUES (:admin_id, :amount, 'debit', 'approval_fee', :desc, 'success')");
    $stmt->execute([
        ':admin_id' => $userId,
        ':amount' => $fee,
        ':desc' => 'Approval fee for student: ' . $student['full_name'] . ' (' . $student['enrollment_no'] . ')',
    ]);

    // Approve student
    $stmt = $db->prepare("UPDATE students SET status = 'Approved' WHERE id = :id");
    $stmt->execute([':id' => $studentId]);

    $db->commit();

    setFlashMessage('success', 'Student approved successfully! ' . CURRENCY_SYMBOL . number_format($fee, 2) . ' debited from wallet.');
    header('Location: view_student.php?id=' . $studentId);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    error_log("Approval error: " . $e->getMessage());
    setFlashMessage('error', 'Approval failed. Please try again.');
    header('Location: students.php');
    exit;
}