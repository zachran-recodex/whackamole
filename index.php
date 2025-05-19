<?php
/**
 * Index Page - Game Entry Point
 * 
 * This is the main entry point for the game.
 * It redirects to the game page and handles authentication checks.
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

// Initialize database if not already done
initializeDatabase();

// Check if user is logged in
$isLoggedIn = isUserLoggedIn();
$username = '';

if ($isLoggedIn) {
    // Get user information
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    $username = $user['username'] ?? '';
    
    // Handle score submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['score'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }
        
        $score = (int)$_POST['score'];
        $difficulty = sanitizeInput($_POST['difficulty'] ?? 'medium');
        
        // Save score to database
        saveScore($userId, $score, $difficulty);
        
        // Redirect to prevent form resubmission
        header('Location: index.php?score_saved=1');
        exit;
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Whack-A-Mole</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="css/hammer2.png">
    <meta http-equiv="Content-Type" content="text/html"/>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <!-- Additional auth-specific styles -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <!-- Auth Overlay for non-logged in users -->
    <?php if (!$isLoggedIn): ?>
    <div class="auth-overlay">
        <div class="auth-card">
            <h2>Welcome to Whack-A-Mole!</h2>
            <p>Log in or register to track your scores and compete on the leaderboard.</p>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-secondary">Register</a>
                <a href="#" class="btn btn-tertiary play-as-guest">Play as Guest</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($isLoggedIn && isset($_GET['score_saved'])): ?>
    <div class="score-saved-notification">
        <p>Your score has been saved!</p>
    </div>
    <?php endif; ?>
    
    <!-- User Menu (if logged in) -->
    <?php if ($isLoggedIn): ?>
    <div class="user-menu">
        <span>Hello, <?php echo htmlspecialchars($username); ?></span>
        <a href="dashboard.php" class="btn btn-sm">Dashboard</a>
        <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
    <?php endif; ?>
    
    <!-- Original Game Code -->
    <form class="modal">
        <div class="title"><span>Welcome to</span><h1>Wack-A-Mole</h1></div>
        <div class="select">select difficulty:</div>
        <div class="buttons">
            <button class="e">Easy</button>
            <button class="m">Medium</button>
            <button class="h">Hard</button>
        </div>
        <div></div>
        <div></div>
        <div></div>
    </form>

    <div class="container">
        <div class="view">
            <main>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <div class="hole">
                    <img src="css/hole.png">
                    <div class="mole"></div>
                </div>
                <span class="side-controls">
                    <div class="side-controls--container">
                        <div class="velocity-level">
                            <img src="css/velocity-mole.svg" alt="velocity">
                            <span class="">2</span>
                        </div>
                        <div class="time-level">
                            <img src="css/timer.svg" alt="timer">
                            <span class="">10</span>
                        </div>
                        <div class="volume-level">
                            <img src="css/volume.svg" alt="volume">
                            <span class="hard"></span>
                        </div>
                    </div>
                </span>
            </main>
            <div class="controls">
                <div class="time">
                    <p>Time</p>
                    <span>-</span>
                </div>
                <div class="score">
                    <p>Score</p>
                    <span>-</span>
                </div>
                <div class="start">START</div>
            </div>
        </div>
    </div>

    <!-- Modified game over form for logged in users -->
    <div class="enterName">
        <?php if ($isLoggedIn): ?>
            <h2>GAME OVER</h2>
            <h3>Save your score, <?php echo htmlspecialchars($username); ?>?</h3><br>
            <form id="saveScoreForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="score" id="finalScore" value="0">
                <input type="hidden" name="difficulty" id="difficulty" value="medium">
                <button type="submit" class="btn btn-primary">Save Score</button>
            </form>
            <h3 class="retry">Retry?</h3><br>
        <?php else: ?>
            <h2>GAME OVER</h2>
            <h3>Enter your name!</h3><br>
            <input type="text">
            <h3 class="retry">Retry?</h3><br>
        <?php endif; ?>
    </div>
    
    <div class="scoreboard">
        <table class="scores"></table>
    </div>
    
    <!-- Original game scripts -->
    <script src="js/script.js"></script>
    
    <!-- Additional auth integration script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Play as guest functionality
            const playAsGuestButton = document.querySelector('.play-as-guest');
            if (playAsGuestButton) {
                playAsGuestButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector('.auth-overlay').style.display = 'none';
                });
            }
            
            // For logged in users, handle score submission
            <?php if ($isLoggedIn): ?>
            const saveScoreForm = document.getElementById('saveScoreForm');
            const finalScoreInput = document.getElementById('finalScore');
            const difficultyInput = document.getElementById('difficulty');
            
            // Update score value when game ends
            function updateScoreValue() {
                const scoreValue = document.querySelector('.score span').textContent;
                if (scoreValue && scoreValue !== '-') {
                    finalScoreInput.value = scoreValue;
                    
                    // Determine difficulty level
                    let difficulty = 'medium'; // default
                    const velocityLevel = document.querySelector('.velocity-level span').textContent;
                    const timeLevel = document.querySelector('.time-level span').textContent;
                    
                    if (velocityLevel === '1' && timeLevel === '30') {
                        difficulty = 'easy';
                    } else if (velocityLevel === '3' && timeLevel === '5') {
                        difficulty = 'hard';
                    }
                    
                    difficultyInput.value = difficulty;
                }
            }
            
            // Game over event listener
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.target.classList.contains('flex') && 
                        mutation.target.classList.contains('enterName')) {
                        updateScoreValue();
                    }
                });
            });
            
            observer.observe(document.querySelector('.enterName'), {
                attributes: true,
                attributeFilter: ['class'],
            });
            <?php endif; ?>
            
            // Hide score saved notification after 3 seconds
            const scoreNotification = document.querySelector('.score-saved-notification');
            if (scoreNotification) {
                setTimeout(function() {
                    scoreNotification.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>