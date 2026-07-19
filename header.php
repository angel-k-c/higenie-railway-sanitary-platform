<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$topLevelPages = ['index.php', 'login.php', 'customer_registration.php', 'reset_password.php', 'about.php', 'contact.php', 'products.php'];

require_once __DIR__ . '/db.php';

$categories = [];
try {
    $stmt = $pdo->query("
        SELECT c.id, c.name 
        FROM categories c
        JOIN products p ON c.id = p.category_id
        WHERE p.status = 'active'
        GROUP BY c.id, c.name
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

function getActiveClass($pageName, $currentPage) {
    return ($pageName === $currentPage) ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm modern-navbar">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center">
            <?php if (!in_array($currentPage, $topLevelPages)): ?>
                <a href="javascript:history.back()" class="flipkart-back-btn me-3" aria-label="Go Back">
                    <i class="fas fa-arrow-left"></i>
                </a>
            <?php endif; ?>

            <a class="navbar-brand fw-bolder d-flex align-items-center me-0" href="/higenie/index.php">
                <span class="text-danger">Hi.</span><span class="text-dark">genie</span>
            </a>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] === 'customer'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= getActiveClass('products.php', $currentPage) ?>" 
                           href="/higenie/products.php" 
                           id="productsDropdown" 
                           role="button" 
                           data-bs-toggle="dropdown" 
                           aria-expanded="false">
                           Products
                        </a>

                        <ul class="dropdown-menu" aria-labelledby="productsDropdown">
                            <li><a class="dropdown-item" href="/higenie/products.php">All Products</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a class="dropdown-item" href="/higenie/products.php?category=<?= htmlspecialchars($category['id']) ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link <?= getActiveClass('about.php', $currentPage) ?>" href="/higenie/about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link <?= getActiveClass('contact.php', $currentPage) ?>" href="/higenie/contact.php">Contact</a></li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'customer'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= getActiveClass('dashboard.php', $currentPage) ?>" href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> My Account
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                                <li><a class="dropdown-item" href="/higenie/customer/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="/higenie/customer/edit_profile.php">Edit Profile</a></li>
                                <li><a class="dropdown-item" href="/higenie/customer/wishlist.php">Wishlist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/higenie/logout.php">Logout</a></li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a href="/higenie/cart/cart.php" class="nav-link position-relative px-3" aria-label="View Shopping Cart">
                                <i class="fas fa-shopping-cart fa-lg"></i>
                                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $_SESSION['cart_count'] ?? 0 ?></span>
                            </a>
                        </li>
                    <?php elseif (in_array($_SESSION['role'], ['d_agent', 'admin'])): ?>
                        <li class="nav-item">
                            <a class="btn btn-danger btn-sm fw-semibold px-3 rounded-custom" href="/higenie/<?= $_SESSION['role'] ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-danger btn-sm fw-semibold px-3 rounded-custom" href="/higenie/logout.php" onclick="confirmLogout(event);">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm fw-semibold px-3 rounded-custom" href="/higenie/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger btn-sm fw-semibold px-3 rounded-custom" href="/higenie/customer/customer_registration.php">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.navbar { min-height: 70px; padding: 0; background-color: #fff; border-bottom: 1px solid #f0f0f0; }
.navbar-brand { font-size: 1.5rem; letter-spacing: -0.5px; }
.navbar-nav .nav-link { font-size: 0.95rem; padding: 10px 15px !important; color: #444; font-weight: 500; transition: all 0.2s ease; }
.navbar-nav .nav-link:hover, .navbar-nav .nav-link.active { color: #d72323 !important; }
.dropdown-menu { border-radius: 8px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 0.5rem 0; }
.dropdown-item { padding: 8px 20px; font-size: 0.9rem; }
.dropdown-item:active { background-color: #d72323; }
.flipkart-back-btn { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: #f8f9fa; color: #555; text-decoration: none; transition: all 0.2s ease; border: 1px solid #eee; }
.flipkart-back-btn:hover { background-color: #e9ecef; color: #000; border-color: #ddd; }
#cart-count { font-size: 0.7rem; padding: 4px 6px; }
.navbar-nav .btn.rounded-custom { border-radius: 6px !important; padding: 8px 20px; font-size: 0.9rem; }
</style>

