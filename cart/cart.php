<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /higenie/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

//GET CART ITEMS
$stmt = $pdo->prepare("
    SELECT
        c.id as cart_id,
        p.id as product_id,
        p.product_name,
        p.price,
        p.image,
        c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND c.status = 'cart'
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// GET WISHLIST ITEMS (as SUGGESTIONS)
$stmt = $pdo->prepare("
    SELECT
        c.id as cart_id,
        p.id as product_id,
        p.product_name,
        p.price,
        p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND c.status = 'wishlist'
    ORDER BY c.created_at DESC
    LIMIT 10 
");
$stmt->execute([$user_id]);
$wishlist_suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);


//CALCULATE TOTALS
$subtotal = 0;
$cart_item_count = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $cart_item_count += $item['quantity'];
}
$delivery_charge = ($subtotal > 0) ? 50.00 : 0; 
$grand_total = $subtotal + $delivery_charge;

// Updates cart count
$_SESSION['cart_count'] = $cart_item_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart - Hi.Genie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .cart-table img {
            width: 60px; height: 60px; object-fit: contain; border-radius: 8px; background: #fff;
        }
        .quantity-controls {
            display: inline-flex; align-items: center; gap: 0.75rem;
        }
        .quantity-value {
            font-weight: bold; min-width: 20px; text-align: center;
        }
        .cart-summary {
            background-color: #ffffff; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: sticky; 
            top: 20px;
        }
        .table > :not(caption) > * > * {
            vertical-align: middle;
        }
        
        .suggestions-row {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 1rem;
            padding-bottom: 1.5rem;
        }
        .suggestion-card {
            flex: 0 0 auto; 
            width: 180px; 
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            transition: all 0.2s ease;
        }
        .suggestion-card:hover {
             transform: translateY(-3px);
             box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .suggestion-card img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: contain;
            padding: 0.5rem;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .suggestion-card-body {
            padding: 0.75rem;
            text-align: center;
        }
        .suggestion-title {
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .suggestion-price {
            font-size: 0.95rem;
            font-weight: bold;
            color: #c0392b;
            margin-bottom: 0.5rem;
        }

        @keyframes shake {
          0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 50% { transform: translateX(5px); } 75% { transform: translateX(-5px); }
        }
        .shake { animation: shake 0.5s; }
    </style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h2 class="mb-4 fw-bold"><i class="fas fa-shopping-cart me-2"></i>My Shopping Cart</h2>

            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5 bg-white rounded-3 shadow-sm">
                    <h4 class="mb-3">Your cart is empty.</h4>
                    <a href="/higenie/products.php" class="btn btn-danger">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="table-responsive bg-white rounded-3 shadow-sm p-3">
                    <table class="table cart-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="text-center">Price</th>
                                <th scope="col" class="text-center">Quantity</th>
                                <th scope="col" class="text-center">Subtotal</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr id="cart-row-<?= $item['cart_id'] ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../uploads/<?= htmlspecialchars(basename($item['image'])) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <span class="ms-3 fw-bold"><?= htmlspecialchars($item['product_name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center" data-price="<?= $item['price'] ?>">
                                    ₹<?= number_format($item['price'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <div class="quantity-controls justify-content-center">
                                        <button class="btn btn-sm btn-outline-secondary quantity-btn" data-cart-id="<?= $item['cart_id'] ?>" data-change="-1">-</button>
                                        <span class="quantity-value"><?= $item['quantity'] ?></span>
                                        <button class="btn btn-sm btn-outline-secondary quantity-btn" data-cart-id="<?= $item['cart_id'] ?>" data-change="1">+</button>
                                    </div>
                                </td>
                                <td class="text-center fw-bold">
                                    ₹<span class="subtotal"><?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning wishlist-btn" data-cart-id="<?= $item['cart_id'] ?>" title="Move to Wishlist"><i class="fas fa-heart"></i></button>
                                    <button class="btn btn-sm btn-danger remove-btn" data-cart-id="<?= $item['cart_id'] ?>" title="Remove Item"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($wishlist_suggestions)): ?>
            <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mt-4">
                <h4 class="mb-3 fw-bold">Saved for Later</h4>
                <div class="suggestions-row">
                    <?php foreach ($wishlist_suggestions as $item): ?>
                    <div class="suggestion-card" id="suggestion-card-<?= $item['cart_id'] ?>">
                        <img src="../uploads/<?= htmlspecialchars(basename($item['image'])) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                        <div class="suggestion-card-body">
                            <div class="suggestion-title" title="<?= htmlspecialchars($item['product_name']) ?>"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="suggestion-price">₹<?= number_format($item['price'], 2) ?></div>
                            <button class="btn btn-sm btn-danger w-100 move-to-cart-btn" data-cart-id="<?= $item['cart_id'] ?>">Add to Cart</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="d-grid gap-2 mt-4">
                <a href="/higenie/products.php" class="btn btn-outline-danger btn-lg py-3 fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> Add More Products from Shop
                </a>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="cart-summary">
                <h4 class="mb-4">Order Summary</h4>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span id="summary-subtotal">₹<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Delivery Charges</span>
                    <span id="summary-delivery">₹<?= number_format($delivery_charge, 2) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Grand Total</span>
                    <span id="summary-total">₹<?= number_format($grand_total, 2) ?></span>
                </div>
                <div class="d-grid mt-4">
                    <a href="checkout.php" class="btn btn-success btn-lg" style="background-color: #c0392b;" <?= empty($cart_items) ? 'disabled' : '' ?>> Place Order</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="removeModalLabel">Remove Item?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Do you want to remove this item from your cart? You can also save it for later.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" id="modalSaveForLaterBtn"><i class="fas fa-heart me-1"></i> Save for Later</button>
        <button type="button" class="btn btn-danger" id="modalRemoveBtn"><i class="fas fa-trash me-1"></i> Remove Item</button>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    const modalElement = document.getElementById('removeModal');
    const removeModal = new bootstrap.Modal(modalElement);
    const modalRemoveBtn = document.getElementById('modalRemoveBtn');
    const modalSaveForLaterBtn = document.getElementById('modalSaveForLaterBtn');

    function handleBackendAction(formData, callback) {
        fetch('../cart_handler.php', { 
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cart_count);
                if (callback) callback(data);
            } else {
                console.error('Server Error:', data.message);
            }
        })
        .catch(err => console.error('Fetch error:', err));
    }

    function updateCartBadge(count) {
        const badge = document.getElementById('cart-count');
        if (badge) {
            badge.textContent = count;
            badge.classList.add('shake');
            setTimeout(() => badge.classList.remove('shake'), 500);
        }
    }
    
    function updatePageTotals() {
        let newSubtotal = 0;
        document.querySelectorAll('tr[id^="cart-row-"]').forEach(row => {
            const price = parseFloat(row.querySelector('[data-price]').dataset.price);
            const quantity = parseInt(row.querySelector('.quantity-value').textContent);
            const subtotalEl = row.querySelector('.subtotal');
            const newRowSubtotal = price * quantity;
            subtotalEl.textContent = newRowSubtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            newSubtotal += newRowSubtotal;
        });

        const deliveryCharge = newSubtotal > 0 ? 50.00 : 0;
        const newGrandTotal = newSubtotal + deliveryCharge;
        
        const formatCurrency = (value) => '₹' + value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('summary-subtotal').textContent = formatCurrency(newSubtotal);
        document.getElementById('summary-delivery').textContent = formatCurrency(deliveryCharge);
        document.getElementById('summary-total').textContent = formatCurrency(newGrandTotal);
        
        const checkoutBtn = document.querySelector('.btn-success');
        if (checkoutBtn) {
            checkoutBtn.disabled = (newSubtotal === 0);
        }

        if (newSubtotal === 0 && document.querySelectorAll('tr[id^="cart-row-"]').length === 0) {
             setTimeout(() => location.reload(), 300);
        }
    }

    //Update Quantity
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.dataset.cartId;
            const change = parseInt(this.dataset.change);
            const row = document.getElementById('cart-row-' + cartId);
            const quantityEl = row.querySelector('.quantity-value');
            let newQty = parseInt(quantityEl.textContent) + change;
            if (newQty < 1) return; 
            
            quantityEl.textContent = newQty; 
            
            const formData = new FormData();
            formData.append('action', 'update_quantity');
            formData.append('cart_id', cartId);
            formData.append('quantity', newQty);
            handleBackendAction(formData, updatePageTotals);
        });
    });

    // Open Remove 
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.dataset.cartId;
            modalRemoveBtn.dataset.cartId = cartId;
            modalSaveForLaterBtn.dataset.cartId = cartId;
            removeModal.show();
        });
    });

    // Confirm Remove 
    modalRemoveBtn.addEventListener('click', function() {
        const cartId = this.dataset.cartId;
        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('cart_id', cartId);
        
        handleBackendAction(formData, () => {
            removeModal.hide();
            document.getElementById('cart-row-' + cartId).remove();
            updatePageTotals();
        });
    });

    //Save for Later 
    modalSaveForLaterBtn.addEventListener('click', function() {
        const cartId = this.dataset.cartId;
        const formData = new FormData();
        formData.append('action', 'move_to_wishlist');
        formData.append('cart_id', cartId);
        
        handleBackendAction(formData, () => {
            removeModal.hide();
            location.reload();
        });
    });

    // Save for Later heart btn
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.dataset.cartId;
            const formData = new FormData();
            formData.append('action', 'move_to_wishlist');
            formData.append('cart_id', cartId);
            
            handleBackendAction(formData, () => {
                location.reload();
            });
        });
    });
    
    //  Move from Suggestions to Cart
    document.querySelectorAll('.move-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.dataset.cartId;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('action', 'move_to_cart');
            formData.append('cart_id', cartId);
            
            handleBackendAction(formData, () => {
                location.reload();
            });
        });
    });

});
</script>

</body>
</html>