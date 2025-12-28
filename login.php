<?php
session_start();
require_once "includes/connection.php";

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo "<script>window.location.href='menu.php';</script>";
    exit();
}
?>

<?php require "includes/header.php"; ?>

<!-- Add FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


<style>
    /* Attempt to hide default navigation from header.php */
    nav, .navbar, .header-area, body > header {
        display: none !important;
    }

    /* Add background to body and html to prevent white space */
    body, html {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        width: 100%;
        background: linear-gradient(135deg, #fcfcfc 0%, #e8f5e9 100%);
        overflow-x: hidden;
    }

    /* keep overflow-x hidden only on the page root; avoid overflow hidden on other containers */
    html, body { overflow-x: hidden; }

    /* Login Overlay & Animation Styles */

    /* Custom Shopee-style Header */
    .shopee-header {
        background-color: #ffffff;
        box-shadow: 0 6px 6px -6px rgba(0,0,0,0.05);
        width: 100%;
        height: 90px; /* Increased height from 84px */
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
    }

    .shopee-header-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .shopee-brand-wrapper {
        display: flex;
        align-items: center;
        gap: 20px; /* Increased gap */
    }

    .shopee-logo {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    
    .shopee-logo img {
        height: 65px; /* Increased logo size from 55px */
        width: auto;
    }

    .shopee-page-title {
        font-size: 26px; /* Increased font size */
        color: #222;
        margin-top: 4px;
    }

    .shopee-help {
        color: #28a745; /* Green */
        text-decoration: none;
        font-size: 15px; /* Slightly larger text */
        font-weight: 500;
    }

    /* Main Layout */
    .login-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 100px); /* Increased to reduce footer visibility */
        width: 100%;
        padding: 20px;
        padding-top: 120px; /* Adjusted top padding */
        padding-bottom: 60px; /* Increased bottom padding */
        background: linear-gradient(135deg, #fcfcfc 0%, #e8f5e9 100%); /* Reverted to light green gradient */
        box-sizing: border-box;
    }

    .login-content {
        display: flex;
        width: 100%;
        max-width: 1200px;
        justify-content: center; /* Center alignment */
        align-items: center;
        gap: 80px; /* Increased gap for better spacing */
        padding: 0 20px;
        margin-left: 100px; /* Match registration page */
        box-sizing: border-box;
    }

    /* Left side branding */
    .login-branding {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        max-width: 500px;
    }

    .branding-logo {
        max-width: 100%;
        width: 350px;
        height: auto;
        filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1)); /* Softer shadow for light bg */
    }

    .branding-text {
        color: #28a745; /* Changed to green for visibility on light background */
        font-size: clamp(24px, 3vw, 36px); /* Responsive font size */
        font-weight: 800; /* Bolder font */
        margin-top: 20px;
        text-align: center;
        letter-spacing: -0.5px; /* Tighter spacing for modern look */
        line-height: 1.2;
    }
    
    .branding-subtext {
        color: #555;
        font-size: clamp(14px, 1.5vw, 18px); /* Responsive font size */
        margin-top: 10px;
        text-align: center;
        font-weight: 400;
        line-height: 1.4;
    }

    /* Card styling */
    .form-container1 {
        background: #fff;
        padding: 28px; /* reduced from 32px 32px 26px */
        border-radius: 12px; /* Increased from 4px */
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 550px;
        flex-shrink: 0;
        box-sizing: border-box;
    }

    .form-title {
        font-size: 22px;
        margin-bottom: 10px;
        color: #333;
        font-weight: 650;
        letter-spacing: -0.2px;
        text-align: center;
        margin-bottom: 10px;
    }

    .form-title::after {
        content: "";
        display: block;
        width: 64px;
        height: 3px;
        margin: 12px auto 0;
        border-radius: 999px;
        background: rgba(40, 167, 69, 0.35);
    }

    .form-field {
        margin-bottom: 16px; /* tighter + consistent */
    }

    .input-group {
        position: relative;
    }
    
    .form-field input {
        width: 100%;
        padding: 12px 15px;
        height: 40px;          /* make height 40px */
        border: 1px solid #e0e0e0;
        border-radius: 12px;   /* slightly rounder */
        background: #fff;
        box-sizing: border-box;
        font-size: 15px;       /* slightly bigger */
        transition: border-color 0.3s;
    }

    .form-field input::placeholder {
        color: #9aa0a6;
    }

    .form-field input:focus {
        border-color: #28a745; /* Green */
        outline: none;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.18); /* visible focus ring */
    }

    /* Password toggle */
    .toggle-password {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px; /* fits inside 40px input */
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        cursor: pointer;
        color: #9aa0a6;
        transition: background-color 0.15s, color 0.15s;
    }

    .toggle-password:hover {
        background-color: rgba(0,0,0,0.04);
        color: #555;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 12px 0 22px;   /* better breathing room */
        font-size: 14px;
    }

    .remember-me {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #666;
        cursor: pointer;
    }

    .remember-me input {
        accent-color: #28a745;
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        height: 40px;          /* make height 40px */
        background-color: #28a745; /* Green */
        color: white;
        border: none;
        border-radius: 14px;
        font-size: 15.5px;
        font-weight: 650;
        letter-spacing: 0.4px;
        cursor: pointer;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: opacity 0.2s, background-color 0.2s;
    }

    .login-btn:hover {
        background-color: #218838; /* Darker Green */
    }

    .login-btn:active {
        transform: translateY(1px);
    }
    
    /* Divider */
    .divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 22px 0;        /* clearer separation */
        color: #ccc;
        font-size: 12px;
    }
    .divider::before, .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #e0e0e0;
    }
    .divider span {
        padding: 0 10px;
        color: #888;
    }

    /* Social Login */
    .social-login {
        display: flex;
        gap: 12px;
        margin-bottom: 18px;   /* align with register-link spacing */
    }
    
    .social-btn {
        flex: 1;
        padding: 8px;
        height: 40px;          /* make height 40px */
        border: 1px solid #e0e0e0;
        border-radius: 14px;
        background: #fff;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #555;
    }
    
    .social-btn:hover {
        background: #f8f8f8;
    }

    /* Align inline SVG icon like FontAwesome icons */
    .social-btn .social-icon {
        width: 18px;
        height: 18px;
        display: inline-block;
        vertical-align: middle;
        flex: 0 0 auto;
    }

    .register-link {
        text-align: center;
        font-size: 14.5px;
        color: #888;
        margin-top: 14px;
    }

    /* App Download Section */
    .footer-app-download {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .app-icon {
        padding: 5px 10px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        font-size: 12px;
        color: #666;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        background: #fff;
    }

    .app-icon:hover {
        border-color: #28a745;
        color: #28a745;
    }

    @media (max-width: 768px) {
        .footer-container {
            grid-template-columns: 1fr;
            gap: 25px;
        }
        
        .footer-bottom {
            flex-direction: column;
            text-align: center;
            justify-content: center;
        }
    }

    /* Mobile: make card a bit more breathable */
    @media (max-width: 480px) {
        .shopee-header {
            height: 70px;
        }
        
        .shopee-logo img {
            height: 50px;
        }
        
        .login-wrapper {
            padding: 15px;
            padding-top: 90px; /* Adjusted for mobile */
            padding-bottom: 30px;
        }
        
        .form-container1 {
            padding: 20px; /* reduced from 22px */
            border-radius: 14px;
        }

        .social-login {
            flex-direction: column;
        }

        .social-btn {
            width: 100%;
        }
    }

    /* Very small zoom out - ensure content stays centered */
    @media (min-width: 1400px) {
        .login-content {
            max-width: 1400px;
        }
    }
</style>

<!-- Custom Shopee-style Header -->
<div class="shopee-header">
    <div class="shopee-header-container">
        <div class="shopee-brand-wrapper">
            <a href="index.php" class="shopee-logo">
                <!-- Using image for logo as requested -->
                <img src="./assets/images/logo.png" alt="CavShop Logo">
            </a>
        </div>
        <a href="#" class="shopee-help">Need Help?</a>
    </div>
</div>

<section class="login-wrapper">
    <div class="login-content">
        <!-- Left Side: Big Logo/Branding -->
        <div class="login-branding">
            <img src="./assets/images/logo.png" alt="CavShop Branding" class="branding-logo">
            <div class="branding-text">
             
            </div>
            <div class="branding-subtext">
            Your trusted online shopping destination for quality products and exceptional service throughout Cavite.
            </div>
        </div>



        <!-- Right Side: Login Form -->
        <div class="form-container1">
            <div class="form-title">Log In</div>
            
            <form id="loginForm" action="#" method="POST">
                <!-- Login Information -->
                <div class="form-field">
                    <div class="input-group">
                        <input type="text" id="username" name="username" placeholder="Username" required>
                    </div>
                </div>

                <div class="form-field">
                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-actions">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot.php" style="color: #28a745; text-decoration: none;">Forgot Password</a>
                </div>

                <button type="submit" name="login" class="login-btn">LOG IN</button>
                
                <div class="divider">
                    <span>OR</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn">
                        <i class="fab fa-facebook" style="color: #4267B2; font-size: 18px;"></i> Facebook
                    </button>
                    <button type="button" class="social-btn">
                        <svg class="social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="18px" height="18px">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            <path fill="none" d="M0 0h48v48H0z"/>
                        </svg> Google
                    </button>
                </div>

                <div class="register-link">
                    New to CavShop? <a href="new-login.php" style="color: #28a745; font-weight: 500;">Sign Up</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require "includes/footer.php"; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const loginForm = document.getElementById('loginForm');
    const overlay = document.getElementById('loginOverlay');
    
    // AJAX Login Handler
    if(loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show Overlay
            if (typeof showGlobalLoader === 'function') {
                showGlobalLoader('', '');
            }
            
            const formData = new FormData(this);
            
            fetch('authenticate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Redirect after a short delay to make the animation feel longer
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    // Hide overlay and show error
                    if (typeof hideGlobalLoader === 'function') hideGlobalLoader();
                    alert(data.message || 'Login failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof hideGlobalLoader === 'function') hideGlobalLoader();
                alert('An error occurred. Please try again.');
            });
        });
    }

    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const isHidden = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isHidden ? 'text' : 'password');

            // keep icon in sync with visibility
            this.classList.toggle('fa-eye', isHidden);
            this.classList.toggle('fa-eye-slash', !isHidden);
        });
    }
});
</script>
