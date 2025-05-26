<?php
/**
 * Profile Page
 * 
 * Allows users to view and update their profile information.
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login to access this page
requireLogin();

// Get user information
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

$errors = [];
$passwordErrors = [];

// Process profile update form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    // Check which form was submitted
    if (isset($_POST['update_profile'])) {
        // Process profile update
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Validate inputs
        $validation = validateFormInput(
            [
                'username' => $username,
                'email' => $email
            ],
            [
                'username' => 'required|min:3|max:50',
                'email' => 'required|email'
            ]
        );
        
        if (!empty($validation)) {
            $errors = $validation;
        } else {
            // Update profile
            $result = updateUserProfile($userId, [
                'username' => $username,
                'email' => $email
            ]);
            
            if ($result['success']) {
                setFlashMessage('success', $result['message']);
                header('Location: profile.php');
                exit;
            } else {
                $errors['general'] = $result['message'];
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Process password change
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        $validation = validateFormInput(
            [
                'current_password' => $currentPassword,
                'new_password' => $newPassword,
                'confirm_password' => $confirmPassword
            ],
            [
                'current_password' => 'required',
                'new_password' => 'required|min:8',
                'confirm_password' => 'required|match:new_password'
            ]
        );
        
        if (!empty($validation)) {
            $passwordErrors = $validation;
        } else {
            // Change password
            $result = changePassword($userId, $currentPassword, $newPassword);
            
            if ($result['success']) {
                setFlashMessage('success', $result['message']);
                header('Location: profile.php');
                exit;
            } else {
                $passwordErrors['general'] = $result['message'];
            }
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
    <title>Profile - Whack-A-Mole Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="css/hammer2.png">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
        .section-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px 15px 0 0;
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
        .btn-outline-secondary {
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .btn-danger {
            border-radius: 25px;
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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card main-card">
                    <div class="profile-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="bi bi-person-gear me-2"></i>
                                    Profile Settings
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Manage your account information and preferences
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-list me-1"></i> Menu
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="dashboard.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                        </a></li>
                                        <li><a class="dropdown-item" href="index.php">
                                            <i class="bi bi-controller me-2"></i>Play Game
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
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
                        
                        <!-- Account Information Section -->
                        <div class="section-card">
                            <div class="section-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-circle me-2"></i>Account Information
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <?php if (isset($errors['general'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <?php echo $errors['general']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">
                                                <i class="bi bi-person"></i> Username
                                            </label>
                                            <input type="text" 
                                                   class="form-control<?php echo isset($errors['username']) ? ' is-invalid' : ''; ?>" 
                                                   id="username" 
                                                   name="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                   required>
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
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                   required>
                                            <?php if (isset($errors['email'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $errors['email']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="info-card">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calendar-event me-2 text-primary"></i>
                                                <div>
                                                    <strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Update Profile
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Change Password Section -->
                        <div class="section-card">
                            <div class="section-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-lock me-2"></i>Change Password
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <?php if (isset($passwordErrors['general'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <?php echo $passwordErrors['general']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="passwordForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="change_password" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">
                                            <i class="bi bi-lock"></i> Current Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control<?php echo isset($passwordErrors['current_password']) ? ' is-invalid' : ''; ?>" 
                                                   id="current_password" 
                                                   name="current_password" 
                                                   required>
                                            <button class="btn btn-outline-secondary password-toggle" type="button" data-target="current_password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if (isset($passwordErrors['current_password'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $passwordErrors['current_password']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">
                                            <i class="bi bi-lock-fill"></i> New Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control<?php echo isset($passwordErrors['new_password']) ? ' is-invalid' : ''; ?>" 
                                                   id="new_password" 
                                                   name="new_password" 
                                                   required>
                                            <button class="btn btn-outline-secondary password-toggle" type="button" data-target="new_password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if (isset($passwordErrors['new_password'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $passwordErrors['new_password']; ?>
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
                                                   class="form-control<?php echo isset($passwordErrors['confirm_password']) ? ' is-invalid' : ''; ?>" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   required>
                                            <button class="btn btn-outline-secondary password-toggle" type="button" data-target="confirm_password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if (isset($passwordErrors['confirm_password'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $passwordErrors['confirm_password']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-text" id="passwordMatch"></div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-shield-check"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Privacy Section -->
                        <div class="section-card">
                            <div class="section-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-shaded me-2"></i>Privacy & Security
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="info-card">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-info-circle me-3 text-primary" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <h6 class="mb-2">Your Privacy Matters</h6>
                                            <p class="mb-0 text-muted">
                                                Your information is never shared with third parties. 
                                                Your scores are only displayed with your username on leaderboards. 
                                                We use industry-standard security measures to protect your data.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        document.querySelectorAll('.password-toggle').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const password = document.getElementById(targetId);
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
        });
        
        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
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