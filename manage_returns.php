<?php
session_start();
require_once "includes/connection.php";

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: adminlogin.php");
    exit;
}

// Check if returns_refunds table exists
$table_exists = false;
try {
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'returns_refunds'");
    $table_exists = ($table_check && mysqli_num_rows($table_check) > 0);
} catch (Exception $e) {
    $table_exists = false;
    error_log("Table check error: " . $e->getMessage());
}

// Handle return/refund status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_return_status'])) {
    if (!$table_exists) {
        $_SESSION['error_message'] = "Returns/refunds table does not exist. Please run the database migration first.";
        header("Location: manage_returns.php");
        exit;
    }
    
    $return_id = intval($_POST['return_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $admin_notes = isset($_POST['admin_notes']) ? mysqli_real_escape_string($conn, trim($_POST['admin_notes'])) : '';
    
    $valid_statuses = ['Pending', 'Approved', 'Rejected', 'Processed'];
    
    if (!in_array($status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid status";
        header("Location: manage_returns.php");
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE returns_refunds SET status = ?, admin_notes = ?, processed_at = NOW() WHERE return_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssi", $status, $admin_notes, $return_id);
        
        if ($stmt->execute()) {
            // Update order return status
            $return_stmt = $conn->prepare("SELECT order_id FROM returns_refunds WHERE return_id = ?");
            if ($return_stmt) {
                $return_stmt->bind_param("i", $return_id);
                $return_stmt->execute();
                $return_result = $return_stmt->get_result();
                $return_data = $return_result->fetch_assoc();
                
                if ($return_data) {
                    $order_status = ($status === 'Approved' || $status === 'Processed') ? 'Approved' : 
                                   ($status === 'Rejected' ? 'Rejected' : 'Requested');
                    // Check if return_status column exists
                    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'return_status'");
                    if ($col_check && mysqli_num_rows($col_check) > 0) {
                        $order_stmt = $conn->prepare("UPDATE orders SET return_status = ? WHERE order_id = ?");
                        if ($order_stmt) {
                            $order_stmt->bind_param("si", $order_status, $return_data['order_id']);
                            $order_stmt->execute();
                        }
                    }
                }
            }
            
            $_SESSION['success_message'] = "Return/refund request updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update request";
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    }
    
    header("Location: manage_returns.php");
    exit;
}

// Fetch all return/refund requests
$result = null;
$return_requests = [];
if ($table_exists) {
    try {
        $query = "SELECT r.*, o.item, o.total_price, o.name as customer_name, o.email 
                  FROM returns_refunds r 
                  JOIN orders o ON r.order_id = o.order_id 
                  ORDER BY r.created_at DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $return_requests[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching returns: " . $e->getMessage());
        $return_requests = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Returns/Refunds - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Base styles */
        body, h1, h2 {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #1a1f36;
        }

        .container-main {
            padding: 1rem 2rem;
            margin-left: 260px;
            width: calc(100% - 260px);
            transition: all 0.3s ease;
        }

        body.sidebar-collapsed .container-main {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        @media (max-width: 1200px) {
            .container-main {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: hsl(115, 29%, 45%);
            font-weight: bold;
            font-size: 36px;
            margin: 0;
            padding: 0 0 1rem 0;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px; /* Reduced from 30px */
            background: #fff;
        }

        .card-header {
            background:rgb(255, 255, 255);
            color: #155724;
            border-radius: 8px 8px 0 0;
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid #218838;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #d4edda !important;
            color: #155724 !important;
            font-weight: 600 !important;
            border-bottom: 2px solid #218838 !important;
            border: none !important;
            padding: 10px 10px !important; /* Reduced from 12px 15px */
        }

        .table thead th:first-child {
            border-top-left-radius: 8px !important;
        }
        
        .table thead th:last-child {
            border-top-right-radius: 8px !important;
        }

        .table tbody td {
            padding: 8px 10px; /* Reduced horiz padding */
            vertical-align: middle;
            font-family: 'Inter', sans-serif;
        }

        .status-badge {
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

        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-processed {
            background-color: #c8e6c9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 550;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #28a745;
            color: white;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .modal-content {
            border-radius: 8px;
            border: none;
        }

        .modal-header {
            background: #d4edda;
            color: #155724;
            border-radius: 8px 8px 0 0;
            border-bottom: 2px solid #218838;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            outline: none;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        /* Bootstrap button overrides */
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            font-weight: 550;
        }

        .btn-primary:hover {
            background-color: #218838;
            border-color: #218838;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
            font-weight: 550;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }

        /* Badge styling */
        .badge.bg-info {
            background-color: #17a2b8 !important;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 550;
        }

        /* Modal close button */
        .modal-header .btn-close {
            filter: brightness(0) saturate(100%) invert(20%) sepia(95%) saturate(2000%) hue-rotate(100deg) brightness(0.3);
            opacity: 1;
        }

        /* Table row hover */
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Green numbers - Return ID and Order ID */
        .table tbody td:first-child,
        .table tbody td:nth-child(2) {
            color: #155724 !important;
            font-weight: 700;
            font-family: monospace;
        }

        /* Green numbers - Refund amount (7th column) */
        .table tbody td:nth-child(7) {
            color: #155724 !important;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php require "includes/adminside.php"; ?>
    
    <div class="container-main">
        <div class="page-header">
            <h1><i class="bi bi-arrow-counterclockwise"></i> Manage Returns / Refunds</h1>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (!$table_exists): ?>
            <div class="alert alert-warning" role="alert">
                <h5><i class="fas fa-exclamation-triangle"></i> Returns/Refunds Table Not Found</h5>
                <p>The <code>returns_refunds</code> table does not exist in the database.</p>
                <p>Please run the database migration file: <code>database/add_returns_table.sql</code></p>
                <p><strong>SQL to run:</strong></p>
                <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
CREATE TABLE IF NOT EXISTS `returns_refunds` (
  `return_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `request_type` enum('Return','Refund','Both') NOT NULL DEFAULT 'Return',
  `status` enum('Pending','Approved','Rejected','Processed') DEFAULT 'Pending',
  `admin_notes` text DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  KEY `order_id` (`order_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `return_status` enum('None','Requested','Approved','Rejected','Processed') DEFAULT 'None' AFTER `status`;
                </pre>
            </div>
        <?php else: ?>
        
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Return ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Request Type</th>
                                <th>Reason</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($return_requests)): ?>
                                <?php foreach ($return_requests as $row): ?>
                                    <tr>
                                        <td>#<?= $row['return_id'] ?></td>
                                        <td>#<?= $row['order_id'] ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($row['customer_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($row['item']) ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($row['request_type']) ?></span></td>
                                        <td>
                                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['reason']) ?>">
                                                <?= htmlspecialchars($row['reason']) ?>
                                            </div>
                                        </td>
                                        <td>₱<?= number_format($row['refund_amount'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <button class="btn-action btn-edit" onclick='openEditModal(<?= json_encode($row) ?>)'>
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">No return/refund requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Return/Refund Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="manage_returns.php">
                    <div class="modal-body">
                        <input type="hidden" name="return_id" id="edit_return_id">
                        <div class="mb-3">
                            <label class="form-label">Order ID</label>
                            <input type="text" class="form-control" id="edit_order_id" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <input type="text" class="form-control" id="edit_customer" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Request Type</label>
                            <input type="text" class="form-control" id="edit_request_type" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" id="edit_reason" rows="3" readonly></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Refund Amount</label>
                            <input type="text" class="form-control" id="edit_amount" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Processed">Processed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" name="admin_notes" id="edit_admin_notes" rows="3" placeholder="Add any notes or comments..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_return_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(data) {
            document.getElementById('edit_return_id').value = data.return_id;
            document.getElementById('edit_order_id').value = '#' + data.order_id;
            document.getElementById('edit_customer').value = data.customer_name + ' (' + data.email + ')';
            document.getElementById('edit_request_type').value = data.request_type;
            document.getElementById('edit_reason').value = data.reason;
            document.getElementById('edit_amount').value = '₱' + parseFloat(data.refund_amount).toFixed(2);
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_admin_notes').value = data.admin_notes || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }
    </script>
</body>
</html>

