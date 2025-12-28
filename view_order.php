<?php
// Security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
session_start();

// Helper functions
function handleError($message) {
    $_SESSION['error'] = $message;
    header("Location: yourorder.php");
    exit;
}

function getStatusDisplay($status) {
    $statusMap = [
        'Pending' => ['display' => 'Pending', 'class' => 'pending'],
        'InProgress' => ['display' => 'In Progress', 'class' => 'inprogress'],
        'Preparing' => ['display' => 'Preparing', 'class' => 'preparing'],
        'Completed' => ['display' => 'Completed', 'class' => 'completed'],
        'Cancelled' => ['display' => 'Cancelled', 'class' => 'cancelled']  // Add this line
    ];
    return $statusMap[$status] ?? ['display' => $status, 'class' => strtolower($status)];
}

// Include required files
include_once "includes/header.php";
require_once "includes/connection.php";

// Set PDO error mode
if ($conn instanceof PDO) {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// Validate order ID
$order_id = isset($_GET['id']) ? trim($_GET['id']) : '';
if (empty($order_id) || !preg_match('/^[A-Za-z0-9-]+$/', $order_id)) {
    handleError("Invalid order ID");
}

$userEmail = $_SESSION['email'] ?? '';
if (empty($userEmail)) {
    header("Location: login.php");
    exit;
}

// Fetch order details
try {
    // Check if order_id is numeric (integer) or string
    $is_numeric = is_numeric($order_id);
    $param_type = $is_numeric ? "is" : "ss";
    
    $stmt = $conn->prepare("
        SELECT 
            o.*, p.image_url 
        FROM orders o 
        LEFT JOIN products p ON o.item = p.name 
        WHERE o.order_id = ? AND o.email = ?
    ");
    
    if ($is_numeric) {
        $order_id_int = intval($order_id);
        $stmt->bind_param("is", $order_id_int, $userEmail);
    } else {
        $stmt->bind_param("ss", $order_id, $userEmail);
    }
    $stmt->execute();
    
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        handleError("Order not found");
    }

    // Check for existing return/refund request (if table exists)
    $return_request = null;
    try {
        // Check if returns_refunds table exists
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'returns_refunds'");
        if ($table_check && mysqli_num_rows($table_check) > 0) {
            $order_id_for_return = $is_numeric ? intval($order_id) : $order_id;
            $return_stmt = $conn->prepare("SELECT * FROM returns_refunds WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
            if ($return_stmt) {
                if ($is_numeric) {
                    $return_stmt->bind_param("i", $order_id_for_return);
                } else {
                    $return_stmt->bind_param("s", $order_id_for_return);
                }
                $return_stmt->execute();
                $return_result = $return_stmt->get_result();
                if ($return_result) {
                    $return_request = $return_result->fetch_assoc();
                }
                $return_stmt->close();
            }
        }
    } catch (Exception $e) {
        // Table doesn't exist or error - just continue without return request
        $return_request = null;
        error_log("Return/refund check error: " . $e->getMessage());
    } catch (Error $e) {
        // Handle fatal errors
        $return_request = null;
        error_log("Return/refund check fatal error: " . $e->getMessage());
    }
} catch (Exception $e) {
    handleError("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order details for order #<?= htmlspecialchars($order_id) ?>">
    <title>Order Details - CavShop</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container">
    <div class="order-details-header">
        <nav class="breadcrumb-nav" aria-label="breadcrumb">
            <ol class="breadcrumbs">
                <li class="breadcrumb-item">
                    <a href="yourorder.php">
                        <i class="bi bi-box-seam-fill"></i>
                        <span>My Orders</span>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span>View Orders</span>
                </li>
            </ol>
        </nav>
        <h1>Order Details</h1>
    </div>

    <div class="order-card" role="article" aria-label="Order details">
        <div class="order-header">
            <div class="order-id">
                <h2>Order #<?= htmlspecialchars($order['order_id']) ?></h2>
                <span class="status-chip status-<?= getStatusDisplay($order['status'])['class'] ?>">
                    <?= htmlspecialchars(getStatusDisplay($order['status'])['display']) ?>
                </span>
            </div>
            <?php if ($order['status'] === 'Completed' && isset($order['completed_at'])): ?>
            <div class="completed-date">
                <i class="bi bi-check-circle-fill"></i>
                Completed on <?= date('F j, Y \a\t h:i A', strtotime($order['completed_at'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="order-content">
            <div class="product-details">
                <img src="<?= htmlspecialchars($order['image_url']) ?>" alt="Product Image" class="product-image">
                <div class="product-info">
                    <h3><?= htmlspecialchars($order['item']) ?></h3>
                    <p class="quantity">Quantity: <?= htmlspecialchars($order['quantity']) ?></p>
                    <p class="price">Price: ₱<?= htmlspecialchars($order['total_price']) ?></p>
                </div>
            </div>


            <div class="info-section">
                <h3>Customer Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <i class="bi bi-person-fill"></i>
                        <div>
                            <label>Full Name</label>
                            <p><?= htmlspecialchars($order['name']) ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-envelope-fill"></i>
                        <div>
                            <label>Email Address</label>
                            <p><?= htmlspecialchars($order['email']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="delivery-info">
                <h3>Order Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <i class="bi bi-geo-alt-fill"></i>
                        <div>
                            <label>Customer Address</label>
                            <p><?= htmlspecialchars($order['address']) ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-telephone-fill"></i>
                        <div>
                            <label>Contact Number</label>
                            <p><?= htmlspecialchars($order['contact']) ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-wallet-fill"></i>
                        <div>
                            <label>Payment Method</label>
                            <p><?= htmlspecialchars($order['payment_method']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($order['status'] === 'Completed' && isset($order['completed_at'])): ?>
            <div class="additional-completed-date">
                <h3>Order Completion</h3>
                <p>Completed on: <?= date('F j, Y \a\t h:i A', strtotime($order['completed_at'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Return/Refund Section -->
            <?php if ($order['status'] === 'Completed'): ?>
            <div class="return-refund-section">
                <h3>Return / Refund</h3>
                <?php if (!empty($return_request) && is_array($return_request)): ?>
                    <div class="return-status-card">
                        <div class="return-status-header">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Request Status: <strong><?= ucfirst($return_request['status']) ?></strong></span>
                        </div>
                        <div class="return-details">
                            <p><strong>Request Type:</strong> <?= htmlspecialchars($return_request['request_type']) ?></p>
                            <p><strong>Reason:</strong> <?= htmlspecialchars($return_request['reason']) ?></p>
                            <p><strong>Submitted:</strong> <?= date('F j, Y \a\t h:i A', strtotime($return_request['created_at'])) ?></p>
                            <?php if ($return_request['admin_notes']): ?>
                                <p><strong>Admin Notes:</strong> <?= htmlspecialchars($return_request['admin_notes']) ?></p>
                            <?php endif; ?>
                            <?php if ($return_request['status'] === 'Approved' && $return_request['refund_amount']): ?>
                                <p class="refund-amount"><strong>Refund Amount:</strong> ₱<?= number_format($return_request['refund_amount'], 2) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="return-actions">
                        <button type="button" class="btn-return" onclick="openReturnModal()">
                            <i class="bi bi-arrow-counterclockwise"></i> Request Return / Refund
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Return/Refund Request Modal -->
<div id="returnModal" class="modal-return">
    <div class="modal-content-return">
        <div class="modal-header-return">
            <h3>Request Return / Refund</h3>
            <span class="close-return" onclick="closeReturnModal()">&times;</span>
        </div>
        <form action="request_return.php" method="POST" id="returnForm">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
            <div class="modal-body-return">
                <div class="form-group-return">
                    <label for="request_type">Request Type <span class="required">*</span></label>
                    <select name="request_type" id="request_type" required>
                        <option value="Return">Return Item</option>
                        <option value="Refund">Refund Only</option>
                        <option value="Both">Return & Refund</option>
                    </select>
                </div>
                <div class="form-group-return">
                    <label for="reason">Reason for Return/Refund <span class="required">*</span></label>
                    <textarea name="reason" id="reason" rows="5" placeholder="Please provide a detailed reason for your return/refund request..." required></textarea>
                </div>
                <div class="order-summary-return">
                    <p><strong>Order #<?= htmlspecialchars($order['order_id']) ?></strong></p>
                    <p>Item: <?= htmlspecialchars($order['item']) ?></p>
                    <p>Total Amount: ₱<?= number_format($order['total_price'], 2) ?></p>
                </div>
            </div>
            <div class="modal-footer-return">
                <button type="button" class="btn-cancel-return" onclick="closeReturnModal()">Cancel</button>
                <button type="submit" class="btn-submit-return">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Add before other styles */


.breadcrumbs {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    color: #666;
    font-size: 14px; /* Make text slightly smaller */
    margin-top: 2rem;
}

.breadcrumb-item a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #666;
    transition: color 0.2s ease;
    gap: 0.4rem;
}

.breadcrumb-item a:hover {
    color: #286816;
}

.breadcrumb-item.active {
    color: #286816; /* Brand color for active page */
    font-weight: 500;
}

.breadcrumb-item.active i {
    color: #286816; /* Match icon color with text */
}

.breadcrumb-item:not(:last-child)::after {
    content: '>';
    margin-left: 0.5rem;
    margin-right: 0.5rem;
    color: #ccc;
    font-weight: 300;
}

/* Remove the old back button styles */
.back-button {
    display: none;
}

.order-details-header {
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-details-header h1 {
    margin-top: 0;
}

/* ...rest of existing styles... */

.container {
    max-width: 1200px; /* Increased from 800px */
    margin: 50px auto; /* Reduced top/bottom margin */
    padding: 0 30px; /* Increased horizontal padding */
}

.order-details-header {
    margin-bottom: 30px;
}

h1 {
    color: #286816;
    font-size: 28px;
    margin: 0;
}

.order-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    background: linear-gradient(to bottom, #ffffff, #fafafa);
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: box-shadow 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.order-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-id {
    display: flex;
    align-items: center;
    gap: 15px;
}

.order-id h2 {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.completed-date {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #1b5e20;
}

.completed-date i {
    color: #2e7d32;
    font-size: 16px;
}

@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .completed-date {
        width: 100%;
    }
}

.order-content {
    padding: 30px; /* Increased padding */
}

.product-details {
    display: flex;
    gap: 30px; /* Increased gap */
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.completed-date {
    color: #1b5e20;
}

.completed-date i {
    color: #2e7d32;
}

.order-content {
    padding: 30px; /* Increased padding */
}

.product-details {
    display: flex;
    gap: 30px; /* Increased gap */
    margin-bottom: 20px;
}

.product-image {
    width: 150px; /* Increased from 120px */
    height: 150px; /* Increased from 120px */
    object-fit: cover;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.05);
}

.product-info {
    flex: 1;
}

.product-info h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 18px;
}

.quantity, .price {
    margin: 5px 0;
    color: #666;
}

@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .order-dates {
        width: 100%;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .order-content {
        padding: 20px;
    }
    
    .status-chip {
        width: fit-content;
    }
}

.info-section,
.delivery-info {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-section h3,
.delivery-info h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #286816;
    font-size: 1.2rem;
    position: relative;
    padding-bottom: 12px;
    margin-bottom: 20px;
}

.info-section h3::after,
.delivery-info h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: #286816;
    border-radius: 2px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Increased from 250px */
    gap: 30px; /* Increased gap */
    margin-top: 15px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease;
}

.info-item:hover {
    transform: translateY(-2px);
}

.info-item i {
    color: #286816;
    font-size: 1.2rem;
    margin-top: 3px;
}

.info-item label {
    display: block;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 4px;
}

.info-item p {
    margin: 0;
    color: #333;
    font-weight: 500;
}

/* Keep existing status chip styles from yourorder.php */
.status-chip {
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 12px;
}

.status-chip::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

.status-pending { background: #fff3e0; color: #e65100; }
.status-completed { background: #e8f5e9; color: #1b5e20; }
.status-inprogress { background: #e3f2fd; color: #0d47a1; }
.status-preparing { background: #f3e5f5; color: #4a148c; }
.status-cancelled { background: #ffebee; color: #b71c1c; }  /* Add this line */

@media (max-width: 768px) {
    .product-details {
        flex-direction: column;
    }
    
    .product-image {
        width: 100%;
        height: 250px; /* Increased from 200px */
    }
}

/* Add loading state styles */
.loading {
    opacity: 0.5;
    pointer-events: none;
}


/* Improve loading state visibility */
.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    z-index: 1;
}

/* Add loading spinner */
.loading-spinner {
    display: none;
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #286816;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
    background: linear-gradient(to right, #286816, #4CAF50);
    -webkit-mask: linear-gradient(transparent 0%, black 50%, transparent 100%);
    mask: linear-gradient(transparent 0%, black 50%, transparent 100%);
}

.loading .loading-spinner {
    display: inline-block;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Additional Animations */
@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

/* Print Styles */
@media print {
    .back-button,
    .order-actions {
        display: none;
    }
    
    .order-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .info-item {
        break-inside: avoid;
    }
}

/* Accessibility Improvements */
.order-card:focus-within {
    outline: 2px solid #286816;
    outline-offset: 4px;
}

.back-button:focus,
.cancel-btn:focus {
    outline: 2px solid #286816;
    outline-offset: 2px;
}

/* Visual Feedback */
.product-image {
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.05);
}

.additional-completed-date {
    margin-top: 20px;
    padding: 15px;
    background: #e8f5e9;
    border-radius: 8px;
    border: 1px solid #c8e6c9;
}

.additional-completed-date h3 {
    margin-bottom: 10px;
    color: #1b5e20;
    font-size: 1.1rem;
}

.additional-completed-date p {
    margin: 0;
    color: #333;
    font-weight: 500;
}

/* Return/Refund Section Styles */
.return-refund-section {
    margin-top: 30px;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.return-refund-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #286816;
    font-size: 1.2rem;
    position: relative;
    padding-bottom: 12px;
}

.return-refund-section h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: #286816;
    border-radius: 2px;
}

.return-status-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #286816;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.return-status-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: #286816;
}

.return-status-header i {
    font-size: 1.3rem;
}

.return-details p {
    margin: 10px 0;
    color: #555;
    line-height: 1.6;
}

.refund-amount {
    color: #28a745 !important;
    font-size: 1.1rem;
    font-weight: 600;
}

.btn-return {
    background: linear-gradient(135deg, #286816 0%, #4a7c59 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(40, 104, 22, 0.2);
}

.btn-return:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(40, 104, 22, 0.3);
}

.btn-return i {
    font-size: 1.1rem;
}

/* Return Modal Styles */
.modal-return {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-content-return {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: none;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header-return {
    padding: 20px 25px;
    background: linear-gradient(135deg, #286816 0%, #4a7c59 100%);
    color: white;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header-return h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.close-return {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s;
}

.close-return:hover {
    transform: scale(1.2);
}

.modal-body-return {
    padding: 25px;
}

.form-group-return {
    margin-bottom: 20px;
}

.form-group-return label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 0.95rem;
}

.required {
    color: #dc3545;
}

.form-group-return select,
.form-group-return textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-group-return select:focus,
.form-group-return textarea:focus {
    outline: none;
    border-color: #286816;
    box-shadow: 0 0 0 3px rgba(40, 104, 22, 0.1);
}

.form-group-return textarea {
    resize: vertical;
    min-height: 120px;
}

.order-summary-return {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
    border-left: 3px solid #286816;
}

.order-summary-return p {
    margin: 8px 0;
    color: #555;
}

.modal-footer-return {
    padding: 20px 25px;
    background: #f8f9fa;
    border-radius: 0 0 16px 16px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-cancel-return,
.btn-submit-return {
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-cancel-return {
    background: #6c757d;
    color: white;
}

.btn-cancel-return:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-submit-return {
    background: linear-gradient(135deg, #286816 0%, #4a7c59 100%);
    color: white;
    box-shadow: 0 4px 6px rgba(40, 104, 22, 0.2);
}

.btn-submit-return:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(40, 104, 22, 0.3);
}

@media (max-width: 768px) {
    .modal-content-return {
        width: 95%;
        margin: 10% auto;
    }

    .modal-header-return,
    .modal-body-return,
    .modal-footer-return {
        padding: 15px;
    }
}

</style>

<script>
function openReturnModal() {
    document.getElementById('returnModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeReturnModal() {
    document.getElementById('returnModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('returnModal');
    if (event.target === modal) {
        closeReturnModal();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReturnModal();
    }
});

// Form validation
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('reason').value.trim();
    if (reason.length < 10) {
        e.preventDefault();
        alert('Please provide a detailed reason (at least 10 characters).');
        return false;
    }
});
</script>

<script>
// Improve image error handling
document.querySelector('.product-image')?.addEventListener('error', function(e) {
    e.target.src = 'images/default-product.jpg';
    e.target.alt = 'Product image not available';
});

</script>

</main>
</body>
</html>