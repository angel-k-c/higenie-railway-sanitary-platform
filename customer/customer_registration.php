<?php
session_start();
// Adjust paths as necessary based on your actual file structure
require '../db.php'; 
// require '../includes/mailer.php'; // Uncomment and adjust path if you have the mailer ready

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Retrieve and trim all form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Password: At least 8 chars, 1 uppercase, 1 number, 1 special char
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

    // 3. Perform validations
    if (!$first_name || !$last_name || !$phone || !$email || !$password || !$confirm_password) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone number must be exactly 10 digits.";
    } elseif (!preg_match($password_pattern, $password)) {
        $error = "Password must be at least 8 chars with 1 uppercase, 1 number, and 1 special char.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "This email address is already registered.";
        } else {
            // 4. Hash password and format phone number
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $full_phone = '+91' . $phone;

            // 5. Insert the new user into the database
            $sql = "INSERT INTO users (first_name, last_name, email, password, role, phone, status, email_verified, created_at) 
                    VALUES (?, ?, ?, ?, 'customer', ?, 'active', 1, NOW())";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$first_name, $last_name, $email, $hashed_password, $full_phone])) {
                $success = "Registration successful! You can now log in.";
                
                // --- EMAIL SENDING LOGIC MOVED HERE ---
                // Only send email IF registration was successful
                /* $full_name = $first_name . ' ' . $last_name;
                $subject = "Welcome to Hi.Genie!";
                // Ensure loadEmailTemplate exists or use a simple string body for testing
                $body = "Welcome, $full_name! Thanks for registering."; 
                // sendMail($email, $subject, $body); 
                */
                // ------------------------------------

            } else {
                $error = "Registration failed. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hi.Genie - Customer Registration</title>
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
            /* Use ../images/ if this file is in a subfolder like /customer/ */
            background: url('../images/background.jpg') no-repeat center center;
            background-size: cover;
        }

        .bg-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
        }

        .register-box {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.96);
            padding: 40px 35px;
            border-radius: 16px;
            max-width: 500px; /* Slightly wider than login for more fields */
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
            margin-bottom: 5px;
        }

        .form-control, .input-group-text {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 1rem;
        }

        .form-control:focus {
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

        .btn-register {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            margin-top: 20px;
        }

        .btn-register:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(192, 57, 43, 0.3);
            color: #fff;
        }

        .password-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 8px;
            margin-bottom: 15px;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: var(--text-dark);
        }
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include "../header.php"; ?>

<div class="page-wrapper">
    <div class="main-bg-container">
        <div class="bg-overlay"></div>
        
        <div class="register-box">
            <h2>Sign Up</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger text-center py-2 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success text-center py-2 mb-4"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" placeholder="John" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Doe" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Mobile Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">+91</span>
                        <input type="tel" name="phone" id="phone" class="form-control" placeholder="9876543210" required pattern="\d{10}" title="Please enter a 10-digit mobile number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
                        <i class="fa-solid fa-eye toggle-eye" id="togglePassword"></i>
                    </div>
                    <div class="password-hint">
                        <i class="fas fa-info-circle me-1"></i> Min 8 chars, with 1 uppercase, 1 number & 1 special character.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-wrapper">
                         <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
                         <i class="fa-solid fa-eye toggle-eye" id="toggleConfirmPassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-register">
                    CREATE ACCOUNT <i class="fas fa-user-plus ms-2"></i>
                </button>

                <div class="login-link">
                    <span>Already have an account? <a href="../login.php">Login here</a></span>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "../footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function setupPasswordToggle(toggleId, passwordId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(passwordId);

        toggle.addEventListener('click', function () {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
</script>
</body>
</html>