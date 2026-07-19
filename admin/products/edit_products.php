<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /higenie/login.php");
    exit;
}

define('ROOT_PATH', __DIR__ . '/../../'); 
define('WEB_ROOT', '/higenie/');

require ROOT_PATH . 'db.php';

$message = '';
$edit_product = null;
$error = '';
$show_form = false; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category'] ?? 0);
    $subcategory_id = !empty($_POST['subcategory']) ? intval($_POST['subcategory']) : null;
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    try {
        $uploadDir = ROOT_PATH . 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (isset($_POST['update_product'])) {
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception("Invalid Product ID for update.");

            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $imagePath = $stmt->fetchColumn();

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('prod_') . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    if ($imagePath && file_exists(ROOT_PATH . $imagePath) && strpos($imagePath, 'placeholder') === false) {
                        unlink(ROOT_PATH . $imagePath);
                    }
                    $imagePath = 'uploads/' . $filename;
                } else {
                    throw new Exception("Failed to move uploaded file. Check folder permissions.");
                }
            }

            $sql = "UPDATE products 
                    SET product_name = ?, brand_name = ?, price = ?, category_id = ?, subcategory_id = ?, stock = ?, image = ?, description = ?, status = ? 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $brand, $price, $category_id, $subcategory_id, $stock, $imagePath, $description, $status, $id]);
            
            $message = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle me-2'></i>Product updated successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";

        } elseif (isset($_POST['add_product'])) {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                 throw new Exception("Please select a product image.");
            }

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_') . '.' . $ext;
            $targetFile = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                throw new Exception("Failed to upload image.");
            }
            $imagePath = 'uploads/' . $filename;

            $sql = "INSERT INTO products (product_name, brand_name, price, category_id, subcategory_id, stock, image, description, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $brand, $price, $category_id, $subcategory_id, $stock, $imagePath, $description, $status]);
            
            $message = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle me-2'></i>Product added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
        $show_form = true; 
        if (isset($_POST['update_product'])) {
             $edit_product = $_POST;
             $edit_product['id'] = $id;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $show_form = true;
        if (isset($_POST['update_product'])) {
             $edit_product = $_POST;
             $edit_product['id'] = intval($_POST['id'] ?? 0);
        }
    }
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists(ROOT_PATH . $img)) { unlink(ROOT_PATH . $img); }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $message = "<div class='alert alert-warning alert-dismissible fade show'>Product deleted.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }

    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($edit_product) {
            $show_form = true; 
        }
    }
}

$products = $pdo->query("SELECT p.*, c.name AS category_name, s.name AS subcategory_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN subcategories s ON p.subcategory_id = s.id ORDER BY p.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $pdo->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hi.Genie - Manage Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary-color: #c0392b; --text-dark: #2c3e50; --bg-light: #f8f9fa; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        .page-wrapper { padding: 30px 15px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
        .card-header-custom { background: #fff; padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .product-img-thumb { width: 45px; height: 45px; object-fit: contain; border-radius: 6px; border: 1px solid #eee; padding: 2px; }
        .current-img-display { width: 100px; height: 100px; object-fit: contain; border: 1px solid #ddd; border-radius: 8px; padding: 5px; margin-top: 10px; }
        .table-custom th { background: #f8f9fa; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; color: #6c757d; }
        .table-custom td { vertical-align: middle; }
        .btn-primary-custom { background: var(--primary-color); color: #fff; border: none; }
        .btn-primary-custom:hover { background: #a93226; color: #fff; }
    </style>
</head>
<body>

<?php include ROOT_PATH . 'header.php'; ?>

<div class="page-wrapper">
    <div class="container-fluid">
        <?php if ($message) echo $message; ?>
        <?php if ($error) echo "<div class='alert alert-danger alert-dismissible fade show'>$error<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"; ?>

        <div class="card-custom mb-4">
            <div class="card-header-custom">
                <h5 class="m-0 fw-bold text-dark"><i class="fas fa-boxes me-2 text-primary-custom" style="color: var(--primary-color);"></i> Product Table</h5>
                <button id="toggleFormBtn" class="btn btn-primary-custom btn-sm px-3 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#productForm" aria-expanded="<?= $show_form ? 'true' : 'false' ?>">
                    <i class="fas fa-plus-circle me-2"></i>Add / Edit Product
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-custom table-hover mb-0">
                        <thead style="position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price & Stock</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No products found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= WEB_ROOT . htmlspecialchars($p['image']) ?>" class="product-img-thumb me-3">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($p['brand_name']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($p['category_name']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($p['subcategory_name'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold">₹<?= number_format($p['price'], 2) ?></div>
                                        <small class="<?= $p['stock'] < 5 ? 'text-danger fw-bold' : 'text-muted' ?>">Stock: <?= $p['stock'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($p['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="?action=edit&id=<?= $p['id'] ?>#productForm" class="btn btn-sm btn-outline-primary border-0"><i class="fas fa-pen"></i></a>
                                        <a href="?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Delete this product?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="collapse <?= $show_form ? 'show' : '' ?>" id="productForm">
            <div class="card-custom" style="border-top: 4px solid var(--primary-color);">
                <div class="card-header-custom">
                    <h5 class="m-0 fw-bold">
                        <?= $edit_product ? '<i class="fas fa-edit me-2"></i>Edit Product' : '<i class="fas fa-plus me-2"></i>Add New Product' ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data" action="edit_products.php">
                        <?php if ($edit_product): ?>
                            <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_product['product_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                                <input type="text" name="brand" class="form-control" required value="<?= htmlspecialchars($edit_product['brand_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" id="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($edit_product && $edit_product['category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory" id="subcategory" class="form-select">
                                    <option value="">Select Subcategory</option>
                                    <?php foreach ($subcategories as $s): ?>
                                        <option value="<?= $s['id'] ?>" data-category="<?= $s['category_id'] ?>" <?= ($edit_product && $edit_product['subcategory_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" required value="<?= htmlspecialchars($edit_product['price'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock <span class="text-danger">*</span></label>
                                <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($edit_product['stock'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($edit_product && $edit_product['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($edit_product && $edit_product['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Product Image <?= $edit_product ? '<span class="text-muted fw-normal">(Optional)</span>' : '<span class="text-danger">*</span>' ?></label>
                                <input type="file" name="image" class="form-control" accept="image/*" <?= !$edit_product ? 'required' : '' ?>>
                                <?php if ($edit_product && !empty($edit_product['image'])): ?>
                                    <img src="<?= WEB_ROOT . htmlspecialchars($edit_product['image']) ?>" class="current-img-display">
                                <?php endif; ?>
                            </div>
                            <div class="col-12 mt-4 d-flex gap-2">
                                <button type="submit" name="<?= $edit_product ? 'update_product' : 'add_product' ?>" class="btn btn-primary-custom px-4 fw-bold">
                                    <?= $edit_product ? 'Save Changes' : 'Create Product' ?>
                                </button>
                                <?php if ($edit_product): ?>
                                    <a href="edit_products.php" class="btn btn-outline-secondary px-4">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    //AUTO SCROLL TO FORM WHEN OPENED
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('shown.bs.collapse', function () {
            this.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        
        if (productForm.classList.contains('show')) {
             productForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    //DYNAMIC SUBCATEGORY FILTERING
    const categorySelect = document.getElementById("category");
    const subcategorySelect = document.getElementById("subcategory");

    function filterSubcategories() {
        const selectedCat = categorySelect.value;
        Array.from(subcategorySelect.options).forEach(opt => {
            if (opt.value === "") return;
            opt.style.display = (opt.getAttribute('data-category') === selectedCat) ? '' : 'none';
        });
        if (subcategorySelect.selectedOptions[0].style.display === 'none') {
            subcategorySelect.value = "";
        }
    }

    if (categorySelect && subcategorySelect) {
        categorySelect.addEventListener("change", filterSubcategories);
        filterSubcategories(); 
    }
});
</script>
</body>
</html>