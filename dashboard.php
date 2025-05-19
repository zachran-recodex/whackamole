<?php
/**
 * Dashboard Page
 * 
 * User dashboard after login.
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login to access this page
requireLogin();

// Get user information
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get user's top scores
$userScores = getUserScores($userId, 5);

// Get global top scores
$topScores = getTopScores(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Whack-A-Mole Game</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="icon" href="css/hammer2.png">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
            <div class="dashboard-nav">
                <a href="index.php" class="btn btn-secondary">Play Game</a>
                <a href="profile.php" class="btn btn-secondary">Profile</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <?php if (hasFlashMessage()): ?>
            <?php displayFlashMessage(); ?>
        <?php endif; ?>
        
        <div class="dashboard-content">
            <div class="dashboard-section">
                <h2>Your Stats</h2>
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Total Games</h3>
                        <p class="stat-value"><?php echo count($userScores); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Best Score</h3>
                        <p class="stat-value">
                            <?php 
                            echo !empty($userScores) ? $userScores[0]['score'] : '0';
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2>Your Recent Scores</h2>
                <?php if (empty($userScores)): ?>
                    <p>You haven't played any games yet. <a href="index.php">Start playing now!</a></p>
                <?php else: ?>
                    <table class="scores-table">
                        <thead>
                            <tr>
                                <th>Score</th>
                                <th>Difficulty</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userScores as $score): ?>
                                <tr>
                                    <td><?php echo $score['score']; ?></td>
                                    <td><?php echo ucfirst($score['difficulty']); ?></td>
                                    <td><?php echo formatDate($score['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-section">
                <h2>Global Top Scores</h2>
                <?php if (empty($topScores)): ?>
                    <p>No scores recorded yet.</p>
                <?php else: ?>
                    <table class="scores-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Player</th>
                                <th>Score</th>
                                <th>Difficulty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topScores as $index => $score): ?>
                                <tr<?php echo $score['username'] === $user['username'] ? ' class="highlight"' : ''; ?>>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $score['username']; ?></td>
                                    <td><?php echo $score['score']; ?></td>
                                    <td><?php echo ucfirst($score['difficulty']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>