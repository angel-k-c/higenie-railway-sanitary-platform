<?php
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    session_start();

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    header("Location: /higenie/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Logout</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .modal-confirm .modal-content {
            border-radius: 12px;
            padding: 20px;
        }
        .modal-confirm .modal-header {
            border-bottom: none;
        }
        .modal-confirm .modal-footer {
            border-top: none;
        }
        .btn-danger {
            background-color: #c0392b;
            border-color: #c0392b;
        }
    </style>
</head>

<body>

<div class="modal fade show" id="logoutModal" tabindex="-1" aria-modal="true" role="dialog" style="display:block; background:rgba(0,0,0,0.6);">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-confirm">
      
      <div class="modal-header">
        <h5 class="modal-title">Confirm Logout</h5>
      </div>

      <div class="modal-body">
        <p>Do you want to logout?</p>
      </div>

      <div class="modal-footer">
        <a href="logout.php?confirm=yes" class="btn btn-danger">Logout</a>
        <button onclick="window.history.back()" class="btn btn-secondary">Cancel</button>
      </div>

    </div>
  </div>
</div>

</body>
</html>
