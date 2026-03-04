<?php
/**
 * RISE - View Student
 * =====================
 */

$pageTitle = 'View Student';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
requireLogin();

$db = getDB();
$studentId = (int) ($_GET['id'] ?? 0);

if ($studentId <= 0) {
    setFlashMessage('error', 'Invalid student.');
    header('Location: students.php');
    exit;
}

verifyStudentOwnership($studentId);

$stmt = $db->prepare("SELECT s.*, p.program_name, p.duration, c.course_name, a.name as admin_name
    FROM students s
    JOIN programs p ON s.program_id = p.id
    JOIN courses c ON s.course_id = c.id
    JOIN admins a ON s.admin_id = a.id
    WHERE s.id = :id");
$stmt->execute([':id' => $studentId]);
$s = $stmt->fetch();

if (!$s) {
    setFlashMessage('error', 'Student not found.');
    header('Location: students.php');
    exit;
}

// Fetch marks
$stmtMarks = $db->prepare("SELECT m.*, sub.subject_name, sub.total_marks FROM marks m JOIN subjects sub ON m.subject_id = sub.id WHERE m.student_id = :sid ORDER BY sub.subject_name");
$stmtMarks->execute([':sid' => $studentId]);
$marks = $stmtMarks->fetchAll();

// Fetch certificate
$stmtCert = $db->prepare("SELECT * FROM certificates WHERE student_id = :sid");
$stmtCert->execute([':sid' => $studentId]);
$certificate = $stmtCert->fetch();

// Calculate totals
$totalObtained = 0;
$totalMax = 0;
foreach ($marks as $m) {
    $totalObtained += $m['marks_obtained'];
    $totalMax += $m['total_marks'];
}
$percentage = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;
$overallGrade = calculateGrade($percentage);
?>

<!-- Student Header -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if ($s['photo']): ?>
                <img src="uploads/photos/<?php echo sanitize($s['photo']); ?>" class="student-photo-lg" alt="Photo">
                <?php else: ?>
                <div class="user-avatar" style="width:120px;height:120px;font-size:3rem;border-radius:50%;">
                    <?php echo strtoupper(substr($s['full_name'], 0, 1)); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="col">
                <h3 class="mb-1"><?php echo sanitize($s['full_name']); ?></h3>
                <div class="d-flex flex-wrap gap-3 mb-2">
                    <span><i class="fas fa-id-badge me-1"></i><?php echo sanitize($s['enrollment_no']); ?></span>
                    <span><i class="fas fa-hashtag me-1"></i><?php echo sanitize($s['roll_no']); ?></span>
                    <span><i class="fas fa-graduation-cap me-1"></i><?php echo sanitize($s['program_name']); ?></span>
                    <span><i class="fas fa-book me-1"></i><?php echo sanitize($s['course_name']); ?></span>
                </div>
                <?php if ($s['status'] === 'Approved'): ?>
                <span class="badge bg-success fs-6">✓ Approved</span>
                <?php else: ?>
                <span class="badge bg-warning text-dark fs-6">⏳ Pending</span>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <div class="d-flex flex-column gap-2">
                    <?php if (!isSuperAdmin()): ?>
                        <?php if ($s['status'] === 'Approved'): ?>
                        <a href="generate_id_card.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                            <i class="fas fa-id-card me-1"></i>ID Card
                        </a>
                        <?php if (!empty($marks)): ?>
                        <a href="generate_marksheet.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                            <i class="fas fa-file-alt me-1"></i>Marksheet
                        </a>
                        <a href="generate_certificate.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-success" target="_blank">
                            <i class="fas fa-certificate me-1"></i>Certificate
                        </a>
                        <?php endif; ?>
                        <?php else: ?>
                        <a href="approve_student.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-check me-1"></i>Approve
                        </a>
                        <?php endif; ?>
                    <a href="edit_student.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details -->
<div class="row g-4">
    <!-- Basic Info -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-user me-2"></i>Basic Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="detail-label">Gender</div>
                        <div class="detail-value"><?php echo sanitize($s['gender']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">DOB</div>
                        <div class="detail-value"><?php echo date('d M Y', strtotime($s['dob'])); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Father's Name</div>
                        <div class="detail-value"><?php echo sanitize($s['father_name']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Mother's Name</div>
                        <div class="detail-value"><?php echo sanitize($s['mother_name']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Mobile</div>
                        <div class="detail-value"><?php echo sanitize($s['mobile']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo sanitize($s['email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Address</div>
                        <div class="detail-value"><?php echo sanitize($s['address']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Aadhaar</div>
                        <div class="detail-value"><?php echo sanitize($s['aadhaar_number']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="detail-label">Session / Batch</div>
                        <div class="detail-value"><?php echo sanitize($s['session_name']); ?> / <?php echo sanitize($s['batch']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Education -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-book-open me-2"></i>Educational Details</h6></div>
            <div class="card-body">
                <h6 class="text-primary mb-2">10th Standard</h6>
                <div class="row g-2 mb-3">
                    <div class="col-6"><div class="detail-label">Board</div><div class="detail-value"><?php echo sanitize($s['tenth_board_name']); ?></div></div>
                    <div class="col-3"><div class="detail-label">Year</div><div class="detail-value"><?php echo $s['tenth_passing_year']; ?></div></div>
                    <div class="col-3"><div class="detail-label">%</div><div class="detail-value"><?php echo $s['tenth_percentage']; ?>%</div></div>
                </div>

                <h6 class="text-primary mb-2">12th Standard</h6>
                <div class="row g-2 mb-3">
                    <div class="col-6"><div class="detail-label">Board</div><div class="detail-value"><?php echo sanitize($s['twelfth_board_name']); ?></div></div>
                    <div class="col-3"><div class="detail-label">Year</div><div class="detail-value"><?php echo $s['twelfth_passing_year']; ?></div></div>
                    <div class="col-3"><div class="detail-label">%</div><div class="detail-value"><?php echo $s['twelfth_percentage']; ?>%</div></div>
                </div>

                <?php if ($s['ug_university_name']): ?>
                <h6 class="text-primary mb-2">UG</h6>
                <div class="row g-2 mb-3">
                    <div class="col-6"><div class="detail-value"><?php echo sanitize($s['ug_university_name']); ?></div></div>
                    <div class="col-3"><div class="detail-value"><?php echo $s['ug_passing_year']; ?></div></div>
                    <div class="col-3"><div class="detail-value"><?php echo $s['ug_percentage']; ?>%</div></div>
                </div>
                <?php endif; ?>

                <?php if ($s['pg_university_name']): ?>
                <h6 class="text-primary mb-2">PG</h6>
                <div class="row g-2">
                    <div class="col-6"><div class="detail-value"><?php echo sanitize($s['pg_university_name']); ?></div></div>
                    <div class="col-3"><div class="detail-value"><?php echo $s['pg_passing_year']; ?></div></div>
                    <div class="col-3"><div class="detail-value"><?php echo $s['pg_percentage']; ?>%</div></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-camera me-2"></i>Photo & Signature
        </h6>
    </div>

    <div class="card-body">

        <div class="row text-center">

            <!-- Student Photo -->
            <div class="col-md-6">
                <h6>Student Photo</h6>

                <?php if ($s['photo']): ?>

                    <img src="uploads/photos/<?php echo sanitize($s['photo']); ?>"
                         class="img-fluid border rounded"
                         style="max-height:200px">

                <?php else: ?>

                    <p class="text-muted">No Photo Uploaded</p>

                <?php endif; ?>

            </div>


            <!-- Student Signature -->
            <div class="col-md-6">
                <h6>Student Signature</h6>

                <?php if ($s['signature']): ?>

                    <img src="uploads/signatures/<?php echo sanitize($s['signature']); ?>"
                         class="img-fluid border rounded"
                         style="max-height:120px">

                <?php else: ?>

                    <p class="text-muted">No Signature Uploaded</p>

                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-folder-open me-2"></i>Student Documents
        </h6>
    </div>



    <div class="card-body">

        <div class="row g-4">

            <!-- Aadhaar -->
            <div class="col-md-4 text-center">
                <h6>Aadhaar Card</h6>

                <?php if ($s['aadhaar_upload']): ?>

                    <?php
                    $file = "uploads/documents/" . $s['aadhaar_upload'];
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    ?>

                    <?php if ($ext == 'pdf'): ?>

                        <a href="<?php echo $file; ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-file-pdf"></i> View PDF
                        </a>

                    <?php else: ?>

                        <img src="<?php echo $file; ?>" class="img-fluid rounded border" style="max-height:200px">

                    <?php endif; ?>

                <?php else: ?>

                    <p class="text-muted">Not Uploaded</p>

                <?php endif; ?>

            </div>


            <!-- 10th Marksheet -->
            <div class="col-md-4 text-center">
                <h6>10th Marksheet</h6>

                <?php if ($s['tenth_marksheet_upload']): ?>

                    <?php
                    $file = "uploads/marksheets/" . $s['tenth_marksheet_upload'];
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    ?>

                    <?php if ($ext == 'pdf'): ?>

                        <a href="<?php echo $file; ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-file-pdf"></i> View PDF
                        </a>

                    <?php else: ?>

                        <img src="<?php echo $file; ?>" class="img-fluid rounded border" style="max-height:200px">

                    <?php endif; ?>

                <?php else: ?>

                    <p class="text-muted">Not Uploaded</p>

                <?php endif; ?>

            </div>


            <!-- 12th Marksheet -->
            <div class="col-md-4 text-center">
                <h6>12th Marksheet</h6>

                <?php if ($s['twelfth_marksheet_upload']): ?>

                    <?php
                    $file = "uploads/marksheets/" . $s['twelfth_marksheet_upload'];
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    ?>

                    <?php if ($ext == 'pdf'): ?>

                        <a href="<?php echo $file; ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-file-pdf"></i> View PDF
                        </a>

                    <?php else: ?>

                        <img src="<?php echo $file; ?>" class="img-fluid rounded border" style="max-height:200px">

                    <?php endif; ?>

                <?php else: ?>

                    <p class="text-muted">Not Uploaded</p>

                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<!-- Marks -->
<?php if (!empty($marks)): ?>
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-pen-alt me-2"></i>Marks</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Subject</th><th>Total</th><th>Obtained</th><th>Grade</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($marks as $m): ?>
                    <tr>
                        <td><?php echo sanitize($m['subject_name']); ?></td>
                        <td><?php echo $m['total_marks']; ?></td>
                        <td><?php echo $m['marks_obtained']; ?></td>
                        <td>
                            <span class="badge <?php echo $m['grade'] === 'Fail' ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo $m['grade']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td><?php echo $totalMax; ?></td>
                        <td><?php echo $totalObtained; ?></td>
                        <td>
                            <span class="badge <?php echo $overallGrade === 'Fail' ? 'bg-danger' : 'bg-primary'; ?> fs-6">
                                <?php echo number_format($percentage, 2); ?>% (<?php echo $overallGrade; ?>)
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($certificate): ?>
<div class="card mt-4">
    <div class="card-body text-center">
        <i class="fas fa-certificate text-success fa-3x mb-2"></i>
        <h5>Certificate Issued</h5>
        <p>Certificate ID: <strong><?php echo sanitize($certificate['certificate_id']); ?></strong></p>
        <p>Issue Date: <?php echo date('d M Y', strtotime($certificate['issue_date'])); ?></p>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>