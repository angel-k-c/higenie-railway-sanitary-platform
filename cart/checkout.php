<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkout_data = [
        'pnr_number'  => filter_input(INPUT_POST, 'pnr_number', FILTER_SANITIZE_STRING),
        'train_number'=> filter_input(INPUT_POST, 'train_number', FILTER_SANITIZE_STRING),
        'coach'       => filter_input(INPUT_POST, 'coach', FILTER_SANITIZE_STRING),
        'seat_number' => filter_input(INPUT_POST, 'seat_number', FILTER_SANITIZE_STRING),
    ];

    $_SESSION['checkout_data'] = $checkout_data;
    header("Location: payment.php");
    exit();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /higenie/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

$trains_stmt = $pdo->query("SELECT train_number, train_name FROM trains ORDER BY train_name ASC");
$trains = $trains_stmt->fetchAll();
$customer_details_stmt = $pdo->prepare("SELECT pnr_number, train_number, coach, seat_number FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$customer_details_stmt->execute([$user_id]);
$customer_details = $customer_details_stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer_details) {
    $customer_details = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Hi.Genie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #fff; font-family: 'Segoe UI', sans-serif; }
        .form-label { font-weight: 600; }
        .card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: none; }
        .order-summary-item { font-size: 0.9rem; }
    </style>
</head>
<body>
<?php include '../header.php'; ?>

<div class="container my-5">
    <h2 class="fw-bold text-center mb-4" style="color: #c0392b;">Checkout: Delivery Details</h2>
    
    <form method="POST" id="checkout-form" class="needs-validation" novalidate>
        <div class="row g-4">
            
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-train me-2"></i>Delivery Details</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Your order will be scheduled for delivery today, <strong><?= date("l, F jS, Y") ?></strong>.</p>
                        
                        <div class="mb-3">
                            <label for="pnr_number" class="form-label">PNR Number</label>
                            <input type="text" class="form-control" id="pnr_number" name="pnr_number" value="<?= htmlspecialchars($_SESSION['checkout_data']['pnr_number'] ?? $customer_details['pnr_number'] ?? '') ?>" pattern="[0-9]{10}" title="PNR must be 10 digits." required>
                            <div class="form-text">Enter your 10-digit train PNR number for today's journey.</div>
                            <div class="invalid-feedback">Please enter a valid 10-digit PNR number.</div>
                        </div>

                        <div class="mb-3">
                            <label for="train_number" class="form-label">Train Number & Name</label>
                            <select class="form-select" id="train_number" name="train_number" required>
                                <option value="" <?= (empty($_SESSION['checkout_data']['train_number']) && empty($customer_details['train_number'])) ? 'selected' : '' ?>>Select your train...</option>
                                <?php 
                                    $selected_train = $_SESSION['checkout_data']['train_number'] ?? $customer_details['train_number'] ?? '';
                                    foreach($trains as $train): 
                                ?>
                                    <option value="<?= htmlspecialchars($train['train_number']) ?>" <?= $selected_train == $train['train_number'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($train['train_number']) ?> - <?= htmlspecialchars($train['train_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select your train.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="coach" class="form-label">Coach</label>
                                <input type="text" class="form-control" id="coach" name="coach" value="<?= htmlspecialchars($_SESSION['checkout_data']['coach'] ?? $customer_details['coach'] ?? '') ?>" placeholder="e.g., S5" required>
                                <div class="invalid-feedback">Please enter your coach number.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="seat_number" class="form-label">Seat</label>
                                <input type="text" class="form-control" id="seat_number" name="seat_number" value="<?= htmlspecialchars($_SESSION['checkout_data']['seat_number'] ?? $customer_details['seat_number'] ?? '') ?>" placeholder="e.g., 32" required>
                                <div class="invalid-feedback">Please enter your seat number.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach($cart_items as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center order-summary-item px-0">
                                <span><?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)</span>
                                <strong>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>₹<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery Charges</span>
                            <span>₹<?= number_format($delivery_charge, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Grand Total</span>
                            <span>₹<?= number_format($grand_total, 2) ?></span>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg" style="background-color: #c0392b; border-color: #c0392b;">
                                <i class="fas fa-arrow-circle-right me-2"></i>Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const coachInput = document.getElementById('coach');
    const seatInput = document.getElementById('seat_number');

    function updateSeatPlaceholder() {
        const coachValue = coachInput.value.trim().toLowerCase();
        if (coachValue === 'ur' || coachValue === 'general') {
            seatInput.placeholder = 'e.g., Front/Back';
        } else {
            seatInput.placeholder = 'e.g., 32';
        }
    }
    coachInput.addEventListener('input', updateSeatPlaceholder);
    updateSeatPlaceholder(); 

    // Bootstrap Form Validation
    const checkoutForm = document.getElementById('checkout-form');
    checkoutForm.addEventListener('submit', function(event) {
        if (!checkoutForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        checkoutForm.classList.add('was-validated');
    }, false);
});
</script>

</body> 
</html>