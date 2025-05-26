<?php
/**
 * Registration Page
 * 
 * Allows new users to create an account.
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in
redirectIfLoggedIn();

$errors = [];
$username = '';
$email = '';

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    // Sanitize inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    $validation = validateFormInput(
        [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $confirmPassword
        ],
        [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|match:password'
        ]
    );
    
    if (!empty($validation)) {
        $errors = $validation;
    } else {
        // Register the user
        $result = registerUser($username, $email, $password);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            header('Location: login.php');
            exit;
        } else {
            $errors['general'] = $result['message'];
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
    <title>Register - Whack-A-Mole Game</title>
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
    </style>
</head>
<body>
    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card auth-card">
                    <div class="auth-header">
                        <i class="bi bi-person-plus game-icon"></i>
                        <h1>Join the Game</h1>
                        <p>Create your Whack-A-Mole account</p>
                    </div>
                    
                    <div class="card-body p-4">
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
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="bi bi-person"></i> Username
                                    </label>
                                    <input type="text" 
                                           class="form-control<?php echo isset($errors['username']) ? ' is-invalid' : ''; ?>" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo htmlspecialchars($username); ?>" 
                                           required 
                                           autocomplete="username">
                                    <?php if (isset($errors['username'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['username']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope"></i> Email Address
                                    </label>
                                    <input type="email" 
                                           class="form-control<?php echo isset($errors['email']) ? ' is-invalid' : ''; ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>" 
                                           required 
                                           autocomplete="email">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['email']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control<?php echo isset($errors['password']) ? ' is-invalid' : ''; ?>" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary password-toggle" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['password']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <div class="strength-indicator" id="strengthText"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="bi bi-lock-fill"></i> Confirm Password
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
                                    <i class="bi bi-person-plus"></i> Create Account
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-2">Already have an account? 
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
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
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
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
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
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
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
        });
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
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
        
        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
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