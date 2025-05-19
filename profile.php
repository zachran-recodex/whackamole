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
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="icon" href="css/hammer2.png">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Your Profile</h1>
            <div class="profile-nav">
                <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                <a href="index.php" class="btn btn-secondary">Play Game</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <?php if (hasFlashMessage()): ?>
            <?php displayFlashMessage(); ?>
        <?php endif; ?>
        
        <div class="profile-content">
            <div class="profile-section">
                <h2>Account Information</h2>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-error"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="error"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Joined</label>
                        <div class="static-field"><?php echo formatDate($user['created_at']); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
            
            <div class="profile-section">
                <h2>Change Password</h2>
                
                <?php if (isset($passwordErrors['general'])): ?>
                    <div class="alert alert-error"><?php echo $passwordErrors['general']; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <?php if (isset($passwordErrors['current_password'])): ?>
                            <div class="error"><?php echo $passwordErrors['current_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <?php if (isset($passwordErrors['new_password'])): ?>
                            <div class="error"><?php echo $passwordErrors['new_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($passwordErrors['confirm_password'])): ?>
                            <div class="error"><?php echo $passwordErrors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
            
            <div class="profile-section">
                <h2>Privacy</h2>
                <p>Your information is never shared with third parties. Your scores are only displayed with your username.</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>