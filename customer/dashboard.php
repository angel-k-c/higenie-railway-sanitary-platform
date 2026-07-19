<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['form_type'] ?? '';

    if ($type === 'rating_submit') {
        $order_id = intval($_POST['order_id']);
        $rating = intval($_POST['rating']);
        $review = trim($_POST['review']);

        $check = $pdo->prepare("SELECT id FROM ratings WHERE order_id=?");
        $check->execute([$order_id]);
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE ratings SET rating=?, review_text=? WHERE order_id=?");
            $stmt->execute([$rating, $review, $order_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO ratings (order_id, customer_id, rating, review_text) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $user_id, $rating, $review]);
        }
        $msg = "Rating submitted successfully.";
    }

    if ($type === 'complaint_submit') {
        $order_id = intval($_POST['order_id']);
        $complaint_text = trim($_POST['complaint_text']);
        $stmt = $pdo->prepare("INSERT INTO complaints (order_id, customer_id, complaint_text) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $user_id, $complaint_text]);
        $msg = "Complaint submitted successfully.";
    }

    if ($type === 'cancel_order') {
        $order_id = intval($_POST['order_id']);
        $cancel_reason = trim($_POST['cancel_reason'] ?? '');
        
        $check = $pdo->prepare("SELECT id, order_status, delivery_agent_id, total_amount FROM orders WHERE id = ? AND user_id = ?");
        $check->execute([$order_id, $user_id]);
        $order = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $msg = "Order not found.";
        } elseif ($order['delivery_agent_id'] !== null) {
            $msg = "Cannot cancel order - delivery agent already assigned.";
        } elseif ($order['order_status'] === 'Cancelled') {
            $msg = "Order is already cancelled.";
        } elseif ($order['order_status'] === 'Delivered') {
            $msg = "Cannot cancel a delivered order.";
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE id = ?");
            $stmt->execute([$order_id]);
            
            $refund_amount = $order['total_amount'];
            $refund_type = 'User Cancelled';
            
            $stmt_refund = $pdo->prepare("
                INSERT INTO refunds (order_id, refund_amount, refund_type, refund_reason) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt_refund->execute([$order_id, $refund_amount, $refund_type, $cancel_reason]);
            
            $msg = "Order cancelled successfully. A **refund request** has been submitted for review.";
        }
    }

    if ($msg) {
        $_SESSION['flash_msg'] = $msg;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$stmt = $pdo->prepare("
    SELECT o.id, o.created_at, o.total_amount, o.order_status, o.delivery_agent_id,
            r.rating, r.review_text,
            c.status AS complaint_status
    FROM orders o
    LEFT JOIN ratings r ON o.id = r.order_id
    LEFT JOIN complaints c ON o.id = c.order_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_orders = count($orders);
$delivered_orders = 0;
$pending_orders = 0;
foreach ($orders as $o) {
    if ($o['order_status'] === 'Delivered') $delivered_orders++;
    if ($o['order_status'] === 'Pending' || $o['order_status'] === 'Out for Delivery') $pending_orders++;
}

function getStatusClass($status) {
    switch ($status) {
        case 'Delivered': return 'bg-success-subtle text-success border border-success-subtle';
        case 'Pending': return 'bg-warning-subtle text-warning border border-warning-subtle';
        case 'Cancelled': return 'bg-danger-subtle text-danger border border-danger-subtle';
        case 'Out for Delivery': return 'bg-info-subtle text-info border border-info-subtle';
        default: return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
    }
}

function canCancelOrder($order) {
    return $order['delivery_agent_id'] === null 
            && $order['order_status'] !== 'Cancelled' 
            && $order['order_status'] !== 'Delivered';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hi.Genie - My Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
    :root { --primary-color: #c0392b; --text-dark: #2c3e50; --bg-light: #f8f9fa; }
    body { font-family: 'Inter', system-ui, sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
    
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color), #e74c3c);
        color: white;
        padding: 3rem 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(192, 57, 43, 0.2);
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
        height: 100%;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-icon {
        width: 50px; height: 50px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 1rem;
    }
    .stat-total .stat-icon { background: rgba(52, 152, 219, 0.1); color: #3498db; }
    .stat-delivered .stat-icon { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
    .stat-pending .stat-icon { background: rgba(241, 196, 15, 0.1); color: #f1c40f; }

    .card-custom {
        border: none; border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        background: white; overflow: hidden;
    }
    .card-header-custom {
        background: white; padding: 1.5rem 2rem;
        border-bottom: 1px solid #f0f0f0;
    }
    .table-custom thead th {
        background: #f8f9fa; color: #7f8c8d;
        font-weight: 600; text-transform: uppercase; font-size: 0.8rem;
        letter-spacing: 0.5px; border-bottom: 2px solid #eee;
        padding: 1rem;
    }
    .table-custom td { padding: 1rem; vertical-align: middle; }
    
    .star { font-size: 1.5rem; color: #e0e0e0; cursor: pointer; transition: color 0.2s; }
    .star.selected, .star:hover { color: #ffc107; }
    .modal-content { border-radius: 16px; border: none; overflow: hidden; }
    .modal-header { background: var(--primary-color); color: white; border-bottom: none; padding: 1.5rem; }
    .btn-primary-custom { background: var(--primary-color); border: none; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; }
    .btn-primary-custom:hover { background: #a93226; }
    
    .alert-warning-custom {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 12px;
        padding: 1rem 1.5rem;
    }
</style>
</head>
<body>

<?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success text-center m-0 rounded-0 fw-bold" style="background: #d4edda; color: #155724; border: none;">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_msg']) ?>
    </div>
    <?php unset($_SESSION['flash_msg']); ?>
<?php endif; ?>

<?php include "../header.php"; ?>

<div class="container py-5">
    <div class="welcome-banner">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold mb-2">Hello, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>! 👋</h1>
                <p class="mb-0 opacity-75 fs-5">Here's what's happening with your orders.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="/higenie/products.php" class="btn btn-light fw-bold px-4 py-2 rounded-pill shadow-sm">
                    <i class="fas fa-shopping-bag me-2"></i> Browse Products
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3 class="fw-bold mb-1"><?= $total_orders ?></h3>
                <p class="text-muted mb-0 fw-semibold">Total Orders</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-delivered">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <h3 class="fw-bold mb-1"><?= $delivered_orders ?></h3>
                <p class="text-muted mb-0 fw-semibold">Delivered</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-pending">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <h3 class="fw-bold mb-1"><?= $pending_orders ?></h3>
                <p class="text-muted mb-0 fw-semibold">In Progress</p>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold"><i class="fas fa-receipt me-2 text-muted"></i> Order History</h4>
        </div>
        <div class="card-body p-0">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" alt="No Orders" style="width: 120px; opacity: 0.5;" class="mb-3">
                    <h5 class="text-muted fw-bold">No orders yet</h5>
                    <p class="text-muted mb-4">Looks like you haven't made your first purchase.</p>
                    <a href="/higenie/products.php" class="btn btn-primary-custom text-white">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Placed On</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Your Rating</th>
                                <th>Support</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="fw-bold text-primary">#<?= $order['id'] ?></td>
                                <td>
                                    <div class="fw-semibold"><?= date("M d, Y", strtotime($order['created_at'])) ?></div>
                                    <small class="text-muted"><?= date("h:i A", strtotime($order['created_at'])) ?></small>
                                </td>
                                <td class="fw-bold">₹<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 <?= getStatusClass($order['order_status']) ?>">
                                        <?= htmlspecialchars($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['rating']): ?>
                                        <div class="text-warning mb-1">
                                            <?php for ($i=1; $i<=5; $i++): ?>
                                                <i class="fa-star <?= $i <= $order['rating'] ? 'fas' : 'far text-muted opacity-25' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <a href="#" class="text-muted small text-decoration-none" data-bs-toggle="modal" data-bs-target="#rateModal" data-order="<?= $order['id'] ?>" data-rating="<?= $order['rating'] ?>" data-review="<?= htmlspecialchars($order['review_text']) ?>">
                                            <i class="fas fa-pen me-1"></i>Edit
                                        </a>
                                    <?php elseif ($order['order_status'] === 'Delivered'): ?>
                                        <button class="btn btn-sm btn-outline-warning fw-semibold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rateModal" data-order="<?= $order['id'] ?>">
                                            <i class="far fa-star me-1"></i> Rate
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($order['complaint_status']): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-2">
                                            <i class="fas fa-headset me-1"></i> <?= htmlspecialchars($order['complaint_status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-danger fw-semibold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#complaintModal" data-order="<?= $order['id'] ?>">
                                            <i class="fas fa-exclamation-circle me-1"></i> Report Issue
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (canCancelOrder($order)): ?>
                                        <button class="btn btn-sm btn-outline-secondary fw-semibold rounded-pill px-3" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#cancelModal" 
                                                    data-order="<?= $order['id'] ?>">
                                            <i class="fas fa-times me-1"></i> Cancel Order
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50 small">
                                            <?php if ($order['delivery_agent_id']): ?>
                                                <i class="fas fa-truck me-1"></i>Agent Assigned
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="rateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-star me-2"></i>Rate Your Experience</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <input type="hidden" name="form_type" value="rating_submit">
                <input type="hidden" name="order_id" id="rate_order_id">
                <p class="text-muted mb-4">How was your delivery? Tap the stars to rate.</p>
                <div class="mb-4">
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <i class="fa-star fas star mx-1" data-value="<?= $i ?>"></i>
                    <?php endfor; ?>
                    <input type="hidden" name="rating" id="rating_value" required>
                </div>
                <div class="form-floating">
                    <textarea name="review" id="review_text" class="form-control" placeholder="Leave a comment here" style="height: 100px"></textarea>
                    <label for="review_text">Additional feedback (optional)</label>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Submit Rating</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="complaintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title fw-bold"><i class="fas fa-shield-alt me-2"></i>Report an Issue</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="form_type" value="complaint_submit">
                <input type="hidden" name="order_id" id="complaint_order_id">
                <p class="text-muted mb-3">We're sorry something went wrong. Please describe the issue below so we can help.</p>
                <div class="form-floating">
                    <textarea name="complaint_text" id="complaint_text" class="form-control" placeholder="Describe issue" style="height: 150px" required></textarea>
                    <label for="complaint_text">What went wrong?</label>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger fw-bold px-4">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-exclamation-triangle me-2"></i>Cancel Order & Request Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="form_type" value="cancel_order">
                <input type="hidden" name="order_id" id="cancel_order_id">
                
                <div class="alert-warning-custom mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Are you sure you want to cancel this order?</strong> A refund will be automatically requested.
                </div>
                
                <div class="form-floating mb-4">
                    <textarea name="cancel_reason" id="cancel_reason" class="form-control" placeholder="Reason for cancellation (optional)" style="height: 100px"></textarea>
                    <label for="cancel_reason">Reason for cancellation (optional, for refund review)</label>
                </div>
                
                <p class="text-muted mb-0">
                    <i class="fas fa-check-circle text-success me-2"></i> You can cancel your order before a delivery agent is assigned.<br>
                    <i class="fas fa-times-circle text-danger me-2"></i> Once an agent is assigned, cancellation is not possible.
                </p>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Keep Order</button>
                <button type="submit" class="btn btn-warning fw-bold px-4 text-dark">Yes, Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<?php include "../footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating_value');

    function updateStars(value) {
        stars.forEach(s => {
            const starValue = parseInt(s.dataset.value);
            s.classList.toggle('text-warning', starValue <= value);
            s.classList.toggle('selected', starValue <= value);
            if (starValue > value) s.style.color = '#e0e0e0';
            else s.style.color = '';
        });
    }

    stars.forEach(star => {
        star.addEventListener('click', () => {
            const value = parseInt(star.dataset.value);
            ratingInput.value = value;
            updateStars(value);
        });
        star.addEventListener('mouseover', () => updateStars(parseInt(star.dataset.value)));
        star.addEventListener('mouseout', () => updateStars(parseInt(ratingInput.value || 0)));
    });

    const rateModal = document.getElementById('rateModal');
    rateModal.addEventListener('show.bs.modal', event => {
        const btn = event.relatedTarget;
        document.getElementById('rate_order_id').value = btn.getAttribute('data-order');
        document.getElementById('review_text').value = btn.getAttribute('data-review') || '';
        const rating = parseInt(btn.getAttribute('data-rating') || 0);
        ratingInput.value = rating;
        updateStars(rating);
    });

    const complaintModal = document.getElementById('complaintModal');
    complaintModal.addEventListener('show.bs.modal', event => {
        const btn = event.relatedTarget;
        document.getElementById('complaint_order_id').value = btn.getAttribute('data-order');
    });

    const cancelModal = document.getElementById('cancelModal');
    cancelModal.addEventListener('show.bs.modal', event => {
        const btn = event.relatedTarget;
        document.getElementById('cancel_order_id').value = btn.getAttribute('data-order');
        document.getElementById('cancel_reason').value = ''; 
    });
});
</script>
</body>
</html>