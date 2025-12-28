<?php
session_start();
require_once "includes/connection.php";

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<script>alert('Please login to proceed with checkout.'); window.location.href='login.php';</script>";
    exit();
}

// Cart Check
if (empty($_SESSION['cart'])) {
    echo "<script>alert('Your cart is empty.'); window.location.href='menu.php';</script>";
    exit();
}

// Fetch User Data
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM customer_users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User database record not found.");
}

// Calculate Totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += ($item['price'] ?? 0) * ($item['Quantity'] ?? 0);
}
$delivery_fee = 0;
$total = $subtotal + $delivery_fee;

$hide_cart = true; // Hide floating cart UI for focused checkout
require_once "includes/header.php";
?>



<div id="premium-checkout">
    <div class="premium-container">
        <h1 class="main-title">Checkout</h1>
        
        <form action="placeorder.php" method="POST" enctype="multipart/form-data" id="checkoutForm">
            <div class="premium-grid">
                <!-- Left Content -->
                <div class="main-content">
                    <div class="glass-card">
                        <div class="section-header">
                            <i class="fas fa-location-dot"></i>
                            <h2>Delivery Details</h2>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Customer Name</span>
                            <span class="info-value"><?= htmlspecialchars(($user['first_name'] . ' ' . $user['last_name']) ?: 'No Name Set') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value"><?= htmlspecialchars($user['contact'] ?: 'No Contact Set') ?></span>
                        </div>
                        <div class="info-item" style="margin-bottom:0">
                            <span class="info-label">Shipping Address</span>
                            <span class="info-value"><?= htmlspecialchars(($user['street_number'] . ', ' . $user['municipality']) ?: 'No Address Set') ?></span>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="section-header">
                            <i class="fas fa-shield-halved"></i>
                            <h2>Payment Method</h2>
                        </div>
                        <div class="payment-options">
                            <label class="payment-label active" onclick="updatePayment('cod')">
                                <input type="radio" name="selected_payment_method" value="cod" checked>
                                <img src="assets/images/cod.png" class="payment-icon" alt="Cash">
                                <span class="payment-name">Cash on Delivery</span>
                            </label>

                            <label class="payment-label" onclick="updatePayment('gcash')">
                                <input type="radio" name="selected_payment_method" value="gcash">
                                <img src="assets/images/gcash.jpg" class="payment-icon" alt="GCash">
                                <span class="payment-name">Pay via GCash</span>
                            </label>
                            <div id="gcash-details" class="details-box">
                                <p style="font-weight:700">Send ₱<?= number_format($total, 2) ?> to:</p>
                                <h3 style="font-size:1.8rem; margin:10px 0">09476416713</h3>
                                <img src="assets/images/gcash-qr.jpg" class="qr-preview" alt="QR">
                                <div class="premium-upload-area" id="upload-gcash">
                                    <input type="file" name="payment_proof_gcash" accept="image/*" class="hidden-input">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Drag & Drop or Click to Upload Screenshot</p>
                                        <span>Click here to select your GCash payment proof</span>
                                    </div>
                                    <div class="upload-preview">
                                        <img src="" alt="Preview">
                                        <button type="button" class="remove-upload"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>

                            <label class="payment-label" onclick="updatePayment('maya')">
                                <input type="radio" name="selected_payment_method" value="maya">
                                <img src="assets/images/maya.jpg" class="payment-icon" alt="Maya">
                                <span class="payment-name">Pay via Maya</span>
                            </label>
                            <div id="maya-details" class="details-box">
                                <p style="font-weight:700">Send ₱<?= number_format($total, 2) ?> to:</p>
                                <h3 style="font-size:1.8rem; margin:10px 0">09476416713</h3>
                                <img src="assets/images/maya-qr.jpg" class="qr-preview" alt="QR">
                                <div class="premium-upload-area" id="upload-maya">
                                    <input type="file" name="payment_proof_maya" accept="image/*" class="hidden-input">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Drag & Drop or Click to Upload Screenshot</p>
                                        <span>Click here to select your Maya payment proof</span>
                                    </div>
                                    <div class="upload-preview">
                                        <img src="" alt="Preview">
                                        <button type="button" class="remove-upload"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="sticky-sidebar">
                    <div class="glass-card" style="padding: 25px;">
                        <div class="tracker">
                            <div class="tracker-step">
                                <div class="dot" style="background:var(--checkout-primary)"></div>
                                <span class="step-label">Cart</span>
                            </div>
                            <div class="tracker-step active">
                                <div class="dot"></div>
                                <span class="step-label">Checkout</span>
                            </div>
                            <div class="tracker-step">
                                <div class="dot"></div>
                                <span class="step-label">Complete</span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div class="section-header">
                            <i class="fas fa-receipt"></i>
                            <h2>Order Summary</h2>
                        </div>
                        
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="order-item-premium">
                                <img src="<?= $item['image_url'] ?>" class="item-thumbnail" alt="Product">
                                <div class="item-info">
                                    <div class="item-title"><?= htmlspecialchars($item['Item_name']) ?></div>
                                    <div class="item-qty">Qty: <?= $item['Quantity'] ?></div>
                                </div>
                                <div class="item-cost">₱<?= number_format($item['price'] * $item['Quantity'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>

                        <div style="margin-top: 20px;">
                            <div class="price-row">
                                <span>Subtotal</span>
                                <span>₱<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="price-row">
                                <span>Delivery Fee</span>
                                <span style="font-weight:800; color:var(--primary) !important;">FREE</span>
                            </div>
                            <div class="price-total">
                                <span>Total</span>
                                <span>₱<?= number_format($total, 2) ?></span>
                            </div>
                        </div>

                        <button type="submit" name="placeorder" class="place-order-btn">Place Order Now</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function updatePayment(method) {
    // Labels
    document.querySelectorAll('.payment-label').forEach(lb => lb.classList.remove('active'));
    // Details
    document.querySelectorAll('.details-box').forEach(db => db.style.display = 'none');
    
    // Select the clicked one
    const input = document.querySelector(`input[value="${method}"]`);
    if(input) {
        input.checked = true;
        input.closest('.payment-label').classList.add('active');
        
        // Show box
        const box = document.getElementById(`${method}-details`);
        if(box) box.style.display = 'block';
    }
}

// Premium Upload Interactions
document.querySelectorAll('.premium-upload-area').forEach(area => {
    const input = area.querySelector('input[type="file"]');
    const preview = area.querySelector('.upload-preview');
    const previewImg = preview.querySelector('img');
    const placeholder = area.querySelector('.upload-placeholder');
    const removeBtn = area.querySelector('.remove-upload');

    // Click to upload
    area.addEventListener('click', (e) => {
        if (e.target !== removeBtn && !removeBtn.contains(e.target)) {
            input.click();
        }
    });

    // Handle File Selection
    input.addEventListener('change', function() {
        handleFiles(this.files);
    });

    // Drag and Drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        area.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        area.addEventListener(eventName, () => area.classList.add('drag-over'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        area.addEventListener(eventName, () => area.classList.remove('drag-over'), false);
    });

    area.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        input.files = files; // Sync hidden input
        handleFiles(files);
    }, false);

    function handleFiles(files) {
        if (files && files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                preview.classList.add('active');
                placeholder.style.opacity = '0';
            };
            reader.readAsDataURL(files[0]);
        }
    }

    // Remove Upload
    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        input.value = '';
        preview.classList.remove('active');
        placeholder.style.opacity = '1';
        setTimeout(() => {
            previewImg.src = '';
        }, 300);
    });
});

// AJAX Submission
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Payment Proof Validation
    const method = document.querySelector('input[name="selected_payment_method"]:checked').value;
    if (method === 'gcash' || method === 'maya') {
        const fileInput = document.querySelector(`input[name="payment_proof_${method}"]`);
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert(`Please upload a screenshot of your ${method.toUpperCase()} payment transaction.`);
            return; // Stop submission
        }
    }
    
    if (typeof showGlobalLoader === 'function') {
        showGlobalLoader('Processing Payment...', 'Please do not close this window');
    }
    
    const formData = new FormData(this);
    
    fetch('placeorder.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Keep the premium loader visible for a moment longer as requested
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 2500);
        } else {
            if (typeof hideGlobalLoader === 'function') hideGlobalLoader();
            alert(data.message || 'An error occurred.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof hideGlobalLoader === 'function') hideGlobalLoader();
        alert('A network error occurred. Please check your connection.');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
