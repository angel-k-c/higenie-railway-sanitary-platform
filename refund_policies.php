<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Refund Policy - Hi.Genie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #fff0f0, #ffe4e4, #ffc1c1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .policy-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="policy-card">
        <h2 class="fw-bold mb-4"><i class="fas fa-undo-alt me-2"></i>Refund & Cancellation Policy</h2>
        
        <p class="lead">At Hi.Genie, we strive for customer satisfaction. Please read our refund and cancellation policies carefully.</p>

        <hr class="my-4">

        <h4 class="text-danger">Cancellation Policy</h4>
        <p>You can request to cancel your order before it has been dispatched from our warehouse.</p>
        <ul>
            <li>If you cancel your order after it has been placed, a refund of <strong>50% of the product value</strong> will be processed.</li>
            <li>The delivery charge is non-refundable if the cancellation occurs after the order is out for delivery.</li>
        </ul>

        <h4 class="text-danger mt-4">Refund Policy</h4>
        <p>We offer a full refund under specific conditions to ensure a fair process for all our customers.</p>
        <ul>
            <li>A <strong>full refund (excluding delivery charges)</strong> will be provided in the case of non-delivery or if the delivery is significantly delayed beyond the estimated delivery time without prior notice.</li>
            <li>To be eligible for a refund, you must report the issue to our customer service team within 48 hours of the expected delivery time.</li>
            <li>Refunds will be processed to the original method of payment within 5-7 business days.</li>
        </ul>
        
        <div class="alert alert-secondary mt-4" role="alert">
            <strong>Note:</strong> Hi.Genie reserves the right to amend this policy at any time. Please check this page for the latest updates.
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>