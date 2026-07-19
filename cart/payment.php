<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /higenie/login.php");
    exit();
}
if (!isset($_SESSION['checkout_data'])) {
    header("Location: checkout.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$checkout_data = $_SESSION['checkout_data'];

$user_stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$cart_stmt = $pdo->prepare("SELECT c.*, p.product_name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND c.status = 'cart'");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll();

if (empty($cart_items)) {
    header("Location: /higenie/cart/cart.php");
    exit();
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$delivery_charge = ($subtotal > 0) ? 50.00 : 0;
$grand_total = $subtotal + $delivery_charge;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm & Pay - Hi.Genie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { background: #fff; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: none; }
        .summary-card-header { background-color: #d75858ff !important; color: white; }
    </style>
</head>
<body>
<?php include '../header.php'; ?>

<div class="container my-5">
    <h2 class="fw-bold text-center mb-4" style="color: #2c3e50;">Confirm and Pay</h2>
    <div id="payment-error" class="alert alert-danger d-none" role="alert"></div>
    
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header summary-card-header">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Final Confirmation</h5>
                </div>
                <div class="card-body p-4">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Delivery Details</h6>
                    <p><strong>PNR Number:</strong> <?= htmlspecialchars($checkout_data['pnr_number']) ?></p>
                    <p><strong>Train:</strong> <?= htmlspecialchars($checkout_data['train_number']) ?></p>
                    <p><strong>Coach / Seat:</strong> <?= htmlspecialchars($checkout_data['coach']) ?> / <?= htmlspecialchars($checkout_data['seat_number']) ?></p>

                    <h6 class="fw-bold border-bottom pb-2 mt-4 mb-3">Order Summary</h6>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach($cart_items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)</span>
                            <strong>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal</span>
                        <span>₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Delivery Charges</span>
                        <span>₹<?= number_format($delivery_charge, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 text-success">
                        <span>Grand Total</span>
                        <span>₹<?= number_format($grand_total, 2) ?></span>
                    </div>

                    <div class="d-grid mt-4">
                        <button id="pay-button" class="btn btn-success btn-lg">
                            <i class="fas fa-lock me-2"></i>Pay ₹<?= number_format($grand_total, 2) ?> Securely
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>

<script>
document.getElementById('pay-button').onclick = function(e) {
    e.preventDefault();
    const payButton = this;
    payButton.disabled = true;
    payButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

    var options = {
        "key": "rzp_test_1DP5mmOlF5G5ag", 
        "amount": "<?= $grand_total * 100 ?>", 
        "currency": "INR",
        "name": "Hi.Genie",
        "description": "Payment for Train Delivery Service",
        "handler": function (response) {
            processOrder(response.razorpay_payment_id);
        },
        "prefill": {
            "first_name": "<?= htmlspecialchars($user['first_name']) ?>",
            "last_name": "<?= htmlspecialchars($user['last_name']) ?>",
            "email": "<?= htmlspecialchars($user['email']) ?>",
            "contact": "<?= htmlspecialchars($user['phone']) ?>"
        },
        "theme": {
            "color": "#2c3e50"
        },
        "modal": {
            "ondismiss": function() {
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-lock me-2"></i>Pay ₹<?= number_format($grand_total, 2) ?> Securely';
            }
        }
    };
    
    var rzp1 = new Razorpay(options);
    rzp1.open();
};

function processOrder(paymentId) {
    const errorDiv = document.getElementById('payment-error');
    
    fetch('process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'razorpay_payment_id=' + encodeURIComponent(paymentId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect_url || '/higenie/customer/dashboard.php';
        } else {
            errorDiv.textContent = 'Payment failed: ' + data.message;
            errorDiv.classList.remove('d-none');
            const payButton = document.getElementById('pay-button');
            payButton.disabled = false;
            payButton.innerHTML = '<i class="fas fa-lock me-2" style="background-color: #c0392b; "></i>Pay ₹<?= number_format($grand_total, 2) ?> Securely';
        }
    })
    .catch(error => {
        errorDiv.textContent = 'A network error occurred. Please try again.';
        errorDiv.classList.remove('d-none');
        document.getElementById('pay-button').disabled = false; 
    });
}
</script>

</body>
</html>