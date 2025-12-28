<?php
session_start();
include_once 'includes/connection.php';

// Add CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Merge POST handling for CSRF validation and order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    if (isset($_POST['delete_selected']) && isset($_POST['order_ids'])) {
        $orderIds = array_map('intval', explode(',', $_POST['order_ids']));
        if (!empty($orderIds)) {
            try {
                $conn->begin_transaction();
                $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
                $deleteQuery = "DELETE FROM archived_orders WHERE order_id IN ($placeholders)";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param(str_repeat('i', count($orderIds)), ...$orderIds);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    $_SESSION['success_message'] = "Selected orders successfully deleted.";
                } else {
                    throw new Exception("Failed to delete selected orders.");
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    if (isset($_POST['delete_order'])) {
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId === null) {
            $_SESSION['error_message'] = "Invalid Order ID.";
        } else {
            try {
                $conn->begin_transaction();
                $deleteOrderQuery = "DELETE FROM archived_orders WHERE order_id = ?";
                $deleteOrderStmt = $conn->prepare($deleteOrderQuery);
                if ($deleteOrderStmt) {
                    $deleteOrderStmt->bind_param("i", $orderId);
                    if ($deleteOrderStmt->execute()) {
                        $conn->commit();
                        $_SESSION['success_message'] = "Order successfully deleted.";
                    } else {
                        throw new Exception("Failed to delete order.");
                    }
                }
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after retrieving
}

// Check if the user is logged in as admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: adminlogin.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Orders</title>
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
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow-x: auto;
        width: 100%;
    }

    /* Table styles */
    table.dataTable {
        margin: 20px 0 !important;
        width: 100% !important;
    }

    table.dataTable td,
    table.dataTable th {
        padding: 8px 15px !important;
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
        padding: 12px 15px !important;
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
        border-radius: 15px !important; /* Changed to 30px for more rounded look */
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

    /* Action button icons */
    .btn-icon.delete-btn {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .btn-icon.delete-btn:hover {
        transform: scale(1.1);
    }

    .btn-icon.delete-btn i {
        font-size: 1.2rem;
        color: #286816; /* Change to your brand color */
    }
    .btn-success {
       
        background:rgb(34, 114, 53); /* Change to your brand color */
    }

    .bulk-delete-btn {
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 550;
        background-color: #d4edda;  /* Changed to green theme */
        color: #155724;             /* Changed to darker green */
        padding: 8px 16px;
    }

    .bulk-delete-btn:hover {
        background-color: #c3e6cb;
        transform: translateY(-1px);
    }

    .bulk-delete-btn i {
        font-size: 1rem;
    }

    #selectedCount {
        font-size: 0.875rem;
        font-weight: 500;
    }

    .bulk-delete-btn:disabled {
        cursor: not-allowed;
    }

    /* Toast Styling */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }

    .toast {
        background-color: #fff !important;
        border-left: 4px solid #286816 !important; /* Change to your brand color */
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15) !important;
        min-width: 300px;
        opacity: 0;
        transition: all 0.3s ease-in-out;
        transform: translateX(100%);
    }

    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast .toast-header {
        background-color: #fff;
        border-bottom: 1px solid #e9ecef;
        color: #155724;
    }

    .toast .toast-body {
        color: #155724;
        font-weight: 500;
    }

    .toast .text-danger {
        color: #155724 !important; /* Change error color to green */
    }
</style>
</head>
<body>

<?php require "includes/adminside.php"; ?>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-check-circle-fill <?php echo isset($_SESSION['error_message']) ? 'text-danger' : 'text-success'; ?> me-2"></i>
            <strong class="me-auto"><?php echo isset($_SESSION['error_message']) ? 'Notice' : 'Success'; ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?php 
                echo isset($_SESSION['error_message']) 
                    ? htmlspecialchars($_SESSION['error_message']) 
                    : htmlspecialchars($_SESSION['success_message']);
                
                // Clear the messages after displaying
                unset($_SESSION['success_message']);
                unset($_SESSION['error_message']);
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="container-fluid">
    <h2 class="mb-4">Archived Orders</h2>

    <!-- Add Bulk Actions -->
    <div class="bulk-actions mb-3">
        <div class="d-flex align-items-center gap-2">
            <button class="status-chip status-cancelled bulk-delete-btn" id="deleteBulkAction" style="opacity: 0.5;" disabled>
                <i class="bi bi-trash me-1"></i> Delete Selected
            </button>
            <span class="ms-3 text-muted" id="selectedCount"></span>
        </div>
    </div>

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
                    <th>Email</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Proof</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT order_id, name, email, item, quantity, total_price, payment_method, status, payment_status, payment_proof FROM `archived_orders` ORDER BY FIELD(status, 'Pending') DESC, order_id ASC");
                $stmt->execute();
                $ordersResult = $stmt->get_result();

                if ($ordersResult->num_rows > 0) {
                    while ($order = $ordersResult->fetch_assoc()) {
                        echo "<tr>
                            <td>
                                <div class='form-check'>
                                    <input class='form-check-input row-checkbox' type='checkbox' 
                                        value='" . htmlspecialchars($order['order_id']) . "'>
                                </div>
                            </td>
                            <td>" . htmlspecialchars($order['order_id']) . "</td>
                            <td>" . htmlspecialchars($order['name']) . "</td>
                            <td>" . htmlspecialchars($order['email']) . "</td>
                            <td>" . htmlspecialchars($order['item']) . "</td>
                            <td>" . htmlspecialchars($order['quantity']) . "</td>
                            <td>₱" . htmlspecialchars($order['total_price']) . "</td>
                            <td><span class='payment-method payment-method-" . strtolower($order['payment_method']) . "'>" . 
                                ($order['payment_method'] == 'gcash' ? 'GCash' : strtoupper($order['payment_method'])) . 
                            "</span></td>
                            
                            <!-- Payment Proof Column -->
                            <td class='text-center'>";
                        if (!empty($order['payment_proof'])) {
                            echo "<button class='btn btn-sm btn-outline-primary' onclick=\"viewProof('" . htmlspecialchars($order['payment_proof']) . "')\">
                                <i class='bi bi-image'></i> View
                            </button>";
                        } else {
                            echo "<span class='text-muted small'>None</span>";
                        }
                        echo "</td>

                            <td>
                                <span class='payment-status-chip payment-status-" . strtolower($order['payment_status']) . "'>" . 
                                    htmlspecialchars($order['payment_status']) . 
                                "</span>
                            </td>
                            <td><span class='status-chip status-" . strtolower($order['status']) . "'>" . htmlspecialchars($order['status']) . "</span></td>
                            <td>
                                <form method='POST' action='" . htmlspecialchars($_SERVER['PHP_SELF']) . "' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete this order?\");'>
                                    <input type='hidden' name='order_id' value='" . htmlspecialchars($order['order_id']) . "'>
                                    <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>
                                    <button type='submit' name='delete_order' class='btn-icon delete-btn'>
                                        <i class='bi bi-trash text-danger'></i>
                                    </button>
                                </form>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='12' class='text-center'>No orders found.</td></tr>";
                }
                $stmt->close();
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

        // Initialize toast if success message exists
        if ($('.toast').length > 0) {
            var toastElement = document.querySelector('.toast');
            var toast = new bootstrap.Toast(toastElement, {
                delay: 3000,
                animation: true,
                autohide: true
            });
            
            // Show toast with animation
            toast.show();
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
            const bulkBtn = $('#deleteBulkAction');
            const countSpan = $('#selectedCount');
            
            if (checkedCount > 0) {
                bulkBtn.prop('disabled', false).css('opacity', '1');
                countSpan.text(checkedCount + ' selected');
            } else {
                bulkBtn.prop('disabled', true).css('opacity', '0.5');
                countSpan.text('');
            }
        }

        $('#deleteBulkAction').on('click', function() {
            const selectedIds = $('.row-checkbox:checked').map(function() {
                return this.value;
            }).get();

            if (selectedIds.length === 0) return;

            if (!confirm('Are you sure you want to delete the selected orders?')) return;

            const form = $('<form>', {
                method: 'POST',
                action: window.location.href
            });

            form.append($('<input>', {
                type: 'hidden',
                name: 'delete_selected',
                value: '1'
            }));

            form.append($('<input>', {
                type: 'hidden',
                name: 'order_ids',
                value: selectedIds.join(',')
            }));

            form.append($('<input>', {
                type: 'hidden',
                name: 'csrf_token',
                value: '<?php echo $_SESSION['csrf_token']; ?>'
            }));

            $('body').append(form);
            form.submit();
        });

        // Update the delete button click handler to use the modal
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            const orderId = $(this).closest('form').find('input[name="order_id"]').val();
            $('#orderIdText').text(orderId);
            $('#orderIdToDelete').val(orderId);
            $('#deleteModal').modal('show');
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
            showToast(<?php echo json_encode($_SESSION['success_message']); ?>);
        <?php endif; ?>
    });

    document.addEventListener('DOMContentLoaded', function() {
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (button) {
                    const orderId = button.getAttribute('data-order-id');
                    document.getElementById('orderIdText').textContent = orderId;
                    document.getElementById('orderIdToDelete').value = orderId;
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
        const toastElement = document.querySelector('.toast');
        document.getElementById('toastMessage').textContent = message;
        const toast = new bootstrap.Toast(toastElement, {
            delay: 3000,
            animation: true
        });
        toast.show();
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
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="modal-body">
                    Are you sure you want to permanently delete order #<span id="orderIdText"></span>?
                    <input type="hidden" name="order_id" id="orderIdToDelete">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="delete_order" class="btn btn-success">Delete</button> <!-- Change to green -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>