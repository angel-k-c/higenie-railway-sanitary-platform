<?php
session_start();
require __DIR__ . '/../db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../includes/PHPMailer/src/Exception.php';
require __DIR__ . '/../includes/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../includes/PHPMailer/src/SMTP.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

if (isset($_POST['action']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'xmlhttprequest') {
    $feedback_id = (int)($_POST['feedback_id'] ?? 0);
    $response = ['success' => false, 'message' => 'Unknown error'];

    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $allowed = ['new', 'read', 'archived'];
        if ($feedback_id > 0 && in_array($new_status, $allowed)) {
            $stmt = $pdo->prepare("UPDATE feedbacks SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $feedback_id])) {
                $response = ['success' => true, 'message' => '<div class="alert alert-success">Status updated to '.ucfirst($new_status).'</div>'];
            } else $response['message'] = 'Database update failed.';
        } else $response['message'] = 'Invalid status.';
    }

    elseif ($_POST['action'] === 'send_reply') {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $subject = trim($_POST['subject'] ?? 'Re: Your Feedback');
        $message = trim($_POST['message'] ?? '');
        $name = htmlspecialchars($_POST['name'] ?? 'Customer');

        if ($feedback_id && $email && $message) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'hi.genie.service@gmail.com'; 
                $mail->Password = 'wxxznxiediathslc';  
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('admin@higenie.com', 'Hi.Genie Admin');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = "
                    <h2>Hi {$name},</h2>
                    <p>This is a response to your feedback.</p>
                    <hr>
                    <blockquote>{$message}</blockquote>
                    <hr>
                    <p>Sincerely,<br>Hi.Genie Team</p>
                ";

                $mail->send();
                $pdo->prepare("UPDATE feedbacks SET status='read' WHERE id=?")->execute([$feedback_id]);
                $response = ['success' => true, 'message' => '<div class="alert alert-success">Email sent successfully. Feedback marked as Read.</div>'];
            } catch (Exception $e) {
                $response['message'] = '<div class="alert alert-danger">Email could not be sent. Error: '.$mail->ErrorInfo.'</div>';
            }
        } else $response['message'] = '<div class="alert alert-warning">Recipient email or message missing.</div>';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// FETCH FEEDBACKS 
$feedbacks = $pdo->query("
    SELECT f.id, CONCAT(u.first_name,' ',u.last_name) AS name, u.email, f.subject, f.message, f.status, f.submitted_at
    FROM feedbacks f
    JOIN users u ON f.user_id = u.id
    ORDER BY FIELD(f.status,'new','read','archived'), f.submitted_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function statusBadge($status){
    $colors = ['new'=>'primary','read'=>'info','archived'=>'secondary'];
    $c = $colors[$status] ?? 'dark';
    return "<span class='badge bg-{$c}'>".ucfirst($status)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Feedback - Hi.Genie Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body{background:#f4f7f6;}
.main-card{border:none;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
.accordion-button:not(.collapsed){background:#f8d7da;color:#842029;}
.status-btn{cursor:pointer;}
</style>
</head>
<body>
<?php include __DIR__ . '../../header.php'; ?>
<div class="container-fluid my-4">
<h2 class="fw-bold mb-4"><i class="fas fa-lightbulb me-2"></i>Manage Feedback</h2>
<div id="message-container"></div>
<div class="main-card">
<div class="accordion" id="feedbackAccordion">
<?php if(empty($feedbacks)): ?>
<div class="text-center p-5">
<h4><i class="fas fa-inbox"></i> No Feedback Yet</h4>
<p class="text-muted">Feedback will appear here.</p>
</div>
<?php else: foreach($feedbacks as $f): ?>
<div class="accordion-item" id="feedback-item-<?= $f['id'] ?>">
<h2 class="accordion-header">
<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $f['id'] ?>">
<div class="d-flex w-100 justify-content-between">
<div><span class="fw-bold me-3"><?= htmlspecialchars($f['name']) ?></span><span class="text-muted"><?= htmlspecialchars($f['subject']) ?></span></div>
<div><small class="me-3 text-muted"><?= date("d M Y, h:i A",strtotime($f['submitted_at'])) ?></small><span class="status-badge"><?= statusBadge($f['status']) ?></span></div>
</div>
</button>
</h2>
<div id="collapse-<?= $f['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#feedbackAccordion">
<div class="accordion-body row">
<div class="col-md-7">
<p><strong>From:</strong> <a href="mailto:<?= htmlspecialchars($f['email']) ?>"><?= htmlspecialchars($f['email']) ?></a></p>
<p><strong>Message:</strong></p>
<blockquote class="blockquote bg-light p-3 rounded"><p class="mb-0"><em><?= nl2br(htmlspecialchars($f['message'])) ?></em></p></blockquote>
<div class="mt-3"><strong>Actions:</strong>
<div class="btn-group btn-group-sm mt-1">
<button class="btn btn-info text-white status-btn" data-id="<?= $f['id'] ?>" data-status="read">Mark as Read</button>
<button class="btn btn-secondary status-btn" data-id="<?= $f['id'] ?>" data-status="archived">Archive</button>
</div>
</div>
</div>
<div class="col-md-5 mt-3 mt-md-0">
<div class="border p-3 rounded bg-light">
<h5 class="mb-3"><i class="fas fa-reply me-2"></i>Send a Reply</h5>
<form class="reply-form">
<input type="hidden" name="feedback_id" value="<?= $f['id'] ?>">
<input type="hidden" name="email" value="<?= htmlspecialchars($f['email']) ?>">
<input type="hidden" name="name" value="<?= htmlspecialchars($f['name']) ?>">
<div class="mb-2"><label class="form-label small">Subject</label><input type="text" name="subject" class="form-control form-control-sm" value="Re: <?= htmlspecialchars($f['subject']) ?>"></div>
<div class="mb-2"><label class="form-label small">Message</label><textarea name="message" class="form-control" rows="4" required></textarea></div>
<button type="submit" class="btn btn-sm btn-dark w-100">Send Email Reply</button>
<div class="mt-2 text-success reply-status" style="display:none;"></div>
</form>
</div>
</div>
</div>
</div>
</div>
<?php endforeach; endif; ?>
</div>
</div>
</div>
<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function(){
    $('.status-btn').click(function(){
        let btn=$(this),id=btn.data('id'),status=btn.data('status');
        $.ajax({
            url:'view_feedbacks.php',type:'POST',
            data:{action:'update_status',feedback_id:id,status:status},
            dataType:'json',
            beforeSend:xhr=>xhr.setRequestHeader('X-Requested-With','XMLHttpRequest'),
            success:function(res){
                $('#message-container').html(res.message).fadeIn();
                if(res.success){
                    let badge=btn.closest('.accordion-item').find('.status-badge');
                    let c=status==='read'?'bg-info':'bg-secondary';
                    badge.html('<span class="badge '+c+'">'+status.charAt(0).toUpperCase()+status.slice(1)+'</span>');
                }
                setTimeout(()=>$('#message-container').fadeOut(),5000);
            }
        });
    });

    $(document).on('submit','.reply-form',function(e){
        e.preventDefault();
        let form=$(this),btn=form.find('button[type="submit"]');
        $.ajax({
            url:'view_feedbacks.php',type:'POST',
            data:form.serialize()+'&action=send_reply',
            dataType:'json',
            beforeSend:xhr=>{
                xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
                btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
            },
            success:function(res){
                $('#message-container').html(res.message).fadeIn();
                if(res.success){
                    form.find('textarea').val('');
                    let feedbackId=form.find('input[name="feedback_id"]').val();
                    $(`#feedback-item-${feedbackId}`).find('.status-badge').html('<span class="badge bg-info">Read</span>');
                    form.find('.reply-status').text('Email sent successfully!').fadeIn().delay(3000).fadeOut();
                }
                setTimeout(()=>$('#message-container').fadeOut(),5000);
            },
            complete:function(){btn.prop('disabled',false).html('Send Email Reply');}
        });
    });
});
</script>
</body>
</html>
