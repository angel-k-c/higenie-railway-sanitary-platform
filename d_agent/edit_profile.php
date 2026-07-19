<?php
session_start();
require '../db.php';

if (!isset($_SESSION['agent_id'])) {
    header("Location: ../login.php");
    exit;
}

$agent_id = $_SESSION['agent_id'];
$message = '';
$error = '';

//current agent info
$stmt = $pdo->prepare("SELECT * FROM delivery_agents WHERE id = ?");
$stmt->execute([$agent_id]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

function handleFileUpload($file, $currentPath = '') {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        if ($currentPath && file_exists(__DIR__ . '/' . $currentPath)) unlink(__DIR__ . '/' . $currentPath);
        $uploadDir = __DIR__ . '/agent_files/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = time() . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($file['name']));
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) return 'agent_files/' . $filename;
    }
    return $currentPath;
}

// profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $profileImage = handleFileUpload($_FILES['agent_image'], $agent['agent_image']);

    try {
        $sql = "UPDATE delivery_agents SET agent_image=?, mobile=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$profileImage, $mobile, $agent_id]);

        $message = "<div class='alert alert-success'>Profile updated successfully!</div>";
        
        // Refreshing 
        $stmt = $pdo->prepare("SELECT * FROM delivery_agents WHERE id = ?");
        $stmt->execute([$agent_id]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile - Hi.Genie</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
.main-content { padding: 40px 15px; }
.card { border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); margin-bottom: 30px; }
.card-header { font-weight: bold; background-color: #d72323; color: white; }
.current-img-display { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-top: 10px; }
</style>
</head>
<body>

<div class="main-content container">
    <h3 class="mb-4"><i class="fas fa-user-edit me-2"></i>Edit Profile</h3>
    <?php if($message) echo $message; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" id="mobile" class="form-control" value="<?= htmlspecialchars($agent['mobile'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="agent_image" class="form-label">Profile Image</label>
                            <input type="file" name="agent_image" id="agent_image" class="form-control">
                            <?php if(!empty($agent['agent_image'])): ?>
                                <img src="<?= htmlspecialchars($agent['agent_image']) ?>" alt="Current Image" class="current-img-display">
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
