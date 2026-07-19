<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");   
    exit();
}

require __DIR__ . '/../db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if ($first_name && $last_name && $password && $email) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = "<div class='alert alert-warning alert-dismissible fade show'>Email already exists.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, status, email_verified, created_at) VALUES (?, ?, ?, ?, 'admin', 'active', 1, NOW())");
                if ($stmt->execute([$first_name, $last_name, $email, $hashed_password])) {
                    $message = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle me-2'></i>Admin added successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                } else {
                    $message = "<div class='alert alert-danger alert-dismissible fade show'>Error adding admin.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                }
            }
        } catch (PDOException $e) {
             $message = "<div class='alert alert-danger alert-dismissible fade show'>Database error: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show'>All fields are required.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_self'])) {
    $edit_id = $_SESSION['user_id']; 
    $first_name = trim($_POST['edit_first_name'] ?? '');
    $last_name = trim($_POST['edit_last_name'] ?? '');
    $email = trim($_POST['edit_email'] ?? '');
    $password = $_POST['edit_password'] ?? '';

    if ($first_name && $last_name && $email) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $edit_id]);
            if ($stmt->fetch()) {
                $message = "<div class='alert alert-warning alert-dismissible fade show'>Email already in use by another user.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                if (!empty($password)) {
                     $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                     $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, password=? WHERE id=?");
                     $stmt->execute([$first_name, $last_name, $email, $hashed_password, $edit_id]);
                } else {
                     $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=? WHERE id=?");
                     $stmt->execute([$first_name, $last_name, $email, $edit_id]);
                }
                $_SESSION['name'] = $first_name;
                $message = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle me-2'></i>Your profile updated successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger alert-dismissible fade show'>Error updating profile.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id == $_SESSION['user_id']) {
         $message = "<div class='alert alert-danger alert-dismissible fade show'>You cannot delete your own account.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
            if ($stmt->execute([$delete_id])) {
                $message = "<div class='alert alert-warning alert-dismissible fade show'><i class='fas fa-trash-alt me-2'></i>Admin deleted successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } catch (PDOException $e) {
             $message = "<div class='alert alert-danger alert-dismissible fade show'>Error deleting admin.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

$admins = [];
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hi.Genie - Manage Admins</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #c0392b;
            --primary-hover: #a93226;
            --text-dark: #2c3e50;
            --bg-light: #f8f9fa;
        }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-wrapper {
            flex: 1;
            padding: 40px 0;
        }
        .admin-panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            height: 100%;
        }
        .panel-header {
            padding: 20px 25px;
            background: #fff;
            border-bottom: 1px solid #eee;
        }
        .panel-header h4 {
            margin: 0;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
        }
        .panel-body {
            padding: 25px;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(192, 57, 43, 0.15);
        }
        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            padding: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(192, 57, 43, 0.2);
        }
        .table-custom thead th {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 2px solid #eee;
        }
        .table-custom td {
            vertical-align: middle;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-left: 5px;
            border: none;
        }
        .delete-btn { color: #e74c3c; background: rgba(231, 76, 60, 0.1); }
        .delete-btn:hover { background: #e74c3c; color: #fff; }
        .edit-btn { color: var(--primary-color); background: rgba(192, 57, 43, 0.1); }
        .edit-btn:hover { background: var(--primary-color); color: #fff; }
    </style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="page-wrapper">
    <div class="container">
        
        <?php if ($message) echo $message; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h4><i class="fas fa-user-plus me-2 text-primary-custom" style="color: var(--primary-color);"></i> Add New Admin</h4>
                    </div>
                    <div class="panel-body">
                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required placeholder="John">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required placeholder="Doe">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control" name="email" required placeholder="name@higenie.com">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control" name="password" required placeholder="Strong password">
                                </div>
                            </div>

                            <button type="submit" name="add_admin" class="btn btn-primary btn-primary-custom text-white">
                                Create Admin Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h4><i class="fas fa-users-cog me-2 text-primary-custom" style="color: var(--primary-color);"></i> Existing Admins</h4>
                    </div>
                    <div class="panel-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Admin Details</th>
                                        <th>Created</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">
                                                <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>
                                                <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-primary-subtle text-primary-custom border border-primary-subtle ms-2" style="font-size: 0.7rem;">YOU</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted"><?= htmlspecialchars($admin['email']) ?></div>
                                        </td>
                                        <td>
                                            <span class="small text-muted">
                                                <?= date('M d, Y', strtotime($admin['created_at'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                                <button type="button" class="action-btn edit-btn" data-bs-toggle="modal" data-bs-target="#editSelfModal" 
                                                        data-fname="<?= htmlspecialchars($admin['first_name']) ?>"
                                                        data-lname="<?= htmlspecialchars($admin['last_name']) ?>"
                                                        data-email="<?= htmlspecialchars($admin['email']) ?>"
                                                        title="Edit My Profile">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="?delete_id=<?= $admin['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Delete this admin?');" title="Delete Admin">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editSelfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-primary-custom"></i>Edit My Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_fname" name="edit_first_name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_lname" name="edit_last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">New Password <small class="text-muted fw-normal">(Leave blank to keep current)</small></label>
                        <input type="password" class="form-control" name="edit_password" placeholder="New password">
                    </div>
                    <button type="submit" name="edit_self" class="btn btn-primary-custom text-black w-100 py-2">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const editSelfModal = document.getElementById('editSelfModal');
    if (editSelfModal) {
        editSelfModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            editSelfModal.querySelector('#edit_fname').value = button.getAttribute('data-fname');
            editSelfModal.querySelector('#edit_lname').value = button.getAttribute('data-lname');
            editSelfModal.querySelector('#edit_email').value = button.getAttribute('data-email');
        });
    }
</script>
</body>
</html>