<?php
session_start();

require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); // Email or Phone
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (!empty($identifier) && !empty($newPassword) && !empty($confirmPassword)) {
        if ($newPassword !== $confirmPassword) {
            $message = "<div class='alert alert-danger text-center'> Passwords do not match</div>";
        } elseif (strlen($newPassword) < 6) {
            $message = "<div class='alert alert-danger text-center'> Password must be at least 6 characters</div>";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = ? WHERE email = ? OR phone = ?";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([$hashedPassword, $identifier, $identifier]);

            if ($stmt->rowCount() > 0) {
                $message = "<div class='alert alert-success text-center'> Password reset successful for <b>" . htmlspecialchars($identifier) . "</b></div>";
            } else {
                $message = "<div class='alert alert-danger text-center'> No account found with that Email/Phone</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-danger text-center'>Please fill in all fields</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hi.Genie - Reset Password</title>
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
            color: var(--text-dark);
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
            background: url('images/background.jpg') no-repeat center center;
            background-size: cover;
        }

        .bg-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5); 
            backdrop-filter: blur(3px); /
        }

        .reset-box {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.96);
            padding: 40px 30px;
            border-radius: 16px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border-top: 5px solid var(--primary-color);
        }

        h2 {
            text-align: center;
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .form-control {
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

        .btn-reset {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-top: 20px;
            font-size: 1.05rem;
        }

        .btn-reset:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(192, 57, 43, 0.3);
            color: #fff;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-weight: 500;
        }

        .back-link a {
            color: var(--text-dark);
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-wrapper">
    <div class="main-bg-container">
        <div class="bg-overlay"></div>
        <div class="reset-box">
            <h2>Reset Password</h2>
            
            <?php if (!empty($message)) echo $message; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="identifier" class="form-label">Email</label>
                    <input type="text" name="identifier" id="identifier" class="form-control" placeholder="john@example.com" required>
                </div>
                
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Min 6 characters" required>
                        <i class="fa-solid fa-eye toggle-eye" id="toggleNewPassword"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
                        <i class="fa-solid fa-eye toggle-eye" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-reset">Reset Password</button>
            </form>

            <div class="back-link">
                <a href="login.php"><i class="fas fa-arrow-left me-2"></i>Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    //  New Password toggle
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const newPasswordInput = document.getElementById('new_password');

    toggleNewPassword.addEventListener('click', function () {
        const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        newPasswordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    //  Confirm Password toggle
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('confirm_password');

    toggleConfirmPassword.addEventListener('click', function () {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>