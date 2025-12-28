<?php
session_start();

require_once "includes/connection.php";

// Handle AJAX email check - must be before any HTML output
if(isset($_POST['check_email']) && isset($_POST['email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $query = "SELECT security_question, security_answer FROM `customer_users` WHERE `email`='$email'";
    $result = mysqli_query($conn, $query);
    
    header('Content-Type: application/json');
    if($result && mysqli_num_rows($result) == 1) {
        $user_data = mysqli_fetch_assoc($result);
        echo json_encode([
            'exists' => true,
            'has_security' => !empty($user_data['security_question']) && !empty($user_data['security_answer']),
            'security_question' => $user_data['security_question'] ?? ''
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit;
}

// Handle AJAX security answer verification
if(isset($_POST['verify_security']) && isset($_POST['email']) && isset($_POST['security_answer'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $security_answer = strtolower(trim(mysqli_real_escape_string($conn, $_POST['security_answer'])));
    
    $query = "SELECT security_answer FROM `customer_users` WHERE `email`='$email'";
    $result = mysqli_query($conn, $query);
    
    header('Content-Type: application/json');
    if($result && mysqli_num_rows($result) == 1) {
        $user_data = mysqli_fetch_assoc($result);
        $stored_answer = strtolower(trim($user_data['security_answer']));
        
        if($security_answer === $stored_answer) {
            echo json_encode(['verified' => true]);
        } else {
            echo json_encode(['verified' => false, 'message' => 'Security answer is incorrect. Please try again.']);
        }
    } else {
        echo json_encode(['verified' => false, 'message' => 'Email not found.']);
    }
    exit;
}

// Handle password reset after security answer verification
if(isset($_POST['update_password']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords match
    if($new_password !== $confirm_password)
    {
        echo "
        <script>
        alert('Passwords do not match. Please try again.');
        window.location.href='forgot.php';
        </script>
        ";
        exit;
    }

    // Validate password strength
    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/';
    if(!preg_match($passwordPattern, $new_password))
    {
        echo "
        <script>
        alert('Password must contain at least 8 characters, including at least one uppercase letter, one lowercase letter, and one number.');
        window.location.href='forgot.php';
        </script>
        ";
        exit;
    }

    // Hash the password and update
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $query = "UPDATE `customer_users` SET `password`='$hashed_password', `resettoken`=NULL, `resettokenexpire`=NULL WHERE `email`='$email'";

    if(mysqli_query($conn, $query))
    {
        echo "
        <script>
        alert('Password updated successfully! You can now login with your new password.');
        window.location.href='login.php';
        </script>
        ";
    }
    else
    {
        echo "
        <script>
        alert('UNKNOWN ISSUE: cannot run your request');
        window.location.href='forgot.php';
        </script>
        ";
    }
    exit;
}

if(isset($_POST['reset']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $security_answer = isset($_POST['security_answer']) ? strtolower(trim(mysqli_real_escape_string($conn, $_POST['security_answer']))) : '';

    // First check if email exists and get security question
    $query = "SELECT * FROM `customer_users` WHERE `email`='$email'";
    $result = mysqli_query($conn, $query);

    if($result)
    {
        if(mysqli_num_rows($result)==1)
        {
            $user_data = mysqli_fetch_assoc($result);
            
            // Check if security question and answer are set
            if(empty($user_data['security_question']) || empty($user_data['security_answer']))
            {
                // If no security question set, allow reset (for existing users)
                $reset_token = bin2hex(random_bytes(16));
                date_default_timezone_set('Asia/Kolkata');
                $date = date("Y-m-d");
                $query = "UPDATE `customer_users` SET `resettoken`='$reset_token', `resettokenexpire`='$date' WHERE `email`='$email'";

                if(mysqli_query($conn,$query))
                {
                    echo "
                    <script>
                    alert('Password reset token generated successfully. Please contact administrator to reset your password.');
                    window.location.href='login.php';
                    </script>
                    ";
                }
                else
                {
                    echo "
                    <script>
                    alert('UNKNOWN ISSUE: cannot run your request');
                    window.location.href='forgot.php';
                    </script>
                    ";
                }
            }
            else
            {
                // Verify security answer
                $stored_answer = strtolower(trim($user_data['security_answer']));
                if($security_answer === $stored_answer)
                {
                    // Security answer is correct - this should be handled via AJAX now
                    // This code path is for non-AJAX fallback
                    echo "
                    <script>
                    alert('Security answer verified. Please use the form to reset your password.');
                    window.location.href='forgot.php';
                    </script>
                    ";
                }
                else
                {
                    echo "
                    <script>
                    alert('Security answer is incorrect. Please try again.');
                    window.location.href='forgot.php';
                    </script>
                    ";
                }
            }
        }
        else
        {
            echo "
            <script>
            alert('Email not registered');
            window.location.href='forgot.php';
            </script>
            ";
        }
    }
    else
    {
        echo "
        <script>
        alert('UNKNOWN ISSUE: cannot run your request');
        window.location.href='forgot.php';
        </script>
        ";
    }
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

    /* Custom Shopee-style Header */
    .shopee-header {
        background-color: #ffffff;
        box-shadow: 0 6px 6px -6px rgba(0,0,0,0.05);
        width: 100%;
        height: 90px;
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
        gap: 20px;
    }

    .shopee-logo {
        display: flex;
        align-items: center;
        text-decoration: none;
    }
    
    .shopee-logo img {
        height: 65px;
        width: auto;
    }

    .shopee-page-title {
        font-size: 26px;
        color: #222;
        margin-top: 4px;
    }

    .shopee-help {
        color: #28a745;
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
    }

    /* Main Layout */
    .login-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 100px);
        width: 100%;
        padding: 20px;
        padding-top: 120px;
        padding-bottom: 60px;
        background: linear-gradient(135deg, #fcfcfc 0%, #e8f5e9 100%);
        box-sizing: border-box;
    }

    .login-content {
        display: flex;
        width: 100%;
        max-width: 1200px;
        justify-content: center;
        align-items: center;
        gap: 80px;
        padding: 0 20px;
        margin-left: 100px;
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
        filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));
    }

    .branding-text {
        color: #28a745;
        font-size: clamp(24px, 3vw, 36px);
        font-weight: 800;
        margin-top: 20px;
        text-align: center;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }
    
    .branding-subtext {
        color: #555;
        font-size: clamp(14px, 1.5vw, 18px);
        margin-top: 10px;
        text-align: center;
        font-weight: 400;
        line-height: 1.4;
    }

    /* Card styling */
    .form-container1 {
        background: #fff;
        padding: 28px;
        border-radius: 12px;
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
        margin-bottom: 16px;
    }

    .form-field label {
        display: block;
        margin-bottom: 6px;
        color: #333;
        font-size: 14px;
        font-weight: 500;
    }

    .input-group {
        position: relative;
    }
    
    .form-field input {
        width: 100%;
        padding: 12px 15px;
        height: 40px;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        background: #fff;
        box-sizing: border-box;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .form-field input[readonly] {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }

    .form-field input::placeholder {
        color: #9aa0a6;
    }

    .form-field input:focus {
        border-color: #28a745;
        outline: none;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.18);
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        height: 40px;
        background-color: #28a745;
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
        margin-top: 8px;
    }

    .login-btn:hover {
        background-color: #218838;
    }

    .login-btn:active {
        transform: translateY(1px);
    }

    .back-link {
        text-align: center;
        font-size: 14.5px;
        color: #888;
        margin-top: 18px;
    }

    .back-link a {
        color: #28a745;
        text-decoration: none;
        font-weight: 500;
    }

    .back-link a:hover {
        text-decoration: underline;
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
            padding-top: 90px;
            padding-bottom: 30px;
        }
        
        .login-content {
            margin-left: 0;
            flex-direction: column;
            gap: 40px;
        }

        .form-container1 {
            padding: 20px;
            border-radius: 14px;
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

        <!-- Right Side: Forgot Password Form -->
        <div class="form-container1">
            <div class="form-title">Reset Password</div>
            
            <form action="#" method="POST" id="forgotForm">
                <div class="form-field">
                    <label for="email">Email</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="Type your registered e-mail" required>
                    </div>
                </div>

                <div class="form-field" id="securitySection" style="display: none;">
                    <label for="security_question_display" id="securityQuestionLabel">Security Question</label>
                    <div class="input-group">
                        <input type="text" id="security_question_display" readonly>
                        <input type="hidden" id="security_question" name="security_question">
                    </div>
                </div>

                <div class="form-field" id="securityAnswerSection" style="display: none;">
                    <label for="security_answer">Security Answer</label>
                    <div class="input-group">
                        <input type="text" id="security_answer" name="security_answer" placeholder="Enter your security answer">
                    </div>
                </div>

                <div id="passwordResetSection" style="display: none;">
                    <div class="form-field">
                        <label for="new_password">New Password</label>
                        <div class="input-group">
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                    </div>
                </div>

                <button type="button" class="login-btn" id="submitBtn">Verify Email</button>
                <button type="submit" name="update_password" class="login-btn" id="updatePasswordBtn" style="display: none;">Reset Password</button>
            </form>

            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotForm');
    const emailInput = document.getElementById('email');
    const securitySection = document.getElementById('securitySection');
    const securityAnswerSection = document.getElementById('securityAnswerSection');
    const passwordResetSection = document.getElementById('passwordResetSection');
    const securityQuestionDisplay = document.getElementById('security_question_display');
    const securityQuestionHidden = document.getElementById('security_question');
    const securityAnswerInput = document.getElementById('security_answer');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    const updatePasswordBtn = document.getElementById('updatePasswordBtn');
    let isEmailVerified = false;
    let isSecurityVerified = false;


    // Password validation
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;

    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        const isValid = passwordPattern.test(password);

        if (!isValid && password.length > 0) {
            this.setCustomValidity('Password must contain at least 8 characters, including at least one uppercase letter, one lowercase letter, and one number.');
        } else {
            this.setCustomValidity('');
        }
    });

    confirmPasswordInput.addEventListener('input', function() {
        const confirmPassword = this.value;
        const password = newPasswordInput.value;

        if (password !== confirmPassword && confirmPassword.length > 0) {
            this.setCustomValidity('Passwords do not match.');
        } else {
            this.setCustomValidity('');
        }
    });

    // Add click handler to submit button
    submitBtn.addEventListener('click', function(e) {
        // If email is not verified, verify email
        if (!isEmailVerified) {
            verifyEmail();
            return;
        }
        // If email is verified but security is not, verify security answer
        if (isEmailVerified && !isSecurityVerified) {
            verifySecurityAnswer();
            return;
        }
    });

    // Handle form submission (only for password reset)
    form.addEventListener('submit', function(e) {
        // Only handle password update submission
        if (e.submitter && e.submitter.name === 'update_password') {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (!passwordPattern.test(password)) {
                e.preventDefault();
                alert('Password must contain at least 8 characters, including at least one uppercase letter, one lowercase letter, and one number.');
                return;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            // Allow form submission for password update
            return;
        }
        
        // Prevent any other form submissions
        e.preventDefault();
    });

    function verifyEmail() {
        const email = emailInput.value;
        if (!email) {
            alert('Please enter your email address');
            return;
        }

        // Check email via AJAX
        const formData = new FormData();
        formData.append('email', email);
        formData.append('check_email', '1');

        fetch('forgot.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                if (data.has_security) {
                    // Show security question
                    securityQuestionDisplay.value = data.security_question;
                    securityQuestionHidden.value = data.security_question;
                    securitySection.style.display = 'block';
                    securityAnswerSection.style.display = 'block';
                    securityAnswerInput.required = true;
                    submitBtn.textContent = 'Verify Security Answer';
                    isEmailVerified = true;
                } else {
                    // No security question, proceed directly
                    isEmailVerified = true;
                    form.submit();
                }
            } else {
                alert('Email not registered');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    function verifySecurityAnswer() {
        const email = emailInput.value;
        const securityAnswer = securityAnswerInput.value;

        if (!securityAnswer) {
            alert('Please enter your security answer');
            return;
        }

        // Verify security answer via AJAX
        const formData = new FormData();
        formData.append('email', email);
        formData.append('security_answer', securityAnswer);
        formData.append('verify_security', '1');

        fetch('forgot.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.verified) {
                // Security verified - show password reset form
                isSecurityVerified = true;
                passwordResetSection.style.display = 'block';
                submitBtn.style.display = 'none';
                updatePasswordBtn.style.display = 'block';
                emailInput.readOnly = true;
                emailInput.style.backgroundColor = '#f5f5f5';
                securityAnswerInput.readOnly = true;
                securityAnswerInput.style.backgroundColor = '#f5f5f5';
                securityQuestionDisplay.style.backgroundColor = '#f5f5f5';
            } else {
                alert(data.message || 'Security answer is incorrect. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
});
</script>

<?php require "includes/footer.php"; ?>
