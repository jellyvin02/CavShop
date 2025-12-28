<?php
require_once "includes/connection.php";
session_start();

if(isset($_POST['register']))
{
  $user_exist_query="SELECT * FROM customer_users WHERE `email`='$_POST[email]'";
  $result=mysqli_query($conn, $user_exist_query);

  if($result)
  {
    if(mysqli_num_rows($result)>0)
    {
      $result_fetch=mysqli_fetch_assoc($result);
      if($result_fetch['email']==$_POST['email'])
      {
        echo"
        <script>
        alert('Email already registered');
        window.location.href='login.php';
        </script>
        ";
      }
    }
  }

  // Check for existing username
  $username_exist_query="SELECT * FROM customer_users WHERE `username`='$_POST[username]'";
  $result_username=mysqli_query($conn, $username_exist_query);

  if($result_username && mysqli_num_rows($result_username)>0)
  {
      echo"
      <script>
      alert('Username already taken');
      window.location.href='new-login.php';
      </script>
      ";
  }
  else
  {
      $first_name = $_POST['first-name'];
      $last_name = $_POST['last-name'];
      $username = $_POST['username'];
      $email = $_POST['email'];

      // Server-side password validation
      $password_raw = $_POST['password'];
      $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/';
      if (!preg_match($passwordPattern, $password_raw)) {
          echo "
          <script>
          alert('Password does not meet criteria.');
          window.history.back();
          </script>
          ";
          exit();
      }
      $password = password_hash($password_raw, PASSWORD_BCRYPT);

      // SIMPLE REGISTER: no verification_code, no is_verified, no email sending
      // Recommended: prepared statement
      $stmt = $conn->prepare("
        INSERT INTO `customer_users`
          (`first_name`, `last_name`, `username`, `email`, `password`, `gender`, `municipality`, `street_number`, `contact`, `security_question`, `security_answer`)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");

      $gender = $_POST['gender'];
      $municipality = $_POST['state'];
      $streetNumber = $_POST['district'];
      $contact = isset($_POST['contact']) ? $_POST['contact'] : '';
      $security_question = isset($_POST['security_question']) ? $_POST['security_question'] : '';
      $security_answer = isset($_POST['security_answer']) ? strtolower(trim($_POST['security_answer'])) : '';

      $stmt->bind_param("sssssssssss", $first_name, $last_name, $username, $email, $password, $gender, $municipality, $streetNumber, $contact, $security_question, $security_answer);

      if($stmt->execute())
      {
        echo "
          <script>
            alert('Registration successful. You can now log in.');
            window.location.href='login.php';
          </script>
        ";
      }
      else
      {
        echo "
          <script>
            alert('UNKNOWN ISSUE: cannot run your request');
            window.location.href='login.php';
          </script>
        ";
      }

      $stmt->close();
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
        min-height: auto; /* Adjusted for footer visibility */
        width: 100%;
        padding: 20px;
        padding-top: 120px; /* Adjusted top padding */
        padding-bottom: 60px; /* Adjusted bottom padding */
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
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 550px; /* Slightly wider for registration form */
        flex-shrink: 0;
        box-sizing: border-box;
    }

    /* Responsive design */
    @media (max-width: 1024px) {
        .login-content { gap: 30px; }
        .branding-logo { width: 280px; }
    }

    @media (max-width: 900px) {
        .login-branding { display: none; }
        .login-content { justify-content: center; margin-left: 0; }
        .form-container1 { margin: 0 auto; }
    }

    @media (max-width: 480px) {
        .shopee-header { height: 70px; }
        .shopee-logo img { height: 50px; }
        .login-wrapper {
            padding: 15px;
            padding-top: 85px;
            padding-bottom: 15px;
        }
        .form-container1 { padding: 20px; }
        .form-grid { grid-template-columns: 1fr !important; gap: 0 !important; }
    }

    .form-title {
        font-size: 22px;
        margin-bottom: 25px;
        color: #333;
        font-weight: 500;
    }

    .form-field {
        margin-bottom: 20px;
    }

    .input-group {
        position: relative;
    }
    
    .form-field input, .form-field select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        box-sizing: border-box;
        font-size: 14px;
        transition: border-color 0.3s;
        background: #fff;
    }

    .form-field input:focus, .form-field select:focus {
        border-color: #28a745;
        outline: none;
    }

    /* Registration specific grid */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: opacity 0.2s, background-color 0.2s;
    }

    .login-btn:hover {
        background-color: #218838;
    }
    
    .register-link {
        text-align: center;
        font-size: 14px;
        color: #888;
        margin-top: 20px;
    }

    /* Password Criteria Styles */
    .password-criteria {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 12px;
        color: #666;
    }

    .criteria-item {
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .criteria-item i {
        font-size: 10px;
        color: #ccc;
    }

    .criteria-item.valid i {
        color: #28a745;
    }

    .criteria-item.valid {
        color: #28a745;
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
            <div class="branding-text"></div>
            <div class="branding-subtext">
            Your trusted online shopping destination for quality products and exceptional service throughout Cavite.
            </div>
        </div>

        <!-- Right Side: Registration Form -->
        <div class="form-container1">
            <div class="form-title">Create an Account</div>
            
            <form action="#" method="POST">
                <div class="form-grid">
                    <div class="form-field">
                        <div class="input-group">
                            <input type="text" id="first-name" name="first-name" placeholder="First Name" required>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="input-group">
                            <input type="text" id="last-name" name="last-name" placeholder="Last Name" required>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <div class="input-group">
                            <input type="text" id="username" name="username" placeholder="Username" required>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="input-group">
                            <input type="email" id="email" name="email" placeholder="Email Address" required>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <div class="input-group">
                            <select id="gender" name="gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="input-group">
                            <input type="text" id="contact" name="contact" placeholder="Contact Number" required>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <div class="input-group">
                            <input type="text" id="municipality" name="state" placeholder="Municipality" required>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="input-group">
                            <input type="text" id="street_number" name="district" placeholder="Street Number" required>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <div class="input-group">
                            <input type="password" id="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="password-criteria" id="passwordCriteria">
                            <div class="criteria-item" id="length"><i class="fas fa-circle"></i> At least 8 characters</div>
                            <div class="criteria-item" id="uppercase"><i class="fas fa-circle"></i> At least one uppercase letter</div>
                            <div class="criteria-item" id="lowercase"><i class="fas fa-circle"></i> At least one lowercase letter</div>
                            <div class="criteria-item" id="number"><i class="fas fa-circle"></i> At least one number</div>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="input-group">
                            <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm Password" required>
                        </div>
                    </div>
                </div>

                <div class="form-field">
                    <div class="input-group">
                        <select id="security_question" name="security_question" required>
                            <option value="" disabled selected>Select Security Question</option>
                            <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                            <option value="What city were you born in?">What city were you born in?</option>
                            <option value="What was your mother's maiden name?">What was your mother's maiden name?</option>
                            <option value="What was the name of your elementary school?">What was the name of your elementary school?</option>
                            <option value="What was your favorite product as a child?">What was your favorite product as a child?</option>
                            <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <div class="input-group">
                        <input type="text" id="security_answer" name="security_answer" placeholder="Security Answer" required>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">This will be used to verify your identity if you forget your password.</small>
                    </div>
                </div>

                <button type="submit" name="register" class="login-btn">REGISTER</button>
                
                <div class="register-link">
                    Already have an account? <a href="login.php" style="color: #28a745; font-weight: 500;">Log In</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require "includes/footer.php"; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    
    // Updated regex without the special character condition
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;

    // Check password complexity
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        const criteria = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password)
        };

        for (const [id, isValid] of Object.entries(criteria)) {
            const el = document.getElementById(id);
            if (isValid) {
                el.classList.add('valid');
                el.querySelector('i').classList.remove('fa-circle');
                el.querySelector('i').classList.add('fa-check-circle');
            } else {
                el.classList.remove('valid');
                el.querySelector('i').classList.add('fa-circle');
                el.querySelector('i').classList.remove('fa-check-circle');
            }
        }

        const allValid = Object.values(criteria).every(v => v);
        if (!allValid) {
            this.setCustomValidity('Password must meet all criteria.');
        } else {
            this.setCustomValidity('');
        }
    });

    // Check if passwords match
    confirmPasswordInput.addEventListener('input', function() {
        const confirmPassword = this.value;
        const password = passwordInput.value;

        if (password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match.');
        } else {
            this.setCustomValidity('');
        }
    });

    // Additional check before form submission
    document.querySelector('form').addEventListener('submit', function(event) {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;
        const isStrong = passwordPattern.test(password);

        if (!isStrong) {
            event.preventDefault();
            passwordInput.setCustomValidity('Password must meet all criteria.');
            passwordInput.reportValidity();
            return;
        }

        if (password !== confirmPassword) {
            event.preventDefault(); // Prevent form submission
            confirmPasswordInput.setCustomValidity('Passwords do not match.');
            confirmPasswordInput.reportValidity(); // Show the validation message
        }
    });
});
</script>

