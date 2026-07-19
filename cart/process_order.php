<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['checkout_data'])) {
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please start over.']);
    exit;
}

$payment_id = $_POST['razorpay_payment_id'] ?? null;

if (empty($payment_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Payment ID is missing.']);
    exit;
}

$user_id       = $_SESSION['user_id'];
$checkout_data = $_SESSION['checkout_data'];

try {
    $pdo->beginTransaction();

    // Fetch cart items directly from the database 
    $cart_stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND c.status = 'cart'");
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception("Your cart is empty.");
    }

    // Calculate total on the server-side
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $delivery_charge = ($subtotal > 0) ? 50.00 : 0;
    $grand_total = $subtotal + $delivery_charge;

    // Insert the final order.
    $order_sql = "INSERT INTO orders (user_id, pnr_number, train_number, coach, seat_number, total_amount, order_date, payment_status, order_status, payment_id) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'Paid', 'Confirmed', ?)";
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([$user_id, $checkout_data['pnr_number'], $checkout_data['train_number'], $checkout_data['coach'], $checkout_data['seat_number'], $grand_total, $payment_id]);
    $order_id = $pdo->lastInsertId();

    // Insert order items.
    $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $item_stmt = $pdo->prepare($item_sql);
    foreach ($cart_items as $item) {
        $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
    }

    // Clear the user's cart from the database.
    $clear_cart_stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND status = 'cart'");
    $clear_cart_stmt->execute([$user_id]);

    $pdo->commit();
    
    unset($_SESSION['checkout_data']);
    

    // Set a flash message for the dashboard to display
    $_SESSION['flash_msg'] = "Order #$order_id placed successfully!";

    // Send success msg
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully!', 
        'order_id' => $order_id,
        'redirect_url' => '/higenie/customer/dashboard.php' 
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while placing your order.']);
}
?>