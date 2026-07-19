<?php
session_start();
require __DIR__ . '/../db.php';


$pharBase = __DIR__ . '/../includes/PHPMailer/src/';

if (!file_exists($pharBase . 'Exception.php') || !file_exists($pharBase . 'PHPMailer.php') || !file_exists($pharBase . 'SMTP.php')) {
    error_log("PHPMailer files not found in includes. Expected path: {$pharBase}");
} else {
    require $pharBase . 'Exception.php';
    require $pharBase . 'PHPMailer.php';
    require $pharBase . 'SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$smtpUser = 'hi.genie.services@gmail.com';
$smtpPass = 'B'; 
$smtpHost = 'smtp.gmail.com';
$smtpPort = 587;

function sendMail($to, $subject, $htmlBody) {
    global $smtpUser, $smtpPass, $smtpHost, $smtpPort;

    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer not loaded, cannot send email to ' . $to);
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;

        $mail->setFrom($smtpUser, 'Hi.Genie');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error ({$to}): " . $mail->ErrorInfo);
        return false;
    }
}

function buildRefundApprovedEmail($customerName, $orderId, $pnr, $refundAmount, $reason = '') {
    $amt = number_format($refundAmount, 2);
    $html = <<<HTML
    <div style="font-family:Arial,Helvetica,sans-serif; padding:24px;">
      <div style="max-width:700px; margin:0 auto; border:1px solid #eee; border-radius:8px; overflow:hidden;">
        <div style="background:#c0392b; color:#fff; padding:18px 24px;">
          <h2 style="margin:0; font-size:20px">Refund Processed</h2>
        </div>
        <div style="padding:18px 24px; color:#333;">
          <p>Hi <strong>{$customerName}</strong>,</p>
          <p>Your refund for order <strong>#{$orderId}</strong> (PNR: <strong>{$pnr}</strong>) has been processed.</p>
          <p><strong>Refund amount:</strong> ₹{$amt}</p>
HTML;
    if (!empty($reason)) {
        $html .= "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";
    }
    $html .= <<<HTML
          <p style="margin-top:18px; color:#555">If you have questions reply to this email.</p>
          <p style="margin-top:12px; color:#777; font-size:13px">Regards,<br>Hi.Genie Team</p>
        </div>
        <div style="background:#f7f7f7; padding:12px 24px; font-size:12px; color:#999;">
          This is an automated message.
        </div>
      </div>
    </div>
HTML;
    return $html;
}

function buildRefundRejectedEmail($customerName, $orderId, $pnr, $reason = '') {
    $html = <<<HTML
    <div style="font-family:Arial,Helvetica,sans-serif; padding:24px;">
      <div style="max-width:700px; margin:0 auto; border:1px solid #eee; border-radius:8px; overflow:hidden;">
        <div style="background:#c0392b; color:#fff; padding:18px 24px;">
          <h2 style="margin:0; font-size:20px">Refund Request Rejected</h2>
        </div>
        <div style="padding:18px 24px; color:#333;">
          <p>Hi <strong>{$customerName}</strong>,</p>
          <p>Your refund request for order <strong>#{$orderId}</strong> (PNR: <strong>{$pnr}</strong>) has been rejected.</p>
HTML;
    if (!empty($reason)) {
        $html .= "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";
    }
    $html .= <<<HTML
          <p style="margin-top:18px; color:#555">If you believe this is an error, contact support.</p>
          <p style="margin-top:12px; color:#777; font-size:13px">Regards,<br>Hi.Genie Team</p>
        </div>
        <div style="background:#f7f7f7; padding:12px 24px; font-size:12px; color:#999;">
          This is an automated message.
        </div>
      </div>
    </div>
HTML;
    return $html;
}

function calculateRefund($pdo, $refundId) {
    $flatDelivery = 50.00;

    $stmt = $pdo->prepare("
        SELECT r.id, r.refund_type, r.order_id, o.total_amount, o.delivery_agent_id
        FROM refunds r
        JOIN orders o ON r.order_id = o.id
        WHERE r.id = ?
    ");
    $stmt->execute([$refundId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) return 0.00;

    $total = (float)$data['total_amount'];
    $assignedAgent = $data['delivery_agent_id']; 

    if ($data['refund_type'] === 'User Cancelled') {
        if ($assignedAgent === null) {
            return $total;
        } else {
            return round($total * 0.75, 2);
        }
    }

    if ($data['refund_type'] === 'Agent Cancelled') {
        return max(0.00, round($total - $flatDelivery, 2));
    }

    return 0.00;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['preview']) && isset($_POST['refund_id'])) {
        $refundId = (int)$_POST['refund_id'];
        $amt = calculateRefund($pdo, $refundId);
        $stmt = $pdo->prepare("SELECT r.refund_type, o.delivery_agent_id FROM refunds r JOIN orders o ON r.order_id=o.id WHERE r.id=?");
        $stmt->execute([$refundId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        $note = '';
        if ($info) {
            if ($info['refund_type'] === 'User Cancelled') {
                if ($info['delivery_agent_id'] === null) $note = 'User cancelled before assignment - full refund.';
                else $note = 'User cancelled after assignment - 25% deduction applies.';
            } elseif ($info['refund_type'] === 'Agent Cancelled') {
                $note = 'Agent cancelled - ₹50 delivery charge deducted.';
            }
        }
        echo json_encode(['success'=>true, 'refund_amount'=>number_format($amt,2), 'note'=>$note]);
        exit;
    }

    // Approve/Reject
    if (isset($_POST['ajax_update']) && isset($_POST['refund_id']) && isset($_POST['action'])) {

        $refundId = (int)$_POST['refund_id'];
        $action = $_POST['action']; 

        $stmt = $pdo->prepare("
            SELECT r.*, o.total_amount, o.delivery_agent_id, o.pnr_number, o.user_id AS order_user_id,
                   u.email AS user_email, u.first_name, u.last_name
            FROM refunds r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON o.user_id = u.id
            WHERE r.id = ?
            LIMIT 1
        ");
        $stmt->execute([$refundId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Refund not found']);
            exit;
        }

        $customerEmail = $row['user_email'];
        $customerName = trim($row['first_name'] . ' ' . $row['last_name']);
        $orderId = (int)$row['order_id'];
        $pnr = $row['pnr_number'] ?? '-';
        $refundReason = $row['refund_reason'] ?? '';

        if ($action === 'approve') {
            $refundAmount = calculateRefund($pdo, $refundId);

            $stmt = $pdo->prepare("
                UPDATE refunds 
                SET refund_status = 'Processed', refund_amount = ?, processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$refundAmount, $refundId]);

            $subjectCust = "Your refund for order #{$orderId} has been processed";
            $htmlCust = buildRefundApprovedEmail($customerName, $orderId, $pnr, $refundAmount, $refundReason);
            sendMail($customerEmail, $subjectCust, $htmlCust);

            $subjectAdmin = "Refund Processed: Order #{$orderId}";
            $htmlAdmin = "<p>Refund #{$refundId} for order #{$orderId} was processed. Amount: ₹" . number_format($refundAmount,2) . ".</p>";
            sendMail($smtpUser, $subjectAdmin, $htmlAdmin);

            echo json_encode([
                'status' => 'success',
                'result' => 'approved',
                'refund_amount' => number_format($refundAmount, 2)
            ]);
            exit();

        } elseif ($action === 'reject') {

            $stmt = $pdo->prepare("
                UPDATE refunds 
                SET refund_status = 'Rejected', processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$refundId]);

            $subjectCust = "Your refund request for order #{$orderId} was rejected";
            $htmlCust = buildRefundRejectedEmail($customerName, $orderId, $pnr, $refundReason);
            sendMail($customerEmail, $subjectCust, $htmlCust);

            $subjectAdmin = "Refund Rejected: Order #{$orderId}";
            $htmlAdmin = "<p>Refund #{$refundId} for order #{$orderId} was rejected.</p>";
            sendMail($smtpUser, $subjectAdmin, $htmlAdmin);

            echo json_encode([
                'status' => 'success',
                'result' => 'rejected'
            ]);
            exit();
        }

        echo json_encode(['status'=>'error','message'=>'Invalid action']);
        exit();
    }
}

$refunds = $pdo->query("
    SELECT r.*, o.pnr_number, u.first_name, u.last_name
    FROM refunds r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON o.user_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Refunds - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; min-height: 100vh; }
.container { max-width: 1200px; }
.card { border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
.table thead th { background: linear-gradient(90deg,#c0392b,#e74c3c); color: #fff; border-bottom: 0; }
.table tbody tr:hover { background-color: #f2f2f2; transition: 0.2s; }
.badge-status { font-weight: 500; padding: 6px 12px; border-radius: 12px; font-size: 0.85rem; }
.badge-user { background-color: #3498db; color: #fff; }
.badge-agent { background-color: #e67e22; color: #fff; }
h2 { color: #c0392b; font-weight: 700; }
.btn-action { margin-right: 5px; }
</style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="container my-5">
    <h2 class="mb-4"><i class="fas fa-undo-alt me-2"></i>Refund Requests</h2>

    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#Refund</th>
                        <th>#Order</th>
                        <th>Customer</th>
                        <th>PNR</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Requested</th>
                        <th>Processed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($refunds)): ?>
                        <tr><td colspan="11" class="text-center py-4">No refund requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach($refunds as $r): ?>
                            <?php
                                if($r['refund_status'] == 'Pending') {
                                    $cls = 'bg-warning text-dark';
                                    $labelText = 'Pending';
                                } elseif($r['refund_status'] == 'Processed') {
                                    $cls = 'bg-success';
                                    $labelText = 'Refund Approved';
                                } else {
                                    $cls = 'bg-danger';
                                    $labelText = 'Rejected';
                                }

                                $typeCls = $r['refund_type'] === 'User Cancelled' ? 'badge-user' : 'badge-agent';
                            ?>
                            <tr id="row-<?= $r['id'] ?>">
                                <td>#<?= $r['id'] ?></td>
                                <td>#<?= $r['order_id'] ?></td>
                                <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
                                <td><?= htmlspecialchars($r['pnr_number']) ?></td>
                                <td id="amount-<?= $r['id'] ?>">₹<?= number_format($r['refund_amount'],2) ?></td>
                                <td><span class="badge <?= $typeCls ?>"><?= htmlspecialchars($r['refund_type']) ?></span></td>
                                <td id="status-<?= $r['id'] ?>"><span class="badge badge-status <?= $cls ?>"><?= $labelText ?></span></td>
                                <td><?= htmlspecialchars($r['refund_reason'] ?? '-') ?></td>
                                <td><?= date("d M Y, h:i A", strtotime($r['created_at'])) ?></td>
                                <td id="processed-<?= $r['id'] ?>"><?= $r['processed_at'] ? date("d M Y, h:i A", strtotime($r['processed_at'])) : '-' ?></td>
                                <td>
                                    <?php if($r['refund_status']=='Pending'): ?>
                                        <button class="btn btn-success btn-sm btn-action approve-btn" data-id="<?= $r['id'] ?>" data-order="<?= $r['order_id'] ?>"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-danger btn-sm btn-action reject-btn" data-id="<?= $r['id'] ?>" data-order="<?= $r['order_id'] ?>"><i class="fas fa-times"></i></button>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="refundConfirmModal" tabindex="-1" aria-labelledby="refundConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="refundConfirmModalLabel">Confirm action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="refund-modal-text">Are you sure?</p>
        <p class="small text-muted" id="refund-calc-note"></p>
      </div>
      <div class="modal-footer">
        <button type="button" id="confirmReject" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmAction" class="btn btn-primary">Proceed</button>
      </div>
    </div>
  </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index:2000">
  <div id="resultToast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="resultToastBody"></div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentAction = null; 
let currentRefundId = null;

function showToast(msg) {
    $('#resultToastBody').text(msg);
    let toast = new bootstrap.Toast(document.getElementById('resultToast'));
    toast.show();
}

$(document).ready(function(){

    // Approval
    $(document).on('click', '.approve-btn', function(){
        currentAction = 'approve';
        currentRefundId = $(this).data('id');
        const orderId = $(this).data('order');

        $.post('', { preview:1, refund_id: currentRefundId }, function(res){
            if(res.success) {
                $('#refund-modal-text').text(`Approve refund for order #${orderId}?`);
                $('#refund-calc-note').text(`Refund amount calculated: ₹${res.refund_amount}. ${res.note}`);
                $('#confirmAction').text('Approve').removeClass('btn-danger').addClass('btn-success');
                let modal = new bootstrap.Modal(document.getElementById('refundConfirmModal'));
                modal.show();
            } else {
                showToast('Failed to calculate refund amount.');
            }
        }, 'json');
    });

    // Rejection
    $(document).on('click', '.reject-btn', function(){
        currentAction = 'reject';
        currentRefundId = $(this).data('id');
        const orderId = $(this).data('order');

        $('#refund-modal-text').text(`Reject refund for order #${orderId}?`);
        $('#refund-calc-note').text('');
        $('#confirmAction').text('Reject').removeClass('btn-success').addClass('btn-danger');
        let modal = new bootstrap.Modal(document.getElementById('refundConfirmModal'));
        modal.show();
    });

    // When confirmAction clicked 
    $('#confirmAction').on('click', function(){
        if(!currentRefundId || !currentAction) return;

        $(this).prop('disabled', true);

        $.post('', {
            ajax_update: 1,
            refund_id: currentRefundId,
            action: currentAction
        }, function(res){
            $('#confirmAction').prop('disabled', false);
            if(res.status === 'success') {
                if(res.result === 'approved') {
                    $('#amount-' + currentRefundId).text('₹' + res.refund_amount);
                    $('#status-' + currentRefundId + ' span').text('Refund Approved').removeClass().addClass('badge badge-status bg-success');
                    $('#processed-' + currentRefundId).text(new Date().toLocaleString());
                    $('#row-' + currentRefundId + ' td:last').html('<span class="text-muted">N/A</span>');
                    showToast('Refund approved and emails sent.');
                } else if(res.result === 'rejected') {
                    $('#status-' + currentRefundId + ' span').text('Rejected').removeClass().addClass('badge badge-status bg-danger');
                    $('#processed-' + currentRefundId).text(new Date().toLocaleString());
                    $('#row-' + currentRefundId + ' td:last').html('<span class="text-muted">N/A</span>');
                    showToast('Refund rejected and notification sent.');
                }
                bootstrap.Modal.getInstance(document.getElementById('refundConfirmModal')).hide();
                currentAction = null;
                currentRefundId = null;
            } else {
                showToast(res.message || 'Operation failed.');
            }
        }, 'json').fail(function(){
            $('#confirmAction').prop('disabled', false);
            showToast('Server error.');
        });
    });

});
</script>

</body>
</html>
