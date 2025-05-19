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
    <title>Reset Password - Whack-A-Mole Game</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="icon" href="css/hammer2.png">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><?php echo $showResetForm ? 'Reset Password' : 'Forgot Password'; ?></h1>
            <p><?php echo $showResetForm ? 'Enter your new password' : 'Request a password reset link'; ?></p>
        </div>
        
        <div class="auth-form">
            <?php if (hasFlashMessage()): ?>
                <?php displayFlashMessage(); ?>
            <?php endif; ?>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?php echo $errors['general']; ?></div>
            <?php endif; ?>
            
            <?php if ($showResetForm): ?>
                <!-- Reset Password Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="error"><?php echo $errors['new_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="error"><?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Request Reset Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="request_reset" value="1">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Request Reset Link</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <p><a href="login.php">Back to Login</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>