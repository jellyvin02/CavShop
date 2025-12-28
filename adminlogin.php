<?php
session_start();
require_once "includes/connection.php"; // Ensure your database connection is correct

// Handle AJAX Login
if (isset($_POST['ajax_login'])) {
    header('Content-Type: application/json');
    
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];

    // Query to fetch the user
    $query = "SELECT * FROM admin_users WHERE LOWER(username) = LOWER('$username')"; 
    $result = mysqli_query($conn, $query);

    if ($result) {
        if (mysqli_num_rows($result) == 1) {
            $result_fetch = mysqli_fetch_assoc($result);
            
            if ($result_fetch['is_verified'] == 1) {
                if (password_verify($password, $result_fetch['password'])) {
                    // Success
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $result_fetch['username'];
                    echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Incorrect username or password']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Username not verified! Please verify your email and try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Username not registered!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
    exit();
}

// Fallback for non-JS (Standard POST)
if (isset($_POST['login'])) {
    // Sanitize and trim the input username to prevent any unwanted spaces
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));

    // Query to fetch the user from the database using a case-insensitive comparison
    $query = "SELECT * FROM admin_users WHERE LOWER(username) = LOWER('$username')"; // Ensure case-insensitive comparison

    // Run the query and check for errors
    $result = mysqli_query($conn, $query);

    if ($result) {
        if (mysqli_num_rows($result) == 1) {
            $result_fetch = mysqli_fetch_assoc($result);
            
            // Check if the user is verified
            if ($result_fetch['is_verified'] == 1) {
                // Verify the password entered by the user
                if (password_verify($_POST['password'], $result_fetch['password'])) {
                    // Set session variables
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $result_fetch['username'];

                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit; // Don't forget to call exit after header redirection
                } else {
                    echo "
                    <script>
                        alert('Incorrect username or password');
                        window.location.href='adminlogin.php';
                    </script>
                    ";
                }
            } else {
                echo "
                <script>
                    alert('Username not verified! Please verify your email and try again.');
                    window.location.href='adminlogin.php';
                </script>
                ";
            }
        } else {
            echo "
            <script>
                alert('Username not registered! Please register first');
                window.location.href='adminlogin.php';
            </script>
            ";
        }
    } else {
        echo "
        <script>
            alert('UNKNOWN ISSUE: Cannot run your request!');
            window.location.href='new-login.php';
        </script>
        ";
    }
}
?>

<section class="admin-login">
    <div class="login-container">
        <div class="logo-title-container">
            <div class="logo-wrapper">
                <img id="logo-img" src="assets/images/logo.png" alt="Logo">
            </div>
            <div class="text-container">
                
                <p class="subtitle">Sign in to continue to your dashboard</p>
            </div>
        </div>

        <form action="#" method="POST" class="login-form">
            <div class="form-group">
                <input type="text" id="username" name="username" required>
                <label for="username">Username</label>
                <i class="fas fa-user"></i>
                <div class="input-highlight"></div>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" required>
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <div class="input-highlight"></div>
                <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit" name="login" class="login-btn">
                <span>Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
            
            <div class="footer-links">
                <a href="forgot.php" class="forgot-link">Forgot password?</a>
            </div>
        </form>
    </div>
    
    <!-- Loader HTML -->
    <div id="globalLoadingOverlay" class="loading-overlay">
        <div class="spinner-card">
            <div class="custom-spinner"></div>
            <div class="loading-text" id="globalLoadingText">Verifying Admin...</div>
            <div class="loading-subtext" id="globalLoadingSubtext">Please wait a moment</div>
        </div>
    </div>
</section>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">

<style>
:root {
    --primary: #388e3c;
    --primary-dark: #2e7d32;
    --primary-light: rgba(56, 142, 60, 0.1);
    --text: #2c3e50;
    --text-light: #7f8c8d;
    --background: #f8f9fa;
    --white: #ffffff;
    --shadow: rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
    --input-bg: rgba(255, 255, 255, 0.9);
    --input-border: #e2e8f0;
    --input-focus: rgba(56, 142, 60, 0.1);
}

body {
    min-height: 100vh;
    margin: 0;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: 
        linear-gradient(120deg, rgba(56, 142, 60, 0.05) 0%, rgba(46, 125, 50, 0.1) 100%),
        linear-gradient(to right bottom, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%),
        url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.374zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83zM37.656 0l8.485 8.485-1.414 1.414L36.242 0h1.414zM22.344 0L13.858 8.485 15.272 9.9l8.485-8.485h-1.414zM32.515 0L41 8.485l-1.414 1.414L30.1 0h2.414zm-4.95 0L36.05 8.485l-1.414 1.414L25.15 0h2.414zm9.192 0L47.243 9.9l-1.414 1.415L35.414 0h1.343zM20.414 0L9.9 10.515l1.414 1.414L22.727 0h-2.313zm-4.95 0L4.95 10.515l1.414 1.414L17.778 0h-2.313zm9.192 0L35.414 11.9l-1.414 1.415L25.657 0h-1.343zm-13.435 0L0 11.222l1.414 1.414L13.778 0H11.22zm-4.95 0L0 6.272l1.414 1.414L8.828 0H6.272zM2.828 0L0 2.828l1.414 1.414L5.657 0H2.828zM54.627 60l.83-.828-1.415-1.415L51.8 60h2.827zm-49.254 0l-.83-.828L5.96 57.757 8.2 60H5.374zm43.597 0l3.657-3.657-1.414-1.414L46.143 60h2.828zm-37.94 0l-3.657-3.657 1.414-1.414L13.857 60H11.03zm32.284 0l6.485-6.485-1.414-1.414-7.9 7.9h2.83zm-26.656 0l-6.485-6.485 1.414-1.414 7.9 7.9h-2.83zm20.97 0l8.485-8.485-1.414-1.414L36.242 60h1.414zm-15.313 0l-8.485-8.485 1.414-1.414 8.485 8.485h-1.414zm10.172 0L41 51.515l-1.414-1.414L30.1 60h2.414zm-4.95 0l8.485-8.485-1.414-1.414L25.15 60h2.414zm9.192 0l10.515-10.515-1.414-1.414L35.414 60h1.343zm-23.743 0L9.9 49.485l1.414-1.414L22.727 60h-2.313zm-4.95 0l-10.515-10.515 1.414-1.414L17.778 60h-2.313zm9.192 0l10.515-10.515-1.414-1.414L25.657 60h-1.343zm-13.435 0L0 48.778l1.414-1.414L13.778 60H11.22zm-4.95 0L0 53.728l1.414-1.414L8.828 60H6.272zM2.828 60L0 57.172l1.414-1.414L5.657 60H2.828z' fill='%23388e3c' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E"),
        linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
    background-attachment: fixed;
}

.login-container {
    width: 100%;
    max-width: 420px;
    padding: 40px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.08),
        0 1px 2px rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.logo-title-container {
    text-align: center;
    margin-bottom: 40px;
}

.logo-wrapper {
    width: 150px;
    height: 150px;
    margin: 0 auto 20px;
    padding: 15px;
    background: transparent;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

#logo-img {
    width: 130%;
    height: 110%;
    object-fit: contain;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

.text-container {
    text-align: center;
    margin-top: 24px;
}

.welcome-title {
    color: var(--text);
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.5px;
}

.title {
    color: var(--primary);
    font-size: 24px;
    font-weight: 600;
    margin: 0;
}

.subtitle {
    color: var(--text-light);
    font-size: 15px;
    margin: 10px 0 0;
}

.login-form {
    margin-top: 32px;
}

.form-group {
    position: relative;
    margin-bottom: 24px;
}

.form-group input {
    width: 100%;
    padding: 16px;
    padding-left: 48px;
    padding-right: 45px; /* Adjusted right padding */
    background: var(--input-bg);
    border: 2px solid var(--input-border);
    border-radius: 16px;
    font-size: 15px;
    transition: all 0.3s ease;
    outline: none;
}

.form-group input:focus {
    border-color: var(--primary);
    background: var(--white);
    box-shadow: 0 0 0 4px var(--input-focus);
}

.input-highlight {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    width: 0;
    background: var(--primary);
    transition: all 0.3s ease;
}

.form-group input:focus ~ .input-highlight {
    width: 100%;
}

.form-group label {
    position: absolute;
    left: 40px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    transition: var(--transition);
    pointer-events: none;
}

.form-group input:focus,
.form-group input:valid {
    border-color: var(--primary);
}

.form-group input:focus ~ label,
.form-group input:valid ~ label {
    top: 0;
    left: 12px;
    font-size: 12px;
    padding: 0 4px;
    background: var(--white);
    color: var(--primary);
}

.form-group i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
}

.toggle-password {
    position: absolute;
    right: 20px; /* Adjusted from 16px */
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-light);
    cursor: pointer;
    padding: 8px;
    width: 32px; /* Added fixed width */
    height: 32px; /* Added fixed height */
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.toggle-password i {
    font-size: 15px; /* Adjusted icon size */
    line-height: 1;
}

.login-btn {
    position: relative;
    width: 100%;
    padding: 16px;
    background: var(--primary);
    color: var(--white);
    border: none;
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
}

.login-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(56, 142, 60, 0.2);
}

.login-btn span {
    z-index: 1;
}

.login-btn i {
    transition: transform 0.3s ease;
    z-index: 1;
}

.login-btn:hover i {
    transform: translateX(4px);
}

.login-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 120%;
    height: 120%;
    background: var(--primary-dark);
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    transition: transform 0.5s ease;
}

.login-btn:hover::before {
    transform: translate(-50%, -50%) scale(1);
}

.footer-links {
    margin-top: 20px;
    text-align: center;
}

.forgot-link {
    color: var(--text-light);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
}

.forgot-link:hover {
    color: var(--primary);
}

@media (max-width: 480px) {
    .login-container {
        padding: 32px 24px;
    }
    
    .welcome-title {
        font-size: 24px;
    }
    
    .form-group input {
        padding: 14px;
        padding-left: 44px;
    }
}

/* Loader CSS */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  z-index: 15000;
  display: none;
  justify-content: center;
  align-items: center;
  animation: fadeInOverlay 0.3s ease;
}

.loading-overlay.active {
  display: flex !important;
}

.spinner-card {
  background: transparent;
  padding: 0;
  box-shadow: none;
  border: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
  min-width: auto;
}

.custom-spinner {
  width: 45px;
  height: 45px;
  border: 3px solid rgba(40, 167, 69, 0.1);
  border-left-color: var(--primary);
  border-radius: 50%;
  animation: spinLoader 0.8s linear infinite;
}

.loading-text {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1a4221;
  margin: 0;
}

.loading-subtext {
  font-size: 0.9rem;
  color: #666;
  margin: 0;
}

@keyframes spinLoader {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

@keyframes fadeInOverlay {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.querySelector('.toggle-password');
    const loginForm = document.querySelector('.login-form');
    
    // Toggle Password
    if(togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }

    // Remove the input validation for password
    if(passwordInput) {
        passwordInput.addEventListener('input', function() {
            this.setCustomValidity(''); // Reset any custom validity
        });
    }
    
    // Loader Functions
    window.showGlobalLoader = function (text, subtext) {
        const overlay = document.getElementById('globalLoadingOverlay');
        const loadingText = document.getElementById('globalLoadingText');
        const loadingSubtext = document.getElementById('globalLoadingSubtext');

        if (overlay) {
            // Show/hide text elements based on whether content is provided
            if (loadingText) {
                loadingText.innerText = text || '';
                loadingText.style.display = text ? 'block' : 'none';
            }
            if (loadingSubtext) {
                loadingSubtext.innerText = subtext || '';
                loadingSubtext.style.display = subtext ? 'block' : 'none';
            }
            overlay.classList.add('active');
        }
    };

    window.hideGlobalLoader = function () {
        const overlay = document.getElementById('globalLoadingOverlay');
        if (overlay) overlay.classList.remove('active');
    };

    // AJAX Login Submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = passwordInput.value;
            
            if(!username || !password) return;
            
            // Show Loader
            showGlobalLoader('', '');
            
            const formData = new FormData(this);
            formData.append('ajax_login', '1'); // Trigger AJAX path in PHP
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Success: Wait a bit then redirect
                    showGlobalLoader('', '');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    // Error: Hide loader and show alert
                    hideGlobalLoader();
                    alert(data.message || 'Login failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                hideGlobalLoader();
                alert('An error occurred. Please try again.');
            });
        });
    }
});
</script>
