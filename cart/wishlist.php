<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /higenie/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        w.id AS wishlist_id,
        p.id AS product_id,
        p.product_name,
        p.price,
        p.image
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist - Hi.Genie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #fff0f0, #ffe4e4, #ffc1c1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .product-box {
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .product-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .product-thumbnail {
            aspect-ratio: 1/1;
            width: 100%;
            object-fit: contain;
            background: #f9f9f9;
            padding: 10px;
        }
        .product-info {
            padding: 1rem;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .product-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .product-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #d72323;
            margin-bottom: 1rem;
        }
        .product-actions {
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }
        @keyframes shake {
          0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 50% { transform: translateX(5px); } 75% { transform: translateX(-5px); }
        }
        .shake { animation: shake 0.5s; }
    </style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="container my-5">
    <h2 class="mb-4 fw-bold"><i class="fas fa-heart text-danger me-2"></i>My Wishlist</h2>

    <?php if (empty($wishlist_items)): ?>
        <div class="text-center py-5 bg-white rounded-3 shadow-sm">
            <h4 class="mb-3">Your wishlist is empty.</h4>
            <p class="text-muted">Looks like you haven't added anything to your wishlist yet.</p>
            <a href="/higenie/products.php" class="btn btn-danger mt-2">Discover Products</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col" id="wishlist-item-<?= $item['wishlist_id'] ?>">
                    <div class="product-box">
                        <img src="../uploads/<?= htmlspecialchars(basename($item['image'])) ?>" class="product-thumbnail" alt="<?= htmlspecialchars($item['product_name']) ?>">
                        <div class="product-info">
                            <div>
                                <h5 class="product-title"><?= htmlspecialchars($item['product_name']) ?></h5>
                                <p class="product-price">₹<?= number_format($item['price'], 2) ?></p>
                            </div>
                            <div class="product-actions">
                                <button class="btn btn-success btn-sm w-100 mb-2 move-to-cart-btn" data-product-id="<?= $item['product_id'] ?>" data-wishlist-id="<?= $item['wishlist_id'] ?>">
                                    <i class="fas fa-shopping-cart me-1"></i> Move to Cart
                                </button>
                                <button class="btn btn-outline-danger btn-sm w-100 remove-wishlist-btn" data-wishlist-id="<?= $item['wishlist_id'] ?>">
                                    <i class="fas fa-trash-alt me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Remove from Wishlist
    document.querySelectorAll('.remove-wishlist-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove this item from your wishlist?')) return;
            
            const wishlistId = this.dataset.wishlistId;
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('wishlist_id', wishlistId);

            fetch('wishlist_handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('wishlist-item-' + wishlistId).remove();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        });
    });

    //ADDS TO CART, THEN REMOVES FROM WISHLIST
    document.querySelectorAll('.move-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const wishlistId = this.dataset.wishlistId;
            const originalButton = this;

            originalButton.disabled = true;
            originalButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Moving...';

            //Add the item to the cart
            const cartFormData = new FormData();
            cartFormData.append('action', 'add');
            cartFormData.append('product_id', productId);
            cartFormData.append('quantity', 1);

            fetch('../cart_handler.php', { method: 'POST', body: cartFormData })
                .then(res => res.json())
                .then(cartData => {
                    if (cartData.success) {
                        const badge = document.getElementById('cart-count');
                        if (badge) {
                            badge.textContent = cartData.cart_count;
                            badge.classList.add('shake');
                            setTimeout(() => badge.classList.remove('shake'), 500);
                        }

                        //Remove the item from the wishlist
                        const wishlistFormData = new FormData();
                        wishlistFormData.append('action', 'remove');
                        wishlistFormData.append('wishlist_id', wishlistId);
                        
                        return fetch('wishlist_handler.php', { method: 'POST', body: wishlistFormData });
                    } else {
                        throw new Error(cartData.message || 'Could not add to cart.');
                    }
                })
                .then(res => res.json())
                .then(wishlistData => {
                    if (wishlistData.success) {
                        // Remove the item from the page
                        document.getElementById('wishlist-item-' + wishlistId).remove();
                    } else {
                         throw new Error(wishlistData.message || 'Could not remove from wishlist.');
                    }
                })
                .catch(err => {
                    alert('An error occurred: ' + err.message);
                    originalButton.disabled = false;
                    originalButton.innerHTML = '<i class="fas fa-shopping-cart me-1"></i> Move to Cart';
                });
        });
    });
});
</script>

</body>
</html>