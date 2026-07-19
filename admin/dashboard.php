<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

require __DIR__ . '/../db.php';

$recentOrders = $pdo->query("
    SELECT o.id, o.pnr_number, o.total_amount, o.order_status, o.created_at,
           u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.delivery_agent_id IS NULL AND o.order_status NOT IN ('Cancelled', 'Delivered')
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
html, body { height: 100%; margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f5f7; }
body { display: flex; flex-direction: column; min-height: 100vh; }
.main-wrapper { display: flex; flex: 1; min-height: calc(100vh - 60px); }
.sidebar { position: sticky; top: 70px; left: 0; width: 250px; background: #fff; padding: 20px 0; box-shadow: 2px 0 12px rgba(0,0,0,0.05); }
.sidebar a { display: flex; align-items: center; gap: 10px; padding: 12px 25px; color: #555; text-decoration: none; font-size: 15px; transition: all 0.2s; }
.sidebar a:hover, .sidebar a.active { background: #c0392b; color: #fff; font-weight: bold; border-radius: 0 25px 25px 0; }
.content { flex-grow: 1; padding: 40px 30px; margin-left: 250px; display: flex; flex-direction: column; gap: 30px; min-height: calc(100vh - 60px); }
.dashboard-heading { font-size: 2rem; color: #c0392b; font-weight: 700; text-align: center; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); animation: fadeIn 1s ease-in-out; }
#recentOrders { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); animation: fadeIn 1s ease-in-out; }
.table thead th { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; border-bottom: 0; font-weight: 600; }
.status-badge { font-weight: 500; padding: 6px 12px; border-radius: 12px; font-size: 0.8rem; }
@keyframes fadeIn { from {opacity:0; transform: translateY(20px);} to {opacity:1; transform: translateY(0);} }
</style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="main-wrapper">
    <div class="sidebar" id="sidebar">
        <a href="/higenie/admin/products/products.php"><i class="bi bi-box-seam"></i> Products</a>
        <a href="/higenie/admin/d_agents.php"><i class="bi bi-truck"></i> Delivery Agents</a>
        <a href="/higenie/admin/trains/live_trains.php"><i class="bi bi-train-front"></i> Trains</a>
        <a href="/higenie/admin/view_customers.php"><i class="bi bi-people"></i> Customers</a>
        <a href="/higenie/admin/view_orders.php"><i class="bi bi-receipt"></i> Orders</a>
        <a href="/higenie/admin/view_refunds.php"><i class="bi bi-arrow-counterclockwise"></i> Refunds</a>
        <a href="/higenie/admin/ratings_reviews.php"><i class="bi bi-star"></i> Ratings & Reviews</a>
        <a href="/higenie/admin/view_complaints.php"><i class="bi bi-exclamation-triangle"></i> Complaints</a>
        <a href="/higenie/admin/view_feedbacks.php"><i class="bi bi-lightbulb"></i> Feedback</a>
        <a href="/higenie/admin/generate_reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
        <a href="/higenie/admin/insert_admin.php"><i class="bi bi-shield-lock"></i> Add Admin</a>
    </div>

    <div class="content">
        <div class="dashboard-heading">
            Welcome Admin, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>! 👋
        </div>

        <div id="recentOrders">
            <h4 class="mb-3">Recent Orders</h4>
            <?php if (empty($recentOrders)): ?>
                <p class="text-center text-muted">No pending orders to assign.</p>
            <?php else: ?>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>PNR</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></td>
                                <td><?= htmlspecialchars($order['pnr_number']) ?></td>
                                <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($order['order_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
