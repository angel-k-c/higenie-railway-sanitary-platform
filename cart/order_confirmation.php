<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_order_id'])) {
    header("Location: /higenie/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_SESSION['last_order_id'];
$stmt = $pdo->prepare(
    "SELECT o.id, o.order_date, o.total_amount, o.pnr_number, o.coach, o.seat_number, u.first_name 
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = ? AND o.user_id = ?"
);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: /higenie/customer/order_history.php");
    exit();
}

unset($_SESSION['last_order_id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - Hi.Genie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e0f2f1, #b2dfdb); }
        .confirmation-card { max-width: 600px; margin: auto; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<?php include '../header.php'; ?>

<div class="container my-5">
    <div class="card p-4 p-md-5 text-center confirmation-card">
        <div class="mb-4">
            <i class="fas fa-check-circle fa-5x text-success"></i>
        </div>
        <h2 class="fw-bold mb-3">Thank You, <?= htmlspecialchars($order['first_name']) ?>!</h2>
        <p class="lead text-muted">Your order has been confirmed.</p>
        
        <div class="card mt-4 text-start">
            <div class="card-body">
                <h5 class="card-title fw-bold">Order #<?= htmlspecialchars($order['id']) ?> Summary</h5>
                <ul class="list-unstyled mt-3">
                    <li><strong>PNR Number:</strong> <?= htmlspecialchars($order['pnr_number']) ?></li>
                    <li><strong>Coach/Seat:</strong> <?= htmlspecialchars($order['coach']) ?> / <?= htmlspecialchars($order['seat_number']) ?></li>
                    <li><strong>Order Date:</strong> <?= date("d M Y", strtotime($order['order_date'])) ?></li>
                    <li class="fw-bold fs-5 mt-2"><strong>Total Paid:</strong> ₹<?= number_format($order['total_amount'], 2) ?></li>
                </ul>
            </div>
        </div>

        <div class="mt-4">
            <a href="/higenie/customer/order_history.php" class="btn btn-primary">View Order History</a>
            <a href="/higenie/index.php" class="btn btn-outline-secondary">Continue Shopping</a>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
</body>
</html>