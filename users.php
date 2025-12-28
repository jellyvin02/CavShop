<?php
session_start();
require_once 'includes/connection.php';

$users_result = mysqli_query($conn, "SELECT * FROM admin_users");

// If a user deletion is requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = $_POST['user_id_to_delete'];
    
    // Prevent SQL injection by using prepared statements
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_delete);
    
    if ($stmt->execute()) {
        $_SESSION['delete_message'] = "User deleted successfully!";
    } else {
        $_SESSION['delete_message'] = "Error deleting user.";
    }

    $stmt->close();
    
    // Redirect to the same page to show the message
    header("Location: users.php");
    exit;
}
?>

<?php require "includes/adminside.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        body,{
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .main-container {
            max-width: 1900px;
            padding: 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            overflow-x: auto; /* Added to fix overflow issue */
        }

        /* Table Design */
        #userTable {
            border-collapse: collapse;
            overflow: hidden;
        }

        #userTable thead th {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            color: #155724;
            padding: 15px 10px;
            border-bottom: 2px solid #e2e8f0;
            background-color: #d4edda;
        }
        table.dataTable tbody td {
            font-family: 'Inter', sans-serif;
            font-weight: 400; /* Added font weight */
            font-size: 0.95rem;
            padding: 12px 10px;
            vertical-align: middle;
        }

        #userTable tbody tr:nth-child(even) td {
            background-color: #ffffff;
        }

        #userTable tbody tr:nth-child(odd) td {
            background-color: #f0f8f0;
        }

        #userTable tbody tr:hover td {
            background-color: #e1f5e1;
        }

        #userTable td {
            font-size: 14px;
            text-align: left;
            padding: 12px 15px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }

        #userTable_length select,
        #userTable_filter input {
            border-radius: 9px;
            padding: 6px 12px;
            border: 1px solid #ddd;
        }

        .dataTables_paginate a:hover {
            background-color: #f0f8f0 !important;
        }

        .dataTables_paginate {
            margin-top: 9px;
        }

        .dataTables_info {
            margin-top: 9px;
        }

        .create-user-btn {
            background: #d4edda !important;
            color: #155724 !important;
            padding: 10px 20px;
            border: none;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            margin-left: auto;
            border-radius: 14px;
            transition: background-color 0.3s ease;
            float: right;
            margin-bottom: 10px;
        }

        .create-user-btn:hover {
            background-color: hsl(145, 60%, 40%);
        }

        .section-title {
            font-size: 36px;
            color: hsl(115, 29%, 45%);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .d-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-footer .btn-success {
            background-color: #28a745;
            border: none;
        }

        .toast-container {
            position: fixed;
            top: 70px;
            right: 20px;
            transform: none;
            width: 300px;
            z-index: 9999;
        }

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

        table.dataTable thead th {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            color: #155724;
            padding: 15px 10px;
            border-bottom: 2px solid #e2e8f0;
            background-color: #d4edda;
        }

        table.dataTable tbody td {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 0.95rem;
            padding: 12px 10px;
            vertical-align: middle;
        }
        
        

        /* Show entries dropdown styling */
        .dataTables_length select {
            background-position: right 8px center !important;
            padding-right: 30px !important;
            width: auto !important;
            display: inline-block !important;
        }

        .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_length label {
            font-family: 'Inter', sans-serif;
            color: #155724;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-right: 1px solid #e2e8f0;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* Updated trash icon */
        .bi-trash {
            color: #dc3545;
            font-size: 1.1rem;
            transition: color 0.2s, transform 0.2s;
        }

        .bi-trash:hover {
            color: #c82333;
            transform: scale(1.1);
        }

        /* Show entries dropdown styling */
        .dataTables_length select {
            background-position: right 8px center !important;
            padding-right: 30px !important;
            width: auto !important;
            display: inline-block !important;
        }

        .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_length label {
            font-family: 'Inter', sans-serif;
            color: #155724;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .dataTables_wrapper .dataTables_length select {
            font-weight: 500;
            padding: 6px 36px 6px 12px;
            background: #28a745 url("data:image/svg+xml,<svg height='10px' width='10px' viewBox='0 0 16 16' fill='%23ffffff' xmlns='http://www.w3.org/2000/svg'><path d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/></svg>") no-repeat;
            background-position: right 8px center !important;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        /* Updated dropdown styling */
        .dataTables_wrapper .dataTables_length select {
            font-weight: 500;
            padding: 6px 12px;
            background: #d4edda url("data:image/svg+xml,<svg height='10px' width='10px' viewBox='0 0 16 16' fill='%23155724' xmlns='http://www.w3.org/2000/svg'><path d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a 1 1 0 0 1-1.506 0z'/></svg>") no-repeat;
            background-position: right 8px center !important;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            cursor: pointer;
            margin: 0 8px;
        }

        /* Search box styling */
        .dataTables_filter label {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            color: #155724;
        }

        .dataTables_filter input {
            border: 1px solid #e2e8f0 !important;
            border-radius: 6px !important;
            padding: 6px 12px !important;
            margin-left: 8px !important;
        }

        /* Delete button icon styling */
        .btn-icon {
            background: none;
            border: none;
            padding: 0;
            width: auto;
            color: #dc3545;
            font-size: 1.2rem;
            transition: color 0.2s;
        }

        .btn-icon:hover {
            color: #c82333;
            transform: none;
            box-shadow: none;
        }

        .btn-icon:focus {
            outline: none;
        }

        /* DataTables Pagination Styling */
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 15px;
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
            background:rgb(55, 146, 76) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            background: #e9ecef !important;
            color: #155724 !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
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


        .dataTables_info {
            color: #155724 !important;
            padding-top: 15px !important;
        }

        .action-buttons {
            display: inline-flex;
            gap: 0;
            align-items: center;
        }

        .btn-icon {
            padding: 4px;
            margin: 0;
        }

        /* Fix table class names */
        table.dataTable {
            margin-top: 20px !important;
            margin-bottom: 20px !important;
            border-spacing: 0 8px !important;
            border-collapse: separate !important;
        }

        .datatable-wrapper {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        /* Fix class name for menu-table to match DataTable */
        #userTable.menu-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Fix cell spacing */
        table.dataTable tbody td {
            padding: 8px !important;
            vertical-align: middle;
            border-spacing: 0;
        }

        table.dataTable {
            border-collapse: collapse !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .table td, .table th {
            padding: 8px;
            vertical-align: middle;
        }

        .action-buttons .btn-icon:first-child {
            margin-right: -4px;
        }

        /* Add this to your existing styles */
        .btn-icon.disabled {
            color: #adb5bd;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.5;
        }

        /* Action buttons styling */
        .btn-icon {
            background: none;
            border: none;
            padding: 4px;
            width: auto;
            font-size: 1.2rem;
            transition: all 0.2s;
            color: #dc3545; /* Default color for trash icon */
        }

        .btn-icon:hover {
            transform: scale(1.1);
            box-shadow: none;
        }

        .btn-icon:focus {
            outline: none;
        }

        .bi-pencil-square {
            color: #28a745;
        }

        .bi-pencil-square:hover {
            color: #218838;
        }

        .bi-trash {
            color: #dc3545;
        }

        .bi-trash:hover {
            color: #c82333;
        }

        .btn-icon.disabled {
            color: #adb5bd;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.5;
        }
        
    </style>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
<main>
    <section class="manage-users">
        <div class="main-container">
            <h2 class="section-title">Manage Users</h2>
            <div class="d-flex justify-content-between">
                <a href="createuser.php" class="btn btn-success create-user-btn">
                    <i class="fas fa-user-plus"></i> Create User
                </a>
            </div>

            <div class="datatable-wrapper">
                <table id="userTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['id']) ?></strong></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['first_name']) ?></td>
                                <td><?= htmlspecialchars($user['last_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><strong><?= ucfirst(htmlspecialchars($user['usertype'])) ?></strong></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" action="edituser.php" style="margin: 0;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn-icon" title="Edit User">
                                                <i class="bi bi-pencil-square"></i> <!-- Removed 'text-primary' class -->
                                            </button>
                                        </form>
                                        <?php if ($user['username'] === 'admin'): ?>
                                            <span class="btn-icon disabled" title="Cannot delete admin user">
                                                <i class="bi bi-trash"></i>
                                            </span>
                                        <?php else: ?>
                                            <button type="button" class="btn-icon" data-toggle="modal" data-target="#deleteModal" data-userid="<?= $user['id'] ?>" title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this user?
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="user_id_to_delete" id="deleteUserId">
                    <button type="submit" name="delete_user" class="btn btn-success">Delete</button>
                </form>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<?php if (isset($_SESSION['delete_message'])): ?>
<div id="success-toast" class="toast">
    <div class="toast-content">
        <i class="fas fa-check-circle toast-icon"></i>
        <div class="toast-message"><?php echo htmlspecialchars($_SESSION['delete_message']); ?></div>
    </div>
    <div class="toast-progress"></div>
</div>
<?php endif; ?>


<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#userTable').DataTable();

    // Show delete user ID in the modal
    $('#deleteModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var userId = button.data('userid'); // Extract user ID from data-* attributes
        var modal = $(this);
        modal.find('#deleteUserId').val(userId);
    });

    // Show toast if delete message exists
    <?php if (isset($_SESSION['delete_message'])): ?>
    var toast = document.getElementById("success-toast");
    if (toast) {
        toast.classList.add("show");
        setTimeout(function(){ 
            toast.classList.remove("show"); 
        }, 1500);
    }
    <?php unset($_SESSION['delete_message']); ?>
    <?php endif; ?>
});
</script>
</body>
</html>

