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
$userScores = getUserScores($userId, 10);

// Get global top scores
$topScores = getTopScores(10);

// Calculate user stats
$totalGames = count($userScores);
$bestScore = !empty($userScores) ? $userScores[0]['score'] : 0;
$averageScore = 0;
if ($totalGames > 0) {
    $totalPoints = array_sum(array_column($userScores, 'score'));
    $averageScore = round($totalPoints / $totalGames, 1);
}

// Find user's rank in global leaderboard
$userRank = 0;
foreach ($topScores as $index => $score) {
    if ($score['username'] === $user['username']) {
        $userRank = $index + 1;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Whack-A-Mole Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="css/hammer2.png">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        .stat-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px 15px 0 0;
        }
        .table th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .table td {
            border: none;
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }
        .table tbody tr {
            border-bottom: 1px solid #f1f3f4;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .highlight-row {
            background-color: rgba(102, 126, 234, 0.1) !important;
            border-left: 4px solid #667eea;
        }
        .difficulty-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        .difficulty-easy { background-color: #d4edda; color: #155724; }
        .difficulty-medium { background-color: #fff3cd; color: #856404; }
        .difficulty-hard { background-color: #f8d7da; color: #721c24; }
        
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4a); color: #b7791f; }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e2e8f0); color: #4a5568; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #d69e2e); color: #744210; }
        .rank-other { background: #e9ecef; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card main-card">
                    <div class="dashboard-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="bi bi-person-circle me-2"></i>
                                    Welcome, <?php echo htmlspecialchars($user['username']); ?>!
                                </h1>
                                <p class="mb-0 opacity-75">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Member since <?php echo formatDate($user['created_at'], 'M j, Y'); ?>
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-gear me-1"></i> Menu
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="index.php">
                                            <i class="bi bi-controller me-2"></i>Play Game
                                        </a></li>
                                        <li><a class="dropdown-item" href="profile.php">
                                            <i class="bi bi-person-gear me-2"></i>Profile Settings
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
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
                        
                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card h-100">
                                    <div class="stat-icon primary">
                                        <i class="bi bi-controller"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $totalGames; ?></div>
                                    <div class="stat-label">Total Games</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card h-100">
                                    <div class="stat-icon success">
                                        <i class="bi bi-trophy"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $bestScore; ?></div>
                                    <div class="stat-label">Best Score</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card h-100">
                                    <div class="stat-icon warning">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $averageScore; ?></div>
                                    <div class="stat-label">Average Score</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card h-100">
                                    <div class="stat-icon info">
                                        <i class="bi bi-award"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $userRank > 0 ? '#' . $userRank : '-'; ?></div>
                                    <div class="stat-label">Global Rank</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- User's Recent Scores -->
                            <div class="col-lg-6 mb-4">
                                <div class="card section-card h-100">
                                    <div class="section-header">
                                        <h5 class="mb-0">
                                            <i class="bi bi-clock-history me-2"></i>Your Recent Scores
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($userScores)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-controller" style="font-size: 3rem; color: #dee2e6;"></i>
                                                <p class="text-muted mt-3 mb-3">No games played yet</p>
                                                <a href="index.php" class="btn btn-primary">
                                                    <i class="bi bi-play-circle"></i> Start Playing
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Score</th>
                                                            <th>Difficulty</th>
                                                            <th>Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach (array_slice($userScores, 0, 5) as $score): ?>
                                                            <tr>
                                                                <td>
                                                                    <strong class="text-primary"><?php echo $score['score']; ?></strong>
                                                                </td>
                                                                <td>
                                                                    <span class="difficulty-badge difficulty-<?php echo strtolower($score['difficulty']); ?>">
                                                                        <?php echo ucfirst($score['difficulty']); ?>
                                                                    </span>
                                                                </td>
                                                                <td class="text-muted">
                                                                    <?php echo formatDate($score['created_at'], 'M j, g:i A'); ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php if (count($userScores) > 5): ?>
                                                <div class="text-center">
                                                    <small class="text-muted">And <?php echo count($userScores) - 5; ?> more games...</small>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Global Leaderboard -->
                            <div class="col-lg-6 mb-4">
                                <div class="card section-card h-100">
                                    <div class="section-header">
                                        <h5 class="mb-0">
                                            <i class="bi bi-trophy me-2"></i>Global Leaderboard
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($topScores)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-trophy" style="font-size: 3rem; color: #dee2e6;"></i>
                                                <p class="text-muted mt-3">No scores recorded yet</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Rank</th>
                                                            <th>Player</th>
                                                            <th>Score</th>
                                                            <th>Level</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach (array_slice($topScores, 0, 5) as $index => $score): ?>
                                                            <tr class="<?php echo $score['username'] === $user['username'] ? 'highlight-row' : ''; ?>">
                                                                <td>
                                                                    <span class="rank-badge rank-<?php echo $index < 3 ? $index + 1 : 'other'; ?>">
                                                                        <?php echo $index + 1; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <strong><?php echo htmlspecialchars($score['username']); ?></strong>
                                                                    <?php if ($score['username'] === $user['username']): ?>
                                                                        <small class="text-primary">(You)</small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <strong class="text-success"><?php echo $score['score']; ?></strong>
                                                                </td>
                                                                <td>
                                                                    <span class="difficulty-badge difficulty-<?php echo strtolower($score['difficulty']); ?>">
                                                                        <?php echo ucfirst($score['difficulty']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card section-card">
                                    <div class="card-body text-center py-4">
                                        <h6 class="text-muted mb-3">Ready for another round?</h6>
                                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                                            <a href="index.php" class="btn btn-primary btn-lg">
                                                <i class="bi bi-play-circle me-2"></i>Play Game
                                            </a>
                                            <a href="profile.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-person-gear me-2"></i>Edit Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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