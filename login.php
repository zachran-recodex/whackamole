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
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="icon" href="css/hammer2.png">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Login</h1>
            <p>Access your Whack-A-Mole account</p>
        </div>
        
        <div class="auth-form">
            <?php if (hasFlashMessage()): ?>
                <?php displayFlashMessage(); ?>
            <?php endif; ?>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?php echo $errors['general']; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" value="<?php echo $username; ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="error"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register</a></p>
                <p><a href="reset-password.php">Forgot Password?</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>