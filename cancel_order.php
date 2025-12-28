<?php
session_start();
require_once "includes/connection.php";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit;
}

// Validate CSRF token
if (!isset($_GET['csrf']) || !isset($_SESSION['csrf_token']) || $_GET['csrf'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid request";
    header("Location: yourorder.php");
    exit;
}

// Validate order ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid order ID";
    header("Location: yourorder.php");
    exit;
}

$orderId = $_GET['id'];
$userEmail = $_SESSION['email'];

try {
    // First verify that the order belongs to the user and is in pending status
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND email = ? AND status = 'Pending'");
    $stmt->bind_param("ss", $orderId, $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Order not found or cannot be cancelled";
        header("Location: yourorder.php");
        exit;
    }

    // Update order status to Cancelled
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ? AND email = ?");
    $stmt->bind_param("ss", $orderId, $userEmail);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Order cancelled successfully!";
        $_SESSION['cancel_success'] = true;
    } else {
        $_SESSION['error'] = "Failed to cancel order";
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "An error occurred while cancelling the order";
}

header("Location: yourorder.php");
exit;
?>

<style>
.toast {
    visibility: hidden;
    min-width: 300px;
    background-color: #fff;
    color: #333;
    text-align: left;
    border-radius: 8px;
    padding: 16px;
    position: fixed;
    z-index: 1000;
    top: 20px;
    right: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transform: translateX(100%);
    transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.toast-icon {
    font-size: 24px;
    color: #286816;
}

.toast-message {
    font-size: 16px;
    font-weight: 600;
}

.toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background: #286816;
    transform-origin: left;
}

.toast.show {
    visibility: visible;
    transform: translateX(0);
}

.toast.show .toast-progress {
    animation: progress 1.5s linear forwards;
}

@keyframes progress {
    to {
        transform: scaleX(0);
    }
}
</style>

<script>
function showSuccessToast() {
    var toast = document.getElementById("success-toast");
    if (toast) {
        toast.classList.add("show");
        setTimeout(function(){ 
            toast.classList.remove("show"); 
        }, 1500);
    }
}

// Show toast on page load if success message exists
window.onload = function() {
    showSuccessToast();
};
</script>
