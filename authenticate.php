<?php
session_start();
header('Content-Type: application/json');
require_once "includes/connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];

    // Basic Validation
    if (empty($username) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
        exit;
    }

    // Query User
    $query = "SELECT * FROM customer_users WHERE username='$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify Password
        if (password_verify($password, $user['password'])) {
            // Success
            $_SESSION['logged_in'] = true;
            $_SESSION['email'] = $user['email'];
            
            echo json_encode([
                "success" => true,
                "message" => "Login Successful!",
                "redirect" => "menu.php"
            ]);
        } else {
            // Invalid Password
            echo json_encode(["success" => false, "message" => "Incorrect username or password."]);
        }
    } else {
        // User not found
        echo json_encode(["success" => false, "message" => "Username not registered! Please register first."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
$conn->close();
?>
