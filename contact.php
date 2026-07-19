<?php
session_start();
require 'db.php';

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = "contact.php";
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (!empty($subject) && !empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedbacks (user_id, subject, message) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $subject, $message]);
            $success = "Feedback submitted successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us - Hi.Genie</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; color: #333; }
.breadcrumbs { background: #fff; padding: 20px 0; text-align: center; border-bottom: 1px solid #eee; }
.breadcrumbs h2 { margin-bottom: 10px; color: #c0392b; }
.breadcrumbs p { color: #666; font-size: 1rem; }
.contact-section { padding: 60px 0; display: flex; align-items: center; justify-content: center; min-height: 80vh; }
.contact-card { background: #ffffff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.07); overflow: hidden; border: 1px solid #e9ecef; }
.info-column { padding: 2.5rem; background: #fdfdfd; }
.info-column h3 { color: #222; font-weight: 600; margin-bottom: 1.5rem; }
.info-column p { color: #555; font-size: 0.95rem; line-height: 1.6; }
.contact-info { list-style: none; padding: 0; margin-top: 1.5rem; }
.contact-info li { display: flex; align-items: center; margin-bottom: 1rem; font-size: 0.95rem; color: #444; }
.contact-info li i { font-size: 1.1rem; width: 30px; text-align: center; margin-right: 10px; color: #c0392b; }
.social-links { text-align: left; margin-top: 2rem; }
.social-links a { display: inline-flex; justify-content: center; align-items: center; width: 40px; height: 40px; border-radius: 50%; background-color: #f1f1f1; color: #555; font-size: 1rem; margin-right: 8px; text-decoration: none; transition: all 0.3s ease; }
.social-links a:hover { background-color: #c0392b; color: #fff; transform: translateY(-3px); }
.form-column { padding: 2.5rem; }
.form-column h3 { color: #222; font-weight: 600; margin-bottom: 1.5rem; }
.feedback-form .form-label { color: #555; font-weight: 500; font-size: 0.9rem; }
.feedback-form .form-control { background-color: #f8f9fa; border: 1px solid #e0e0e0; color: #333; border-radius: 8px; padding: 0.75rem 1rem; transition: border-color 0.3s, box-shadow 0.3s; }
.feedback-form .form-control::placeholder { color: #aaa; }
.feedback-form .form-control:focus { background-color: #fff; color: #333; border-color: #c0392b; box-shadow: 0 0 0 0.2rem rgba(192,57,43,0.15); }
.feedback-form .btn-submit { background: #c0392b; border: none; color: #fff; font-weight: bold; padding: 12px; border-radius: 8px; width: 100%; transition: background-color 0.3s, transform 0.3s; }
.feedback-form .btn-submit:hover { background: #a93226; transform: translateY(-2px); }
.login-prompt { text-align: center; padding: 30px 0; border: 1px dashed #ccc; border-radius: 10px; background: #fff; }
.login-prompt a { text-decoration: none; font-weight: bold; color: #c0392b; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<main id="main">

<section class="breadcrumbs">
    <div class="container">
        <h2>Contact Us</h2>
        <p>We're here to help! Reach out to us with any questions or feedback.</p>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">
                <div class="contact-card">
                    <div class="row g-0">

                        <div class="col-lg-6 info-column">
                            <h3>Contact Information</h3>
                            <p>Feel free to contact us through any of the methods below, or follow us on social media.</p>
                            <ul class="contact-info">
                                <li><i class="fas fa-map-marker-alt"></i><span>Hi.Genie @ Kannur Railway Station, Kerala</span></li>
                                <li><i class="fas fa-phone"></i><span>+91 987 654 3210</span></li>
                                <li><i class="fas fa-envelope"></i><span>support@higenie.com</span></li>
                            </ul>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>

                        <div class="col-lg-6 form-column">
                            <h3>Send a Message</h3>

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <?php if ($success): ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <form action="contact.php" method="POST" class="feedback-form">
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" placeholder="e.g., Question about an order" required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" placeholder="Your message here..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-submit">Submit Feedback</button>
                                </form>
                            <?php else: ?>
                                <div class="login-prompt">
                                    <p>Please <a href="login.php">login</a> to submit feedback.</p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
