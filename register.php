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
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="icon" href="css/hammer2.png">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join the Whack-A-Mole community</p>
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
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo $username; ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="error"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?php echo $errors['email']; ?></div>
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
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="error"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>