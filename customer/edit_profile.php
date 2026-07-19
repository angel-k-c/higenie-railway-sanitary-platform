<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /higenie/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //  UPDATE PROFILE
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($first_name) || empty($last_name) || empty($phone)) {
            $error = "Name and phone fields are required.";
        } elseif (!preg_match('/^\d{10}$/', $phone)) {
            $error = "Phone number must be exactly 10 digits.";
        } else {
            $full_phone = '+91' . $phone;
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$first_name, $last_name, $full_phone, $user_id])) {
                $message = "Profile updated successfully!";
                $_SESSION['name'] = $first_name; // Update session name immediately
            } else {
                $error = "Failed to update profile.";
            }
        }
    }

    //  CHANGE PASSWORD
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';
        $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($current_password, $user_data['password'])) {
            $error = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_new_password) {
            $error = "New passwords do not match.";
        } elseif (!preg_match($password_pattern, $new_password)) {
            $error = "Password must be 8+ chars with 1 uppercase, 1 number & 1 special char.";
        } else {
            $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_new, $user_id])) {
                $message = "Password changed successfully!";
            } else {
                $error = "Failed to change password.";
            }
        }
    }

    //  DELETE ACCOUNT
    if (isset($_POST['delete_account'])) {
        $del_password = $_POST['delete_confirm_password'] ?? '';
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($del_password, $user_data['password'])) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                session_destroy();
                header("Location: /higenie/login.php?deleted=success");
                exit();
            } else {
                $error = "Failed to delete account. Please contact support.";
            }
        } else {
            $error = "Incorrect password. Account NOT deleted.";
        }
    }
}

// FETCH CURRENT USER DATA 
$stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$phone_display = (substr($user['phone'], 0, 3) === '+91') ? substr($user['phone'], 3) : $user['phone'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hi.Genie - My Account</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #c0392b;
            --primary-hover: #a93226;
            --text-dark: #2c3e50;
        }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-wrapper { flex: 1; display: flex; flex-direction: column; }
        .main-bg-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
            background: url('../images/login.jpg') no-repeat center center;
            background-size: cover;
        }
        .bg-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        .profile-box {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.96);
            padding: 40px;
            border-radius: 16px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            border-top: 5px solid var(--primary-color);
        }
        h2 { color: var(--text-dark); font-weight: 800; margin-bottom: 25px; text-align: center; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: var(--text-dark); }
        .form-control, .input-group-text { padding: 12px; border-radius: 8px; border: 1px solid #dee2e6; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(192, 57, 43, 0.15); }
        .btn-primary-custom { background: var(--primary-color); color: #fff; font-weight: 700; padding: 12px; border: none; border-radius: 8px; width: 100%; transition: all 0.3s; }
        .btn-primary-custom:hover { background: var(--primary-hover); color: #fff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(192, 57, 43, 0.3); }
        .nav-pills .nav-link.active { background-color: var(--primary-color); }
        .nav-pills .nav-link { color: var(--text-dark); font-weight: 600; }
        .password-wrapper { position: relative; }
        .toggle-eye { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6c757d; z-index: 5; }
        .delete-section { border-top: 2px dashed #eee; margin-top: 30px; padding-top: 30px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../header.php'; ?>

<div class="page-wrapper">
    <div class="main-bg-container">
        <div class="bg-overlay"></div>
        <div class="profile-box">
            <h2><i class="fas fa-user-circle me-2 text-primary-custom" style="color: var(--primary-color);"></i> My Account</h2>

            <?php if ($message): ?>
                <div class="alert alert-success text-center"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <ul class="nav nav-pills nav-justified mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" role="tab"><i class="fas fa-address-card me-2"></i>Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab"><i class="fas fa-lock me-2"></i>Security</button>
                </li>
            </ul>

            <div class="tab-content" id="profileTabsContent">
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address <small class="text-muted">(Read-only)</small></label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">+91</span>
                                <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($phone_display) ?>" required pattern="\d{10}" maxlength="10">
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn-primary-custom">Save Changes</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="security" role="tabpanel">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" name="current_password" id="current_password" required>
                                <i class="fa-solid fa-eye toggle-eye" data-target="current_password"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" name="new_password" id="new_password" required placeholder="Min 8 chars, 1 Upper, 1 Number, 1 Symbol">
                                <i class="fa-solid fa-eye toggle-eye" data-target="new_password"></i>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" name="confirm_new_password" id="confirm_new_password" required>
                                <i class="fa-solid fa-eye toggle-eye" data-target="confirm_new_password"></i>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn-primary-custom">Update Password</button>
                    </form>

                    <div class="delete-section text-center">
                        <h5 class="text-danger fw-bold"><i class="fas fa-triangle-exclamation me-2"></i>Danger Zone</h5>
                        <p class="text-muted small">Once you delete your account, there is no going back. Please be certain.</p>
                        <button class="btn btn-outline-danger fw-semibold px-4" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            Delete My Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Delete Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold">Are you absolutely sure?</p>
                <p class="small text-muted">To confirm deletion, please enter your current password.</p>
                <form method="POST" action="">
                    <div class="password-wrapper mb-3">
                        <input type="password" class="form-control" name="delete_confirm_password" id="del_pass" placeholder="Enter your password" required>
                        <i class="fa-solid fa-eye toggle-eye" data-target="del_pass"></i>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="delete_account" class="btn btn-danger fw-bold">Yes, Delete My Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.toggle-eye').forEach(eye => {
        eye.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });
</script>
</body>
</html>