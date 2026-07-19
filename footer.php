<footer class="footer-container">
    <?php 
    if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}if ($current_page === 'index.php' || $current_page === 'customer/products.php'|| $current_page === 'customer/customer_registration.php'|| $current_page === 'reset_password.php'): ?>
        <div class="footer-content container d-flex flex-wrap justify-content-between">
            
            <div class="footer-section contact-info">
                <h6>Contact Us</h6>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Hi.Genie @ Kannur Railway Station, Kerala</li>
                    <li><i class="fas fa-phone"></i> +91 987 654 3210</li>
                    <li><i class="fas fa-envelope"></i> support@higenie.com</li>
                </ul>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="footer-section quick-links">
                <h6>Quick Links</h6>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="refund_policies.php">Refund policies</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom text-center py-2">
            &copy; <?= date('Y') ?> Hi.Genie - Freshness on Track
        </div>

    <?php else: ?>
        <div class="simple-footer text-center py-3">
            &copy; <?= date('Y') ?> Hi.Genie - Freshness on Track
        </div>
    <?php endif; ?>
</footer>

<style>
footer.footer-container {
    background-color: #ffffff;
    color: #000000;
    font-family: 'Segoe UI', sans-serif;
    font-size: 0.9rem;
    width: 100%;
    margin-top: auto;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    padding: 25px 0;
}

.footer-section {
    flex: 1 1 250px;
    min-width: 230px;
}

.footer-section h6 {
    font-weight: 600;
    color: #c0392b;
    margin-bottom: 12px;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    font-size: 0.9rem;
    color: #333;
    margin-bottom: 6px;
}

.footer-section ul li i {
    width: 20px;
    color: #c0392b;
    margin-right: 8px;
}

.quick-links ul li a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s;
}

.quick-links ul li a:hover {
    color: #c0392b;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #f1f1f1;
    color: #555;
    font-size: 0.9rem;
    margin-right: 6px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-links a:hover {
    background-color: #c0392b;
    color: #fff;
    transform: translateY(-2px);
}

.footer-bottom {
    border-top: 1px solid #e0e0e0;
    padding-top: 10px;
    text-align: center;
    color: #444;
}

.simple-footer {
    background-color: #ffffff;
    color: #000;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    font-family: 'Segoe UI', sans-serif;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }
    .footer-section.contact-info {
        text-align: left;
        margin-bottom: 15px;
    }
}
</style>
