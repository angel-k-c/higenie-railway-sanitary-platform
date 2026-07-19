<?php
session_start();
require 'db.php'; 

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please log in first.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    //  ADD TO CART 
    if ($action === 'add_to_cart') {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        // Check if product already exists in cart (status='cart')
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND status = 'cart'");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantity
            $new_qty = $existing['quantity'] + $quantity;
            $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update->execute([$new_qty, $existing['id']]);
        } else {
            // Insert new item
            $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, status) VALUES (?, ?, ?, 'cart')");
            $insert->execute([$user_id, $product_id, $quantity]);
        }
        $response['success'] = true;
        $response['message'] = 'Item added to cart!';
    }

    // UPDATE QUANTITY 
    if ($action === 'update_quantity') {
        $cart_id = (int)$_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ? AND status = 'cart'");
            $stmt->execute([$quantity, $cart_id, $user_id]);
            $response['success'] = true;
        }
    }

    // REMOVE ITEM
    if ($action === 'remove_item') {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ? AND status = 'cart'");
        $stmt->execute([$cart_id, $user_id]);
        $response['success'] = true;
    }

    // MOVE TO WISHLIST
    if ($action === 'move_to_wishlist') {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $pdo->prepare("SELECT product_id FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        $product_id = $stmt->fetchColumn();

        if ($product_id) {
             $check = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND status = 'wishlist'");
             $check->execute([$user_id, $product_id]);
             
             if ($check->fetch()) {
                 $del = $pdo->prepare("DELETE FROM cart WHERE id = ?");
                 $del->execute([$cart_id]);
             } else {
                 $update = $pdo->prepare("UPDATE cart SET status = 'wishlist', quantity = 1 WHERE id = ? AND user_id = ?");
                 $update->execute([$cart_id, $user_id]);
             }
             $response['success'] = true;
        }
    }

    //  ADD TO WISHLIST
    if ($action === 'add_to_wishlist') {
        $product_id = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND status = 'wishlist'");
        $stmt->execute([$user_id, $product_id]);
        
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, status) VALUES (?, ?, 1, 'wishlist')");
            $insert->execute([$user_id, $product_id]);
        }
        $response['success'] = true;
        $response['message'] = 'Added to wishlist!';
    }

    // MOVE TO CART 
    if ($action === 'move_to_cart') {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $pdo->prepare("UPDATE cart SET status = 'cart', quantity = 1 WHERE id = ? AND user_id = ? AND status = 'wishlist'");
        $stmt->execute([$cart_id, $user_id]);
        $response['success'] = true;
    }

    // EMPTY CART (Called after successful order) 
    if ($action === 'empty_cart') {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND status = 'cart'");
        $stmt->execute([$user_id]);
        $response['success'] = true;
        $response['message'] = 'Cart has been emptied.';
    }

    // ALWAYS RETURN NEW CART COUNT
    $count_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ? AND status = 'cart'");
    $count_stmt->execute([$user_id]);
    $response['cart_count'] = (int)$count_stmt->fetchColumn();

} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>