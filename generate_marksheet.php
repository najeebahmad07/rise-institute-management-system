<?php
/**
 * RISE SaaS - Generate Marksheet (TCPDF)
 * File: generate_marksheet.php
 */

require_once 'includes/auth.php';
requireLogin();

if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/db.php';
require_once 'includes/csrf.php';

$db = getDB();

// Validate student ID
$student_id = (int)($_GET['id'] ?? $_GET['student_id'] ?? 0);
if ($student_id <= 0) {
    setFlashMessage('error', 'Invalid student ID.');
    header('Location: students.php');
    exit;
}

// Fetch student with role-based access control
if ($_SESSION['user_role'] === 'super_admin') {
    $stmt = $db->prepare("
        SELECT s.*, p.program_name, p.duration, c.course_name, a.name as center_name
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
        ':admin_id' => $_SESSION['user_id']
    ]);
}

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    setFlashMessage('error', 'Student not found or access denied.');
    header('Location: students.php');
    exit;
}

if ($student['status'] !== 'Approved') {
    setFlashMessage('error', 'Marksheet can only be generated for approved students.');
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

// Fetch marks with subjects
$marks_stmt = $db->prepare("
    SELECT m.*, sub.subject_name, sub.total_marks
    FROM marks m
    JOIN subjects sub ON m.subject_id = sub.id
    WHERE m.student_id = :student_id
    ORDER BY sub.subject_name ASC
");
$marks_stmt->execute([':student_id' => $student_id]);
$marks = $marks_stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= TOTAL CALCULATION =================

$total_max = 0;
$total_obtained = 0;

foreach ($marks as $mark) {
    $total_max += (int)$mark['total_marks'];
    $total_obtained += (int)$mark['marks_obtained'];
}

if (empty($marks)) {
    setFlashMessage('error', 'No marks found for this student. Please enter marks first.');
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

// ===================== LOAD TCPDF =====================
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

// Custom TCPDF class for Marksheet
class RISE_Marksheet extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

// Create PDF - A4 Portrait
$pdf = new RISE_Marksheet('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('RISE SaaS');
$pdf->SetAuthor('RISE');
$pdf->SetTitle('Marksheet - ' . htmlspecialchars($student['full_name']));

$pdf->SetMargins(15, 10, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

$pdf->AddPage();

// ===================== BORDER =====================
$pdf->SetDrawColor(13, 110, 253);
$pdf->SetLineWidth(1);
$pdf->Rect(8, 8, 194, 280, 'D');
$pdf->SetLineWidth(0.3);
$pdf->Rect(10, 10, 190, 276, 'D');

// ===================== HEADER SECTION =====================
// Logo
$logo_path = 'assets/images/logo.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 85, 14, 25, 25, '', '', '', true, 300, '', false, false, 0);
}

// Institution Name
$pdf->SetFont('helvetica', 'B', 22);
$pdf->SetTextColor(13, 110, 253);
$pdf->SetXY(15, 40);
$pdf->Cell(180, 10, 'RISE', 0, 1, 'C');

// Tagline
$pdf->SetFont('helvetica', 'I', 10);
$pdf->SetTextColor(108, 117, 125);
$pdf->Cell(180, 5, 'Above The Ordinary', 0, 1, 'C');

// Document title
$pdf->Ln(3);
$pdf->SetFillColor(13, 110, 253);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetX(50);
$pdf->Cell(110, 9, 'STATEMENT OF MARKS', 0, 1, 'C', true);

// ===================== STUDENT INFO =====================
$pdf->Ln(6);
$pdf->SetTextColor(33, 37, 41);

// Photo on right side
$photo_path = 'uploads/photos/' . $student['photo'];
if (!empty($student['photo']) && file_exists($photo_path)) {
    $pdf->Image($photo_path, 160, 70, 28, 32, '', '', '', true, 300);
    $pdf->SetDrawColor(13, 110, 253);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(159.5, 69.5, 29, 33, 'D');
}

// Student details - left column
$details_left = [
    'Student Name' => strtoupper($student['full_name']),
    "Father's Name" => strtoupper($student['father_name']),
    "Mother's Name" => strtoupper($student['mother_name']),
    'Date of Birth' => date('d-m-Y', strtotime($student['dob'])),
    'Enrollment No' => $student['enrollment_no'],
    'Roll No' => $student['roll_no'],
    'Program' => $student['program_name'],
    'Course' => $student['course_name'],
    'Session' => $student['session_name'],
    'Batch' => $student['batch'],
];

$y = 70;
foreach ($details_left as $label => $value) {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY(18, $y);
    $pdf->Cell(35, 5, $label, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(5, 5, ':', 0, 0, 'C');
    $pdf->Cell(95, 5, htmlspecialchars($value), 0, 1, 'L');
    $y += 5.5;
}

// ===================== MARKS TABLE =====================
$pdf->Ln(8);
$table_y = $pdf->GetY();

// Table header
$pdf->SetFillColor(13, 110, 253);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetDrawColor(13, 110, 253);
$pdf->SetLineWidth(0.3);

$pdf->SetX(18);
$pdf->Cell(12, 8, 'S.No', 1, 0, 'C', true);
$pdf->Cell(75, 8, 'Subject Name', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Max Marks', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Marks Obtained', 1, 0, 'C', true);
$pdf->Cell(22, 8, 'Grade', 1, 1, 'C', true);

// Table body
$pdf->SetTextColor(33, 37, 41);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetFont('helvetica', '', 9);

$sno = 1;
$fill = false;
foreach ($marks as $mark) {
    if ($fill) {
        $pdf->SetFillColor(248, 249, 250);
    } else {
        $pdf->SetFillColor(255, 255, 255);
    }

    $pdf->SetX(18);
    $pdf->Cell(12, 7, $sno, 1, 0, 'C', true);
    $pdf->Cell(75, 7, htmlspecialchars($mark['subject_name']), 1, 0, 'L', true);
    $pdf->Cell(25, 7, $mark['total_marks'], 1, 0, 'C', true);
    $pdf->Cell(30, 7, $mark['marks_obtained'], 1, 0, 'C', true);

    // Individual subject grade
    $subj_pct = ($mark['total_marks'] > 0) ? ($mark['marks_obtained'] / $mark['total_marks']) * 100 : 0;
    if ($subj_pct >= 75) $subj_grade = 'A';
    elseif ($subj_pct >= 60) $subj_grade = 'B';
    elseif ($subj_pct >= 50) $subj_grade = 'C';
    else $subj_grade = 'F';

    $pdf->Cell(22, 7, $subj_grade, 1, 1, 'C', true);

    $sno++;
    $fill = !$fill;
}

// Total row
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(13, 110, 253);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetX(18);
$pdf->Cell(87, 8, 'TOTAL', 1, 0, 'R', true);
$pdf->Cell(25, 8, $total_max, 1, 0, 'C', true);
$pdf->Cell(30, 8, $total_obtained, 1, 0, 'C', true);
$pdf->Cell(22, 8, $overall_grade, 1, 1, 'C', true);

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);

$pdf->SetX(20);
$pdf->Cell(60, 8, 'Total Maximum Marks:', 0, 0, 'L');
$pdf->Cell(30, 8, $total_max, 0, 1, 'L');

$pdf->SetX(20);
$pdf->Cell(60, 8, 'Total Obtained Marks:', 0, 0, 'L');
$pdf->Cell(30, 8, $total_obtained, 0, 1, 'L');

$percentage = ($total_max > 0)
    ? round(($total_obtained / $total_max) * 100, 2)
    : 0;

if ($percentage >= 75) {
    $grade_text = 'First Division';
    $overall_grade = 'A';
} elseif ($percentage >= 60) {
    $grade_text = 'Second Division';
    $overall_grade = 'B';
} elseif ($percentage >= 50) {
    $grade_text = 'Third Division';
    $overall_grade = 'C';
} else {
    $grade_text = 'Fail';
    $overall_grade = 'F';
}

$result_status = ($percentage >= 50) ? 'PASS' : 'FAIL';

// ===================== RESULT SUMMARY =====================
$pdf->Ln(6);
$pdf->SetTextColor(33, 37, 41);

// Result box
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetX(18);
$pdf->Cell(40, 7, 'Percentage:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 7, $percentage . '%', 0, 0, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 7, 'Division:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 7, $grade_text, 0, 1, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetX(18);
$pdf->Cell(40, 7, 'Overall Grade:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 7, $overall_grade, 0, 0, 'L');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 7, 'Result:', 0, 0, 'L');

if ($result_status === 'PASS') {
    $pdf->SetTextColor(25, 135, 84);
} else {
    $pdf->SetTextColor(220, 53, 69);
}
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(50, 7, $result_status, 0, 1, 'L');

// ===================== ISSUE DATE & SIGNATURES =====================
$pdf->SetTextColor(33, 37, 41);
$pdf->Ln(10);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(18);
$pdf->Cell(80, 5, 'Date of Issue: ' . date('d-m-Y'), 0, 0, 'L');

// Signature area
// Move down
$pdf->Ln(10);

// Signature image path (JPG recommended)
$signature_path = __DIR__ . '/assets/images/authorized_signature.jpg';

// Current Y position
$currentY = $pdf->GetY();

// LEFT SIGNATURE (Controller)
if (file_exists($signature_path)) {
    $pdf->Image($signature_path, 18, $currentY, 35, 12, 'JPG');
}

// RIGHT SIGNATURE (Director)
if (file_exists($signature_path)) {
    $pdf->Image($signature_path, 145, $currentY, 35, 12, 'JPG');
}

// Move below signature images
$pdf->Ln(15);

// Draw signature lines
$pdf->SetX(18);

$pdf->Line(140, $pdf->GetY(), 185, $pdf->GetY());

// Titles


$pdf->SetX(140);
$pdf->Cell(45, 5, 'Director', 0, 1, 'C');

// ===================== VERIFICATION NOTE =====================
// ===================== QR CODE (Bottom Left) =====================

// Verification URL
$verify_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'rise.example.com') .
              '/verify_student.php?enrollment=' . urlencode($student['enrollment_no']);

// Position for QR (bottom left)
$qr_x = 18;
$qr_y = 245; // Adjust if needed

// QR style
$style = array(
    'border' => 0,
    'padding' => 1,
    'fgcolor' => array(0,0,0),
    'bgcolor' => false
);

// Generate QR Code (size 25x25)
$pdf->write2DBarcode($verify_url, 'QRCODE,H', $qr_x, $qr_y, 25, 25, $style, 'N');

// QR Label
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(33, 37, 41);
$pdf->SetXY($qr_x, $qr_y + 26);
$pdf->Cell(25, 4, 'Scan to Verify', 0, 0, 'C');
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(108, 117, 125);
$pdf->SetX(15);


// ===================== GRADING SCALE =====================
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetTextColor(33, 37, 41);
$pdf->SetX(18);
$pdf->Cell(160, 4, 'Grading Scale: A (75% & above) | B (60-74%) | C (50-59%) | F (Below 50%)', 0, 1, 'L');

// Save PDF to file
$pdf_filename = 'MS_' . $student['enrollment_no'] . '_' . time() . '.pdf';
$pdf_path = __DIR__ . '/uploads/marksheets/' . $pdf_filename;

if (!is_dir(__DIR__ . '/uploads/marksheets')) {
    mkdir(__DIR__ . '/uploads/marksheets', 0755, true);
}

$pdf->Output($pdf_path, 'F');

// Update student record
$update_stmt = $db->prepare("UPDATE students SET marksheet_pdf = :pdf WHERE id = :id");
$update_stmt->execute([
    ':pdf' => $pdf_filename,
    ':id' => $student_id
]);

// Output to browser
$pdf->Output('RISE_Marksheet_' . $student['enrollment_no'] . '.pdf', 'I');
exit;