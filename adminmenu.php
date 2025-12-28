<?php
session_start();
require_once 'includes/connection.php';

// Ensure delete request is POST and the product ID is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    // Get the product ID to delete
    $product_id = $_POST['product_id_to_delete'];

    // Prevent SQL injection by ensuring product ID is an integer
    $product_id = intval($product_id);

    // Prepare the DELETE query
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    // Bind the product ID to the prepared statement
    $stmt->bind_param("i", $product_id);

    // Execute the query and check for success
    if ($stmt->execute()) {
        // Set a session variable for success message
        $_SESSION['delete_message'] = "Product deleted successfully!";
    } else {
        $_SESSION['delete_message'] = "Error deleting product!";
    }

    // Close the statement
    $stmt->close();

    // Redirect back to the admin menu
    header("Location: adminmenu.php");
    exit;
}

// Fetch products with categories using JOIN to avoid N+1 query problem
$products_query = "SELECT p.id, p.name, p.product_code, p.price, p.category_id, p.image_url, p.description, 
                   p.colors, p.available_sizes, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.name";
$products_result = mysqli_query($conn, $products_query);

// Check for delete status
if (isset($_SESSION['delete_message'])) {
    echo '<div id="success-toast" class="toast">
            <div class="toast-content">
                <i class="fas fa-check-circle toast-icon"></i>
                <div class="toast-message">' . htmlspecialchars($_SESSION['delete_message']) . '</div>
            </div>
            <div class="toast-progress"></div>
          </div>';
    unset($_SESSION['delete_message']);
}
?>

<?php require "includes/adminside.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Menu</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Base styles */
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        /* Main container */
        .main-container {
            max-width: 1900px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        /* Section title */
        .section-title {
            font-size: 36px;
            color: hsl(115, 29%, 45%);
            font-weight: 700;
        }

        /* Table styles */
        .menu-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            overflow: hidden;
            border-radius: 9px;
            box-shadow: 0 0 0 1px #e2e8f0;
        }

        .menu-table th {
            background: #d4edda !important;
            color: #286816;
            text-align: center !important;
            font-family: 'Inter', sans-serif;
            padding: 12px 15px;
            font-weight: 600;
            white-space: nowrap;
            font-size: 14px;
        }

        .menu-table td {
            font-size: 14px;
            text-align: center;
            padding: 12px 15px;
            font-family: 'Inter', sans-serif;
        }


        .menu-table tr:not(:last-child) td {
            border-bottom: 1px solid #e2e8f0;
        }

        .menu-table tbody tr:nth-child(even) td {
            background-color: #ffffff;
        }

        .menu-table tbody tr:nth-child(odd) td {
            background-color: #f0f8f0;
        }

        .menu-table tr:hover td {
            background-color: white;
        }

        .menu-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .menu-table td:nth-child(1) {
            font-weight: 600;
        }

        .menu-table form {
            display: inline-block;
            margin-right: 15px;
        }
        
        /* Action buttons */
        .action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 0;
            height: 40px;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            padding: 0;
            margin: 0 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: none;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .action-btn i {
            font-size: 19px !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-btn i { color: #28a745; }
        .delete-btn i { color: #dc3545; }
        .action-btn:hover { transform: scale(1.1); }

        /* Toast notification */
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

        .toast.show {
            visibility: visible;
            transform: translateX(0);
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

        .toast.show .toast-progress {
            animation: progress 1.5s linear forwards;
        }

        @keyframes progress {
            to {
                transform: scaleX(0);
            }
        }

        /* Focus states */
        *:focus {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25) !important;
        }

        .action-btn:focus {
            border-radius: 8px;
        }


        .dataTables_filter input:focus,
        .dataTables_length select:focus {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25) !important;
        }

        .modal .btn:focus,
        .dataTables_wrapper .dataTables_paginate .paginate_button:focus {
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25) !important;
            outline: none !important;
        }

        /* DataTables specific styles */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 30px !important;
            padding: 3px 3px !important;
            background-color: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb !important;
            width: 150px !important; /* Adjusted width */
        }

        .dataTables_wrapper .dataTables_length select {
           
            padding: 2px 2px !important;
            background-color: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb !important;
           
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: none !important;
            padding: 5px 12px !important;
            margin: 0 3px !important;
            border-radius: 4px !important;
            background: #d4edda !important;
            color: #155724 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #c3e6cb !important;
            color: #155724 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #c3e6cb !important;
            color: #155724 !important;
            font-weight: 500 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: rgb(55, 146, 76) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            background: #e9ecef !important;
            color: #155724 !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
        }

        .dataTables_info {
            color: #155724 !important;
            padding-top: 15px !important;
        }

        .dataTables_filter button {
            border-radius: 6px !important;
            padding: 6px 12px !important;
            background-color: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb !important;
            margin-left: 10px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <main>
        <section class="manage-menu">
            <div class="main-container">
                <section class="right-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="section-title">Menu Items</h2>
                        <!-- Removed Add Product button -->
                    </div>

                    <!-- Toast Notification -->
                    <div class="toast-container">
                        <div id="deleteSuccessToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header">
                                <strong class="me-auto">Success</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                <?php if (isset($_SESSION['delete_message'])) echo htmlspecialchars($_SESSION['delete_message']); ?>
                            </div>
                        </div>
                    </div>

                    <table id="menuTable" class="menu-table">
                        <thead>
                            <tr>
                                <th>Product Code</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Colors</th>
                                <th>Sizes</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 600; color: #155724;"><?= !empty($product['product_code']) ? htmlspecialchars($product['product_code']) : '<span style="color: #999;">N/A</span>' ?></td>
                                    <td><img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Product Image"></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : '<span style="color: #999;">N/A</span>' ?></td>
                                    <td><?= htmlspecialchars($product['description']) ?></td>
                                    <td><?= !empty($product['colors']) ? htmlspecialchars($product['colors']) : '<span style="color: #999;">N/A</span>' ?></td>
                                    <td><?= !empty($product['available_sizes']) ? htmlspecialchars($product['available_sizes']) : '<span style="color: #999;">N/A</span>' ?></td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" action="editproduct.php" style="margin: 0;">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <button type="submit" class="action-btn edit-btn" title="Edit Product">
                                                    <i class="bi bi-pencil-square"></i> <!-- Removed 'text-primary' class -->
                                                  
                                                </button>
                                            </form>
                                            <?php if ($product['name'] === 'Featured'): ?>
                                                <span class="action-btn delete-btn disabled" title="Cannot delete featured product">
                                                    <i class="bi bi-trash"></i>
                                                   
                                                </span>
                                            <?php else: ?>
                                                <button type="button" class="action-btn delete-btn" data-toggle="modal" data-target="#confirmationModal" data-product-id="<?= $product['id'] ?>" title="Delete Product">
                                                    <i class="bi bi-trash"></i>
                                                  
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </section>
    </main>

    <!-- Bootstrap Confirmation Modal -->
    <div id="confirmationModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this product?
            </div>
            <div class="modal-footer">
                <form method="POST" action="adminmenu.php" style="display: inline;">
                    <input type="hidden" name="product_id_to_delete" id="productIdToDelete">
                    <button type="submit" name="delete_product" class="btn btn-success">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#menuTable').DataTable();

            // Handle delete button click event to pass product ID to modal
            $('.delete-btn').on('click', function() {
                var productId = $(this).data('product-id');
                $('#productIdToDelete').val(productId);
            });

            // Updated toast notification function
            function showSuccessToast() {
                const toast = document.getElementById('success-toast');
                if (toast) {
                    toast.classList.add('show');
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 1500);
                }
            }

            // Call showSuccessToast immediately if toast element exists
            const toast = document.getElementById('success-toast');
            if (toast) {
                showSuccessToast();
            }
        });
    </script>
</body>
</html>
