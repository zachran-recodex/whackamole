<?php
/**
 * Logout Script
 * 
 * Logs out the current user and redirects to the login page.
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';

// Log out the user
logoutUser();

// Redirect to login page
setFlashMessage('success', 'You have been successfully logged out.');
header('Location: login.php');
exit;