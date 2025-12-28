<?php
session_start();
include_once 'includes/connection.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: adminlogin.php");
    exit;
}

$adminId = $_SESSION['username'];
$adminDetails = fetchAdminDetails($conn, $adminId);

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $message = updateOrderStatus($conn, $_POST['order_id'], $_POST['status']);
    } elseif (isset($_POST['delete_order'])) {
        $message = archiveOrder($conn, $_POST['order_id']);
    }
}

// Add input validation for status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newStatus = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    
    $validStatuses = ['Pending', 'In Progress', 'Preparing', 'Completed'];
    
    if ($orderId === false || !in_array($newStatus, $validStatuses)) {
        $_SESSION['error_message'] = "Invalid input data";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Order status updated successfully";
        } else {
            throw new Exception("Failed to update order status");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Add this near the top of the file after other POST handlers
if (isset($_POST['update_payment_status'])) {
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newPaymentStatus = filter_input(INPUT_POST, 'payment_status', FILTER_SANITIZE_STRING);
    
    if ($orderId && in_array($newPaymentStatus, ['Paid', 'Unpaid'])) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?");
            $stmt->bind_param("si", $newPaymentStatus, $orderId);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Payment status updated successfully";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                throw new Exception("Failed to update payment status");
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
}

// Add this after other POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = filter_input(INPUT_POST, 'bulk_action', FILTER_SANITIZE_STRING);
    $orderIds = isset($_POST['order_ids']) ? explode(',', $_POST['order_ids']) : [];
    $successCount = 0;
    
    try {
        $conn->begin_transaction();
        
        foreach ($orderIds as $orderId) {
            $orderId = filter_var($orderId, FILTER_VALIDATE_INT);
            if (!$orderId) continue;

            switch ($action) {
                case 'archive':
                    if (archiveOrder($conn, $orderId, true)) {
                        $successCount++;
                    }
                    break;
                    
                case 'markPaid':
                    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'Paid' WHERE order_id = ?");
                    $stmt->bind_param("i", $orderId);
                    if ($stmt->execute()) $successCount++;
                    break;
                    
                case 'markUnpaid':
                    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'Unpaid' WHERE order_id = ?");
                    $stmt->bind_param("i", $orderId);
                    if ($stmt->execute()) $successCount++;
                    break;
            }
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "$successCount orders processed successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error performing bulk action: " . $e->getMessage();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Add this near the existing status arrays
$paymentStatuses = [
    'Unpaid' => 'unpaid',
    'Paid' => 'paid',
];

function fetchAdminDetails($conn, $adminId) {
    $stmt = $conn->prepare("SELECT * FROM customer_users WHERE email = ?");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $adminDetails = $result->fetch_assoc();
    $stmt->close();
    return $adminDetails;
}

function updateOrderStatus($conn, $orderId, $newStatus) {
    $stmt = $conn->prepare("SELECT quantity FROM `orders` WHERE `order_id` = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order['quantity'] > 0) {
        $stmt = $conn->prepare("UPDATE `orders` SET `status` = ? WHERE `order_id` = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Order status updated successfully.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        $stmt->close();
        return "Failed to update order status.";
    }
    return "Order quantity must be greater than zero.";
}

// Modify archiveOrder function to not redirect immediately for bulk operations
// Modify archiveOrder function to not redirect immediately for bulk operations
function archiveOrder($conn, $orderId, $isBulk = false) {
    try {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $orderData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($orderData) {
            $stmt = $conn->prepare("INSERT INTO archived_orders (order_id, name, email, item, quantity, total_price, status, payment_method, payment_status, payment_proof) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssidssss", 
                $orderData['order_id'], 
                $orderData['name'], 
                $orderData['email'], 
                $orderData['item'], 
                $orderData['quantity'], 
                $orderData['total_price'], 
                $orderData['status'], 
                $orderData['payment_method'],
                $orderData['payment_status'],
                $orderData['payment_proof']
            );

            if ($stmt->execute()) {
                $stmt->close();

                // Only delete if insert was successful
                $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
                $stmt->bind_param("i", $orderId);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    
                    if (!$isBulk) {
                        $_SESSION['success_message'] = "Order archived successfully.";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    }
                    return true;
                } else {
                    // Delete failed
                    throw new Exception("Failed to delete original order after archiving.");
                }
            } else {
                // Insert failed
                throw new Exception("Failed to archive order: " . $stmt->error);
            }
        }
        return false;
    } catch (Exception $e) {
        if (isset($stmt)) $stmt->close(); // Ensure statement is closed
        
        if (!$isBulk) {
            $_SESSION['error_message'] = "Error archiving order: " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        return false;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css"> <!-- Add Bootstrap Icons -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <style>
    /* Base styles */
    body, h2 {
        font-family: 'Inter', sans-serif;
        background-color: #f5f7fa;
        color: #1a1f36;
    }

    h2 {
        color: hsl(115, 29%, 45%);
        font-weight: bold;
        font-size: 36px;
        margin: 0;
        padding: 0 0 1rem 0;
    }

    /* Layout */
    .container-fluid {
        padding: 1rem 2rem;
        margin-left: 260px;
        width: calc(100% - 260px);
    }

    /* Table wrapper */
    .datatable-wrapper {
        background: #fff;
        padding: 15px; /* Reduced from 20px */
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow-x: auto;
        width: 100%;
    }

    /* Table styles */
    table.dataTable {
        margin: 0 !important; /* Removed 20px margin */
        width: 100% !important;
    }

    table.dataTable td,
    table.dataTable th {
        padding: 8px 10px !important; /* Reduced horizontal padding from 15px */
        vertical-align: middle !important;
        height: 40px !important;
        white-space: nowrap !important;
        text-align: left !important;
        font-family: 'Inter', sans-serif !important;
    }

    /* Table header */
    table.dataTable thead th {
        background: #d4edda !important;
        color: #155724 !important;
        font-weight: 600 !important;
        border-bottom: 2px solid #218838 !important;
        border: none !important;
        padding: 10px 10px !important; /* Reduced horizontal padding */
    }

    table.dataTable thead th:first-child {
        border-top-left-radius: 8px !important;
    }
    
    table.dataTable thead th:last-child {
        border-top-right-radius: 8px !important;
    }

    /* Order ID column */
    table.dataTable tbody td:first-child {
        font-family: monospace;
        font-size: 0.9rem;
        color: #1a1f36;
        font-weight: 700;
    }

    /* Status chips */
    .status-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.875rem;
        font-weight: 550;
        line-height: 1;
        min-width: 80px;
        text-transform: capitalize;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .status-completed {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-inprogress {
        background-color: #e3f2fd;
        color: #0d47a1;
        border: 1px solid #90caf9;
    }

    .status-preparing {
        background-color: #e2e3ff;
        color: #2d2d85;
        border: 1px solid #d4d6ff;
    }

    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Payment status */
    .payment-status-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.875rem;
        font-weight: 550;
        line-height: 1;
        min-width: 80px;
    }

    .payment-status-paid {
        background-color: #c8e6c9;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }

    .payment-status-unpaid {
        background-color: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }

    /* Payment method colors */
    .payment-method {
        font-weight: 700;
        text-align: center !important;
        display: block;
    }

    .payment-method-cod {
        color: rgb(109, 113, 131);
        font-weight: 600;
    }

    .payment-method-gcash {
        color: #0069FF;
        font-weight: 600;
    }

    .payment-method-maya {
        color: #28a745;
        font-weight: 600;
    }

    /* Action buttons */
    .actions-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-icon {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        margin: 0 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-icon i {
        font-size: 1.25rem;
        color: #28a745;
    }

    .btn-icon i.bi-wallet2 {
        color: #198754;
        font-size: 1.1rem;
    }

    .btn-icon:hover i.bi-wallet2 {
        color: #157347;
    }

    .btn-icon.disabled {
        opacity: 0.5;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }

    .btn-icon[disabled] {
        opacity: 0.5;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }

    .btn-icon[disabled] i.bi-wallet2 {
        color: #6c757d !important;
    }

    /* Toast notifications */
    .toast-container {
        position: fixed;
        top: 70px;
        right: 20px;
        width: 300px;
        z-index: 9999;
    }

    .toast {
        background-color: rgb(255, 255, 255) !important;
        border-color: #c3e6cb !important;
        color: #155724 !important;
    }

    /* DataTables specific styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 5px 12px !important;
        margin: 0 3px !important;
        border-radius: 4px !important;
        background: #d4edda !important;
        color: #155724 !important;
        border: none !important;
        min-width: 32px !important;
        height: 32px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: rgb(55, 146, 76) !important;
        color: white !important;
        font-weight: 500 !important;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .container-fluid {
            margin-left: 0;
            width: 100%;
            padding: 1rem;
        }
    }

    /* DataTables search and length styles */
    .dataTables_wrapper .dataTables_filter input {
        background-color: #d4edda !important;
        color: #155724 !important;
        border: 1px solid #c3e6cb !important;
        padding: 1px 12px !important;
        border-radius: 15px !important;
        font-weight: 500 !important;
        width: 200px !important;
        transition: all 0.2s ease !important;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        outline: none !important;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25) !important;
        border-color: #28a745 !important;
    }

    .dataTables_wrapper .dataTables_filter label {
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
    }

    .dataTables_wrapper .dataTables_filter label::after {
        content: "\F52A" !important;
        font-family: bootstrap-icons !important;
        position: absolute !important;
        right: 12px !important;
        color: #155724 !important;
        font-size: 1rem !important;
        pointer-events: none !important;
    }

    .dataTables_wrapper .dataTables_length select {
        background-color: #d4edda !important;
        color: #155724 !important;
        border: 1px solid #c3e6cb !important;
        padding: 6px 30px 6px 12px !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23155724' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 8px center !important;
        background-size: 16px 12px !important;
    }

    /* Pagination styling */
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 15px !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        margin: 0 2px !important;
        border: none !important;
        background: #d4edda !important;
        color: #155724 !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
        min-width: 32px !important;
        height: 32px !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current) {
        background: #c3e6cb !important;
        color: #155724 !important;
        transform: translateY(-1px) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #28a745 !important;
        color: white !important;
        font-weight: 600 !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }

    /* DataTables info styling */
    .dataTables_info {
        color: #155724 !important;
        font-weight: 500 !important;
        padding-top: 12px !important;
    }

    /* Search and entries text styling */
    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label {
        font-family: 'Inter', sans-serif !important;
        color: #155724 !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
    }

    .dataTables_wrapper .dataTables_filter input {
        font-family: 'Inter', sans-serif !important;
        font-size: 0.95rem !important;
        margin-left: 8px !important;
    }

    .dataTables_wrapper .dataTables_length select {
        font-family: 'Inter', sans-serif !important;
        font-size: 0.95rem !important;
        margin: 0 4px !important;
    }

    /* Custom checkbox styling */
    .form-check-input {
        border-color: #28a745 !important;
        outline: none !important;
    }

    .form-check-input:checked {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        border-color: #28a745 !important;
    }

    .form-check-input:disabled {
        background-color: #e9ecef !important;
        border-color: #ced4da !important;
    }

    .form-check-input:disabled:checked {
        background-color: #a8d7b4 !important;
        border-color: #a8d7b4 !important;
    }

    /* Optional: Adjust Pagination Container */
    .pagination {
        justify-content: center; /* Center align pagination */
    }

    /* Update Pagination Background to Light Green */
    .pagination .page-link {
        background-color: #d4edda; /* Light green background */
        color: #155724; /* Text color */
        border: 1px solid #d4edda; /* Border matching background */
    }

    .pagination .page-link:hover {
        background-color: #c3e6cb; /* Slightly darker green on hover */
        border-color: #b1ddb1;
        color: #155724;
    }

    .pagination .page-item.active .page-link {
        background-color: #aed9aa; /* Active page light green */
        border-color: #9bd59b;
        color: #155724;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        background: #d4edda !important; /* Light green background */
        color: #155724 !important;
        border-radius: 6px !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #aed9aa !important; /* Active button light green */
        color: white !important;
        font-weight: 600 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current) {
        background: #c3e6cb !important; /* Hover state */
        color: #155724 !important;
    }

    /* Toast Styling */
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
        font-weight: 500;
        color: rgb(0, 0, 0);
        font-family: 'Poppins', sans-serif;
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
    .btn-success{
        background-color: #28a745;
        color: white;
    }
    

    .toast.show {
        visibility: visible;
        transform: translateX(0);
    }

    .toast.show .toast-progress {
        animation: progress 2s linear forwards;
    }

    @keyframes progress {
        to {
            transform: scaleX(0);
        }
    }

</style>
</head>
<body>

<?php require "includes/adminside.php"; ?>

<div class="container-fluid">
    <h2 class="mb-4">Manage Orders</h2>

    <!-- Add Bulk Actions -->
    <div class="bulk-actions mb-3" style="display: none;">
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm w-auto" id="bulkAction">
                <option value="">Bulk Actions</option>
                <option value="archive">Archive Selected</option>
                <option value="markPaid">Mark as Paid</option>
                <option value="markUnpaid">Mark as Unpaid</option>
            </select>
            <button class="btn btn-sm btn-success" id="applyBulkAction">Apply</button>
            <span class="ms-3" id="selectedCount"></span>
        </div>
    </div>

    <?php if (!empty($message) || isset($_SESSION['success_message'])): ?>
    <div id="success-toast" class="toast">
        <div class="toast-content">
            <i class="fas fa-check-circle toast-icon"></i>
            <div class="toast-message">
                <?php 
                    echo !empty($message) ? htmlspecialchars($message) : 
                        (isset($_SESSION['success_message']) ? htmlspecialchars($_SESSION['success_message']) : '');
                ?>
            </div>
        </div>
        <div class="toast-progress"></div>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <div class="datatable-wrapper">
        <table id="ordersTable" class="table table-striped">
            <thead>
                <tr>
                    <th>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                        </div>
                    </th>
                    <th>Order ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Proof</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Updated query to include username from customer_users
                $stmt = $conn->prepare("SELECT o.order_id, o.name, o.email, o.item, o.quantity, o.total_price, o.payment_method, o.status, o.payment_status, o.payment_proof, u.username FROM `orders` o LEFT JOIN `customer_users` u ON o.email = u.email ORDER BY FIELD(o.status, 'Pending') DESC, o.order_id ASC");
                $stmt->execute();
                $ordersResult = $stmt->get_result();

                if ($ordersResult->num_rows > 0) {
                    while ($order = $ordersResult->fetch_assoc()) {
                        $statusClass = '';
                        switch ($order['status']) {
                            case 'Pending': $statusClass = 'status-pending'; break;
                            case 'In Progress': $statusClass = 'status-inprogress'; break;
                            case 'Preparing': $statusClass = 'status-preparing'; break;
                            case 'Completed': $statusClass = 'status-completed'; break;
                            default: $statusClass = 'status-cancelled'; break;
                        }

                        $paymentStatusClass = ($order['payment_status'] === 'Paid') ? 'payment-status-paid' : 'payment-status-unpaid';
                        
                        // Payment Method Class
                        $pmClass = '';
                        $pm = strtolower($order['payment_method']);
                        if(strpos($pm, 'gcash') !== false) $pmClass = 'payment-method-gcash';
                        elseif(strpos($pm, 'maya') !== false) $pmClass = 'payment-method-maya';
                        else $pmClass = 'payment-method-cod';
                ?>
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input order-checkbox" type="checkbox" value="<?php echo $order['order_id']; ?>">
                        </div>
                    </td>
                    <td>#<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <div><?php echo htmlspecialchars($order['name']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($order['item']); ?></td>
                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                    <td><span class="payment-method <?php echo $pmClass; ?>"><?php echo htmlspecialchars($order['payment_method']); ?></span></td>
                    
                    <!-- Payment Proof Column -->
                    <td class="text-center">
                        <?php if (!empty($order['payment_proof'])): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewProof('<?php echo htmlspecialchars($order['payment_proof']); ?>')">
                                <i class="bi bi-image"></i> View
                            </button>
                        <?php else: ?>
                            <span class="text-muted small">None</span>
                        <?php endif; ?>
                    </td>

                    <td><span class="payment-status-chip <?php echo $paymentStatusClass; ?>"><?php echo htmlspecialchars($order['payment_status']); ?></span></td>
                    <td>
                        <span class="status-chip <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions-wrapper">
                            <!-- Update Status Form -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    <option value="" disabled selected>Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Preparing">Preparing</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </form>
                            
                            <!-- Payment Status Toggle -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <input type="hidden" name="update_payment_status" value="1">
                                <?php if ($order['payment_status'] === 'Unpaid'): ?>
                                    <input type="hidden" name="payment_status" value="Paid">
                                    <button type="submit" class="btn-icon" title="Mark as Paid">
                                        <i class="bi bi-wallet2"></i>
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="payment_status" value="Unpaid">
                                    <button type="submit" class="btn-icon" title="Mark as Unpaid" style="opacity:0.6">
                                        <i class="bi bi-x-circle text-danger"></i>
                                    </button>
                                <?php endif; ?>
                            </form>

                            <!-- Archive Action -->
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this order?');">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <input type="hidden" name="delete_order" value="1">
                                <button type="submit" class="btn-icon text-danger" title="Archive Order">
                                    <i class="bi bi-archive"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='11' class='text-center'>No orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- ...existing code... -->

    <script>
    $(document).ready(function() {
        $('#ordersTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            pagingType: "full_numbers",
            language: {
                search: "Search orders:",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>'
                },
                emptyTable: "No orders found"
            },
            drawCallback: function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-sm');
            }
        });

        if (document.querySelector('.toast')) {
            var toastEl = document.querySelector('.toast');
            var bsToast = new bootstrap.Toast(toastEl);
            bsToast.show();
            setTimeout(function() {
                bsToast.hide();
            }, 2000);
        }

        let isAllSelected = false;

        $('.select-all-header').click(function(e) {
            isAllSelected = !isAllSelected;
            $('.select-checkbox').prop('checked', isAllSelected);
            $('#select-all').prop('checked', isAllSelected);
        });

        $('#select-all').click(function(e) {
            e.stopPropagation();
            isAllSelected = this.checked;
            $('.select-checkbox').prop('checked', isAllSelected);
        });

        $(document).on('change', '.select-checkbox', function(e) {
            e.stopPropagation();
            const totalCheckboxes = $('.select-checkbox').length - 1;
            const checkedCheckboxes = $('.select-checkbox:checked').length;
            isAllSelected = checkedCheckboxes === totalCheckboxes;
            $('#select-all').prop('checked', isAllSelected);
        });

        // Add this after DataTable initialization
        $('#selectAll').on('change', function() {
            $('.row-checkbox').prop('checked', this.checked);
            updateBulkActionsVisibility();
        });

        $(document).on('change', '.row-checkbox', function() {
            updateBulkActionsVisibility();
            const allChecked = $('.row-checkbox:not(:disabled)').length === $('.row-checkbox:checked').length;
            $('#selectAll').prop('checked', allChecked);
        });

        function updateBulkActionsVisibility() {
            const checkedCount = $('.row-checkbox:checked').length;
            if (checkedCount > 0) {
                $('.bulk-actions').show();
                $('#selectedCount').text(`${checkedCount} selected`);
            } else {
                $('.bulk-actions').hide();
                $('#selectedCount').text('');
            }
        }

        $('#applyBulkAction').on('click', function() {
            const action = $('#bulkAction').val();
            if (!action) return;

            const selectedIds = $('.row-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selectedIds.length === 0) return;

            // Create and submit form for bulk action
            const form = $('<form>', {
                method: 'POST',
                action: window.location.href
            });

            form.append($('<input>', {
                type: 'hidden',
                name: 'bulk_action',
                value: action
            }));

            form.append($('<input>', {
                type: 'hidden',
                name: 'order_ids',
                value: selectedIds.join(',')
            }));

            $('body').append(form);
            form.submit();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const archiveModal = document.getElementById('archiveModal');
        if (archiveModal) {
            archiveModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (button) {
                    const orderId = button.getAttribute('data-order-id');
                    document.getElementById('orderIdText').textContent = orderId;
                    document.getElementById('orderIdToArchive').value = orderId;
                }
            });
        }

        const toastElList = [].slice.call(document.querySelectorAll('.toast'));
        const toastList = toastElList.map(function(toastEl) {
            return new bootstrap.Toast(toastEl, {
                delay: 3000
            });
        });

        if (document.querySelector('.toast')) {
            const toast = bootstrap.Toast.getInstance(document.querySelector('.toast'));
            if (toast) {
                toast.show();
            }
        }
    });

    function showToast(message) {
        const toast = document.getElementById('success-toast');
        const toastMessage = toast.querySelector('.toast-message');
        toastMessage.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 1500);
    }

    <?php if (isset($_SESSION['success_message'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(<?php echo json_encode($_SESSION['success_message']); ?>);
        });
    <?php unset($_SESSION['success_message']); endif; ?>

    function updateStatus(orderId, newStatus) {
        const form = document.createElement('form');
        form.method = 'POST';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'order_id';
        orderIdInput.value = orderId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = newStatus;
        
        const submitInput = document.createElement('input');
        submitInput.type = 'hidden';
        submitInput.name = 'update_status';
        submitInput.value = '1';
        
        form.appendChild(orderIdInput);
        form.appendChild(statusInput);
        form.appendChild(submitInput);
        
        document.body.appendChild(form);
        form.submit();
    }
    </script>
</body>
</html>


<!-- Proof Modal -->
<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Payment Proof</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="proofImage" src="" alt="Payment Proof" style="max-width: 100%; border-radius: 8px;">
      </div>
    </div>
  </div>
</div>

<script>
function viewProof(imagePath) {
    if (!imagePath) return;
    document.getElementById('proofImage').src = imagePath;
    var myModal = new bootstrap.Modal(document.getElementById('proofModal'));
    myModal.show();
}
</script>

<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="archiveModalLabel">Confirm Archive Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="modal-body">
                    Are you sure you want to archive order #<span id="orderIdText"></span>?
                    <input type="hidden" name="order_id" id="orderIdToArchive">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="delete_order" class="btn btn-success">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>
