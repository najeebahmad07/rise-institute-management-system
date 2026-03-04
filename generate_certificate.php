<?php
/**
 * RISE SaaS - Generate Certificate (TCPDF)
 * File: generate_certificate.php
 *
 * Design: Pure white background · Navy + Sky-blue + Soft-red accent
 *         Triple ornate border · Classic prestigious certificate feel
 *
 * Signature : Always uses institute-level authorized signature (same for all).
 * Footer    : Accreditation logos banner image at the very bottom.
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

$student_id = (int)($_GET['id'] ?? $_GET['student_id'] ?? 0);
if ($student_id <= 0) {
    setFlashMessage('error', 'Invalid student ID.');
    header('Location: students.php');
    exit;
}

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
    $stmt->execute([':student_id' => $student_id, ':admin_id' => $_SESSION['user_id']]);
}

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    setFlashMessage('error', 'Student not found or access denied.');
    header('Location: students.php');
    exit;
}
if ($student['status'] !== 'Approved') {
    setFlashMessage('error', 'Certificate can only be generated for approved students.');
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

$marks_check = $db->prepare("SELECT COUNT(*) FROM marks WHERE student_id = :student_id");
$marks_check->execute([':student_id' => $student_id]);
if ($marks_check->fetchColumn() == 0) {
    setFlashMessage('error', 'Marks must be entered before generating certificate.');
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

$marks_stmt = $db->prepare("
    SELECT m.marks_obtained, sub.total_marks
    FROM marks m JOIN subjects sub ON m.subject_id = sub.id
    WHERE m.student_id = :student_id
");
$marks_stmt->execute([':student_id' => $student_id]);
$all_marks = $marks_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_obtained = array_sum(array_column($all_marks, 'marks_obtained'));
$total_max      = array_sum(array_column($all_marks, 'total_marks'));
$percentage     = ($total_max > 0) ? round(($total_obtained / $total_max) * 100, 2) : 0;

if ($percentage >= 75)     { $grade = 'A'; $grade_text = 'First Division with Distinction'; }
elseif ($percentage >= 60) { $grade = 'B'; $grade_text = 'First Division'; }
elseif ($percentage >= 50) { $grade = 'C'; $grade_text = 'Second Division'; }
else                       { $grade = 'Fail'; $grade_text = 'Fail'; }

if ($grade === 'Fail') {
    setFlashMessage('error', 'Certificate cannot be generated for failed students.');
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

// Certificate record
$cert_stmt = $db->prepare("SELECT * FROM certificates WHERE student_id = :student_id");
$cert_stmt->execute([':student_id' => $student_id]);
$certificate = $cert_stmt->fetch(PDO::FETCH_ASSOC);

if (!$certificate) {
    $cert_id = 'RISE-' . date('Y') . '-' . str_pad($student_id, 6, '0', STR_PAD_LEFT);
    $chk = $db->prepare("SELECT COUNT(*) FROM certificates WHERE certificate_id = :c");
    $chk->execute([':c' => $cert_id]);
    if ($chk->fetchColumn() > 0) $cert_id .= '-' . rand(100, 999);
    $ins = $db->prepare("INSERT INTO certificates (student_id, certificate_id, issue_date, created_at) VALUES (:s,:c,:d,NOW())");
    $ins->execute([':s' => $student_id, ':c' => $cert_id, ':d' => date('Y-m-d')]);
    $certificate = ['certificate_id' => $cert_id, 'issue_date' => date('Y-m-d')];
}

// ── Student photo ─────────────────────────────────────────────────────────────
$student_photo_path = '';
if (!empty($student['photo'])) {
    $p = __DIR__ . '/uploads/photos/' . $student['photo'];
    if (file_exists($p)) $student_photo_path = $p;
}

// ── Authorized signature — institute-level ONLY (same for every certificate) ─
$signature_path = __DIR__ . '/assets/images/authorized_signature.jpg';
$has_signature  = file_exists($signature_path);

// ── Accreditation logos banner ────────────────────────────────────────────────
// Place the logos strip image at:  assets/images/accreditation_logos.png
$logos_path = __DIR__ . '/assets/images/accreditation_logos.png';
$has_logos  = file_exists($logos_path);

// TCPDF loader
foreach (['vendor/tecnickcom/tcpdf/tcpdf.php','tcpdf/tcpdf.php','lib/tcpdf/tcpdf.php','TCPDF/tcpdf.php'] as $p) {
    if (file_exists($p)) { require_once $p; break; }
}
if (!class_exists('TCPDF') && file_exists('vendor/autoload.php')) require_once 'vendor/autoload.php';
if (!class_exists('TCPDF')) {
    $_SESSION['error'] = 'TCPDF not found.';
    header('Location: view_student.php?id=' . $student_id);
    exit;
}

class RISE_Certificate extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new RISE_Certificate('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('RISE SaaS');
$pdf->SetAuthor('RISE');
$pdf->SetTitle('Certificate – ' . $student['full_name']);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->AddPage();

$W = 297;
$H = 210;

// ══════════════════════════════════════════════════════════════════════════════
//  WHITE BACKGROUND
// ══════════════════════════════════════════════════════════════════════════════
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(0, 0, $W, $H, 'F');

// ══════════════════════════════════════════════════════════════════════════════
//  ACCREDITATION LOGOS BANNER  — sits at the very bottom, full width
//  Height: 14 mm.  A thin navy top-rule separates it from the main body.
// ══════════════════════════════════════════════════════════════════════════════
$logos_h    = 14;                  // height of the logos strip (mm)
$logos_y    = $H - $logos_h;       // y position = 196 mm

// Very light grey background behind logos strip
$pdf->SetFillColor(250, 250, 252);
$pdf->Rect(0, $logos_y, $W, $logos_h, 'F');

// Thin navy top border of logos strip
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.5);
$pdf->Line(0, $logos_y, $W, $logos_y);

// Render the logos image, centred and vertically fitted inside the strip
if ($has_logos) {
    // Keep the image at natural aspect ratio, max height = logos_h - 2 mm padding
    $img_h      = $logos_h - 3;
    $img_w      = $W - 20;          // stretch nearly full width (logos are a wide strip)
    $img_x      = ($W - $img_w) / 2;
    $img_y      = $logos_y + 1.5;
    $pdf->Image($logos_path, $img_x, $img_y, $img_w, $img_h, '', '', '', true, 200);
} else {
    // Placeholder text if image not yet placed
    $pdf->SetFont('helvetica', 'I', 6);
    $pdf->SetTextColor(160, 160, 170);
    $pdf->SetXY(0, $logos_y + 4);
    $pdf->Cell($W, 5, 'National Career Service · Rural Health · IAF · ISO · NSDC · Skill India · MSME · IAO', 0, 0, 'C');
}

// ══════════════════════════════════════════════════════════════════════════════
//  ORNATE TRIPLE BORDER  (fits above the logos strip)
// ══════════════════════════════════════════════════════════════════════════════
$border_bottom = $logos_y - 1;     // border stops just above the logos strip

// 1. Outermost thick navy
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(2.5);
$pdf->Rect(5, 5, $W - 10, $border_bottom - 5, 'D');

// 2. Thin navy (3 mm inside)
$pdf->SetLineWidth(0.5);
$pdf->Rect(8, 8, $W - 16, $border_bottom - 8, 'D');

// 3. Sky-blue dashed (another 3 mm inside)
$pdf->SetDrawColor(70, 130, 180);
$pdf->SetLineWidth(0.4);

$pdf->Rect(11, 11, $W - 22, $border_bottom - 11, 'D');


// 4. Innermost thin navy
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.3);
$pdf->Rect(14, 14, $W - 28, $border_bottom - 14, 'D');

// Corner diamonds
$pdf->SetFillColor(185, 28, 28);
$inner_bottom = $border_bottom - 14;
foreach ([[14, 14], [$W - 14, 14], [14, $inner_bottom + 14], [$W - 14, $inner_bottom + 14]] as [$dx, $dy]) {
    $s = 2.2;
    $pdf->Polygon([$dx, $dy - $s, $dx + $s, $dy, $dx, $dy + $s, $dx - $s, $dy], 'F');
}

// Mid-edge dots
$pdf->SetFillColor(70, 130, 180);
$mid_border_v = $logos_y / 2;
foreach ([[$W / 2, 14], [$W / 2, $border_bottom - 1], [14, $mid_border_v], [$W - 14, $mid_border_v]] as [$ex, $ey]) {
    $pdf->Circle($ex, $ey, 1.2, 0, 360, 'F');
}

// ══════════════════════════════════════════════════════════════════════════════
//  FAINT BLUE HEADER BAND
// ══════════════════════════════════════════════════════════════════════════════
$pdf->SetFillColor(240, 247, 255);
$pdf->Rect(15, 15, $W - 30, 38, 'F');

$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, 53, $W - 15, 53);

$pdf->SetDrawColor(70, 130, 180);
$pdf->SetLineWidth(0.3);
$pdf->Line(20, 55, $W - 20, 55);

// ══════════════════════════════════════════════════════════════════════════════
//  LOGO
// ══════════════════════════════════════════════════════════════════════════════
$logo_path = __DIR__ . '/assets/images/logo.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, ($W / 2) - 10, 18, 20, 20, '', '', '', true, 300);
}

// ══════════════════════════════════════════════════════════════════════════════
//  INSTITUTION NAME & TAGLINE
// ══════════════════════════════════════════════════════════════════════════════
$pdf->SetFont('times', 'B', 22);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetXY(0, file_exists($logo_path) ? 19 : 22);
$pdf->Cell($W, 10, 'RISE', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetTextColor(70, 130, 180);
$pdf->SetX(0);
$pdf->Cell($W, 5, 'A B O V E   T H E   O R D I N A R Y', 0, 1, 'C');

$pdf->SetDrawColor(70, 130, 180);
$pdf->SetLineWidth(0.3);
$pdf->Line(30, 42, 120, 42);
$pdf->Line($W - 120, 42, $W - 30, 42);

// ══════════════════════════════════════════════════════════════════════════════
//  CERTIFICATE TITLE
// ══════════════════════════════════════════════════════════════════════════════
$pdf->SetFont('times', 'B', 34);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetXY(0, 57);
$pdf->Cell($W, 16, 'Certificate of Completion', 0, 1, 'C');

$mx = $W / 2;
$pdf->SetDrawColor(185, 28, 28);
$pdf->SetLineWidth(1.2);
$pdf->Line($mx - 45, 73, $mx + 45, 73);

$pdf->SetFillColor(185, 28, 28);
foreach ([[$mx - 45, 73], [$mx + 45, 73]] as [$ox, $oy]) {
    $pdf->Polygon([$ox, $oy - 1.5, $ox + 1.5, $oy, $ox, $oy + 1.5, $ox - 1.5, $oy], 'F');
}

$pdf->SetDrawColor(70, 130, 180);
$pdf->SetLineWidth(0.3);
$pdf->Line($mx - 65, 71, $mx - 47, 71);
$pdf->Line($mx + 47, 71, $mx + 65, 71);
$pdf->Line($mx - 65, 75, $mx - 47, 75);
$pdf->Line($mx + 47, 75, $mx + 65, 75);

// ══════════════════════════════════════════════════════════════════════════════
//  STUDENT PHOTO
// ══════════════════════════════════════════════════════════════════════════════
$ph_w = 32;
$ph_h = 38;
$ph_x = $W - 58;
$ph_y = 78;

$pdf->SetFillColor(255, 255, 255);
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(1.2);
$pdf->Rect($ph_x - 2, $ph_y - 2, $ph_w + 4, $ph_h + 4, 'DF');

$pdf->SetDrawColor(70, 130, 180);
$pdf->SetLineWidth(0.3);
$pdf->Rect($ph_x - 0.5, $ph_y - 0.5, $ph_w + 1, $ph_h + 1, 'D');

if ($student_photo_path) {
    $pdf->Image($student_photo_path, $ph_x, $ph_y, $ph_w, $ph_h, '', '', '', true, 150);
} else {
    $pdf->SetFillColor(240, 247, 255);
    $pdf->Rect($ph_x, $ph_y, $ph_w, $ph_h, 'F');
    $pdf->SetFont('helvetica', '', 6);
    $pdf->SetTextColor(100, 130, 160);
    $pdf->SetXY($ph_x, $ph_y + $ph_h / 2 - 3);
    $pdf->Cell($ph_w, 6, 'STUDENT PHOTO', 0, 0, 'C');
}

$pdf->SetFont('helvetica', '', 6);
$pdf->SetTextColor(80, 95, 115);
$pdf->SetXY($ph_x - 2, $ph_y + $ph_h + 3);
$pdf->Cell($ph_w + 4, 4, htmlspecialchars($student['full_name']), 0, 0, 'C');

// ══════════════════════════════════════════════════════════════════════════════
//  BODY TEXT
// ══════════════════════════════════════════════════════════════════════════════
$bx = 18;
$bw = $ph_x - 28;

$pdf->SetFont('times', 'I', 12);
$pdf->SetTextColor(80, 95, 115);
$pdf->SetXY($bx, 78);
$pdf->Cell($bw, 6, 'This is to certify that', 0, 1, 'L');

$pdf->SetFont('times', 'BI', 28);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetXY($bx, 84);
$pdf->Cell($bw, 13, htmlspecialchars($student['full_name']), 0, 1, 'L');

$pdf->SetDrawColor(185, 28, 28);
$pdf->SetLineWidth(0.8);
$nw = min($pdf->GetStringWidth($student['full_name']), $bw - 4);
$pdf->Line($bx, 97.5, $bx + $nw, 97.5);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(80, 95, 115);
$pdf->SetXY($bx, 99);
$pdf->Cell($bw, 5, 'Enrollment No:  ' . htmlspecialchars($student['enrollment_no']), 0, 1, 'L');

$pdf->SetFont('times', '', 12);
$pdf->SetTextColor(20, 30, 45);
$pdf->SetXY($bx, 106);
$pdf->Cell($bw, 6, 'has successfully completed all requirements of the program', 0, 1, 'L');

// Program box
$prog_y = 114;
$prog_w = min($bw, 158);

$pdf->SetFillColor(240, 247, 255);
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.5);
$pdf->RoundedRect($bx, $prog_y, $prog_w, 13, 2, '1111', 'DF');

$pdf->SetFillColor(185, 28, 28);
$pdf->Rect($bx, $prog_y, 3, 13, 'F');

$pdf->SetFont('times', 'B', 15);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetXY($bx + 5, $prog_y + 2);
$pdf->Cell($prog_w - 8, 9, htmlspecialchars($student['program_name']), 0, 1, 'L');

$pdf->SetFont('times', 'I', 10.5);
$pdf->SetTextColor(80, 95, 115);
$pdf->SetXY($bx, $prog_y + 15);
$pdf->Cell($prog_w, 5, 'Course: ' . htmlspecialchars($student['course_name']), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetXY($bx, $prog_y + 21);
$pdf->Cell($prog_w, 5,
    'Session: ' . htmlspecialchars($student['session_name']) .
    '     |     Batch: ' . htmlspecialchars($student['batch']),
    0, 1, 'L'
);

// Score pill
$pill_y = $prog_y + 28;
$pill_w = 80;
$pdf->SetFillColor(15, 52, 96);
$pdf->RoundedRect($bx, $pill_y, $pill_w, 10, 5, '1111', 'F');
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY($bx, $pill_y + 1.8);
$pdf->Cell($pill_w, 6.5,
    'Score: ' . $percentage . '%     Grade: ' . $grade . '     ' . $grade_text,
    0, 0, 'C'
);

// ══════════════════════════════════════════════════════════════════════════════
//  SEPARATOR + SIGNATURE SECTION
//  Pushed up slightly to leave room for the logos strip at the bottom
// ══════════════════════════════════════════════════════════════════════════════
$sep_y     = $logos_y - 38;   // separator sits 38 mm above logos strip
$sig_base  = $sep_y + 20;
$sig_label = $sig_base + 2;
$sl        = 52;

$pdf->SetDrawColor(70, 130, 180);
$pdf->SetLineWidth(0.3);
$pdf->Line(18, $sep_y, $W - 18, $sep_y);

$pdf->SetFillColor(248, 251, 255);
$pdf->Rect(15, $sep_y, $W - 30, 30, 'F');

// Left — Controller of Examinations
$s1x = 28;
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.5);
$pdf->Line($s1x, $sig_base, $s1x + $sl, $sig_base);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetXY($s1x, $sig_label);
$pdf->Cell($sl, 4, 'Controller of Examinations', 0, 0, 'C');

// Centre — Cert ID + Date + Seal
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(80, 95, 115);
$pdf->SetXY($W / 2 - 38, $sep_y + 5);
$pdf->Cell(76, 4, 'Certificate ID: ' . htmlspecialchars($certificate['certificate_id']), 0, 1, 'C');
$pdf->SetX($W / 2 - 38);
$pdf->Cell(76, 4, 'Issue Date: ' . date('d F Y', strtotime($certificate['issue_date'])), 0, 1, 'C');

$scx = $W / 2;
$scy = $sig_base - 2;
$sr  = 7.5;
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.6);
$pdf->Circle($scx, $scy, $sr, 0, 360, 'D');
$pdf->SetLineWidth(0.2);
$pdf->Circle($scx, $scy, $sr - 2, 0, 360, 'D');
$pdf->SetFont('helvetica', 'B', 5);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetXY($scx - $sr, $scy - 2.5);
$pdf->Cell($sr * 2, 3, 'OFFICIAL', 0, 0, 'C');
$pdf->SetXY($scx - $sr, $scy + 0.5);
$pdf->Cell($sr * 2, 3, 'SEAL', 0, 0, 'C');

// Right — Director with institute authorized signature
$s2x = $W - 28 - $sl;
if ($has_signature) {
    $pdf->Image(
        $signature_path,
        $s2x + ($sl / 2) - 16, $sig_base - 14,
        32, 12,
        '', '', '', true, 150, '', false, false, 0, 'CM'
    );
}
$pdf->SetDrawColor(15, 52, 96);
$pdf->SetLineWidth(0.5);
$pdf->Line($s2x, $sig_base, $s2x + $sl, $sig_base);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetTextColor(15, 52, 96);
$pdf->SetXY($s2x, $sig_label);
$pdf->Cell($sl, 4, 'Director', 0, 0, 'C');

// ══════════════════════════════════════════════════════════════════════════════
//  SAVE & OUTPUT
// ══════════════════════════════════════════════════════════════════════════════
$pdf_filename = 'CERT_' . $student['enrollment_no'] . '_' . time() . '.pdf';
$pdf_dir      = __DIR__ . '/uploads/certificates/';
if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0755, true);

$pdf->Output($pdf_dir . $pdf_filename, 'F');

$upd = $db->prepare("UPDATE students SET certificate_pdf = :pdf WHERE id = :id");
$upd->execute([':pdf' => $pdf_filename, ':id' => $student_id]);

$pdf->Output('RISE_Certificate_' . $student['enrollment_no'] . '.pdf', 'I');
exit;