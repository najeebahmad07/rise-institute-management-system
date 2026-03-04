<?php
/**
 * RISE - Admin Management
 */

$pageTitle = 'Admin Management';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
requireSuperAdmin();

$db = getDB();

/* =============================
   HANDLE FORM ACTIONS
=============================*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCSRF();
    $action = $_POST['action'] ?? '';

    /* ===== CREATE ADMIN ===== */

    if ($action === 'create') {

        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (!$name) $errors[] = "Name required";
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required";
        if (strlen($password) < 6) $errors[] = "Password must be 6 characters";
        if ($password !== $confirm) $errors[] = "Passwords do not match";

        if (!$errors) {

            $check = $db->prepare("SELECT COUNT(*) FROM admins WHERE email=:email");
            $check->execute([':email'=>$email]);

            if ($check->fetchColumn() > 0) {
                setFlashMessage('error','Email already exists');
            } else {

                $hash = password_hash($password,PASSWORD_DEFAULT);

                $stmt = $db->prepare("
                    INSERT INTO admins (name,email,password,role,status)
                    VALUES (:name,:email,:password,'admin','active')
                ");

                $stmt->execute([
                    ':name'=>$name,
                    ':email'=>$email,
                    ':password'=>$hash
                ]);

                setFlashMessage('success','Admin created successfully');
            }

        } else {
            setFlashMessage('error',implode('<br>',$errors));
        }

        header("Location: admin_management.php");
        exit;
    }


    /* ===== CHANGE PASSWORD ===== */

    if ($action === 'change_password') {

        $adminId = (int)$_POST['admin_id'];
        $password = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if(strlen($password) < 6){
            setFlashMessage('error','Password must be 6 characters');
        }

        elseif($password !== $confirm){
            setFlashMessage('error','Passwords do not match');
        }

        else{

            $hash = password_hash($password,PASSWORD_DEFAULT);

            $stmt = $db->prepare("UPDATE admins SET password=:pass WHERE id=:id");

            $stmt->execute([
                ':pass'=>$hash,
                ':id'=>$adminId
            ]);

            setFlashMessage('success','Password updated successfully');
        }

        header("Location: admin_management.php");
        exit;
    }


    /* ===== TOGGLE STATUS ===== */

    if ($action === 'toggle_status') {

        $adminId = (int)$_POST['admin_id'];
        $status = sanitize($_POST['new_status']);

        if ($adminId == getCurrentUserId()) {
            setFlashMessage('error','You cannot deactivate yourself');
        }

        else{

            $stmt=$db->prepare("UPDATE admins SET status=:status WHERE id=:id");

            $stmt->execute([
                ':status'=>$status,
                ':id'=>$adminId
            ]);

            setFlashMessage('success','Status updated');
        }

        header("Location: admin_management.php");
        exit;
    }
}


/* =============================
   FETCH ADMINS
=============================*/

$stmt = $db->prepare("
SELECT a.*,
(SELECT COUNT(*) FROM students WHERE admin_id=a.id) as student_count
FROM admins a
WHERE a.role='admin'
ORDER BY created_at DESC
");

$stmt->execute();
$admins = $stmt->fetchAll();
?>



<div class="d-flex justify-content-between mb-3">
<h5>Admin Accounts</h5>

<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
Create Admin
</button>

</div>



<div class="card">

<div class="card-body p-0">

<table class="table">

<thead>
<tr>
<th>#</th>
<th>Name</th>
<th>Email</th>
<th>Students</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach($admins as $i=>$admin): ?>

<tr>

<td><?php echo $i+1 ?></td>

<td><?php echo sanitize($admin['name']) ?></td>

<td><?php echo sanitize($admin['email']) ?></td>

<td><?php echo $admin['student_count'] ?></td>

<td>

<?php if($admin['status']=='active'): ?>
<span class="badge bg-success">Active</span>
<?php else: ?>
<span class="badge bg-danger">Inactive</span>
<?php endif ?>

</td>

<td>

<!-- Activate / Deactivate -->

<form method="POST" class="d-inline">

<?php echo csrfField(); ?>

<input type="hidden" name="action" value="toggle_status">
<input type="hidden" name="admin_id" value="<?php echo $admin['id'] ?>">

<?php if($admin['status']=='active'): ?>

<input type="hidden" name="new_status" value="inactive">

<button class="btn btn-sm btn-danger">Deactivate</button>

<?php else: ?>

<input type="hidden" name="new_status" value="active">

<button class="btn btn-sm btn-success">Activate</button>

<?php endif ?>

</form>


<!-- Change Password -->

<button class="btn btn-sm btn-warning"
data-bs-toggle="modal"
data-bs-target="#passwordModal"
data-id="<?php echo $admin['id']; ?>">
Change Password
</button>


<!-- Login As Admin -->




</td>

</tr>

<?php endforeach ?>

</tbody>

</table>

</div>

</div>



<!-- CREATE ADMIN MODAL -->

<div class="modal fade" id="createAdminModal">

<div class="modal-dialog">

<div class="modal-content">

<form method="POST">

<?php echo csrfField(); ?>

<input type="hidden" name="action" value="create">

<div class="modal-header">
<h5>Create Admin</h5>
</div>

<div class="modal-body">

<input class="form-control mb-2" name="name" placeholder="Full Name" required>

<input class="form-control mb-2" name="email" type="email" placeholder="Email" required>

<input class="form-control mb-2" name="password" type="password" placeholder="Password" required>

<input class="form-control mb-2" name="confirm_password" type="password" placeholder="Confirm Password" required>

</div>

<div class="modal-footer">

<button class="btn btn-primary">Create Admin</button>

</div>

</form>

</div>

</div>

</div>



<!-- CHANGE PASSWORD MODAL -->

<div class="modal fade" id="passwordModal">

<div class="modal-dialog">

<div class="modal-content">

<form method="POST">

<?php echo csrfField(); ?>

<input type="hidden" name="action" value="change_password">

<input type="hidden" name="admin_id" id="adminPasswordId">

<div class="modal-header">
<h5>Change Password</h5>
</div>

<div class="modal-body">

<input class="form-control mb-2" type="password" name="new_password" placeholder="New Password" required>

<input class="form-control mb-2" type="password" name="confirm_password" placeholder="Confirm Password" required>

</div>

<div class="modal-footer">

<button class="btn btn-primary">Update Password</button>

</div>

</form>

</div>

</div>

</div>



<script>

var passwordModal = document.getElementById('passwordModal');

passwordModal.addEventListener('show.bs.modal', function (event) {

var button = event.relatedTarget;

var id = button.getAttribute('data-id');

document.getElementById('adminPasswordId').value = id;

});

</script>



<?php require_once 'includes/footer.php'; ?>