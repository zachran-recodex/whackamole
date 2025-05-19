<?php
/**
 * Utility Functions
 *
 * General helper functions for the application.
 */

/**
 * Sanitize user input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate form input
 * 
 * @param array $data Form data
 * @param array $rules Validation rules
 * @return array Array of errors, empty if no errors
 */
function validateFormInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        // Check if field exists in data
        if (!isset($data[$field]) || empty($data[$field])) {
            if (strpos($rule, 'required') !== false) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
            continue;
        }
        
        $value = $data[$field];
        
        // Email validation
        if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = 'Invalid email format';
        }
        
        // Minimum length validation
        if (preg_match('/min:(\d+)/', $rule, $matches)) {
            $min = (int)$matches[1];
            if (strlen($value) < $min) {
                $errors[$field] = ucfirst($field) . ' must be at least ' . $min . ' characters';
            }
        }
        
        // Maximum length validation
        if (preg_match('/max:(\d+)/', $rule, $matches)) {
            $max = (int)$matches[1];
            if (strlen($value) > $max) {
                $errors[$field] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
            }
        }
        
        // Match validation (for password confirmation)
        if (preg_match('/match:(\w+)/', $rule, $matches)) {
            $matchField = $matches[1];
            if (!isset($data[$matchField]) || $value !== $data[$matchField]) {
                $errors[$field] = ucfirst($field) . ' does not match ' . $matchField;
            }
        }
    }
    
    return $errors;
}

/**
 * Save game score for a user
 * 
 * @param int $userId User ID
 * @param int $score Score value
 * @param string $difficulty Game difficulty
 * @return bool True if saved successfully, false otherwise
 */
function saveScore($userId, $score, $difficulty) {
    try {
        $db = connectDB();
        $stmt = $db->prepare("INSERT INTO scores (user_id, score, difficulty) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $score, $difficulty]);
    } catch (PDOException $e) {
        error_log('Save score error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get top scores
 * 
 * @param int $limit Number of scores to get
 * @param string|null $difficulty Filter by difficulty
 * @return array Array of scores
 */
function getTopScores($limit = 10, $difficulty = null) {
    try {
        $db = connectDB();
        
        $sql = "SELECT s.id, s.score, s.difficulty, s.created_at, u.username 
                FROM scores s 
                JOIN users u ON s.user_id = u.id ";
        
        $params = [];
        if ($difficulty !== null) {
            $sql .= "WHERE s.difficulty = ? ";
            $params[] = $difficulty;
        }
        
        $sql .= "ORDER BY s.score DESC LIMIT ?";
        $params[] = (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Get top scores error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get user's scores
 * 
 * @param int $userId User ID
 * @param int $limit Number of scores to get
 * @return array Array of user's scores
 */
function getUserScores($userId, $limit = 10) {
    try {
        $db = connectDB();
        $stmt = $db->prepare(
            "SELECT id, score, difficulty, created_at 
            FROM scores 
            WHERE user_id = ? 
            ORDER BY score DESC 
            LIMIT ?"
        );
        $stmt->execute([$userId, (int)$limit]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Get user scores error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y g:i A') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}