<?php
session_start();
require '../../db.php';

header('Content-Type: text/html; charset=UTF-8');

$message = '';
$edit_category = null;

function fetchCategories($pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

$categories = fetchCategories($pdo);

if (isset($_POST['action']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
    $response = ['success' => false, 'message' => 'Unknown error'];
    
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name !== '') {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $response = ['success' => true, 'message' => 'Category added successfully.'];
            } else {
                $response['message'] = 'Database error while adding category.';
            }
        } else {
            $response['message'] = 'Category name is required.';
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($id > 0 && $name !== '') {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $id])) {
                $response = ['success' => true, 'message' => 'Category updated successfully.'];
            } else {
                $response['message'] = 'Database error while updating category.';
            }
        } else {
            $response['message'] = 'Category name and ID are required.';
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $response = ['success' => true, 'message' => 'Category deleted successfully.'];
            } else {
                $response['message'] = 'Database error while deleting category.';
            }
        }
    } elseif ($_POST['action'] === 'fetch') {
        $categories = fetchCategories($pdo);
        ob_start(); ?>
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="4" class="text-center py-4">No categories found.</td></tr>
            <?php else: foreach ($categories as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['id']) ?></td>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($cat['description'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary edit-btn" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" data-description="<?= htmlspecialchars($cat['description']) ?>">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $cat['id'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php
        $response = ['success' => true, 'html' => ob_get_clean()];
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Categories - AJAX</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #fff; font-family: 'Segoe UI', sans-serif; color: #333; }
.main-content { padding: 40px 15px; }
.card { border: none; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.15); }
.card-header { background-color: #fff; color: #d72323; font-weight: bold; border-bottom: 1px solid #eee; padding: 1rem 1.25rem; }
.btn-submit { width: 100%; background: #d72323; color: white; padding: 12px; font-size: 16px; border: none; border-radius: 8px; cursor: pointer; margin-top: 10px; font-weight: bold; transition: background-color 0.3s; }
.btn-submit:hover { background: #b71c1c; }
</style>
</head>
<body>

<?php include '../../header.php'; ?>

<div class="main-content container">
    <div id="message"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Categories</h4>
        </div>
        <div class="card-body" id="categories-table">
           
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 id="form-title">Add New Category</h4>
        </div>
        <div class="card-body">
            <form id="category-form">
                <input type="hidden" name="id" id="cat-id">
                <div class="mb-3">
                    <label class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="cat-name" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="cat-desc" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn-submit" id="submit-btn">Add Category</button>
                <button type="button" class="btn btn-secondary mt-2" id="cancel-btn" style="display:none;">Cancel</button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadCategories() {
    $.post('categories.php', { action: 'fetch' }, function(data){
        if(data.success) $('#categories-table').html(data.html);
    }, 'json');
}

$(document).ready(function(){
    loadCategories();

    $('#category-form').on('submit', function(e){
        e.preventDefault();
        let action = $('#cat-id').val() ? 'edit' : 'add';
        $.post('categories.php', {
            action: action,
            id: $('#cat-id').val(),
            name: $('#cat-name').val(),
            description: $('#cat-desc').val()
        }, function(data){
            $('#message').html('<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>');
            if(data.success){
                $('#category-form')[0].reset();
                $('#cat-id').val('');
                $('#submit-btn').text('Add Category');
                $('#form-title').text('Add New Category');
                $('#cancel-btn').hide();
                loadCategories();
            }
        }, 'json');
    });

    $(document).on('click', '.edit-btn', function(){
        $('#cat-id').val($(this).data('id'));
        $('#cat-name').val($(this).data('name'));
        $('#cat-desc').val($(this).data('description'));
        $('#submit-btn').text('Update Category');
        $('#form-title').text('Edit Category');
        $('#cancel-btn').show();
        window.scrollTo({ top: $('#category-form').offset().top - 100, behavior: 'smooth' });
    });

    $(document).on('click', '#cancel-btn', function(){
        $('#category-form')[0].reset();
        $('#cat-id').val('');
        $('#submit-btn').text('Add Category');
        $('#form-title').text('Add New Category');
        $(this).hide();
    });

    $(document).on('click', '.delete-btn', function(){
        if(confirm('Are you sure you want to delete this category?')){
            let id = $(this).data('id');
            $.post('categories.php', { action: 'delete', id: id }, function(data){
                $('#message').html('<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>');
                if(data.success) loadCategories();
            }, 'json');
        }
    });
});
</script>
</body>
</html>
