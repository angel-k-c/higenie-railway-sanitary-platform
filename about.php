<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Hi.Genie</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff;
            color: #000;
            margin: 0;
        }

        h2, h3, h4 {
            color: #c0392b;
        }

        .breadcrumbs {
            background: #f9f9f9;
            padding: 40px 0;
            text-align: center;
        }

        .breadcrumbs h2 {
            margin-bottom: 10px;
        }

        .about .content ul {
            list-style: none;
            padding: 0;
        }

        .about .content ul li {
            margin-bottom: 10px;
            font-weight: 500;
        }

        .about .content ul li i {
            color: #c0392b;
            margin-right: 8px;
        }

        .why-us .box {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: 0.3s;
        }

        .why-us .box:hover {
            transform: translateY(-5px);
        }

        .why-us .box span {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .bg-light {
            background-color: #f9f9f9 !important;
        }

        .img-fluid {
            width: 100%;
            border-radius: 12px;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main id="main">

    <!-- ======= Breadcrumbs ======= -->
    <section class="breadcrumbs">
        <div class="container">
            <h2>About Hi.Genie</h2>
            <p>Freshness on Track – Bringing comfort and hygiene to your journey</p>
        </div>
    </section>

    <!-- ======= About Section ======= -->
    <section id="about" class="about py-5">
        <div class="container">
            <div class="row gy-4 align-items-center">
                <div class="col-lg-6">
                    <img src="images/service.png" alt="Hi.Genie Service" class="img-fluid shadow">
                </div>
                <div class="col-lg-6 content">
                    <h3>Your travel partner for hygiene & essentials</h3>
                    <p class="fst-italic">
                        Hi.Genie ensures that your train journey is safe, hygienic, and stress-free. 
                        From sanitary products to daily toiletries, we deliver your essentials directly to your seat on time.
                    </p>
                    <ul>
                        <li><i class="fa-solid fa-check-circle"></i> On-time delivery at your train seat.</li>
                        <li><i class="fa-solid fa-check-circle"></i> Wide range of sanitary and toiletry products.</li>
                        <li><i class="fa-solid fa-check-circle"></i> Secure online payments and instant order tracking.</li>
                        <li><i class="fa-solid fa-check-circle"></i> Dedicated support for customers and delivery agents.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="mission" class="bg-light py-5">
        <div class="container">
            <div class="section-title">
                <h2>Our Mission & Vision</h2>
            </div>
            <div class="row gy-4">
                <div class="col-md-6">
                    <div class="p-4 bg-white shadow rounded-3 h-100">
                        <h4>Mission</h4>
                        <p>To provide reliable, hygienic, and on-time delivery of sanitary and toiletry essentials to passengers traveling through Indian Railways, starting with Kannur Station.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-4 bg-white shadow rounded-3 h-100">
                        <h4>Vision</h4>
                        <p>To redefine train travel experiences by ensuring health, hygiene, and comfort for every passenger, and expand Hi.Genie services to multiple railway stations across India.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="why-us" class="why-us py-5">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Hi.Genie?</h2>
            </div>
            <div class="row gy-4">
                <div class="col-lg-4">
                    <div class="box shadow h-100">
                        <span>01</span>
                        <h4>Smart Delivery</h4>
                        <p>Enter your PNR and seat number – we’ll deliver directly to your seat.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="box shadow h-100">
                        <span>02</span>
                        <h4>Secure Payments</h4>
                        <p>Pay safely online with Razorpay and get instant order confirmation.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="box shadow h-100">
                        <span>03</span>
                        <h4>Trusted Quality</h4>
                        <p>Only branded, safe, and hygienic products delivered by trained agents.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
