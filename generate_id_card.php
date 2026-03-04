<?php

/**
 * RISE SaaS - Generate ID Card (TCPDF)
 * File: generate_id_card.php
 */

require_once 'includes/auth.php';
requireLogin();

// Only admin and super_admin can generate
if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/db.php';
require_once 'includes/csrf.php';

$db = getDB(); // ← use getDB() not $pdo

// Validate student ID
$student_id = (int)($_GET['id'] ?? $_GET['student_id'] ?? 0);
if ($student_id <= 0) {
    setFlashMessage('error', 'Invalid student ID.');
    header('Location: students.php');
    exit;
}

// Fetch student with role-based access control
if ($_SESSION['user_role'] === 'super_admin') {  // ← user_role not role
    $stmt = $db->prepare("
        SELECT s.*, p.program_name, p.duration, c.course_name, a.name as admin_name
        FROM students s
        JOIN programs p ON s.program_id = p.id
        JOIN courses c ON s.course_id = c.id
        JOIN admins a ON s.admin_id = a.id
        WHERE s.id = :student_id
    ");
    $stmt->execute([':student_id' => $student_id]);
} else {
    $stmt = $db->prepare("
        SELECT s.*, p.program_name, p.duration, c.course_name
        FROM students s
        JOIN programs p ON s.program_id = p.id
        JOIN courses c ON s.course_id = c.id
        WHERE s.id = :student_id AND s.admin_id = :admin_id
    ");
    $stmt->execute([
        ':student_id' => $student_id,
        ':admin_id' => $_SESSION['user_id']  // ← user_id not admin_id
    ]);
}

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    setFlashMessage('error', 'Student not found or access denied.');
    header('Location: students.php');
    exit;
}

// Check if student is approved
if ($student['status'] !== 'Approved') {
    setFlashMessage('error', 'ID Card can only be generated for approved students.');
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

// ===================== TCPDF ID CARD GENERATION =====================

// Check if TCPDF is available
$tcpdf_paths = [
    'vendor/tecnickcom/tcpdf/tcpdf.php',
    'tcpdf/tcpdf.php',
    'lib/tcpdf/tcpdf.php',
    'TCPDF/tcpdf.php'
];

$tcpdf_loaded = false;
foreach ($tcpdf_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $tcpdf_loaded = true;
        break;
    }
}

if (!$tcpdf_loaded) {
    // Fallback: Try composer autoload
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        if (class_exists('TCPDF')) {
            $tcpdf_loaded = true;
        }
    }
}

if (!$tcpdf_loaded) {
    $_SESSION['error'] = 'TCPDF library not found. Please install TCPDF.';
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

// Custom TCPDF class for ID Card
class RISE_IDCard extends TCPDF {
    public function Header() {
        // No default header
    }
    public function Footer() {
        // No default footer
    }
}

// Create PDF - ID Card size (credit card: 85.6mm x 53.98mm, we use slightly larger)
$pdf = new RISE_IDCard('L', 'mm', array(90, 58), true, 'UTF-8', false);

$pdf->SetCreator('RISE SaaS');
$pdf->SetAuthor('RISE');
$pdf->SetTitle('ID Card - ' . htmlspecialchars($student['full_name']));

// Remove margins
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

// ===================== FRONT SIDE =====================
$pdf->AddPage();

// Background gradient effect
$pdf->SetFillColor(13, 110, 253); // Primary blue
$pdf->Rect(0, 0, 90, 20, 'F');

// Gradient overlay
$pdf->SetFillColor(10, 88, 202);
$pdf->Rect(0, 0, 90, 2, 'F');

// Logo area / Institution name
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(5, 3);

// Check for logo
$logo_path = 'assets/images/logo.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 5, 3, 12, 12, '', '', '', true, 300, '', false, false, 0);
    $pdf->SetXY(19, 3);
    $pdf->Cell(66, 7, 'RISE', 0, 1, 'L', false);
    $pdf->SetFont('helvetica', '', 6);
    $pdf->SetXY(19, 9);
    $pdf->Cell(66, 4, 'Above The Ordinary', 0, 1, 'L', false);
} else {
    $pdf->Cell(80, 7, 'RISE', 0, 1, 'C', false);
    $pdf->SetFont('helvetica', '', 6);
    $pdf->SetXY(5, 10);
    $pdf->Cell(80, 4, 'Above The Ordinary', 0, 1, 'C', false);
}

// "IDENTITY CARD" label
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(220, 53, 69);
$pdf->SetXY(28, 16);
$pdf->Cell(34, 4, 'IDENTITY CARD', 0, 1, 'C', true);

// White body area
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(0, 20, 90, 38, 'F');

// Student photo
$photo_path = 'uploads/photos/' . $student['photo'];
if (!empty($student['photo']) && file_exists($photo_path)) {
    // Photo border
    $pdf->SetDrawColor(13, 110, 253);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(4, 22, 22, 26, 'D');
    $pdf->Image($photo_path, 4.5, 22.5, 21, 25, '', '', '', true, 300, '', false, false, 0);
} else {
    // Placeholder
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetDrawColor(13, 110, 253);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(4, 22, 22, 26, 'DF');
    $pdf->SetFont('helvetica', '', 6);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->SetXY(4, 32);
    $pdf->Cell(22, 5, 'PHOTO', 0, 1, 'C');
}

// Student details
$pdf->SetTextColor(33, 37, 41);

// Name
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(29, 22);
$pdf->Cell(56, 4, strtoupper(htmlspecialchars($student['full_name'])), 0, 1, 'L');

// Details
$pdf->SetFont('helvetica', '', 6);

$details = [
    'Enrollment' => $student['enrollment_no'],
    'Roll No' => $student['roll_no'],
    'Program' => $student['program_name'],
    'Course' => $student['course_name'],
    'DOB' => date('d-m-Y', strtotime($student['dob'])),
];

$y = 27;
foreach ($details as $label => $value) {
    $pdf->SetFont('helvetica', 'B', 5);
    $pdf->SetXY(29, $y);
    $pdf->Cell(15, 3, $label . ':', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 5.5);
    $pdf->Cell(41, 3, htmlspecialchars($value), 0, 1, 'L');
    $y += 3.5;
}

// Bottom bar
$pdf->SetFillColor(13, 110, 253);
$pdf->Rect(0, 50, 90, 8, 'F');

// Issue date
$pdf->SetFont('helvetica', '', 5);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(3, 51);
$pdf->Cell(40, 4, 'Issue Date: ' . date('d-m-Y'), 0, 0, 'L');

// Session
$pdf->SetXY(45, 51);
$pdf->Cell(42, 4, 'Session: ' . htmlspecialchars($student['session_name']), 0, 0, 'R');

// ===================== BACK SIDE =====================
$pdf->AddPage();

// Background
$pdf->SetFillColor(248, 249, 250);
$pdf->Rect(0, 0, 90, 58, 'F');

// Top bar
$pdf->SetFillColor(13, 110, 253);
$pdf->Rect(0, 0, 90, 8, 'F');

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(5, 1.5);
$pdf->Cell(80, 5, 'RISE - Student Identity Card', 0, 1, 'C');

// Terms & details
$pdf->SetTextColor(33, 37, 41);
$pdf->SetFont('helvetica', '', 5.5);

$terms = [
    '1. This card is the property of RISE.',
    '2. If found, please return to the nearest RISE center.',
    '3. This card is non-transferable.',
    '4. Must be carried at all times during classes.',
    '5. Report loss immediately to the administration.'
];

$y = 12;
foreach ($terms as $term) {
    $pdf->SetXY(5, $y);
    $pdf->Cell(80, 3.5, $term, 0, 1, 'L');
    $y += 4;
}

// Father's name
$pdf->SetFont('helvetica', 'B', 6);
$pdf->SetXY(5, $y + 1);
$pdf->Cell(20, 4, "Father's Name:", 0, 0, 'L');
$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(55, 4, htmlspecialchars($student['father_name']), 0, 1, 'L');

// Address
$pdf->SetFont('helvetica', 'B', 6);
$pdf->SetXY(5, $y + 5);
$pdf->Cell(12, 4, 'Address:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 5.5);
$address = htmlspecialchars(substr($student['address'], 0, 80));
$pdf->Cell(63, 4, $address, 0, 1, 'L');

// Signature area
// Authorized Signature (Common for all students)
$authorized_signature_path = __DIR__ . '/assets/images/authorized_signature.jpg';

// Debug check (temporary)
if (!file_exists($authorized_signature_path)) {
    die('Signature not found at: ' . $authorized_signature_path);
}

$pdf->Image($authorized_signature_path, 60, 43, 20, 8, '', '', '', true, 300);

$pdf->SetFont('helvetica', '', 5);
$pdf->SetTextColor(100, 100, 100);
$pdf->Line(55, 51, 82, 51);
$pdf->SetXY(55, 51.5);
$pdf->Cell(27, 3, 'Authorized Signature', 0, 0, 'C');

// Bottom bar
$pdf->SetFillColor(13, 110, 253);
$pdf->Rect(0, 55, 90, 3, 'F');
$pdf->SetFont('helvetica', '', 4);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(5, 55.2);
$pdf->Cell(80, 2, 'Verify at: ' . ($_SERVER['HTTP_HOST'] ?? 'rise.example.com') . '/verify_student.php', 0, 0, 'C');

// Save PDF to file
$pdf_filename = 'ID_' . $student['enrollment_no'] . '_' . time() . '.pdf';
$pdf_path = __DIR__ . '/uploads/id_cards/' . $pdf_filename;

// Ensure directory exists
if (!is_dir(__DIR__ . '/uploads/id_cards')) {
    mkdir(__DIR__ . '/uploads/id_cards', 0755, true);
}

$pdf->Output($pdf_path, 'F');

// Update student record with PDF path
$update_stmt = $db->prepare("UPDATE students SET id_card_pdf = :pdf WHERE id = :id");
$update_stmt->execute([
    ':pdf' => $pdf_filename,
    ':id' => $student_id
]);

// Output PDF to browser
$pdf->Output('RISE_ID_Card_' . $student['enrollment_no'] . '.pdf', 'I');
exit;