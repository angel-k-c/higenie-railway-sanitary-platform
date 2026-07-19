<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: /higenie/login.php");
    exit;
}
require __DIR__ . '/../db.php';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $blood_group = $_POST['blood_group'] ?? '';
    $driving_license_no = strtoupper(trim($_POST['driving_license_no'] ?? ''));
    $status = $_POST['status'] ?? 'active';
    $edit_id = $_POST['id'] ?? null;

    function uploadFile($inputName, $existingFile = '') {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return $existingFile;
        }
        $file = $_FILES[$inputName];
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $originalName = basename($file['name']);
        $safeFilename = preg_replace("/[^A-Za-z0-9._-]/", '', $originalName);
        $uniqueFilename = uniqid() . '-' . $safeFilename;
        $targetFile = $uploadDir . $uniqueFilename;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            if ($existingFile && file_exists(__DIR__ . '/' . $existingFile)) {
                unlink(__DIR__ . '/' . $existingFile);
            }
            return 'uploads/' . $uniqueFilename;
        }
        return $existingFile;
    }

    $pdo->beginTransaction();
    try {
        if ($edit_id) { 
            $stmt = $pdo->prepare("SELECT user_id FROM delivery_agents WHERE id=?");
            $stmt->execute([$edit_id]);
            $agent = $stmt->fetch();
            if (!$agent) throw new Exception("Agent not found.");
            $user_id_to_update = $agent['user_id'];
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id_to_update]);
            if ($stmt->fetch()) {
                throw new Exception("Email '$email' is already in use by another user.");
            }
            if (!empty($password)) {
                if ($password !== $confirm_password) throw new Exception("Passwords do not match.");
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, password=?, phone=? WHERE id=?")
                    ->execute([$first_name, $last_name, $email, $hashed_password, $phone, $user_id_to_update]);
            } else {
                $pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?")
                    ->execute([$first_name, $last_name, $email, $phone, $user_id_to_update]);
            }
            $stmt = $pdo->prepare("SELECT agent_image, driving_license_image, police_clearance_image FROM delivery_agents WHERE id = ?");
            $stmt->execute([$edit_id]);
            $currentFiles = $stmt->fetch();
            $agent_image = uploadFile('agent_image', $currentFiles['agent_image']);
            $license_image = uploadFile('license_image', $currentFiles['driving_license_image']);
            $police_clearance_image = uploadFile('police_clearance_image', $currentFiles['police_clearance_image']);
            $sql = "UPDATE delivery_agents SET address=?, agent_image=?, driving_license_no=?, driving_license_image=?, blood_group=?, police_clearance_image=?, status=? WHERE id=?";
            $pdo->prepare($sql)->execute([$address, $agent_image, $driving_license_no, $license_image, $blood_group, $police_clearance_image, $status, $edit_id]);
            $message = "<div class='alert alert-success'>Agent '<b>" . htmlspecialchars($first_name) . "</b>' updated successfully!</div>";
        } else { 
            if (empty($password) || $password !== $confirm_password) throw new Exception("Passwords are required and must match.");
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) throw new Exception("Email '$email' already exists.");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, 'd_agent')");
            $stmt->execute([$first_name, $last_name, $email, $hashed_password, $phone]);
            $user_id = $pdo->lastInsertId();
            $agent_image = uploadFile('agent_image');
            $license_image = uploadFile('license_image');
            $police_clearance_image = uploadFile('police_clearance_image');
            if (empty($agent_image) || empty($license_image) || empty($police_clearance_image)) {
                throw new Exception("All three images (Agent Photo, License, Police Clearance) are required.");
            }
            $sql = "INSERT INTO delivery_agents (user_id, address, agent_image, driving_license_no, driving_license_image, blood_group, police_clearance_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$user_id, $address, $agent_image, $driving_license_no, $license_image, $blood_group, $police_clearance_image, $status]);
            $message = "<div class='alert alert-success'>✅ Agent '<b>" . htmlspecialchars($first_name) . "</b>' added successfully!</div>";
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = " Error: " . $e->getMessage();
    }
}


$agents = $pdo->query("
    SELECT 
        da.id, da.address, da.agent_image, da.driving_license_no, da.driving_license_image, da.blood_group, da.police_clearance_image, da.status,
        u.first_name, u.last_name, u.email, u.phone
    FROM users u
    JOIN delivery_agents da ON u.id = da.user_id
    WHERE u.role = 'd_agent'
    ORDER BY u.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Delivery Agents</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #ffffff; font-family: 'Segoe UI', sans-serif; }
        .main-content { padding: 40px 15px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15); }
        .card-header { background-color: #fff; color: #d72323; font-weight: bold; }
        .agent-img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .action-icons a { margin: 0 8px; font-size: 1.1rem; }
        .current-img-display { max-width: 60px; border-radius: 8px; margin-top: 10px; }
        .password-group { position: relative; }
        .toggle-eye { position: absolute; top: 40px; right: 10px; cursor: pointer; color: #6c757d; }
    </style>
</head>
<body>

<?php 
include __DIR__ . '/../header.php'; 
?>

<div class="main-content container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-truck-fast me-2"></i>Existing Delivery Agents</h4>
            <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                <i class="fas fa-plus-circle me-2"></i>Add New Agent
            </button>
        </div>
        <div class="card-body">
            <?php if (!empty($message)) echo $message; ?>
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th><th>Agent Name</th><th>Contact</th><th>License No.</th><th>Status</th><th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($agent['agent_image'] ?? 'placeholder.png') ?>" class="agent-img-thumb"></td>
                            <td><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']) ?></td>
                            <td><?= htmlspecialchars($agent['email']) ?><br><small class="text-muted"><?= htmlspecialchars($agent['phone']) ?></small></td>
                            <td><?= htmlspecialchars($agent['driving_license_no'] ?? 'N/A') ?></td>
                            <td><span class="badge <?= $agent['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($agent['status']) ?></span></td>
                            <td class="text-center action-icons">
                                <a href="#" class="text-primary" title="Edit" data-bs-toggle="modal" data-bs-target="#editAgentModal"
                                   data-bs-id="<?= $agent['id'] ?>"
                                   data-bs-first-name="<?= htmlspecialchars($agent['first_name']) ?>"
                                   data-bs-last-name="<?= htmlspecialchars($agent['last_name']) ?>"
                                   data-bs-email="<?= htmlspecialchars($agent['email']) ?>"
                                   data-bs-phone="<?= htmlspecialchars($agent['phone']) ?>"
                                   data-bs-address="<?= htmlspecialchars($agent['address']) ?>"
                                   data-bs-license-no="<?= htmlspecialchars($agent['driving_license_no']) ?>"
                                   data-bs-blood-group="<?= htmlspecialchars($agent['blood_group']) ?>"
                                   data-bs-status="<?= $agent['status'] ?>"
                                   data-bs-agent-image="<?= htmlspecialchars($agent['agent_image']) ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?= $agent['id'] ?>" class="text-danger" title="Delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAgentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="d_agents.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php include '_agent_form_fields.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Add Agent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAgentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="d_agents.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php include '_agent_form_fields.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Update Agent</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include "../footer.php"; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editAgentModal = document.getElementById('editAgentModal');
    if (editAgentModal) {
        editAgentModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const modal = this;
            modal.querySelector('#edit-id').value = button.getAttribute('data-bs-id');
            modal.querySelector('#form-first_name').value = button.getAttribute('data-bs-first-name');
            modal.querySelector('#form-last_name').value = button.getAttribute('data-bs-last-name');
            modal.querySelector('#form-email').value = button.getAttribute('data-bs-email');
            modal.querySelector('#form-phone').value = button.getAttribute('data-bs-phone');
            modal.querySelector('#form-address').value = button.getAttribute('data-bs-address');
            modal.querySelector('#form-license_no').value = button.getAttribute('data-bs-license-no');
            modal.querySelector('#form-blood_group').value = button.getAttribute('data-bs-blood-group');
            modal.querySelector('#form-status').value = button.getAttribute('data-bs-status');
            
            const imagePreview = modal.querySelector('#form-agent-image-preview');
            const agentImage = button.getAttribute('data-bs-agent-image');
            if (agentImage) {
                imagePreview.innerHTML = `<img src="${agentImage}" alt="Current Image" class="current-img-display">`;
            } else {
                imagePreview.innerHTML = '';
            }
        });
    }

    const addAgentModal = document.getElementById('addAgentModal');
    if (addAgentModal) {
        addAgentModal.addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            this.querySelector('#form-agent-image-preview').innerHTML = '';
        });
    }
    
    // TOGGLE EYE SCRIPT 
    function setupToggleEye(toggleEl) {
        toggleEl.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            const passwordInput = document.querySelector(targetId);
            if (passwordInput) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            }
        });
    }

    document.querySelectorAll('.toggle-eye').forEach(setupToggleEye);
});
</script>
</body>
</html>