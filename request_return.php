<?php
session_start();
require_once "includes/connection.php";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit;
}

$userEmail = $_SESSION['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, trim($_POST['reason'])) : '';
    $request_type = isset($_POST['request_type']) ? mysqli_real_escape_string($conn, $_POST['request_type']) : 'Return';

    // Validate inputs
    if (empty($order_id) || empty($reason)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: view_order.php?id=" . $order_id);
        exit;
    }

    // Validate request type
    if (!in_array($request_type, ['Return', 'Refund', 'Both'])) {
        $_SESSION['error'] = "Invalid request type";
        header("Location: view_order.php?id=" . $order_id);
        exit;
    }

    try {
        // Verify order belongs to user and is completed
        $stmt = $conn->prepare("SELECT order_id, total_price, status FROM orders WHERE order_id = ? AND email = ? AND status = 'Completed'");
        $stmt->bind_param("is", $order_id, $userEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Order not found or cannot be returned/refunded";
            header("Location: view_order.php?id=" . $order_id);
            exit;
        }

        $order = $result->fetch_assoc();

        // Check if return/refund already requested
        $stmt = $conn->prepare("SELECT return_id FROM returns_refunds WHERE order_id = ? AND status IN ('Pending', 'Approved')");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $existing = $stmt->get_result();

        if ($existing->num_rows > 0) {
            $_SESSION['error'] = "A return/refund request for this order already exists";
            header("Location: view_order.php?id=" . $order_id);
            exit;
        }

        // Insert return/refund request
        $stmt = $conn->prepare("INSERT INTO returns_refunds (order_id, email, reason, request_type, refund_amount) VALUES (?, ?, ?, ?, ?)");
        $refund_amount = $order['total_price'];
        $stmt->bind_param("isssd", $order_id, $userEmail, $reason, $request_type, $refund_amount);
        
        if ($stmt->execute()) {
            // Update order return status
            $stmt = $conn->prepare("UPDATE orders SET return_status = 'Requested' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Return/refund request submitted successfully! We will review your request and get back to you soon.";
            header("Location: view_order.php?id=" . $order_id);
            exit;
        } else {
            $_SESSION['error'] = "Failed to submit request. Please try again.";
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = "An error occurred. Please try again.";
    }
}

header("Location: yourorder.php");
exit;
?>

