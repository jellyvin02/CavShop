<?php
session_start();
require "includes/header.php"; 
require_once "includes/connection.php"; 

// Add CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit;
}

$userEmail = $_SESSION['email'];

// Pagination setup
$records_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
try {
    $total_query = $conn->prepare("SELECT COUNT(*) as total FROM `orders` WHERE email = ?");
    $total_query->bind_param("s", $userEmail);
    $total_query->execute();
    $total_result = $total_query->get_result();
    $total_records = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);
} catch (Exception $e) {
    error_log($e->getMessage());
    $error = "An error occurred while counting records.";
}

// Modified query with LIMIT and OFFSET
try {
    $stmt = $conn->prepare("SELECT o.order_id, o.item, o.quantity, o.total_price, 
        o.status, o.created_at, p.image_url, o.payment_status 
        FROM `orders` o 
        LEFT JOIN products p ON o.item = p.name 
        WHERE o.email = ?
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sii", $userEmail, $records_per_page, $offset);
    $stmt->execute();
    $ordersResult = $stmt->get_result();
} catch (Exception $e) {
    error_log($e->getMessage());
    $error = "An error occurred while fetching orders.";
}

// Display success toast if set
if (isset($_SESSION['success'])) {
    echo '
    <div id="success-toast" class="toast">
        <div class="toast-content">
            <i class="fas fa-check-circle toast-icon"></i>
            <div class="toast-message">' . $_SESSION['success'] . '</div>
        </div>
        <div class="toast-progress"></div>
    </div>';
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders</title>
    <!-- Remove Bootstrap CSS, keep essential CSS -->
    <link rel="stylesheet" href="assets/css/vizza.css">
    <link rel="stylesheet" href="assets/css/media_query.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    
    <style>
        /* Add breadcrumb styles before the orders-header styles */
        .breadcrumb {
            margin-top: 80px;
            padding: 15px 20px;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .breadcrumb-item {
            color: #6b7280;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .breadcrumb-item:hover {
            color: hsl(115, 29%, 45%);
        }

        .breadcrumb-separator {
            color: #9ca3af;
            font-size: 0.8rem;
        }

        .breadcrumb-current {
            color: hsl(115, 29%, 45%);
            font-weight: 500;
        }

        /* Update orders-header margin-top */
        .orders-header {
            margin-top: 1px; /* Reduced from 100px since breadcrumb now takes space */
            text-align: left;
            padding: 0 20px;
        }

        .orders-header h1 {
            margin: 0;
            font-size: 36px; /* Increased from 28px to 36px */
            font-weight: 600;
            color: hsl(115, 29%, 45%);  /* Same green color as other headings */
        }

        /* Media query for responsive font size */
        @media (max-width: 768px) {
            .orders-header h1 {
                font-size: 28px;
            }
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .container-fluid {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        h2 {
            color: hsl(115, 29%, 45%);  /* Changed from #2c3e50 to match green theme */
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .datatable-wrapper {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        /* Enhanced table styles */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1rem;
            text-align: center;  /* Center align all table content */
        }

        .table thead th {
            background: #f1f5f9;  /* Slightly darker header background */
            color: hsl(115, 29%, 45%);  /* Changed to green to match theme */
            font-weight: 700;    /* Bolder header text */
            padding: 1rem;
            border-bottom: 2px solid #e9ecef;
            text-transform: capitalize !important;  /* Changed from uppercase to capitalize */
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            position: relative;
            cursor: pointer;
            padding-right: 2rem;
            transition: background-color 0.2s;
        }

        .table thead th.sorting {
            position: relative;
            cursor: pointer;
            padding-right: 2rem !important;
        }

        .table thead th.sorting:before,
        .table thead th.sorting:after {
            position: absolute;
            right: 0.5em;
            content: "";
            width: 0;
            height: 0;
            border-style: solid;
        }

        .table thead th.sorting:before {
            top: 40%;
            border-width: 0 4px 4px 4px;
            border-color: transparent transparent #ccc transparent;
        }

        .table thead th.sorting:after {
            bottom: 40%;
            border-width: 4px 4px 0 4px;
            border-color: #ccc transparent transparent transparent;
        }

        .table thead th.sorting_asc:before {
            border-color: transparent transparent #4CAF50 transparent;
        }

        .table thead th.sorting_desc:after {
            border-color: #4CAF50 transparent transparent transparent;
        }

        /* Add hover effect for sort icons */
        .table thead th.sorting:hover:before {
            border-color: transparent transparent #4CAF50 transparent;
        }

        .table thead th.sorting:hover:after {
            border-color: #4CAF50 transparent transparent transparent;
        }

        /* Highlight active sort column */
        .table thead th.sorting_asc,
        .table thead th.sorting_desc {
            background-color: #f3f4f6;
            color: #4CAF50;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            color: #2c3e50;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Status badges */
        .status-chip {
            padding: 0.3rem 1rem;
            border-radius: 20px; /* Changed from 50px to 20px for a more rounded pill shape */
            font-size: 0.813rem;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.3px;
            min-width: 100px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .status-pending {
            background: #fff4e6;  /* Light orange background */
            color: #d9480f;      /* Dark orange text */
            border: 1px solid #ffd8a8;
        }

        .status-completed {
            background: #ebfbee;  /* Mint green background */
            color: #2b8a3e;      /* Dark green text */
            border: 1px solid #b2f2bb;
        }

        .status-inprogress {
            background: #e7f5ff;  /* Light sky blue background */
            color: #1971c2;      /* Dark blue text */
            border: 1px solid #a5d8ff;
        }

        .status-preparing {
            background: #f3f0ff;  /* Light purple background */
            color: #5f3dc4;      /* Dark purple text */
            border: 1px solid #d0bfff;
        }

        .status-cancelled {
            background: #fee2e2;  /* Light red background */
            color: #dc2626;      /* Dark red text */
            border: 1px solid #fecaca;
        }

        /* Payment status badges */
        .payment-status-chip {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.813rem;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.3px;
            min-width: 100px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .payment-status-paid {
            background: #ebfbee;  /* Mint green background */
            color: #2b8a3e;      /* Dark green text */
            border: 1px solid #b2f2bb;
        }

        .payment-status-unpaid {
            background: #fff5f5;  /* Light red background */
            color: #e03131;      /* Dark red text */
            border: 1px solid #ffc9c9;
        }

        /* DataTables styling */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            min-width: 250px;
            background: #f8f9fa;
        }

        .dataTables_paginate {
            margin-top: 1rem;
        }

        .paginate_button {
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            border-radius: 6px;
            color: #2c3e50;
            border: 1px solid #e9ecef;
        }

        .paginate_button.current {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .paginate_button:hover:not(.current) {
            background: #f8f9fa;
            color: #2c3e50;
        }

        /* Product image */
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            
            .datatable-wrapper {
                padding: 1rem;
            }

            .table thead th {
                padding: 0.75rem;
            }

            .table td {
                padding: 0.75rem;
            }
        }

        /* Add these new styles */
        .table-striped tbody tr:nth-of-type(even) {
            background-color: #f9fafb;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #ffffff;
        }

        /* Row hover effect */
        .table-striped tbody tr:hover {
            background-color: #f0f9ff !important;
            transition: background-color 0.2s ease;
        }

        /* Make sort icons more visible */
        .sorting:before,
        .sorting:after,
        .sorting_asc:before,
        .sorting_desc:after {
            opacity: 1 !important;
            content: "";
            position: absolute;
            right: 0.5em;
            width: 0;
            height: 0;
            border: 5px solid transparent;
        }

        .sorting:before,
        .sorting_asc:before {
            bottom: 50%;
            border-bottom-color: #95a5a6;
        }

        .sorting:after,
        .sorting_desc:after {
            top: 50%;
            border-top-color: #95a5a6;
        }

        .sorting_asc:before {
            border-bottom-color: #4CAF50;
        }

        .sorting_desc:after {
            border-top-color: #4CAF50;
        }

        /* Column specific styling */
        .table td:nth-child(4), /* Quantity column */
        .table td:nth-child(5)  /* Price column */ {
            font-weight: 600;
        }

        /* Order ID styling */
        .table td:first-child {
            color: #666;
            font-family: monospace;
            font-size: 0.9rem;
        }

        /* Date column styling */
        .date-column {
            white-space: nowrap;
            color: #64748b;
            font-size: 0.875rem;
        }

        .date-column i {
            margin-right: 5px;
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .date-column .relative-time {
            display: block;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Tooltip styling */
        .date-tooltip {
            position: relative;
            cursor: help;
        }

        .date-tooltip:hover::after {
            content: attr(data-full-date);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 8px;
            background: #334155;
            color: white;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 1000;
        }

        /* Action column styling */
        .action-column {
            width: 40px;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .action-btn:hover {
            color: hsl(115, 29%, 35%);  /* Slightly darker green on hover */
        }

        .action-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 8px 0;
            min-width: 160px;
            z-index: 1000;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 8px 16px;
            color: #334155;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: #f1f5f9;
        }

        .dropdown-item i {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        /* Add new styles for cancel order */
        .dropdown-item.cancel-order {
            color: #dc2626;
        }

        .dropdown-item.cancel-order:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        .dropdown-item.cancel-order i {
            color: #dc2626;
            opacity: 1;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
        }

        .modal.show {
            display: block;
        }

        .modal-dialog {
            position: relative;
            width: auto;
            margin: 1.75rem auto;
            height: 30px; /* Changed from 50px to 30px */
            max-width: 500px;
        }

        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 3.5rem);
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-title {
            margin: 0;
            color: #2c3e50;
            font-size: 1.25rem;
            text-align: center;
            width: 100%;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            font-weight: 700;
            color: #6c757d;
            cursor: pointer;
        }

        .modal-body {
            padding: 1.5rem;
            text-align: center;
        }

        .modal-body i {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 1rem;
            display: block;
        }

        .modal-body .confirmation-text {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .modal-body .order-details {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-secondary, .btn-accept, .btn-success {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            min-width: 100px; /* Added min-width */
        }

        .btn-secondary {
            background-color: #e9ecef;
            color: #2c3e50;
            width: 120px; /* Added fixed width */
        }

        .btn-accept {
            background-color: #4CAF50;
            color: white;
            width: 120px; /* Added fixed width */
        }

        .btn-success {
            background-color: #4CAF50;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #dae0e5;
        }


        .btn-success:hover {
            background-color: #45a049;
        }

        .modal-header {
            padding: 0.5rem; /* Reduced from 0.75rem 1rem */
        }

        .modal-body {
            padding: 0.75rem; /* Reduced from 1rem */
        }

        .modal-footer {
            padding: 0.5rem; /* Reduced from 0.75rem 1rem */
        }

        .modal-body i {
            font-size: 2rem; /* Reduced from 3rem */
            margin-bottom: 0.5rem; /* Reduced from 1rem */
        }

        .modal-body .confirmation-text {
            margin-bottom: 0.25rem; /* Reduced from 0.5rem */
        }

        /* Toast styles */
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

        /* Custom Pagination Styles */
        .custom-pagination {
            display: flex;
            justify-content: flex-end;  /* Changed from center to flex-end */
            align-items: center;
            gap: 5px;
            margin-top: 20px;
            padding: 10px 0;
            margin-right: 10px;  /* Added right margin */
        }

        /* Rest of pagination styles remain the same */
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 35px;
            height: 35px;
            padding: 0 10px;
            border-radius: 6px;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .page-link:hover {
            background-color: #f7fafc;
            color: #2d3748;
            border-color: #cbd5e0;
        }

        .page-link.active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
            font-weight: 600;
        }

        .first-page, .last-page {
            font-size: 0.75rem;
        }

        @media (max-width: 640px) {
            .custom-pagination {
                gap: 3px;
            }

            .page-link {
                min-width: 30px;
                height: 30px;
                padding: 0 8px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
<main>
    <div class="container-fluid">
        <!-- Add breadcrumb navigation -->
        <div class="breadcrumb">
            <a href="menu.php" class="breadcrumb-item">      
                <i class="bi bi-cart4"></i> 
                Menu
            </a>
            <span class="breadcrumb-separator">
                <i class="bi bi-chevron-right"></i>
            </span>
            <span class="breadcrumb-current">My Orders</span>
            
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="orders-header">
            <h1>My Orders</h1>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="datatable-wrapper">
            <table id="ordersTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Image</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Order Placed</th>
                        <th class="action-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($ordersResult) && $ordersResult->num_rows > 0): ?>
                        <?php while ($order = $ordersResult->fetch_assoc()): 
                            $status = strtolower(htmlspecialchars($order['status']));
                            $paymentStatus = strtolower(htmlspecialchars($order['payment_status']));
                            $validStatuses = ['pending', 'completed', 'inprogress', 'preparing', 'cancelled'];
                            $statusClass = in_array($status, $validStatuses) ? $status : 'pending';
                            $paymentStatusClass = in_array($paymentStatus, ['paid', 'unpaid']) ? $paymentStatus : 'unpaid';
                            $orderId = htmlspecialchars($order['order_id']);
                        ?>
                            <tr data-order-id="<?php echo $orderId; ?>">
                                <td><?php echo $orderId; ?></td>
                                <td><img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                         alt="Product Image" 
                                         class="product-image"
                                         onerror="this.src='assets/images/default-product.png'"></td>
                                <td><?php echo htmlspecialchars($order['item']); ?></td>
                                <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                <td>₱<?php echo htmlspecialchars($order['total_price']); ?></td>
                                <td><span class='payment-status-chip payment-status-<?php echo $paymentStatusClass; ?>'><?php echo htmlspecialchars($order['payment_status']); ?></span></td>
                                <td><span class='status-chip status-<?php echo $statusClass; ?>'><?php echo htmlspecialchars($order['status']); ?></span></td>
                                <td class='date-column'>
                                    <span class='date-tooltip' data-full-date='<?php echo date('D, M j, Y h:i A', strtotime($order['created_at'])); ?>'>
                                        <i class='bi bi-clock'></i>
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        <span class='relative-time'><?php echo date('h:i A', strtotime($order['created_at'])); ?></span>
                                    </span>
                                </td>
                                <td>
                                    <div class='action-dropdown'>
                                        <button class='action-btn' onclick='toggleDropdown(this)'>
                                            <i class='bi bi-three-dots-vertical'></i>
                                        </button>
                                        <div class='dropdown-menu'>
                                            <a class='dropdown-item' href='view_order.php?id=<?php echo urlencode($orderId); ?>&csrf=<?php echo $_SESSION['csrf_token']; ?>'>
                                                <i class='bi bi-eye'></i> View Details
                                            </a>
                                            <?php if ($status === 'pending'): ?>
                                            <a class='dropdown-item cancel-order' href='#' onclick='confirmCancel("<?php echo urlencode($orderId); ?>", "<?php echo $_SESSION['csrf_token']; ?>")'>
                                                <i class='bi bi-x-circle'></i> Cancel Order
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Add pagination links with a container -->
            <?php if ($total_pages > 1): ?>
            <div class="datatable-wrapper-footer">
                <div class="custom-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1" class="page-link first-page">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                        <a href="?page=<?php echo $page-1; ?>" class="page-link">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>" class="page-link">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="?page=<?php echo $total_pages; ?>" class="page-link last-page">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Cancel Order Modal -->
    <div class="modal" id="cancelOrderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Order</h5>
                    <button type="button" class="close-modal" onclick="closeModal()">×</button>
                </div>
                <div class="modal-body">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p class="confirmation-text">Are you sure you want to cancel this order?</p>
                    <p class="order-details"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-accept" onclick="proceedWithCancel()">Accept</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script>
    let currentOrderId = null;
    let currentCsrf = null;

    function confirmCancel(orderId, csrf) {
        currentOrderId = orderId;
        currentCsrf = csrf;
        const modal = document.getElementById('cancelOrderModal');
        modal.style.display = 'block';
        
        // Get order details
        const orderRow = document.querySelector(`tr[data-order-id="${orderId}"]`);
        const itemName = orderRow.querySelector('td:nth-child(3)').textContent;
        const quantity = orderRow.querySelector('td:nth-child(4)').textContent;
        const price = orderRow.querySelector('td:nth-child(5)').textContent;
        
        // Update modal content
        const orderDetails = `
            <table style="width: 100%; margin-top: 10px;">
                <tr>
                    <td style="padding: 5px 0;"><strong>Item:</strong></td>
                    <td>${itemName}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Quantity:</strong></td>
                    <td>${quantity}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Total Amount:</strong></td>
                    <td>${price}</td>
                </tr>
            </table>
        `;
        document.querySelector('.order-details').innerHTML = orderDetails;
    }

    function closeModal() {
        const modal = document.getElementById('cancelOrderModal');
        modal.style.display = 'none';
    }

    function proceedWithCancel() {
        if (currentOrderId && currentCsrf) {
            window.location.href = `cancel_order.php?id=${currentOrderId}&csrf=${currentCsrf}`;
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('cancelOrderModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    $(document).ready(function() {
        $('#ordersTable').DataTable({
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function(row) {
                            return 'Order Details';
                        }
                    })
                }
            },
            ordering: true,
            order: [[6, 'desc']], // Order by date column
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            pagingType: "full_numbers",
            columnDefs: [
                {
                    targets: [1], // Image column
                    orderable: false,
                    className: 'text-center'
                },
                {
                    targets: [3, 4], // Quantity and Price columns
                    className: 'text-right'
                }
            ],
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

        // Update relative times
        function updateRelativeTimes() {
            $('.date-column .relative-time').each(function() {
                const dateStr = $(this).closest('.date-tooltip').attr('data-full-date');
                $(this).text(moment(new Date(dateStr)).fromNow());
            });
        }

        // Initial update
        updateRelativeTimes();

        // Update every minute
        setInterval(updateRelativeTimes, 60000);
    });

    function toggleDropdown(button) {
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            if(menu !== button.nextElementSibling) {
                menu.classList.remove('show');
            }
        });
        
        // Toggle current dropdown
        button.nextElementSibling.classList.toggle('show');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

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
</main>
</body>
</html>