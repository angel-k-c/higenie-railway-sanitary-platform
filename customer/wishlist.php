<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /higenie/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT p.id, p.product_name, p.price, p.image, p.brand_name
    FROM cart w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ? AND w.status = 'wishlist'
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hi.Genie - My Wishlist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #c0392b;
            --primary-hover: #a93226;
            --text-dark: #2c3e50;
            --bg-light: #f8f9fa;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-wrapper {
            flex: 1;
            padding: 40px 0;
        }
        .card-custom {
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header-custom {
            background: #fff;
            padding: 25px 30px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .card-header-custom h4 {
            margin: 0;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
        }
        .table-custom thead th {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eee;
            padding: 15px 20px;
        }
        .table-custom tbody td {
            padding: 15px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #eee;
            padding: 5px;
            background: #fff;
        }
        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            padding: 8px 20px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #fff;
        }
        .btn-primary-custom:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(192, 57, 43, 0.2);
            color: #fff;
        }
        .btn-remove {
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
        }
        .btn-remove:hover {
            background: #e74c3c;
            color: #fff;
        }
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #95a5a6;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../header.php'; ?>

<div class="page-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h4>
                            <i class="fas fa-heart me-3 text-primary-custom" style="background: rgba(192, 57, 43, 0.1); padding: 10px; border-radius: 10px; color: var(--primary-color);"></i> 
                            My Wishlist
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($wishlist_items)): ?>
                            <div class="empty-state">
                                <i class="far fa-heart"></i>
                                <h4>Your wishlist is empty</h4>
                                <p>Save items you want to buy later here.</p>
                                <a href="/higenie/products.php" class="btn btn-primary-custom mt-3">Explore Products</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($wishlist_items as $product): ?>
                                            <tr id="wishlist-row-<?= $product['id'] ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="/higenie/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="product-thumb me-3">
                                                        <div>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($product['product_name']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($product['brand_name']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary-custom" style="color: var(--primary-color);">
                                                        ₹<?= number_format($product['price'], 2) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-primary-custom btn-sm me-2 add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                                                        <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                                    </button>
                                                    </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const btn = this;
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Adding...';

            const formData = new FormData();
            formData.append('action', 'add_to_cart_from_wishlist');
            formData.append('product_id', productId);

            fetch('wishlist_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success state
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Added!';
                    btn.classList.remove('btn-primary-custom');
                    btn.classList.add('btn-success');

                    // after successful move to cart
                    setTimeout(() => {
                         document.getElementById('wishlist-row-' + productId).remove();
                    }, 1000);

                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-primary-custom');
                    }, 2000);

                    // Update cart count in header 
                     updateCartCount(data.new_cart_count); 

                } else {
                    // Error state
                    alert(data.message || 'Failed to add item to cart.');
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred. Please try again.');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        });
    });
});
</script>
</body>
</html>