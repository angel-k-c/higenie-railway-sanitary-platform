<?php
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

// Assign Delivery Agent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_assign'])) {
    $orderId = (int)$_POST['order_id'];
    $agentId = (int)$_POST['agent_id'];

    header('Content-Type: application/json');

    $checkStmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = ?");
    $checkStmt->execute([$orderId]);
    $status = $checkStmt->fetchColumn();

    if ($status === 'Cancelled') {
        echo json_encode(["status" => "error", "message" => "Cannot assign agent to a cancelled order."]);
        exit();
    }

    if ($agentId > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET delivery_agent_id = ?, order_status = 'Assigned', assigned_at = NOW() WHERE id = ?");
        $stmt->execute([$agentId, $orderId]);

        $agent = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $agent->execute([$agentId]);
        $agentData = $agent->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "message" => "Delivery agent assigned successfully.",
            "agent_name" => $agentData ? $agentData['first_name'] . ' ' . $agentData['last_name'] : ''
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid agent selection."]);
    }
    exit();
}

if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];

    $stmt = $pdo->prepare("
        SELECT
            o.*,
            c.first_name AS customer_first_name,
            c.last_name AS customer_last_name,
            c.email AS customer_email,
            da.first_name AS agent_first_name,
            da.last_name AS agent_last_name
        FROM orders o
        JOIN users c ON o.user_id = c.id
        LEFT JOIN users da ON o.delivery_agent_id = da.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $stmt_items = $pdo->prepare("
            SELECT oi.*, p.product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt_items->execute([$orderId]);
        $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $agents = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'd_agent' AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $orders = $pdo->query("
        SELECT o.id, o.pnr_number, o.total_amount, o.order_status, o.created_at, 
               u.first_name, u.last_name, da.first_name AS agent_first_name, da.last_name AS agent_last_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN users da ON o.delivery_agent_id = da.id
        ORDER BY o.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= isset($order) ? 'Order Details' : 'Orders Management' ?> - Hi.Genie Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
.main-card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
.table thead th { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; border-bottom: 0; font-weight: 600; }
.status-badge { font-weight: 500; padding: 6px 12px; border-radius: 12px; font-size: 0.8rem; letter-spacing: 0.5px; }
.detail-card .card-header { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; font-size: 1.2rem; font-weight: 600; }
.info-group dt { font-weight: 600; color: #6c757d; }
.info-group dd { font-weight: 500; }
.btn-higenie { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; border: none; }
.btn-higenie:hover { background: #a93226; color: #fff; }
.alert { border-radius: 10px; }
</style>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="container-fluid mt-4">

<?php if (isset($order) && $order): ?>
    <div id="alertBox"></div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Order Details</h2>
        <a href="view_orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card main-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-receipt"></i> Order #<?= htmlspecialchars($order['id']) ?></span>
                    <?php
                        $status_class = 'bg-secondary';
                        if ($order['order_status'] == 'Delivered') $status_class = 'bg-success';
                        if ($order['order_status'] == 'Pending' || $order['order_status'] == 'Confirmed') $status_class = 'bg-warning text-dark';
                        if ($order['order_status'] == 'Cancelled') $status_class = 'bg-danger';
                        if (in_array($order['order_status'], ['Assigned', 'Out for Delivery'])) $status_class = 'bg-info text-dark';
                    ?>
                    <span class="badge <?= $status_class ?>"><?= htmlspecialchars($order['order_status']) ?></span>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Order Items</h5>
                    <table class="table align-middle">
                        <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                            <?php foreach($order_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($item['quantity']) ?></td>
                                <td class="text-end">₹<?= number_format($item['price'], 2) ?></td>
                                <td class="text-end">₹<?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="3" class="text-end border-0 fs-5 fw-bold">Total</td>
                                <td class="text-end border-0 fs-5 fw-bold">₹<?= number_format($order['total_amount'], 2) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card main-card">
                <div class="card-header"><i class="fas fa-info-circle"></i> Details</div>
                <div class="card-body">
                    <dl class="row info-group g-3">
                        <dt class="col-12">Customer</dt>
                        <dd class="col-12"><?= htmlspecialchars($order['customer_first_name'] . ' ' . $order['customer_last_name']) ?><br>
                            <small><?= htmlspecialchars($order['customer_email']) ?></small></dd>

                        <dt class="col-12">Assigned Agent</dt>
                        <dd class="col-12" id="agentSection">
                            <?php if ($order['order_status'] === 'Cancelled'): ?>
                                <span class="text-danger fw-bold">Order Cancelled</span>
                            <?php elseif ($order['delivery_agent_id']): ?>
                                <div id="assignedAgent">
                                    <?= htmlspecialchars($order['agent_first_name'] . ' ' . $order['agent_last_name']) ?>
                                </div>
                            <?php else: ?>
                                <div id="assignForm">
                                    <select id="agentSelect" class="form-select mb-2">
                                        <option value="">Select Delivery Agent</option>
                                        <?php foreach ($agents as $agent): ?>
                                            <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-higenie btn-sm" id="assignBtn">Assign</button>
                                </div>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-12">PNR</dt>
                        <dd class="col-12"><?= htmlspecialchars($order['pnr_number']) ?></dd>

                        <dt class="col-12">Payment</dt>
                        <dd class="col-12 mb-0">Status: <span class="badge bg-success"><?= htmlspecialchars($order['payment_status']) ?></span></dd>
                        <dd class="col-12">ID: <small><?= htmlspecialchars($order['payment_id'] ?? 'N/A') ?></small></dd>

                        <dt class="col-12">Timestamps</dt>
                        <dd class="col-12 mb-0">Created: <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></dd>
                        <dd class="col-12 mb-0">Assigned: <?= $order['assigned_at'] ? date("d M Y, h:i A", strtotime($order['assigned_at'])) : 'Auto' ?></dd>
                        <dd class="col-12">Delivered: <?= $order['delivered_at'] ? date("d M Y, h:i A", strtotime($order['delivered_at'])) : 'N/A' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <h2 class="fw-bold mb-4">Orders Management</h2>
    <div class="main-card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr><th>Order ID</th><th>Customer</th><th>PNR</th><th>Amount</th><th>Status</th><th>Agent</th><th>Date</th><th>View</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" class="text-center py-4">No orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                <td><?= htmlspecialchars($order['pnr_number']) ?></td>
                                <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <?php
                                        $status_class = 'bg-secondary';
                                        if ($order['order_status'] == 'Delivered') $status_class = 'bg-success';
                                        if ($order['order_status'] == 'Pending' || $order['order_status'] == 'Confirmed') $status_class = 'bg-warning text-dark';
                                        if ($order['order_status'] == 'Cancelled') $status_class = 'bg-danger';
                                        if (in_array($order['order_status'], ['Assigned', 'Out for Delivery'])) $status_class = 'bg-info text-dark';
                                    ?>
                                    <span class="badge <?= $status_class ?> status-badge"><?= htmlspecialchars($order['order_status']) ?></span>
                                </td>
                                <td><?= $order['agent_first_name'] ? htmlspecialchars($order['agent_first_name'] . ' ' . $order['agent_last_name']) : 'Auto-assigned / Pending' ?></td>
                                <td><?= htmlspecialchars(date("d M Y", strtotime($order['created_at']))) ?></td>
                                <td><a href="view_orders.php?id=<?= $order['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).on("click", "#assignBtn", function() {
    const agentId = $("#agentSelect").val();
    const orderId = <?= isset($order['id']) ? (int)$order['id'] : 0 ?>;

    if (!agentId) {
        showAlert("Please select a delivery agent.", "danger");
        return;
    }

    $.post("", { ajax_assign: 1, order_id: orderId, agent_id: agentId }, function(res) {
        if (res.status === "success") {
            $("#assignForm").replaceWith(`<div id='assignedAgent'>${res.agent_name}</div>`);
            showAlert(res.message, "success");
             setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(res.message, "danger");
        }
    }, "json");
});

function showAlert(msg, type) {
    $("#alertBox").html(`<div class="alert alert-${type}">${msg}</div>`);
    setTimeout(() => $("#alertBox").html(""), 3000);
}
</script>

<?php include '../footer.php'; ?>
</body>
</html>