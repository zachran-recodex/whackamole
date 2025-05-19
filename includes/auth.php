<?php
/**
 * Authentication Functions
 *
 * This file contains functions related to user authentication.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Register a new user
 * 
 * @param string $username The username
 * @param string $email The email address
 * @param string $password The password (will be hashed)
 * @return array Result with status and message
 */
function registerUser($username, $email, $password) {
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    
    try {
        $db = connectDB();
        
        // Check if username or email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $result = $stmt->execute([$username, $email, $hashedPassword]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Registration successful! You can now login.'];
        } else {
            return ['success' => false, 'message' => 'Registration failed. Please try again later.'];
        }
    } catch (PDOException $e) {
        error_log('Registration error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during registration.'];
    }
}

/**
 * Authenticate a user
 * 
 * @param string $username The username or email
 * @param string $password The password
 * @return array Result with status, message, and user data if successful
 */
function loginUser($username, $password) {
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Both username/email and password are required'];
    }
    
    try {
        $db = connectDB();
        
        // Check if input is email or username
        $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        $stmt = $db->prepare("SELECT id, username, email, password FROM users WHERE $field = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid username/email or password'];
        }
        
        $user = $stmt->fetch();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Start session and store user data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            return [
                'success' => true, 
                'message' => 'Login successful', 
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid username/email or password'];
        }
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during login.'];
    }
}

/**
 * Log out the current user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isUserLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Generate a password reset token for a user
 * 
 * @param string $email The user's email
 * @return array Result with status and message
 */
function generatePasswordResetToken($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    try {
        $db = connectDB();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            // Don't reveal if email exists or not for security
            return ['success' => true, 'message' => 'If your email is registered, a password reset link will be sent.'];
        }
        
        // Generate a random token
        $token = bin2hex(random_bytes(32));
        
        // Set token expiry (24 hours from now)
        $expires = date('Y-m-d H:i:s', time() + 86400);
        
        // Update user with reset token
        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);
        
        // In a real application, you would send an email with a reset link
        // For this example, we'll just return the token
        return [
            'success' => true, 
            'message' => 'Password reset link has been generated.', 
            'token' => $token
        ];
    } catch (PDOException $e) {
        error_log('Password reset token error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while processing your request.'];
    }
}

/**
 * Reset a user's password using a valid token
 * 
 * @param string $token Reset token
 * @param string $newPassword New password
 * @return array Result with status and message
 */
function resetPassword($token, $newPassword) {
    if (empty($token) || empty($newPassword)) {
        return ['success' => false, 'message' => 'Invalid token or password'];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    
    try {
        $db = connectDB();
        
        // Find user with this token and check if it's expired
        $stmt = $db->prepare(
            "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()"
        );
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        $user = $stmt->fetch();
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update user's password and clear reset token
        $stmt = $db->prepare(
            "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?"
        );
        $stmt->execute([$hashedPassword, $user['id']]);
        
        return ['success' => true, 'message' => 'Password has been reset successfully. You can now login.'];
    } catch (PDOException $e) {
        error_log('Password reset error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while resetting your password.'];
    }
}

/**
 * Get user information by ID
 * 
 * @param int $userId User ID
 * @return array|null User data or null if not found
 */
function getUserById($userId) {
    try {
        $db = connectDB();
        $stmt = $db->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() === 0) {
            return null;
        }
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Get user error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Update user's profile information
 * 
 * @param int $userId User ID
 * @param array $data Data to update (can include username, email)
 * @return array Result with status and message
 */
function updateUserProfile($userId, $data) {
    if (empty($userId) || empty($data)) {
        return ['success' => false, 'message' => 'Invalid user ID or data'];
    }
    
    try {
        $db = connectDB();
        $updates = [];
        $params = [];
        
        // Prepare update fields and parameters
        if (isset($data['username']) && !empty($data['username'])) {
            $updates[] = "username = ?";
            $params[] = $data['username'];
        }
        
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            $updates[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }
        
        // Add user ID to params
        $params[] = $userId;
        
        // Update user profile
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            // Update session variables if applicable
            if (isset($data['username']) && isset($_SESSION['username'])) {
                $_SESSION['username'] = $data['username'];
            }
            
            if (isset($data['email']) && isset($_SESSION['email'])) {
                $_SESSION['email'] = $data['email'];
            }
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    } catch (PDOException $e) {
        error_log('Update profile error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating your profile'];
    }
}

/**
 * Change user's password
 * 
 * @param int $userId User ID
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return array Result with status and message
 */
function changePassword($userId, $currentPassword, $newPassword) {
    if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long'];
    }
    
    try {
        $db = connectDB();
        
        // Get current password hash
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        $user = $stmt->fetch();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Hash new password and update
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashedPassword, $userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    } catch (PDOException $e) {
        error_log('Change password error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while changing your password'];
    }
}