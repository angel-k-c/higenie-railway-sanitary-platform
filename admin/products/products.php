<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /higenie/login.php");
    exit();
}
require __DIR__ . '/../../db.php';

try {
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $total_subcategories = $pdo->query("SELECT COUNT(*) FROM subcategories")->fetchColumn();
    $out_of_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0 AND status = 'active'")->fetchColumn();
} catch (PDOException $e) {
    $total_products = $total_categories = $total_subcategories = $out_of_stock = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hi.Genie - Product Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #c0392b;
            --primary-hover: #a93226;
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
            --card-bg: #ffffff;
            --bg-light: #f8f9fa;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
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

        h2.page-title {
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--text-muted);
            margin-bottom: 40px;
        }

        /* --- Stats Cards --- */
        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            border-color: transparent;
        }

        .stat-icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .stat-card h3 {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-dark);
            line-height: 1;
        }
        
        .stat-card p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Specific stat colors */
        .stat-products .stat-icon-wrapper { background: rgba(52, 152, 219, 0.1); color: #3498db; }
        .stat-categories .stat-icon-wrapper { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
        .stat-subcategories .stat-icon-wrapper { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        .stat-stock .stat-icon-wrapper { background: rgba(192, 57, 43, 0.1); color: var(--primary-color); }

        /* Navigation Cards */
        .nav-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start; 
            padding: 30px;
            height: 100%;
            border-radius: 16px;
            background: var(--card-bg);
            text-decoration: none;
            color: var(--text-dark);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

      
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .nav-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(192, 57, 43, 0.1); 
        }

        .nav-card:hover::before {
            opacity: 1;
            height: 6px;
        }

        .nav-card .icon {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            background: rgba(192, 57, 43, 0.08);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .nav-card:hover .icon {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1) rotate(-5deg);
        }

        .nav-card h4 {
            font-weight: 700;
            font-size: 1.35rem;
            margin-bottom: 10px;
        }

        .nav-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 0;
        }
    </style>
</head>
<body>

<?php include '../../header.php'; ?>

<div class="page-wrapper">
    <div class="container">
        
        <div class="text-center">
            <h2 class="page-title">Product Management</h2>
            <p class="page-subtitle">Overview and management tools for your inventory</p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card stat-products">
                    <div class="stat-icon-wrapper"><i class="fas fa-box-open"></i></div>
                    <h3><?= number_format($total_products) ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card stat-categories">
                    <div class="stat-icon-wrapper"><i class="fas fa-tags"></i></div>
                    <h3><?= number_format($total_categories) ?></h3>
                    <p>Categories</p>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card stat-subcategories">
                    <div class="stat-icon-wrapper"><i class="fas fa-layer-group"></i></div>
                    <h3><?= number_format($total_subcategories) ?></h3>
                    <p>Sub-Categories</p>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card stat-stock">
                    <div class="stat-icon-wrapper"><i class="fas fa-exclamation-triangle"></i></div>
                    <h3><?= number_format($out_of_stock) ?></h3>
                    <p>Out of Stock</p>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center mb-5">
            <hr class="flex-grow-1" style="color: #dee2e6;">
            <span class="px-3 text-muted fw-semibold text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Quick Actions</span>
            <hr class="flex-grow-1" style="color: #dee2e6;">
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <a href="/higenie/admin/products/categories.php" class="nav-card">
                    <div class="icon"><i class="fas fa-list-ul"></i></div>
                    <h4>Manage Categories</h4>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="/higenie/admin/products/subcategories.php" class="nav-card">
                    <div class="icon"><i class="fas fa-sitemap"></i></div>
                    <h4>Manage Subcategories</h4>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="/higenie/admin/products/edit_products.php" class="nav-card">
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                    <h4>Manage Products</h4>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../footer.php'; ?>   
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>