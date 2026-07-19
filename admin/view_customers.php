<?php
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action']) && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $newStatus = ($action === 'activate') ? 'active' : 'deactivated';

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'customer'");
    if ($stmt->execute([$newStatus, $id])) {
        $_SESSION['message'] = "<div class='alert alert-success'>Customer status updated successfully.</div>";
    } else {
        $_SESSION['message'] = "<div class='alert alert-danger'>Failed to update customer status.</div>";
    }
    header("Location: view_customers.php?id=" . $id);
    exit();
}

if (isset($_POST['action'], $_POST['id']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'xmlhttprequest') {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Action failed.'];

    if ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'deactivated' WHERE id = ? AND role = 'customer'");
        if ($stmt->execute([$id])) {
            $response = [
                'success' => true,
                'message' => '<div class="alert alert-warning">Customer account deactivated successfully.</div>',
                'new_status_html' => '<span class="badge bg-danger status-badge">Deactivated</span>',
                'new_action_html' => '<div class="btn-group"><a href="view_customers.php?id='.$id.'" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a><button class="btn btn-success btn-sm toggle-status" data-id="'.$id.'" data-action="activate"><i class="fas fa-user-check"></i></button></div>'
            ];
        }
    } elseif ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'customer'");
        if ($stmt->execute([$id])) {
            $response = [
                'success' => true,
                'message' => '<div class="alert alert-success">Customer account activated successfully.</div>',
                'new_status_html' => '<span class="badge bg-success status-badge">Active</span>',
                'new_action_html' => '<div class="btn-group"><a href="view_customers.php?id='.$id.'" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a><button class="btn btn-warning btn-sm toggle-status" data-id="'.$id.'" data-action="deactivate"><i class="fas fa-user-slash"></i></button></div>'
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_GET['id'])) {
    $customerId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    
    $customers = $pdo->query("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at ASC")->fetchAll();
    $statsQuery = "
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'deactivated' THEN 1 ELSE 0 END) as deactivated,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM users WHERE role = 'customer'";
    $stats = $pdo->query($statsQuery)->fetch(PDO::FETCH_ASSOC);
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= isset($customer) ? 'Customer Details' : 'Customers Management' ?> - Hi.Genie Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
    .container-fluid { padding: 15px; }
    .dashboard-header { margin-bottom: 1.4rem; }
    .stat-card { background-color: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; align-items: center; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card .icon { font-size: 2rem; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; color: #fff; }
    .icon-users { background-color: #0d6efd; }
    .icon-active { background-color: #198754; }
    .icon-deactivated { background-color: #dc3545; }
    .icon-pending { background-color: #ffc107; }
    .stat-card h3 { margin: 0; font-size: 1.8rem; font-weight: 700; }
    .stat-card p { margin: 0; color: #6c757d; }
    .main-card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
    .table thead th { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; border-bottom: 0; font-weight: 600; }
    .table { margin-bottom: 0; }
    .status-badge { font-weight: 500; padding: 6px 12px; border-radius: 12px; font-size: 0.8rem; letter-spacing: 0.5px; }
    .search-bar { padding: 1rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .detail-card { max-width: 800px; margin: auto; }
    .detail-card .card-header { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; font-size: 1.2rem; font-weight: 600; }
    .detail-list { font-size: 1.1rem; }
</style>
</head>
<body>

<?php include __DIR__ . '../../header.php'; ?>

<div class="container-fluid">

<?php if (isset($customer) && $customer):  ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Customer Details</h2>
        <a href="view_customers.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
    <div id="message"><?= $message ?? '' ?></div>
    <div class="card main-card detail-card">
        <div class="card-header"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($customer['name'] ?? 'N/A') ?></div>
        <div class="card-body p-4">
            <dl class="row detail-list">
                <dt class="col-sm-3">Customer ID</dt><dd class="col-sm-9"><?= htmlspecialchars($customer['id']) ?></dd>
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] ?? 'N/A') ?></dd>
                <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= htmlspecialchars($customer['email']) ?></dd>
                <dt class="col-sm-3">Phone</dt><dd class="col-sm-9"><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></dd>
                <dt class="col-sm-3">Account Status</dt>
                <dd class="col-sm-9">
                    <?php if ($customer['status'] === 'active'): ?><span class="badge bg-success status-badge">Active</span>
                    <?php elseif ($customer['status'] === 'pending'): ?><span class="badge bg-warning status-badge">Pending</span>
                    <?php else: ?><span class="badge bg-danger status-badge">Deactivated</span><?php endif; ?>
                </dd>
                <dt class="col-sm-3">Email Verified</dt>
                <dd class="col-sm-9">
                    <?php if ($customer['email_verified'] == 1): ?><span class="badge bg-info">Verified</span>
                    <?php else: ?><span class="badge bg-secondary">Not Verified</span><?php endif; ?>
                </dd>
                <dt class="col-sm-3">Login Count</dt><dd class="col-sm-9"><?= htmlspecialchars($customer['login_count']) ?></dd>
                <dt class="col-sm-3">Registered On</dt><dd class="col-sm-9"><?= htmlspecialchars(date("d M Y, h:i A", strtotime($customer['created_at']))) ?></dd>
            </dl>
        </div>
        <div class="card-footer text-end">
            <form method="POST" action="view_customers.php?id=<?= $customer['id'] ?>" class="d-inline">
                <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                <?php if ($customer['status'] === 'active'): ?>
                    <button type="submit" name="action" value="deactivate" class="btn btn-warning"><i class="fas fa-user-slash"></i> Deactivate</button>
                <?php else: ?>
                    <button type="submit" name="action" value="activate" class="btn btn-success"><i class="fas fa-user-check"></i> Activate</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

<?php elseif(isset($_GET['id'])): ?>
    <div class="alert alert-danger text-center"><h4>Customer Not Found</h4><p>The customer with the specified ID could not be found.</p><a href="view_customers.php" class="btn btn-primary mt-2">Return to Customer List</a></div>

<?php else: ?>
    <div class="dashboard-header">
        <br>
        <h2 class="fw-bold mb-4">Customers Management</h2>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6"><div class="stat-card"><div class="icon icon-users"><i class="fas fa-users"></i></div><div><h3><?= $stats['total'] ?? 0 ?></h3><p>Total Customers</p></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="stat-card"><div class="icon icon-active"><i class="fas fa-user-check"></i></div><div><h3><?= $stats['active'] ?? 0 ?></h3><p>Active</p></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="stat-card"><div class="icon icon-pending"><i class="fas fa-clock"></i></div><div><h3><?= $stats['pending'] ?? 0 ?></h3><p>Pending</p></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="stat-card"><div class="icon icon-deactivated"><i class="fas fa-user-slash"></i></div><div><h3><?= $stats['deactivated'] ?? 0 ?></h3><p>Deactivated</p></div></div></div>
        </div>
    </div>
    <div id="message"></div>
    <div class="main-card">
        <div class="search-bar"><input type="text" id="searchInput" class="form-control" placeholder="Search customers by name, email, or phone..."></div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="customersTable">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Verified</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="8" class="text-center py-4">No customers found.</td></tr>
                    <?php else: ?>
                        <?php $n=1; foreach ($customers as $customer): ?>
                            <tr id="row-<?= $customer['id'] ?>">
                                <td><?= $n++ ?></td>
                                <td><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($customer['email']) ?></td>
                                <td><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($customer['email_verified'] == 1): ?><span class="badge bg-info">Yes</span>
                                    <?php else: ?><span class="badge bg-secondary">No</span><?php endif; ?>
                                </td>
                                <td class="status">
                                    <?php if ($customer['status'] === 'active'): ?><span class="badge bg-success status-badge">Active</span>
                                    <?php elseif ($customer['status'] === 'pending'): ?><span class="badge bg-warning status-badge">Pending</span>
                                    <?php else: ?><span class="badge bg-danger status-badge">Deactivated</span><?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date("d M Y", strtotime($customer['created_at']))) ?></td>
                                <td class="actions">
                                    <div class="btn-group">
                                        <a href="view_customers.php?id=<?= $customer['id'] ?>" class="btn btn-info btn-sm" title="View Details"><i class="fas fa-eye"></i></a>
                                        <?php if ($customer['status'] === 'active'): ?>
                                            <button class="btn btn-warning btn-sm toggle-status" data-id="<?= $customer['id'] ?>" data-action="deactivate" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm toggle-status" data-id="<?= $customer['id'] ?>" data-action="activate" title="Activate"><i class="fas fa-user-check"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr id="no-results" style="display: none;"><td colspan="8" class="text-center py-4">No customers match your search.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
</div>

<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function(){
    // Live Search Functionality
    $('#searchInput').on('keyup', function() {
        let searchTerm=$(this).val().toLowerCase();let rowsFound=0;$('#customersTable tbody tr').each(function(){let row=$(this);if(row.attr('id')==='no-results'){return}let rowText=row.text().toLowerCase();if(rowText.includes(searchTerm)){row.show();rowsFound++}else{row.hide()}});if(rowsFound>0){$('#no-results').hide()}else{$('#no-results').show()}
    });

    // AJAX for status toggle on list page
    $(document).on('click', '.toggle-status', function(){
        let id = $(this).data('id');
        let action = $(this).data('action');
        if(!confirm(`Are you sure you want to ${action} this customer?`)) return;

        $.ajax({
            url: 'view_customers.php',
            type: 'POST',
            data: {id: id, action: action},
            dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(response){
                if(response.success){
                    $('#message').html(response.message).fadeIn();
                    let row = $('#row-' + id);
                    row.find('.status').html(response.new_status_html);
                    row.find('.actions').html(response.new_action_html);
                    setTimeout(() => $('#message').fadeOut(), 3000);
                } else {
                    $('#message').html(response.message || '<div class="alert alert-danger">An error occurred.</div>').fadeIn();
                    setTimeout(() => $('#message').fadeOut(), 3000);
                }
            }
        });
    });
});
</script>
</body>
</html>