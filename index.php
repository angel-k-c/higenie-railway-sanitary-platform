<?php
session_start();
require 'db.php';

// Products
$stmt = $pdo->query("
    SELECT id, product_name, price, image 
    FROM products 
    WHERE status = 'active' 
    ORDER BY RAND() 
    LIMIT 8
");
$products = $stmt->fetchAll();

//  Top Reviews
try {
    $reviewStmt = $pdo->query("
        SELECT r.rating, r.review_text as review, CONCAT(u.first_name, ' ', u.last_name) as full_name, r.created_at
        FROM ratings r
        JOIN users u ON r.customer_id = u.id
        WHERE r.rating >= 4 AND r.review_text != '' AND r.review_text IS NOT NULL
        ORDER BY r.created_at DESC
        LIMIT 6
    ");
    $reviews = $reviewStmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hi.Genie - Freshness on Track</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-color: #c0392b;
        --text-dark: #2c3e50;
        --text-muted: #6c757d;
        --header-height-estimate: 76px; 
    body {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: #fff;
        color: var(--text-dark);
        overflow-x: hidden;
    }

    h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -0.03em; }
    .text-primary-custom { color: var(--primary-color); }

    .section-padding { padding: 60px 0; }
    .section-title { text-align: center; margin-bottom: 40px; }
    .section-title h2 { font-size: 2.25rem; margin-bottom: 10px; color: var(--text-dark); }
    .section-title p { font-size: 1.05rem; color: var(--text-muted); max-width: 600px; margin: 0 auto; }

    .hero-section {
        position: relative;
        min-height: 100vh;
        margin-top: 0;
        padding-top: 0;
        background: url('/higenie/images/background.jpg') no-repeat center center;
        background-size: cover; 
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background: url('/higenie/images/background.jpg') no-repeat center center;
        background-size: cover;
        transform: scale(1);
        z-index: 0;
    }

    @keyframes zoomEffect {
        0% { transform: scale(1); }
        100% { transform: scale(1.08); }
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.6), rgba(0,0,0,0.5), rgba(0,0,0,0.65));
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    .hero-content {
        text-align: center;
        color: #fff;
        max-width: 700px;
        padding: 20px;
        z-index: 2;
        animation: fadeInUp 1.5s ease;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .hero-content h1 { 
        font-size: 3.5rem; font-weight: 800; margin-bottom: 15px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .hero-content p { 
        font-size: 1.25rem; margin-bottom: 30px; opacity: 0.95; font-weight: 500;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    .hero-btn {
        padding: 12px 35px;
        background: var(--primary-color);
        color: #fff;
        font-weight: 600;
        font-size: 1.05rem;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
    }
    .hero-btn:hover {
        background: #a93226;
        transform: translateY(-2px);
        color: #fff;
        box-shadow: 0 5px 15px rgba(192, 57, 43, 0.3);
    }

    @media (max-width: 768px) {
        .hero-section {
            min-height: 600px; 
            background-attachment: scroll;
            background-position: center;
        }
        .hero-content h1 { font-size: 2.5rem; }
    }

    .product-row-container { position: relative; background: #fff; }
    .product-row { display: flex; overflow-x: auto; gap: 20px; padding: 15px 5px 30px 5px; scroll-behavior: smooth; -ms-overflow-style: none; scrollbar-width: none; scroll-padding-left: 15px; }
    .product-row::-webkit-scrollbar { display: none; }
    .product-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); width: 220px; flex-shrink: 0; text-align: center; transition: all 0.3s ease; overflow: hidden; border: 1px solid #f1f3f5; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); border-color: transparent; }
    .product-card img.product-thumbnail { width: 100%; aspect-ratio: 1/1; object-fit: contain; padding: 20px; background: #fff; transition: transform 0.3s ease; }
    .product-card:hover img.product-thumbnail { transform: scale(1.03); }
    .product-card .card-body { padding: 15px; background: #fff; position: relative; z-index: 2; }
    .product-card h5 { font-size: 1rem; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-dark); }
    .product-card p { color: var(--text-dark); font-weight: 700; font-size: 1.1rem; margin: 0; }

    .product-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.96); display: flex; flex-direction: column; justify-content: center; align-items: center; opacity: 0; visibility: hidden; transition: all 0.2s ease; z-index: 5; padding: 20px; }
    .product-card:hover .product-overlay { opacity: 1; visibility: visible; }
    .overlay-btn { width: 100%; padding: 10px; margin: 5px 0; font-weight: 600; border-radius: 8px; border: none; transition: transform 0.2s ease; font-size: 0.95rem; }
    .overlay-btn:hover { transform: scale(1.02); }
    .btn-add { background: #fff; color: var(--text-dark); border: 1px solid #dee2e6; }
    .btn-buy { background: var(--primary-color); color: #fff; }

    .scroll-btn { position: absolute; top: 50%; transform: translateY(-50%); width: 45px; height: 45px; border-radius: 50%; background: #fff; border: 1px solid #eee; box-shadow: 0 4px 12px rgba(0,0,0,0.08); z-index: 3; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; color: var(--text-dark); }
    .scroll-btn:hover { background: #f8f9fa; border-color: #dee2e6; }
    .scroll-left { left: 15px; }
    .scroll-right { right: 15px; }

    .order-steps ul { list-style: none; padding: 0; margin-top: 30px; }
    .order-steps ul li { display: flex; align-items: flex-start; margin-bottom: 30px; }
    .step-icon-wrapper { flex-shrink: 0; width: 50px; height: 50px; background: rgba(192, 57, 43, 0.08); color: var(--primary-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 1.3rem; }
    .step-content h5 { font-weight: 700; margin-bottom: 5px; font-size: 1.1rem; }
    .step-content p { color: var(--text-muted); margin: 0; line-height: 1.5; font-size: 0.95rem; }

    .reviews-section { background-color: #f8f9fa; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
    .review-card { background: #fff; border-radius: 16px; padding: 35px; box-shadow: 0 8px 30px rgba(0,0,0,0.05); text-align: center; max-width: 700px; margin: 15px auto; border: 1px solid #f0f0f0; }
    .review-user-icon { width: 60px; height: 60px; margin: 0 auto 20px; background: #e9ecef; color: var(--text-muted); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .review-text { font-size: 1.15rem; line-height: 1.6; color: var(--text-dark); margin: 20px 0; font-weight: 500; }
    .review-author { font-weight: 700; color: var(--text-dark); margin-bottom: 3px; }

    @media (max-width: 768px) {
        .scroll-btn { display: none; }
        .section-padding { padding: 50px 0; }
    }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="hero-section">
  <div class="hero-overlay">
    <div class="hero-content">
      <h1>Freshness on Track</h1>
      <p>Your journey deserves comfort. We deliver essential hygiene products directly to your train seat, on time, every time.</p>
      <a href="#products" class="hero-btn">Explore Products</a>
    </div>
  </div>
</section>

<section id="products" class="section-padding product-row-container">
    <div class="container">
        <div class="section-title">
            <h2>Travel Essentials</h2>
            <p>Bestselling hygiene products curated for your journey.</p>
        </div>
        <div style="position: relative;">
            <button class="scroll-btn scroll-left" onclick="scrollRow(-300)"><i class="fas fa-chevron-left"></i></button>
            <div class="product-row" id="productRow">
                <?php foreach($products as $product): ?>
                <div class="product-card">
                     <img src="uploads/<?= basename($product['image']) ?>" class="product-thumbnail" alt="<?= htmlspecialchars($product['product_name']) ?>">
                     <div class="product-overlay">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="overlay-btn btn-add add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                                <i class="fas fa-cart-plus me-2"></i> Add to Cart
                            </button>
                            <button class="overlay-btn btn-buy buy-now-btn" data-product-id="<?= $product['id'] ?>">
                                <i class="fas fa-bolt me-2"></i> Buy Now
                            </button>
                        <?php else: ?>
                            <a href="/higenie/login.php" class="overlay-btn btn-add text-decoration-none">
                                <i class="fas fa-cart-plus me-2"></i> Add to Cart
                            </a>
                            <a href="/higenie/login.php" class="overlay-btn btn-buy text-decoration-none">
                                <i class="fas fa-bolt me-2"></i> Buy Now
                            </a>
                        <?php endif; ?>
                     </div>
                     <div class="card-body">
                         <h5 title="<?= htmlspecialchars($product['product_name']) ?>"><?= htmlspecialchars($product['product_name']) ?></h5>
                         <p>₹<?= number_format($product['price'], 2) ?></p>
                     </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="scroll-btn scroll-right" onclick="scrollRow(300)"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="text-center mt-5">
             <a href="products.php" class="btn btn-outline-dark px-4 py-2 fw-semibold" style="border-radius: 6px;">View All Products</a>
        </div>
    </div>
</section>

<section id="how-to-order" class="section-padding">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <img src="images/service.jpg" class="img-fluid rounded-4 shadow-sm" alt="Hi.Genie Delivery Service">
            </div>
            <div class="col-lg-6 ps-lg-5">
                <div class="text-start mb-4">
                     <h6 class="text-primary-custom text-uppercase fw-bold letter-spacing-1 mb-2" style="font-size: 0.85rem;">Simple Process</h6>
                     <h2 class="fw-bold mb-3">Your Comfort, Just a Few Clicks Away</h2>
                     <p class="text-muted" style="font-size: 1.1rem;">We've simplified on-train delivery so you can relax and enjoy your journey.</p>
                </div>
                <div class="order-steps">
                    <ul>
                        <li>
                            <div class="step-icon-wrapper"><i class="fas fa-hand-pointer"></i></div>
                            <div class="step-content">
                                <h5>Browse & Select</h5>
                                <p>Explore our wide range of trusted hygiene and toiletry essentials.</p>
                            </div>
                        </li>
                        <li>
                            <div class="step-icon-wrapper"><i class="fas fa-ticket-alt"></i></div>
                            <div class="step-content">
                                <h5>Enter Journey Details</h5>
                                <p>Provide your PNR and seat number so our executive knows where to find you.</p>
                            </div>
                        </li>
                        <li>
                            <div class="step-icon-wrapper"><i class="fas fa-shield-alt"></i></div>
                            <div class="step-content">
                                <h5>Secure Payment</h5>
                                <p>Pay safely online using standard gateways like UPI, Cards, or Netbanking.</p>
                            </div>
                        </li>
                        <li>
                            <div class="step-icon-wrapper"><i class="fas fa-box-open"></i></div>
                            <div class="step-content">
                                <h5>Seat Delivery</h5>
                                <p>Relax! Our agent will deliver your sanitized package directly to your berth.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (count($reviews) > 0): ?>
<section id="customer-reviews" class="section-padding reviews-section">
    <div class="container">
        <div class="section-title">
            <h2>Trusted by Travelers</h2>
            <p>See what passengers are saying about their Hi.Genie experience.</p>
        </div>
        <div id="reviewCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-inner">
                <?php foreach($reviews as $index => $review): ?>
                <div class="carousel-item <?= ($index === 0) ? 'active' : '' ?>">
                    <div class="review-card">
                        <div class="review-user-icon"><i class="fas fa-user"></i></div>
                        <div class="star-rating mb-3">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?= ($i <= $review['rating']) ? '#ffc107' : '#e9ecef' ?>; font-size: 0.9rem;"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="review-text">"<?= nl2br(htmlspecialchars($review['review'])) ?>"</p>
                        <div>
                            <h5 class="review-author"><?= htmlspecialchars($review['full_name']) ?></h5>
                            <span class="badge bg-light text-dark border fw-normal px-3 py-2" style="border-radius: 20px;"><i class="fas fa-check-circle text-success me-1"></i> Verified Passenger</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if(count($reviews) > 1): ?>
                <button class="carousel-control-prev w-auto" type="button" data-bs-target="#reviewCarousel" data-bs-slide="prev" style="left: 2%;">
                    <span class="carousel-control-prev-icon p-3 bg-dark rounded-3 opacity-50" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next w-auto" type="button" data-bs-target="#reviewCarousel" data-bs-slide="next" style="right: 2%;">
                    <span class="carousel-control-next-icon p-3 bg-dark rounded-3 opacity-50" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section id="about-us" class="section-padding bg-white">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-5">
                <h6 class="text-primary-custom text-uppercase fw-bold letter-spacing-1 mb-3">Our Story</h6>
                <h2 class="fw-bold mb-4">Redefining Travel Hygiene</h2>
                <p class="lead text-dark mb-4" style="font-weight: 500;">Hi.Genie was born from a simple realization: access to basic hygiene shouldn't stop when you board a train.</p>
                <p class="text-muted">Starting with Kannur Station, we are on a mission to ensure every journey is safe, dignified, and stress-free. Whether it's an unexpected need for sanitary products or just forgetting your toothbrush, we've got your back.</p>
            </div>
            <div class="col-lg-6 offset-lg-1">
                <div class="row g-4">
                    <div class="col-sm-6">
                        <div class="p-4 h-100 bg-light rounded-4 border-0">
                            <i class="fas fa-bullseye fa-2x text-primary-custom mb-3"></i>
                            <h4>Our Mission</h4>
                            <p class="text-muted mb-0">To provide reliable, on-time delivery of essential hygiene products to Indian Railway passengers.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-4 h-100 bg-light rounded-4 border-0">
                            <i class="fas fa-eye fa-2x text-primary-custom mb-3"></i>
                            <h4>Our Vision</h4>
                            <p class="text-muted mb-0">To expand across major railway networks, making safe travel accessible to everyone, everywhere.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function scrollRow(distance) {
    document.getElementById('productRow').scrollBy({ left: distance, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', function() {
    function handleCartAction(productId, quantity, successCallback) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch('/higenie/cart_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) { if(successCallback) successCallback(data); } 
            else { alert('Error: ' + data.message); }
        })
        .catch(error => console.error('Error:', error));
    }

    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const originalHTML = this.innerHTML;
            handleCartAction(this.dataset.productId, 1, () => {
                this.innerHTML = '<i class="fas fa-check"></i> Added';
                setTimeout(() => this.innerHTML = originalHTML, 2000);
            });
        });
    });

    document.querySelectorAll('.buy-now-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            handleCartAction(this.dataset.productId, 1, () => window.location.href = '/higenie/cart.php');
        });
    });
});
</script>
</body>
</html>