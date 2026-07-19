<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['admin', 'customer', 'd_agent'])) {
        $error = "Invalid role selected.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $error = "Invalid credentials.";
        } elseif ($user['status'] !== 'active') {
            $error = "Account is not active.";
        } else {
            if (!$user['email_verified']) {
                $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?")
                    ->execute([$user['id']]);
            }
            $pdo->prepare("UPDATE users SET login_count = login_count + 1, last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['first_name'];
            switch ($user['role']) {
                case 'admin':
                    $_SESSION['admin_id'] = $user['id'];
                    header("Location: admin/dashboard.php");
                    break;
                case 'customer':
                    header("Location: customer/dashboard.php");
                    break;
                case 'd_agent':
                    header("Location: d_agent/dashboard.php");
                    break;
                default:
                    header("Location: dashboard.php");
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hi.Genie - Login</title>
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
            margin: 0;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .main-bg-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
            /* Use images/background.jpg as requested */
            background: url('images/background.jpg') no-repeat center center;
            background-size: cover;
        }
        .bg-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
        }
        .login-box {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.96);
            padding: 40px 35px;
            border-radius: 16px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border-top: 5px solid var(--primary-color);
        }
        h2 {
            text-align: center;
            color: var(--text-dark);
            font-weight: 800;
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(192, 57, 43, 0.15);
        }
        .password-wrapper {
            position: relative;
        }
        .toggle-eye {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
            padding: 5px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(192, 57, 43, 0.3);
            color: #fff;
        }
        .reset-password-link {
            text-align: right;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .reset-password-link a, .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .reset-password-link a:hover, .register-link a:hover {
            text-decoration: underline;
        }
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: var(--text-dark);
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="page-wrapper">
    <div class="main-bg-container">
        <div class="bg-overlay"></div>
        <div class="login-box">
            <h2>Welcome Back</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger text-center py-2 mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
                <div class="alert alert-success text-center py-2 mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>Logged out successfully.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'success'): ?>
                <div class="alert alert-success text-center py-2 mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>Account deleted successfully.
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper input-group">
                        <span class="input-group-text bg-white text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        <i class="fa-solid fa-eye toggle-eye" id="togglePassword"></i>
                    </div>
                </div>
                <div class="reset-password-link">
                    <a href="reset_password.php">Forgot Password?</a>
                </div>
                <div class="mb-4">
                    <label for="role" class="form-label">I am a...</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="customer" selected>Customer</option>
                        <option value="d_agent">Delivery Agent</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn-login">
                    LOGIN <i class="fas fa-arrow-right ms-2"></i>
                </button>
                <div class="register-link">
                    <span>New to Hi.Genie? <a href="customer/customer_registration.php">Create Account</a></span>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include "footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>
</body>
</html>