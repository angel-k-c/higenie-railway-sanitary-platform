<?php
session_start();
require 'db.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid action.', 'cart_count' => 0];

try {
    // A single handler for all cart and wishlist actions
    switch ($action) {
        // Adds a new item directly to the cart
        case 'add_to_cart':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

            // Check if item is already in cart or wishlist
            $stmt = $pdo->prepare("SELECT id, quantity, status FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $item = $stmt->fetch();

            if ($item) { // Item exists
                if ($item['status'] === 'wishlist') {
                    // If it's in the wishlist, move it to the cart
                    $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ?, status = 'cart' WHERE id = ?");
                    $update_stmt->execute([$quantity, $item['id']]);
                } else {
                    // If already in cart, just increase quantity
                    $update_stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
                    $update_stmt->execute([$quantity, $item['id']]);
                }
            } else { // New item
                $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, status) VALUES (?, ?, ?, 'cart')");
                $insert_stmt->execute([$user_id, $product_id, $quantity]);
            }
            $response = ['success' => true, 'message' => 'Product added to cart.'];
            break;

        // Adds a new item directly to the wishlist
        case 'add_to_wishlist':
            $product_id = (int)($_POST['product_id'] ?? 0);
            // Check if it already exists to avoid duplicates
            $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            if (!$stmt->fetch()) {
                $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, status) VALUES (?, ?, 1, 'wishlist')");
                $insert_stmt->execute([$user_id, $product_id]);
            }
            $response = ['success' => true, 'message' => 'Product added to wishlist.'];
            break;
            
        // Moves an item from cart to wishlist
        case 'move_to_wishlist':
            $cart_id = (int)($_POST['cart_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE cart SET status = 'wishlist', quantity = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            $response = ['success' => true, 'message' => 'Moved to wishlist.'];
            break;

        // Moves an item from wishlist to cart
        case 'move_to_cart':
            $cart_id = (int)($_POST['cart_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE cart SET status = 'cart', quantity = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            $response = ['success' => true, 'message' => 'Moved to cart.'];
            break;
            
        // Removes an item completely (for both cart and wishlist)
        case 'remove_item':
            $cart_id = (int)($_POST['cart_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            $response = ['success' => true, 'message' => 'Item removed.'];
            break;

        // Updates quantity for an item in the cart
        case 'update_quantity':
            $cart_id  = (int)($_POST['cart_id'] ?? 0);
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ? AND status = 'cart'");
            $stmt->execute([$quantity, $cart_id, $user_id]);
            $response = ['success' => true];
            break;
    }

    // RECALCULATE and return the cart count (only items with status='cart')
    $count_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ? AND status = 'cart'");
    $count_stmt->execute([$user_id]);
    $total_items = (int)$count_stmt->fetchColumn();
    
    $response['cart_count'] = $total_items;
    $_SESSION['cart_count'] = $total_items;

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);