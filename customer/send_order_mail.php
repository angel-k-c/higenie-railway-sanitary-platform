<?php
require '../db.php';

require '../includes/PHPMailer/src/PHPMailer.php';
require '../includes/PHPMailer/src/SMTP.php';
require '../includes/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if (!isset($_GET['order_id'])) {
    exit("Order ID missing");
}

$order_id = intval($_GET['order_id']);

$order_stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    exit("Order not found");
}


$item_stmt = $pdo->prepare("
    SELECT oi.quantity, oi.price, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$item_stmt->execute([$order_id]);
$items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

$item_rows = "";
foreach ($items as $i) {
    $item_rows .= "
        <tr>
            <td>{$i['name']}</td>
            <td>{$i['quantity']}</td>
            <td>₹{$i['price']}</td>
        </tr>
    ";
}

$html = "
<html>
<body style='font-family: Arial, sans-serif; color: #333;'>
    <div style='width: 100%; max-width: 600px; margin: auto; border:1px solid #eee; padding:20px;'>
        <h2 style='text-align:center; color:#c92a2a;'>Hi.Genie Order Confirmation</h2>
        <p>Hello {$order['full_name']},</p>

        <p>Your order has been confirmed.</p>

        <h3 style='color:#c92a2a;'>Order Details</h3>
        <p><strong>Order ID:</strong> {$order_id}</p>
        <p><strong>PNR:</strong> {$order['pnr_number']}</p>
        <p><strong>Train:</strong> {$order['train_number']}</p>
        <p><strong>Coach:</strong> {$order['coach']}</p>
        <p><strong>Seat:</strong> {$order['seat_number']}</p>

        <h3 style='color:#c92a2a;'>Items</h3>
        <table style='width:100%; border-collapse: collapse;'>
            <tr style='background:#f8f8f8;'>
                <th style='border:1px solid #ddd; padding:8px;'>Item</th>
                <th style='border:1px solid #ddd; padding:8px;'>Qty</th>
                <th style='border:1px solid #ddd; padding:8px;'>Price</th>
            </tr>
            $item_rows
        </table>

        <h3 style='color:#c92a2a; margin-top:20px;'>Total: ₹{$order['total_amount']}</h3>

        <p>We will prepare your order and deliver it to your seat on time.</p>

        <p style='text-align:center; margin-top:30px; font-size:14px; color:#777;'>
            Hi.Genie, Freshness on Track
        </p>
    </div>
</body>
</html>
";


try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "hi.genie.service@gmail.com";
    $mail->Password = "wxxznxiediathslc";   
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;

    $mail->setFrom("hi.genie.service@gmail.com", "Hi.Genie");
    $mail->addAddress($order['email'], $order['full_name']);

    $mail->isHTML(true);
    $mail->Subject = "Your Hi.Genie Order #$order_id";
    $mail->Body = $html;

    $mail->send();
    echo "MAIL_SENT";

} catch (Exception $e) {
    echo "MAIL_FAILED";
}
