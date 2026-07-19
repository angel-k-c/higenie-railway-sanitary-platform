<?php
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

if (isset($_POST['action']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'xmlhttprequest') {
    $complaint_id = (int)($_POST['complaint_id'] ?? 0);
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $allowed_statuses = ['Pending', 'In Progress', 'Resolved', 'Closed'];
        if ($complaint_id > 0 && in_array($new_status, $allowed_statuses)) {
            $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $complaint_id])) {
                $response = ['success' => true, 'message' => '<div class="alert alert-success">Status updated successfully.</div>'];
            } else {
                $response['message'] = 'Failed to update status in the database.';
            }
        } else {
            $response['message'] = 'Invalid data provided for status update.';
        }
    } 
    elseif ($_POST['action'] === 'add_response') {
        $admin_response = trim($_POST['admin_response'] ?? '');
        if ($complaint_id > 0 && !empty($admin_response)) {
            $stmt = $pdo->prepare("UPDATE complaints SET admin_response = ?, status = 'In Progress' WHERE id = ?");
            if ($stmt->execute([$admin_response, $complaint_id])) {
                $response = [
                    'success' => true, 
                    'message' => '<div class="alert alert-success">Response posted successfully. Status set to "In Progress".</div>',
                    'new_response_html' => '<strong><i class="fas fa-user-shield me-2"></i>Your Response:</strong><p class="mb-0 mt-2 fst-italic">"' . htmlspecialchars($admin_response) . '"</p>'
                ];
            } else {
                $response['message'] = 'Failed to save the response.';
            }
        } else {
            $response['message'] = 'Response text cannot be empty.';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$complaints = $pdo->query("
    SELECT 
        c.id, c.order_id, c.complaint_text, c.status, c.admin_response, c.created_at,
        u.first_name, u.last_name
    FROM complaints c
    JOIN users u ON c.customer_id = u.id
    ORDER BY FIELD(c.status, 'Pending', 'In Progress', 'Resolved', 'Closed'), c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    $colors = [
        'Pending' => 'warning',
        'In Progress' => 'info',
        'Resolved' => 'success',
        'Closed' => 'secondary'
    ];
    $color = $colors[$status] ?? 'dark';
    return "<span class=\"badge bg-{$color}\">" . htmlspecialchars($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Complaints - Hi.Genie Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
    .main-card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .card-header { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; font-weight: 600; }
    .complaint-card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #dee2e6; }
    .admin-response-section { background-color: #e9ecef; border-top: 2px solid #adb5bd; }
</style>
</head>
<body>

<?php include __DIR__ . '../../header.php'; ?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Manage Complaints</h2>
    </div>

    <div id="message-container" class="mb-3"></div>

    <div class="main-card">
        <div class="card-body p-0">
            <?php if (empty($complaints)): ?>
                <div class="text-center p-5">
                    <h4><i class="fas fa-check-circle text-success"></i> All Clear!</h4>
                    <p class="text-muted">There are no customer complaints at this time.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($complaints as $complaint): ?>
                        <div class="list-group-item p-4">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1 fw-bold text-primary">
                                            <?= htmlspecialchars($complaint['first_name'] . ' ' . $complaint['last_name']) ?>
                                        </h5>
                                        <small class="text-muted"><?= date("d M Y", strtotime($complaint['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-1">Regarding Order #<?= htmlspecialchars($complaint['order_id']) ?></p>
                                    <p class="mt-3"><strong>Complaint:</strong><br>
                                        <em>"<?= nl2br(htmlspecialchars($complaint['complaint_text'])) ?>"</em>
                                    </p>
                                </div>
                                <div class="col-md-5 mt-3 mt-md-0">
                                    <div class="border p-3 rounded">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Status:</label>
                                            <div class="input-group">
                                                <select class="form-select status-select" data-id="<?= $complaint['id'] ?>">
                                                    <option value="Pending" <?= $complaint['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="In Progress" <?= $complaint['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                    <option value="Resolved" <?= $complaint['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                    <option value="Closed" <?= $complaint['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                                </select>
                                                <span class="input-group-text status-badge-display"><?= getStatusBadge($complaint['status']) ?></span>
                                            </div>
                                        </div>
                                        <div class="admin-response-section p-3 rounded" id="response-section-<?= $complaint['id'] ?>">
                                            <?php if (!empty($complaint['admin_response'])): ?>
                                                <strong><i class="fas fa-user-shield me-2"></i>Your Response:</strong>
                                                <p class="mb-0 mt-2 fst-italic">"<?= htmlspecialchars($complaint['admin_response']) ?>"</p>
                                            <?php else: ?>
                                                <form class="response-form">
                                                    <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                                                    <div class="mb-2">
                                                        <textarea name="admin_response" class="form-control" rows="3" placeholder="Write a response to the customer..." required></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-dark">Post Response</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function(){
    $('.status-select').on('change', function() {
        let complaintId = $(this).data('id');
        let newStatus = $(this).val();

        $.ajax({
            url: 'manage_complaints.php',
            type: 'POST',
            data: {
                action: 'update_status',
                complaint_id: complaintId,
                status: newStatus
            },
            dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(response){
                $('#message-container').html(response.message).fadeIn();
                if(response.success){
                    let badge = $(`select[data-id="${complaintId}"]`).closest('.input-group').find('.status-badge-display');
                    badge.html(newStatus);
                    badge.removeClass('bg-warning bg-info bg-success bg-secondary').addClass(getStatusClass(newStatus));
                }
                setTimeout(() => $('#message-container').fadeOut(), 4000);
            }
        });
    });

    $(document).on('submit', '.response-form', function(e){
        e.preventDefault();
        
        let form = $(this);
        let complaintId = form.find('input[name="complaint_id"]').val();
        let adminResponse = form.find('textarea[name="admin_response"]').val();
        
        $.ajax({
            url: 'manage_complaints.php',
            type: 'POST',
            data: {
                action: 'add_response',
                complaint_id: complaintId,
                admin_response: adminResponse
            },
            dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(response){
                $('#message-container').html(response.message).fadeIn();
                if(response.success){
                    $('#response-section-' + complaintId).html(response.new_response_html);
                    let statusSelect = $(`select[data-id="${complaintId}"]`);
                    statusSelect.val('In Progress');
                    statusSelect.closest('.input-group').find('.status-badge-display').html('In Progress').removeClass().addClass('status-badge-display badge bg-info');
                }
                setTimeout(() => $('#message-container').fadeOut(), 4000);
            }
        });
    });

    function getStatusClass(status) {
        switch(status) {
            case 'Pending': return 'bg-warning';
            case 'In Progress': return 'bg-info';
            case 'Resolved': return 'bg-success';
            case 'Closed': return 'bg-secondary';
            default: return 'bg-dark';
        }
    }
});
</script>
</body>
</html>