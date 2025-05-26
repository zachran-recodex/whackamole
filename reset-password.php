<?php
/**
 * Reset Password Page
 * 
 * Allows users to reset their password.
 * This is a simplified version without email sending functionality.
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in
redirectIfLoggedIn();

$errors = [];
$email = '';
$showResetForm = false;
$token = '';

// Check if token is provided in URL (for reset form)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $showResetForm = true;
}

// Process request password reset form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reset'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    // Sanitize input
    $email = sanitizeInput($_POST['email'] ?? '');
    
    // Validate input
    $validation = validateFormInput(
        ['email' => $email],
        ['email' => 'required|email']
    );
    
    if (!empty($validation)) {
        $errors = $validation;
    } else {
        // Generate reset token
        $result = generatePasswordResetToken($email);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            
            // In a real application, you would send an email with the reset link
            // For this example, we'll just display the token
            setFlashMessage('info', 'For demonstration purposes, here is your reset token: ' . ($result['token'] ?? 'N/A'));
            
            header('Location: reset-password.php');
            exit;
        } else {
            $errors['general'] = $result['message'];
        }
    }
}

// Process reset password form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    // Get token and new password
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    $validation = validateFormInput(
        [
            'token' => $token,
            'new_password' => $newPassword,
            'confirm_password' => $confirmPassword
        ],
        [
            'token' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|match:new_password'
        ]
    );
    
    if (!empty($validation)) {
        $errors = $validation;
        $showResetForm = true;
    } else {
        // Reset password
        $result = resetPassword($token, $newPassword);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            header('Location: login.php');
            exit;
        } else {
            $errors['general'] = $result['message'];
            $showResetForm = true;
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showResetForm ? 'Reset Password' : 'Forgot Password'; ?> - Whack-A-Mole Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="css/hammer2.png">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .auth-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .auth-header p {
            opacity: 0.9;
            margin: 0;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .game-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #667eea;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .strength-indicator {
            font-size: 0.85rem;
            margin-top: 5px;
            font-weight: 500;
        }
        .strength-weak { background-color: #dc3545; }
        .strength-moderate { background-color: #ffc107; }
        .strength-strong { background-color: #28a745; }
        .strength-very-strong { background-color: #20c997; }
        .info-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid #2196F3;
            margin-bottom: 1rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 1rem;
        }
        .step.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        .step-line {
            width: 50px;
            height: 2px;
            background: #e9ecef;
        }
        .step-line.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="auth-header">
                        <i class="bi bi-<?php echo $showResetForm ? 'shield-lock' : 'key'; ?> game-icon"></i>
                        <h1><?php echo $showResetForm ? 'Reset Password' : 'Forgot Password'; ?></h1>
                        <p><?php echo $showResetForm ? 'Enter your new password below' : 'We\'ll help you get back in'; ?></p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php echo !$showResetForm ? 'active' : 'inactive'; ?>">1</div>
                            <div class="step-line <?php echo $showResetForm ? 'active' : ''; ?>"></div>
                            <div class="step <?php echo $showResetForm ? 'active' : 'inactive'; ?>">2</div>
                        </div>
                        
                        <?php if (hasFlashMessage()): ?>
                            <?php $flash = getFlashMessage(); ?>
                            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']); ?> alert-dismissible fade show" role="alert">
                                <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                                <?php echo $flash['message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?php echo $errors['general']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($showResetForm): ?>
                            <!-- Reset Password Form -->
                            <div class="info-card">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-info-circle me-2 text-primary"></i>
                                    <small class="text-muted">
                                        Create a strong password with at least 8 characters including letters, numbers, and symbols.
                                    </small>
                                </div>
                            </div>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="resetForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="reset_password" value="1">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">
                                        <i class="bi bi-lock-fill"></i> New Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control<?php echo isset($errors['new_password']) ? ' is-invalid' : ''; ?>" 
                                               id="new_password" 
                                               name="new_password" 
                                               required 
                                               autocomplete="new-password">
                                        <button class="btn btn-outline-secondary password-toggle" type="button" id="toggleNewPassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if (isset($errors['new_password'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo $errors['new_password']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="password-strength" id="passwordStrength"></div>
                                    <div class="strength-indicator" id="strengthText"></div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">
                                        <i class="bi bi-shield-check"></i> Confirm New Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control<?php echo isset($errors['confirm_password']) ? ' is-invalid' : ''; ?>" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               required 
                                               autocomplete="new-password">
                                        <button class="btn btn-outline-secondary password-toggle" type="button" id="toggleConfirmPassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo $errors['confirm_password']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-text" id="passwordMatch"></div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-shield-check"></i> Reset Password
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Request Reset Form -->
                            <div class="info-card">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-info-circle me-2 text-primary"></i>
                                    <small class="text-muted">
                                        Enter your email address and we'll provide you with a reset token. 
                                        In a real application, this would be sent to your email.
                                    </small>
                                </div>
                            </div>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="request_reset" value="1">
                                
                                <div class="mb-4">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope"></i> Email Address
                                    </label>
                                    <input type="email" 
                                           class="form-control<?php echo isset($errors['email']) ? ' is-invalid' : ''; ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>" 
                                           placeholder="Enter your registered email address"
                                           required 
                                           autocomplete="email">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['email']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send"></i> Send Reset Link
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-2">Remember your password? 
                                <a href="login.php" class="text-decoration-none fw-bold">Sign In</a>
                            </p>
                            <p class="mb-0">
                                <a href="index.php" class="text-decoration-none">
                                    <i class="bi bi-arrow-left"></i> Back to Game
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        document.getElementById('toggleNewPassword')?.addEventListener('click', function() {
            const password = document.getElementById('new_password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
            const password = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Password strength checker (only for reset form)
        const newPasswordField = document.getElementById('new_password');
        if (newPasswordField) {
            newPasswordField.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('passwordStrength');
                const strengthText = document.getElementById('strengthText');
                
                let strength = 0;
                let feedback = '';
                
                if (password.length > 0) {
                    // Length check
                    if (password.length >= 8) strength += 1;
                    // Contains letters
                    if (/[a-zA-Z]/.test(password)) strength += 1;
                    // Contains numbers
                    if (/\d/.test(password)) strength += 1;
                    // Contains special characters
                    if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
                    
                    switch (strength) {
                        case 0:
                        case 1:
                            strengthBar.style.width = '25%';
                            strengthBar.className = 'password-strength strength-weak';
                            feedback = 'Weak';
                            break;
                        case 2:
                            strengthBar.style.width = '50%';
                            strengthBar.className = 'password-strength strength-moderate';
                            feedback = 'Moderate';
                            break;
                        case 3:
                            strengthBar.style.width = '75%';
                            strengthBar.className = 'password-strength strength-strong';
                            feedback = 'Strong';
                            break;
                        case 4:
                            strengthBar.style.width = '100%';
                            strengthBar.className = 'password-strength strength-very-strong';
                            feedback = 'Very Strong';
                            break;
                    }
                    
                    strengthText.textContent = feedback;
                    strengthText.className = 'strength-indicator text-' + 
                        (strength <= 1 ? 'danger' : strength === 2 ? 'warning' : 'success');
                } else {
                    strengthBar.style.width = '0';
                    strengthText.textContent = '';
                }
                
                checkPasswordMatch();
            });
            
            // Password match checker
            function checkPasswordMatch() {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const matchDiv = document.getElementById('passwordMatch');
                
                if (confirmPassword.length > 0) {
                    if (newPassword === confirmPassword) {
                        matchDiv.innerHTML = '<i class="bi bi-check-circle text-success"></i> Passwords match';
                        matchDiv.className = 'form-text text-success';
                    } else {
                        matchDiv.innerHTML = '<i class="bi bi-x-circle text-danger"></i> Passwords do not match';
                        matchDiv.className = 'form-text text-danger';
                    }
                } else {
                    matchDiv.textContent = '';
                }
            }
            
            document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>