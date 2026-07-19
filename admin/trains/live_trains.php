<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../../db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save_train'])) {
    try {
        $train_id = !empty($_POST['train_id']) ? (int)$_POST['train_id'] : null;

        if ($train_id) {
            $sql = "UPDATE trains SET 
                        train_number = :tn, train_name = :tname, 
                        source_name = :src, source_code = :srcc, 
                        destination_name = :dest, destination_code = :destc, 
                        scheduled_arrival = :arr, scheduled_departure = :dep, 
                        platform = :plat 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tn' => $_POST['train_number'], ':tname' => $_POST['train_name'],
                ':src' => $_POST['source_name'], ':srcc' => strtoupper($_POST['source_code']),
                ':dest' => $_POST['destination_name'], ':destc' => strtoupper($_POST['destination_code']),
                ':arr' => $_POST['scheduled_arrival'], ':dep' => $_POST['scheduled_departure'],
                ':plat' => $_POST['platform'], ':id' => $train_id
            ]);
            $msg_text = "Train updated successfully.";
        } else {
            // --- INSERTING NEW TRAIN ---
            $sql = "INSERT INTO trains (train_number, train_name, source_name, source_code, destination_name, destination_code, scheduled_arrival, scheduled_departure, platform, avg_delay_minutes) 
                    VALUES (:tn, :tname, :src, :srcc, :dest, :destc, :arr, :dep, :plat, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tn' => $_POST['train_number'], ':tname' => $_POST['train_name'],
                ':src' => $_POST['source_name'], ':srcc' => strtoupper($_POST['source_code']),
                ':dest' => $_POST['destination_name'], ':destc' => strtoupper($_POST['destination_code']),
                ':arr' => $_POST['scheduled_arrival'], ':dep' => $_POST['scheduled_departure'],
                ':plat' => $_POST['platform']
            ]);
            $msg_text = "Train added successfully.";
        }

        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='bi bi-check-circle-fill me-2'></i>$msg_text
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";

    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

//FETCH DATA FROM 'trains' TABLE ---
$trains = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM trains ORDER BY scheduled_arrival ASC");
    $stmt->execute();
    $trains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($trains) && empty($message) && !isset($_SESSION['api_msg'])) {
        $message = "<div class='alert alert-info'>No trains found. Use Auto-Fetch or add one manually below.</div>";
    }
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Train Schedule - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { padding: 30px 15px; }
        .card { border-radius: 0.75rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
        .card-header { background-color: #ffffff; font-weight: 500; font-size: 1.2rem; }
    </style>
</head>
<body>
<?php include '../../header.php'; ?>

<div class="main-content container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-train-front me-2"></i>Train Schedule</span>
            <div>
                <a href="fetch_api.php" class="btn btn-success btn-sm me-2">
                    <i class="bi bi-cloud-download-fill me-1"></i> Auto-Fetch Trains
                </a>
                <button type="button" class="btn btn-primary btn-sm btn-add-train" data-bs-toggle="modal" data-bs-target="#trainModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Manual Train
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['api_msg'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['api_msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['api_msg']); ?>
            <?php endif; ?>

            <?php if (!empty($message)) echo $message; ?>
            <?php if (!empty($error)) echo "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle-fill me-2'></i>" . htmlspecialchars($error) . "</div>"; ?>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Train No.</th>
                            <th>Name</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Platform</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trains) && empty($error)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">No train data available.</td></tr>
                        <?php else: foreach ($trains as $train): ?>
                            <?php
                                $delay = $train['avg_delay_minutes'] ?? 0;
                                $statusClass = ($delay <= 0) ? 'text-success' : 'text-danger';
                                $statusText = ($delay <= 0) ? 'On Time' : "Delay " . htmlspecialchars($delay) . " min";
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($train['train_number']) ?></strong></td>
                                <td><?= htmlspecialchars($train['train_name']) ?></td>
                                <td><?= htmlspecialchars($train['source_name']) ?> <small class="text-muted">(<?= htmlspecialchars($train['source_code']) ?>)</small></td>
                                <td><?= htmlspecialchars($train['destination_name']) ?> <small class="text-muted">(<?= htmlspecialchars($train['destination_code']) ?>)</small></td>
                                <td><?= date('h:i A', strtotime($train['scheduled_arrival'])) ?></td>
                                <td><?= date('h:i A', strtotime($train['scheduled_departure'])) ?></td>
                                <td><span class="badge bg-dark"><?= htmlspecialchars($train['platform'] ?? '-') ?></span></td>
                                <td><strong class="<?= $statusClass ?>"><?= $statusText ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary btn-edit-train" 
                                            data-bs-toggle="modal" data-bs-target="#trainModal"
                                            data-id="<?= $train['id'] ?>"
                                            data-tn="<?= htmlspecialchars($train['train_number']) ?>"
                                            data-tname="<?= htmlspecialchars($train['train_name']) ?>"
                                            data-src="<?= htmlspecialchars($train['source_name']) ?>"
                                            data-srcc="<?= htmlspecialchars($train['source_code']) ?>"
                                            data-dest="<?= htmlspecialchars($train['destination_name']) ?>"
                                            data-destc="<?= htmlspecialchars($train['destination_code']) ?>"
                                            data-arr="<?= $train['scheduled_arrival'] ?>"
                                            data-dep="<?= $train['scheduled_departure'] ?>"
                                            data-plat="<?= htmlspecialchars($train['platform']) ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="trainModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" id="trainForm">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Train</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="train_id" id="train_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Train Number <span class="text-danger">*</span></label>
                        <input type="text" name="train_number" id="train_number" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Train Name <span class="text-danger">*</span></label>
                        <input type="text" name="train_name" id="train_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source Name</label>
                        <input type="text" name="source_name" id="source_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source Code</label>
                        <input type="text" name="source_code" id="source_code" class="form-control" maxlength="10">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Destination Name</label>
                        <input type="text" name="destination_name" id="destination_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Destination Code</label>
                        <input type="text" name="destination_code" id="destination_code" class="form-control" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Scheduled Arrival <span class="text-danger">*</span></label>
                        <input type="time" name="scheduled_arrival" id="scheduled_arrival" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Scheduled Departure <span class="text-danger">*</span></label>
                        <input type="time" name="scheduled_departure" id="scheduled_departure" class="form-control" required>
                    </div>
                     <div class="col-md-4">
                        <label class="form-label">Platform</label>
                        <input type="text" name="platform" id="platform" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="btn_save_train" class="btn btn-primary" id="modalSaveBtn">Save Train</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trainModal = document.getElementById('trainModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSaveBtn = document.getElementById('modalSaveBtn');
    const trainForm = document.getElementById('trainForm');

    // Reset form when modal is opened for "Add"
    document.querySelector('.btn-add-train').addEventListener('click', function() {
        trainForm.reset();
        document.getElementById('train_id').value = '';
        modalTitle.textContent = 'Add Train Manually';
        modalSaveBtn.textContent = 'Save Train';
    });

    // Fill form when modal is opened for "Edit"
    document.querySelectorAll('.btn-edit-train').forEach(button => {
        button.addEventListener('click', function() {
            modalTitle.textContent = 'Edit Train Details';
            modalSaveBtn.textContent = 'Update Train';
            
            document.getElementById('train_id').value = this.dataset.id;
            document.getElementById('train_number').value = this.dataset.tn;
            document.getElementById('train_name').value = this.dataset.tname;
            document.getElementById('source_name').value = this.dataset.src;
            document.getElementById('source_code').value = this.dataset.srcc;
            document.getElementById('destination_name').value = this.dataset.dest;
            document.getElementById('destination_code').value = this.dataset.destc;
            document.getElementById('scheduled_arrival').value = this.dataset.arr;
            document.getElementById('scheduled_departure').value = this.dataset.dep;
            document.getElementById('platform').value = this.dataset.plat;
        });
    });
});
</script>
</body>
</html>