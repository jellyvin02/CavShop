<?php
require_once 'includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id_to_delete'])) {
    $user_id = intval($_POST['user_id_to_delete']);

    // Delete user
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: users.php?message=User+deleted+successfully");
    } else {
        echo "Error deleting user.";
    }
} else {
    header("Location: users.php"); // Redirect if accessed directly
    exit;
}
?>
