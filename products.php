<?php
session_start();
require 'db.php';

$all_categories_stmt = $pdo->query("
    SELECT DISTINCT c.id, c.name FROM categories c
    JOIN products p ON c.id = p.category_id
    WHERE p.status = 'active'
    ORDER BY c.name ASC
");
$all_categories = $all_categories_stmt->fetchAll();

// FILTERS 
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['search'] ?? '');
$params = [];

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active'";

if ($category_id > 0) {
    $sql .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($search)) {
    $sql .= " AND (p.product_name LIKE :product_search
                  OR p.description LIKE :desc_search
                  OR p.brand_name LIKE :brand_search)";
    $params[':product_search'] = "%$search%";
    $params[':desc_search']    = "%$search%";
    $params[':brand_search']   = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC"; 
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$category_name = "All Products";
if ($category_id > 0) {
    foreach ($all_categories as $cat) {
        if ($cat['id'] == $category_id) {
            $category_name = $cat['name'];
            break;
        }
    }
}
if (!empty($search)) {
    $category_name = 'Search Results for "' . htmlspecialchars($search) . '"';
}

// CART COUNT 
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ? AND status = 'cart'");
    $stmt->execute([$user_id]);
    $_SESSION['cart_count'] = (int) $stmt->fetchColumn();
} else {
    $_SESSION['cart_count'] = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($category_name) ?> - Hi.Genie</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    :root {
        --primary-color: #d72323;
        --primary-hover: #b71c1c;
    }
    body {
        background-color: #f8f9fa; 
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }
    .search-container {
        max-width: 600px;
    }
    .category-bar {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 15px; 
        scrollbar-width: none; 
    }
    .category-bar::-webkit-scrollbar { display: none;  }
    .category-bar a {
        text-decoration: none;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        white-space: nowrap;
        color: #555;
        background: #fff;
        border: 1px solid #eee;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .category-bar a.active, .category-bar a:hover {
        background: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(215, 35, 35, 0.3);
    }

    /* PRODUCT CARD*/
    .product-box {
        position: relative;
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        height: 100%; 
        border: 1px solid #f0f0f0;
    }
    .product-box:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    }
    .product-thumbnail {
        width: 100%;
        aspect-ratio: 1/1;
        object-fit: contain;
        padding: 25px;
        background: #fff;
        transition: transform 0.3s ease;
    }
    .product-box:hover .product-thumbnail {
        transform: scale(1.05); 
    }
    .product-info {
        padding: 15px 20px 20px;
        text-align: center;
        background: #fff;
        position: relative;
        z-index: 2; 
    }
    .product-title {
        font-size: 1rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 8px;
        white-space: nowrap; 
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .product-price {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary-color);
        margin: 0;
    }

    /* OVERLAY & BUTTONS --- */
    .product-overlay {
        position: absolute;
        inset: 0; 
        background: rgba(172, 171, 171, 0.95); 
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 5;
    }
    @media (min-width: 993px) {
        .product-box:hover .product-overlay {
            opacity: 1;
            visibility: visible;
        }
    }
    @media (max-width: 992px) {
        .product-overlay {
            position: relative;
            opacity: 1;
            visibility: visible;
            background: #fff;
            padding: 0 15px 20px 15px;
            inset: auto;
            height: auto;
        }
        .product-thumbnail {
            padding-bottom: 0; 
        }
    }

    .overlay-btn {
        width: 100%;
        max-width: 200px;
        margin: 5px 0;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 12px;
        border: none;
        transition: transform 0.2s;
    }
    .overlay-btn:active { transform: scale(0.95); }

    .btn-add {
        background: #333;
        color: #fff;
    }
    .btn-add:hover { background: #000; color: #fff; }

    .btn-wishlist {
        background: #fff;
        border: 2px solid #eee;
        color: #333;
    }
    .btn-wishlist:hover { border-color: #333; background: #fff; }

    .btn-buy {
        background: var(--primary-color);
        color: #fff;
    }
    .btn-buy:hover { background: var(--primary-hover); color: #fff; }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      50% { transform: translateX(5px); }
      75% { transform: translateX(-5px); }
    }
    .shake { animation: shake 0.4s ease-in-out; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6 mb-3 mb-md-0">
            <h2 class="fw-bold text-dark m-0"><?= htmlspecialchars($category_name) ?></h2>
            <p class="text-muted mb-0"><?= count($products) ?> items found</p>
        </div>
        <div class="col-md-6">
            <form method="get" class="d-flex search-container ms-auto">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search essentials..." value="<?= htmlspecialchars($search) ?>" style="box-shadow: none;">
                    <button class="btn btn-danger fw-bold px-4">Find</button>
                </div>
            </form>
        </div>
    </div>

    <div class="category-bar mb-5">
        <a href="products.php" class="<?= ($category_id == 0 && empty($search)) ? 'active' : '' ?>">
            <i class="fas fa-th-large me-2"></i>All Items
        </a>
        <?php foreach ($all_categories as $cat): ?>
            <a href="products.php?category=<?= $cat['id'] ?>" class="<?= ($category_id == $cat['id']) ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3 opacity-50"></i>
            <h4 class="text-muted">No products found here.</h4>
            <a href="products.php" class="btn btn-outline-dark mt-3">View All Products</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="product-box">
                        <img src="uploads/<?= basename($product['image']) ?>" class="product-thumbnail" alt="<?= htmlspecialchars($product['product_name']) ?>">
                        
                        <div class="product-info">
                            <h5 class="product-title" title="<?= htmlspecialchars($product['product_name']) ?>">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </h5>
                            <p class="product-price">₹<?= number_format($product['price'], 2) ?></p>
                        </div>

                        <div class="product-overlay">
                            <?php if ($user_id): ?>
                                <button class="btn overlay-btn btn-add add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                                <button class="btn overlay-btn btn-buy buy-now-btn" data-product-id="<?= $product['id'] ?>">
                                    <i class="fas fa-bolt me-2"></i>Buy Now
                                </button>
                                <button class="btn overlay-btn btn-wishlist add-to-wishlist-btn" data-product-id="<?= $product['id'] ?>">
                                    <i class="far fa-heart"></i>Add to Wishlist
                                </button>
                            <?php else: ?>
                                <a href="/higenie/login.php" class="btn overlay-btn btn-add text-decoration-none">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </a>
                                <a href="/higenie/login.php" class="btn overlay-btn btn-buy text-decoration-none">
                                    <i class="fas fa-bolt me-2"></i>Buy Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function handleAction(formData, btnElement, successText, originalText) {
        if (btnElement) {
             btnElement.disabled = true;
             btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        fetch('/higenie/cart_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (btnElement) {
                     btnElement.innerHTML = successText;
                     btnElement.classList.add('btn-success');
                }

                if (data.cart_count !== undefined) {
                    const badge = document.getElementById('cart-count');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.classList.add('shake');
                        setTimeout(() => badge.classList.remove('shake'), 500);
                    }
                }
                
                if (formData.get('buy_now') === 'true') {
                     window.location.href = '/higenie/cart/cart.php';
                     return;
                }

                setTimeout(() => {
                    if (btnElement) {
                        btnElement.innerHTML = originalText;
                        btnElement.disabled = false;
                        btnElement.classList.remove('btn-success');
                    }
                }, 2000);

            } else {
                alert(data.message || 'Action failed.');
                if (btnElement) {
                     btnElement.innerHTML = originalText;
                     btnElement.disabled = false;
                }
            }
        })
        .catch(err => {
            console.error('Error:', err);
            if (btnElement) {
                 btnElement.innerHTML = originalText;
                 btnElement.disabled = false;
            }
        });
    }


    // Add to Cart
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', this.dataset.productId);
            formData.append('quantity', 1);
            handleAction(formData, this, '<i class="fas fa-check"></i> Added', this.innerHTML);
        });
    });

    //  Buy Now
    document.querySelectorAll('.buy-now-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', this.dataset.productId);
            formData.append('quantity', 1);
            formData.append('buy_now', 'true'); // Custom flag for redirect
            handleAction(formData, this, '<i class="fas fa-spinner fa-spin"></i> Redirecting...', this.innerHTML);
        });
    });

    // Wishlist
    document.querySelectorAll('.add-to-wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'add_to_wishlist');
            formData.append('product_id', this.dataset.productId);
            handleAction(formData, this, '<i class="fas fa-heart text-danger"></i>', this.innerHTML);
        });
    });

});
</script>
</body>
</html>