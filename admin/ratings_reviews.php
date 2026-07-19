<?php
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}

if (isset($_POST['action'], $_POST['review_id'], $_POST['reply_text']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'xmlhttprequest') {
    $review_id = (int)$_POST['review_id'];
    $reply_text = trim($_POST['reply_text']);
    $response = ['success' => false, 'message' => 'Reply cannot be empty.'];

    if (!empty($reply_text)) {
        $stmt = $pdo->prepare("UPDATE ratings SET admin_reply_text = ?, replied_at = NOW() WHERE id = ?");
        if ($stmt->execute([$reply_text, $review_id])) {
            $response = [
                'success' => true,
                'message' => '<div class="alert alert-success">Reply posted successfully.</div>',
                'new_reply_html' => '<strong><i class="fas fa-user-shield me-2"></i>Your Reply:</strong><p class="mb-0 mt-2 fst-italic">"' . htmlspecialchars($reply_text) . '"</p>'
            ];
        } else {
            $response['message'] = 'Failed to save the reply to the database.';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$reviews = $pdo->query("
    SELECT 
        r.id, r.order_id, r.rating, r.review_text, r.admin_reply_text, r.created_at,
        u.first_name, u.last_name
    FROM ratings r
    JOIN users u ON r.customer_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function render_stars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fas fa-star" style="color: ' . ($i <= $rating ? '#ffc107' : '#e0e0e0') . ';"></i>';
    }
    return $html;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ratings & Reviews - Hi.Genie Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
    .main-card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
    .card-header { background: linear-gradient(to right, #c0392b, #a93226); color: #fff; font-size: 1.2rem; font-weight: 600; }
    .review-card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s; border: 1px solid #dee2e6; }
    .review-card:hover { transform: translateY(-5px); }
    .admin-reply { background-color: #e9ecef; border-top: 2px solid #adb5bd; }
</style>
</head>
<body>

<?php include __DIR__ . '../../header.php'; ?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Ratings & Reviews</h2>
    </div>

    <div id="message-container" class="mb-3"></div>

    <div class="main-card">
        <div class="card-body p-0">
            <?php if (empty($reviews)): ?>
                <div class="text-center p-5">
                    <h4><i class="fas fa-comment-slash"></i> No Reviews Yet</h4>
                    <p class="text-muted">When customers leave ratings and reviews, they will appear here.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($reviews as $review): ?>
                        <div class="list-group-item p-4">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1 fw-bold text-primary">
                                            <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                        </h5>
                                        <small class="text-muted"><?= date("d M Y", strtotime($review['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-1">For Order #<?= htmlspecialchars($review['order_id']) ?></p>
                                    <div class="my-2"><?= render_stars($review['rating']) ?></div>
                                    <p class="mb-0">
                                        <em>"<?= nl2br(htmlspecialchars($review['review_text'])) ?>"</em>
                                    </p>
                                </div>
                                <div class="col-md-5 mt-3 mt-md-0">
                                    <div class="admin-reply p-3 rounded" id="reply-section-<?= $review['id'] ?>">
                                        <?php if (!empty($review['admin_reply_text'])): ?>
                                            <strong><i class="fas fa-user-shield me-2"></i>Your Reply:</strong>
                                            <p class="mb-0 mt-2 fst-italic">"<?= htmlspecialchars($review['admin_reply_text']) ?>"</p>
                                        <?php else: ?>
                                            <form class="reply-form">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <div class="mb-2">
                                                    <textarea name="reply_text" class="form-control" rows="3" placeholder="Write a public reply..." required></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-dark">Post Reply</button>
                                            </form>
                                        <?php endif; ?>
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
    $(document).on('submit', '.reply-form', function(e){
        e.preventDefault();
        
        let form = $(this);
        let reviewId = form.find('input[name="review_id"]').val();
        let replyText = form.find('textarea[name="reply_text"]').val();
        
        $.ajax({
            url: 'ratings_reviews.php',
            type: 'POST',
            data: {
                action: 'submit_reply',
                review_id: reviewId,
                reply_text: replyText
            },
            dataType: 'json',
            beforeSend: function(xhr) { 
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); 
            },
            success: function(response){
                $('#message-container').html(response.message).fadeIn();
                if(response.success){
                    
                    $('#reply-section-' + reviewId).html(response.new_reply_html);
                }
                setTimeout(() => $('#message-container').fadeOut(), 4000);
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An unexpected error occurred.</div>').fadeIn();
                setTimeout(() => $('#message-container').fadeOut(), 4000);
            }
        });
    });
});
</script>
</body>
</html>