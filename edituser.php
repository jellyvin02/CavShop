<?php
session_start();
require_once 'includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // Fetch user details
    $query = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "User not found!";
        exit;
    }

    // Update user details on form submission
    if (isset($_POST['update_user'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $usertype = mysqli_real_escape_string($conn, $_POST['usertype']);
        
        // Password handling
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Check if the passwords match
        if (!empty($password) && $password !== $confirm_password) {
            $error_message = "Passwords do not match!";
        } else {
            // If passwords match or are empty, proceed with the update
            if (!empty($password)) {
                // Hash the new password
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                
                // Update query with password
                $update_query = $conn->prepare("UPDATE admin_users SET username = ?, first_name = ?, last_name = ?, email = ?, usertype = ?, password = ? WHERE id = ?");
                $update_query->bind_param("ssssssi", $username, $first_name, $last_name, $email, $usertype, $password_hashed, $user_id);
            } else {
                // Update query without password if no new password is entered
                $update_query = $conn->prepare("UPDATE admin_users SET username = ?, first_name = ?, last_name = ?, email = ?, usertype = ? WHERE id = ?");
                $update_query->bind_param("sssssi", $username, $first_name, $last_name, $email, $usertype, $user_id);
            }
            
            if ($update_query->execute()) {
                header("Location: users.php?message=User updated successfully");
                exit;
            } else {
                $error_message = "Error updating user.";
            }
        }
    }
} else {
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
    <title>Edit User</title>
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
        .form-field[readonly] {
            background-color: #f0f0f0; /* Light gray */
            color: hsl(115, 29%, 45%);
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
        .error-message {
            color: red;
            margin-bottom: 10px;
        }
        .form-container label {
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
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
        .form-container input[type="text"]:focus,
        .form-container input[type="email"]:focus,
        .form-container input[type="password"]:focus {
            border-color: hsl(115, 29%, 45%);
            outline: none;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit User</h2>
        <form method="POST" action="edituser.php" onsubmit="return validatePasswords()">
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

            <label for="username">Username:</label>
            <input class="form-field" type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" readonly><br>

            <label for="first_name">First Name:</label>
            <input class="form-field" type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required><br>

            <label for="last_name">Last Name:</label>
            <input class="form-field" type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required><br>

            <label for="email">Email:</label>
            <input class="form-field" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>

            <label for="usertype">User Type:</label>
            <input class="form-field" type="text" name="usertype" value="<?= htmlspecialchars($user['usertype']) ?>" readonly><br>

            <label for="password">Password:</label>
            <input class="form-field" type="password" name="password" id="password" placeholder="Enter new password (optional)"><br>

            <label for="confirm_password">Confirm Password:</label>
            <input class="form-field" type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password"><br>

            <?php if (isset($error_message)): ?>
                <div class="error-message"><?= $error_message ?></div>
            <?php endif; ?>

            <button class="button" type="submit" name="update_user">Update</button>
        </form>
    </div>

    <script>
        function validatePasswords() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
