<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'd_agent') {
    header("Location: ../login.php");
    exit();
}

$agent_id = $_SESSION['user_id'];

// Order Status Update 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    //  REMOVED 'Cancelled' from allowed list for agents.
    if (in_array($new_status, ['Out for Delivery', 'Delivered'])) {
        $sql = "UPDATE orders SET order_status = :status";
        if ($new_status === 'Delivered') {
            $sql .= ", delivered_at = NOW()";
        }
        // ensure order belongs to agent
        $sql .= " WHERE id = :id AND delivery_agent_id = :agent_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['status' => $new_status, 'id' => $order_id, 'agent_id' => $agent_id]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- agent details ---
$query = $pdo->prepare("SELECT u.first_name, u.last_name, u.email, u.phone, da.agent_image FROM users u JOIN delivery_agents da ON u.id = da.user_id WHERE u.id = ?");
$query->execute([$agent_id]);
$agent = $query->fetch(PDO::FETCH_ASSOC);

$default_photo = "../images/default_agent.png";
$agent_photo = $default_photo;

if (!empty($agent['agent_image'])) {
    $clean_image = basename($agent['agent_image']);
    
    if (file_exists(__DIR__ . "/../uploads/" . $clean_image)) {
        $agent_photo = "../uploads/" . $clean_image;
    } elseif (file_exists(__DIR__ . "/../images/" . $clean_image)) {
        $agent_photo = "../images/" . $clean_image;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone']);
    $upload_dir = __DIR__ . "/../uploads/"; 
  
    $file_name = basename($agent['agent_image']); 

    if (isset($_FILES['agent_image']) && $_FILES['agent_image']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['agent_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = uniqid("agt_").".".$ext;
            if (!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
            if (move_uploaded_file($_FILES['agent_image']['tmp_name'], $upload_dir.$new_name)) {
                $file_name = $new_name; 
                $stmt = $pdo->prepare("UPDATE delivery_agents SET agent_image=? WHERE user_id=?");
                $stmt->execute([$file_name, $agent_id]);
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET phone=? WHERE id=?");
    $stmt->execute([$phone, $agent_id]);

    echo json_encode(['success'=>true]);
    exit;
}

// ALL ORDERS FOR REPORT & DASHBOARD
$stmt = $pdo->prepare("
    SELECT o.id, o.train_number, o.coach, o.seat_number, o.order_status, o.delivered_at, o.created_at,
           c.first_name AS cust_fname, c.last_name AS cust_lname
    FROM orders o
    JOIN users c ON o.user_id = c.id
    WHERE o.delivery_agent_id=? 
    ORDER BY o.id DESC
");
$stmt->execute([$agent_id]);
$all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CALCULATE STATS & LISTS ---
$active_orders_list = array_filter($all_orders, fn($o)=>in_array($o['order_status'], ['Assigned', 'Confirmed', 'Out for Delivery']));
$completed_orders_list = array_filter($all_orders, fn($o)=>in_array($o['order_status'], ['Delivered','Cancelled']));

$active_count = count($active_orders_list);
$delivered_today = count(array_filter($all_orders, fn($o)=>$o['order_status']==='Delivered' && $o['delivered_at'] && date('Y-m-d', strtotime($o['delivered_at']))===date('Y-m-d')));

// REPORT STATS (Lifetime)
$report_total_assigned = count($all_orders); 
$report_total_delivered = count(array_filter($all_orders, fn($o)=>$o['order_status']==='Delivered'));
$report_remainder = max(0, $report_total_assigned - $report_total_delivered);

// CHECK IF BUSY
$is_busy_delivering = count(array_filter($active_orders_list, fn($o) => $o['order_status'] === 'Out for Delivery')) > 0;

function getStatusBadge($status) {
    return match($status) {
        'Assigned' => 'bg-warning text-dark',
        'Confirmed' => 'bg-primary',
        'Out for Delivery' => 'bg-info text-dark',
        'Delivered' => 'bg-success',
        'Cancelled' => 'bg-danger',
        default => 'bg-secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hi.Genie - Agent Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
.stats-card { border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.05); transition:0.2s; border:none; }
.stats-card:hover { transform:translateY(-3px); }
.profile-avatar { width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid #0d6efd; padding:2px; cursor:pointer; }
.order-card { border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.05); border:none; }
.report-section { background: #fff; border-radius: 16px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
.profile-panel { position:fixed; top:0; right:-400px; width:350px; height:100%; background:#fff; box-shadow:-4px 0 10px rgba(0,0,0,0.1); transition:right 0.3s ease-in-out; z-index:1050; padding:20px; overflow-y:auto; }
.profile-panel.open { right:0; }
.profile-panel img { width:100px; height:100px; border-radius:50%; object-fit:cover; margin-bottom:10px; background-color:#f0f0f0; }
.overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); display:none; z-index:1040; }
.overlay.show { display:block; }
</style>
</head>
<body>
<?php include '../header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Agent Dashboard</h3>
            <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($agent['first_name']) ?></p>
        </div>
        <img src="<?= htmlspecialchars($agent_photo) ?>" alt="Profile" class="profile-avatar" id="openProfileBtn">
    </div>

    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card stats-card p-3 text-center bg-primary bg-opacity-10">
                <h4 class="fw-bold text-primary mb-0"><?= $active_count ?></h4>
                <small class="text-muted fw-medium">Active</small>
            </div>
        </div>
        <div class="col-4">
             <div class="card stats-card p-3 text-center bg-success bg-opacity-10">
                <h4 class="fw-bold text-success mb-0"><?= $delivered_today ?></h4>
                <small class="text-muted fw-medium">Today</small>
            </div>
        </div>
        <div class="col-4">
             <div class="card stats-card p-3 text-center bg-warning bg-opacity-10">
                 <h4 class="fw-bold text-warning mb-0">
                    <?= $is_busy_delivering ? '<i class="fas fa-motorcycle"></i>' : '<i class="fas fa-check"></i>' ?>
                </h4>
                <small class="text-muted fw-medium"><?= $is_busy_delivering ? 'Busy' : 'Available' ?></small>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Active Deliveries</h5>
    <div class="row g-3 mb-5">
        <?php if(empty($active_orders_list)): ?>
            <div class="col-12 text-center text-muted py-5 bg-white rounded-4 shadow-sm">
                <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                <p class="mb-0">No active deliveries assigned.</p>
            </div>
        <?php else: foreach($active_orders_list as $order): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card order-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-user me-2"></i><?= htmlspecialchars($order['cust_fname'] . ' ' . $order['cust_lname']) ?></h6>
                        <span class="badge <?= getStatusBadge($order['order_status']) ?> px-3 py-2"><?= $order['order_status'] ?></span>
                    </div>
                    <div class="mb-4">
                        <p class="text-muted small mb-1">Order #<?= htmlspecialchars($order['id']) ?></p>
                        <h6 class="fw-bold mb-1"><i class="fas fa-train me-2 text-secondary"></i>Train <?= htmlspecialchars($order['train_number']) ?></h6>
                        <p class="text-muted mb-0 ms-4">Coach <?= htmlspecialchars($order['coach']) ?>, Seat <?= htmlspecialchars($order['seat_number']) ?></p>
                    </div>

                    <div class="d-grid gap-2 mt-auto">
                        <?php if ($order['order_status'] === 'Assigned'): ?>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="new_status" value="Out for Delivery">
                                <button class="btn btn-primary w-100 fw-medium" type="submit" name="update_order_status">
                                    Accept & Start Delivery
                                </button>
                            </form>
                            <?php elseif ($order['order_status'] === 'Confirmed'): ?>
                             <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="new_status" value="Out for Delivery">
                                <button class="btn btn-info text-white w-100 fw-medium" type="submit" name="update_order_status">
                                    <i class="fas fa-motorcycle me-2"></i>Out for Delivery
                                </button>
                            </form>
                        <?php elseif ($order['order_status'] === 'Out for Delivery'): ?>
                             <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="new_status" value="Delivered">
                                <button class="btn btn-success w-100 fw-bold py-2" type="submit" name="update_order_status">
                                    <i class="fas fa-check-double me-2"></i>MARK DELIVERED
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="report-section mb-5">
        <h5 class="fw-bold mb-4"><i class="fas fa-chart-pie me-2 text-primary"></i>Performance Report</h5>
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0" style="height: 250px;">
                 <canvas id="performanceChart"></canvas>
            </div>
            <div class="col-md-6">
                 <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h3 class="fw-bold text-dark"><?= $report_total_assigned ?></h3>
                        <p class="text-muted small text-uppercase mb-0 fw-bold ls-1">Total Assigned</p>
                    </div>
                    <div class="col-6 mb-3">
                        <h3 class="fw-bold text-success"><?= $report_total_delivered ?></h3>
                        <p class="text-muted small text-uppercase mb-0 fw-bold ls-1">Total Delivered</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Recent History</h5>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="list-group list-group-flush">
            <?php if(empty($completed_orders_list)): ?>
                <div class="list-group-item p-4 text-center text-muted">No history yet.</div>
            <?php else: 
                $recent_history = array_slice($completed_orders_list, 0, 5);
                foreach($recent_history as $order): ?>
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="mb-1">
                                <span class="badge <?= getStatusBadge($order['order_status']) ?> me-2"><?= $order['order_status'] ?></span>
                                <span class="fw-medium"><?= htmlspecialchars($order['cust_fname'] . ' ' . $order['cust_lname']) ?></span>
                            </div>
                            <h6 class="mb-0 text-muted small">
                                #<?= htmlspecialchars($order['id']) ?> | Train <?= htmlspecialchars($order['train_number']) ?>
                            </h6>
                        </div>
                        <span class="text-muted small"><?= date('M d, H:i', strtotime($order['created_at'] ?? 'now')) ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<div class="profile-panel" id="profilePanel">
    <button type="button" class="btn-close float-end" id="closePanel"></button>
    <div class="text-center mt-4">
        <img id="profileImage" src="<?= htmlspecialchars($agent_photo) ?>" alt="Agent Photo">
        <h5 class="mt-2"><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']) ?></h5>
        <p class="text-muted small mb-2"><?= htmlspecialchars($agent['email']) ?></p>
    </div>
    <form id="profileForm" enctype="multipart/form-data" method="POST">
        <input type="hidden" name="update_profile" value="1">
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($agent['phone']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Change Photo</label>
            <input type="file" name="agent_image" class="form-control" accept="image/jpeg,image/png,image/gif">
        </div>
        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
    </form>
</div>

<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const openBtn = document.getElementById('openProfileBtn');
const closeBtn = document.getElementById('closePanel');
const panel = document.getElementById('profilePanel');
const overlay = document.getElementById('overlay');
const form = document.getElementById('profileForm');

openBtn.addEventListener('click', ()=>{ panel.classList.add('open'); overlay.classList.add('show'); });
closeBtn.addEventListener('click', ()=>{ panel.classList.remove('open'); overlay.classList.remove('show'); });
overlay.addEventListener('click', ()=>{ panel.classList.remove('open'); overlay.classList.remove('show'); });

form.addEventListener('submit', e=>{
    e.preventDefault();
    const formData = new FormData(form);
    fetch('', {method:'POST', body:formData})
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                location.reload();
            }else{
                alert('Update failed: '+(data.error||'Unknown error'));
            }
        }).catch(err=>{ console.error(err); alert('Error updating profile.'); });
});

//  Chart
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Delivered', 'Other Status'],
        datasets: [{
            data: [<?= $report_total_delivered ?>, <?= $report_remainder ?>],
            backgroundColor: ['#198754', '#e9ecef'],
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
</body>
</html>