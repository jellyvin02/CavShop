<?php
session_start();
require_once 'includes/connection.php'; // Ensure database connection

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);  // Add username
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $usertype = $_POST['usertype'];
    $municipality = isset($_POST['municipality']) ? trim($_POST['municipality']) : ''; // Add municipality
    $street_number = trim($_POST['street_number']); // Add street_number

    // Check if password and confirm password match
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username already exists
        $query = "SELECT * FROM admin_users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error_message = "Username already exists!";
        } else {
            // Insert the new user with is_verified set to 1 (verified by admin)
            $query = "INSERT INTO admin_users (username, first_name, last_name, email, password, usertype, municipality, street_number, is_verified) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";  // Include municipality and street_number
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("ssssssss", $username, $first_name, $last_name, $email, $hashed_password, $usertype, $municipality, $street_number); // Bind municipality and street_number
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "User created successfully and verified!";
                    header("Location: users.php"); // Redirect to the user list page or wherever you want
                    exit;
                } else {
                    $error_message = "Error creating user: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Error preparing statement: " . $conn->error;
            }
        }
    }
} else {
    // Clear session message on page load if any
    unset($_SESSION['success_message']);
}
?>

<?php require "includes/adminside.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .form-container {
            max-width: 1900px;
            margin-left: 240px;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-bottom: 20px;
            color: hsl(115, 29%, 45%);
        }
        .form-field {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .button {
            background-color: hsl(115, 29%, 45%);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .button:hover {
            background-color: hsl(115, 29%, 40%);
        }
        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #c82333;
        }
        .form-container label {
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
        .form-container select,
        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-container input[type="password"]::placeholder {
            color: #888;
        }
        .form-container .form-field:focus {
            border-color: hsl(115, 29%, 45%);
            outline: none;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Create New User</h2>
    <?php if (isset($error_message)): ?>
        <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
    <?php elseif (isset($_SESSION['success_message'])): ?>
        <p class="success-message"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <form method="POST">
        <input class="form-field" type="text" name="first_name" placeholder="First Name" required>
        <input class="form-field" type="text" name="last_name" placeholder="Last Name" required>
        <input class="form-field" type="text" name="username" placeholder="Username" required>
        <input class="form-field" type="email" name="email" placeholder="Email" required>
        <input class="form-field" type="password" name="password" placeholder="Password" required>
        <input class="form-field" type="password" name="confirm_password" placeholder="Confirm Password" required>
        <input class="form-field" type="text" name="municipality" placeholder="Municipality" required> <!-- Add municipality field -->
        <input class="form-field" type="text" name="street_number" placeholder="Street Number" required> <!-- Add street_number field -->
        <select class="form-field" name="usertype" required>
            <option value="">Select User Type</option>
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
        </select>

        <!-- Button container for Save and Cancel -->
        <div style="display: flex; justify-content: flex-start; gap: 10px; margin-top: 15px;">
            <button class="button" type="submit">Save</button>
            <a href="users.php" class="button cancel-btn">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
