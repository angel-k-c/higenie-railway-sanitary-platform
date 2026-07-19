<?php
session_start();
require '../../db.php';

header('Content-Type: text/html; charset=UTF-8');

$message = '';
$edit_sub = null;

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

function fetchSubcategories($pdo) {
    return $pdo->query("
        SELECT s.*, c.name AS category_name
        FROM subcategories s
        JOIN categories c ON s.category_id = c.id
        ORDER BY s.id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$subcategories = fetchSubcategories($pdo);

if (isset($_POST['action']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {

    $response = ['success' => false, 'message' => 'Unknown error'];

    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $cid = (int)($_POST['category_id'] ?? 0);

        if ($name !== '' && $cid > 0) {
            $stmt = $pdo->prepare("INSERT INTO subcategories (name, category_id) VALUES (?, ?)");
            if ($stmt->execute([$name, $cid])) {
                $response = ['success' => true, 'message' => 'Subcategory added successfully.'];
            } else {
                $response['message'] = 'Error adding subcategory.';
            }
        } else {
            $response['message'] = 'Name and category required.';
        }

    } elseif ($_POST['action'] === 'edit') {

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $cid = (int)($_POST['category_id'] ?? 0);

        if ($id > 0 && $name !== '' && $cid > 0) {
            $stmt = $pdo->prepare("UPDATE subcategories SET name = ?, category_id = ? WHERE id = ?");
            if ($stmt->execute([$name, $cid, $id])) {
                $response = ['success' => true, 'message' => 'Subcategory updated successfully.'];
            } else {
                $response['message'] = 'Error updating subcategory.';
            }
        } else {
            $response['message'] = 'Name, category, ID required.';
        }

    } elseif ($_POST['action'] === 'delete') {

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $response = ['success' => true, 'message' => 'Subcategory deleted successfully.'];
            } else {
                $response['message'] = 'Error deleting subcategory.';
            }
        }

    } elseif ($_POST['action'] === 'fetch') {

        $subcategories = fetchSubcategories($pdo);

        ob_start(); ?>
        
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Subcategory</th>
                    <th>Category</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($subcategories)): ?>
                <tr><td colspan="4" class="text-center py-4">No subcategories found.</td></tr>
            <?php else: foreach ($subcategories as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['category_name']) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary edit-btn"
                            data-id="<?= $s['id'] ?>"
                            data-name="<?= htmlspecialchars($s['name']) ?>"
                            data-cid="<?= $s['category_id'] ?>">
                            Edit
                        </button>

                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $s['id'] ?>">Delete</button>
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
<title>Manage Subcategories - AJAX</title>
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
            <h4>Subcategories</h4>
        </div>
        <div class="card-body" id="subcategories-table">
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 id="form-title">Add New Subcategory</h4>
        </div>
        <div class="card-body">

            <form id="subcategory-form">
                <input type="hidden" name="id" id="sub-id">

                <div class="mb-3">
                    <label class="form-label">Select Category</label>
                    <select class="form-control" id="sub-category" name="category_id" required>
                        <option value="">Choose category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subcategory Name</label>
                    <input type="text" class="form-control" id="sub-name" name="name" required>
                </div>

                <button type="submit" class="btn-submit" id="submit-btn">Add Subcategory</button>
                <button type="button" class="btn btn-secondary mt-2" id="cancel-btn" style="display:none;">Cancel</button>
            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
function loadSubcategories() {
    $.post('subcategories.php', { action: 'fetch' }, function(data){
        if(data.success) $('#subcategories-table').html(data.html);
    }, 'json');
}

$(document).ready(function(){

    loadSubcategories();

    $('#subcategory-form').on('submit', function(e){
        e.preventDefault();

        let action = $('#sub-id').val() ? 'edit' : 'add';

        $.post('subcategories.php', {
            action: action,
            id: $('#sub-id').val(),
            category_id: $('#sub-category').val(),
            name: $('#sub-name').val(),
        }, function(data){

            $('#message').html('<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>');

            if(data.success){
                $('#subcategory-form')[0].reset();
                $('#sub-id').val('');
                $('#form-title').text('Add New Subcategory');
                $('#submit-btn').text('Add Subcategory');
                $('#cancel-btn').hide();
                loadSubcategories();
            }

        }, 'json');
    });

    $(document).on('click', '.edit-btn', function(){

        $('#sub-id').val($(this).data('id'));
        $('#sub-name').val($(this).data('name'));
        $('#sub-category').val($(this).data('cid'));

        $('#form-title').text('Edit Subcategory');
        $('#submit-btn').text('Update Subcategory');
        $('#cancel-btn').show();

        window.scrollTo({ top: $('#subcategory-form').offset().top - 80, behavior: 'smooth' });
    });

    $('#cancel-btn').click(function(){
        $('#subcategory-form')[0].reset();
        $('#sub-id').val('');
        $('#form-title').text('Add New Subcategory');
        $('#submit-btn').text('Add Subcategory');
        $(this).hide();
    });

    $(document).on('click', '.delete-btn', function(){
        if(confirm('Delete this subcategory?')){

            $.post('subcategories.php', {
                action: 'delete',
                id: $(this).data('id')
            }, function(data){

                $('#message').html('<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>');

                if(data.success) loadSubcategories();

            }, 'json');
        }
    });

});
</script>

</body>
</html>
