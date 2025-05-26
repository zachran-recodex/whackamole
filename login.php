<?php
/**
 * Login Page
 * 
 * Handles user authentication.
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in
redirectIfLoggedIn();

$errors = [];
$username = '';

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    // Sanitize inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    $validation = validateFormInput(
        [
            'username' => $username,
            'password' => $password
        ],
        [
            'username' => 'required',
            'password' => 'required'
        ]
    );
    
    if (!empty($validation)) {
        $errors = $validation;
    } else {
        // Authenticate user
        $result = loginUser($username, $password);
        
        if ($result['success']) {
            // Regenerate session ID for security
            regenerateSession();
            
            // Set success message
            setFlashMessage('success', 'Welcome back, ' . $result['user']['username'] . '!');
            
            // Redirect to dashboard
            header('Location: dashboard.php');
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
    <title>Login - Whack-A-Mole Game</title>
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
    </style>
</head>
<body>
    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card auth-card">
                    <div class="auth-header">
                        <i class="bi bi-hammer game-icon"></i>
                        <h1>Welcome Back</h1>
                        <p>Sign in to your Whack-A-Mole account</p>
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
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person"></i> Username or Email
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
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control<?php echo isset($errors['password']) ? ' is-invalid' : ''; ?>" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           autocomplete="current-password">
                                    <button class="btn btn-outline-secondary password-toggle" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['password']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-2">Don't have an account? 
                                <a href="register.php" class="text-decoration-none fw-bold">Create Account</a>
                            </p>
                            <p class="mb-2">
                                <a href="reset-password.php" class="text-decoration-none">Forgot Password?</a>
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