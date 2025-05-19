# Whack-A-Mole Game with PHP Authentication

A classic Whack-A-Mole game enhanced with PHP MySQL authentication to track player scores and provide leaderboards.

![Whack-A-Mole Game](css/hammer.png)

## Features

- **Classic Whack-A-Mole Gameplay**: Hit moles as they appear from holes to score points
- **Multiple Difficulty Levels**: Easy, medium, and hard modes
- **Customizable Settings**: Control mole speed, game duration, and sound effects
- **User Authentication System**:
  - User registration and login
  - Password reset functionality
  - User profile management
- **Score Tracking**:
  - Personal score history
  - Global leaderboards
  - Score filtering by difficulty
- **Responsive Design**: Playable on desktop and mobile devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- MAMP, XAMPP, or similar local development environment
- Web browser with JavaScript enabled

## Installation

### Using MAMP (Recommended)

1. **Install MAMP**:
   - Download and install [MAMP](https://www.mamp.info/) if you haven't already

2. **Clone or Download the Project**:
   - Download the ZIP file or clone the repository
   - Extract files to your MAMP htdocs folder (typically `/Applications/MAMP/htdocs/whackamole`)

3. **Start MAMP Services**:
   - Launch MAMP application
   - Start the Apache and MySQL servers
   - Ensure MySQL is running on port 8889 (default for MAMP)

4. **Access the Game**:
   - Open your web browser
   - Navigate to `http://localhost:8888/whackamole` (adjust the port if your MAMP uses a different one)
   - The database and tables will be automatically created on first access

### Manual Database Setup (Optional)

If you prefer to set up the database manually rather than letting the application do it automatically:

1. **Create the Database**:
   - Open phpMyAdmin (usually at `http://localhost:8888/phpMyAdmin`)
   - Create a new database called `whackamole_game`

2. **Import SQL Schema**:
   - Use the following SQL to create the required tables:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    difficulty VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Configuration

### Database Settings

If you need to modify the database connection settings (different port, username, or password):

1. Open `config/database.php`
2. Update the following constants:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'whackamole_game');
   define('DB_USER', 'root');
   define('DB_PASS', 'root'); // Change if your MAMP password is different
   define('DB_PORT', '8889'); // Change if your MySQL port is different
   ```

### Game Settings

The game has several customizable settings that can be adjusted in `js/script.js`:

- Mole appearance speed
- Game duration
- Sound effects

## Usage

### Playing as a Guest

1. Click "Play as Guest" on the login screen
2. Select a difficulty level
3. Click "START" to begin playing
4. Hit moles as they appear to score points

### Playing as a Registered User

1. Register a new account or log in with existing credentials
2. Select a difficulty level
3. Play the game
4. Your score will be automatically saved to your profile
5. View your scores and global rankings on the dashboard

## File Structure

```
whack-a-mole/
├── assets/
│   ├── css/
│   │   └── auth.css
│   └── js/
│       └── auth.js
├── config/
│   └── database.php
├── css/
│   └── [Game stylesheets and assets]
├── includes/
│   ├── auth.php
│   ├── functions.php
│   └── session.php
├── js/
│   └── script.js
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── profile.php
├── register.php
├── reset-password.php
└── README.md
```

## Troubleshooting

### Database Connection Issues

- Verify MAMP services are running
- Check if MySQL is running on port 8889
- Ensure database credentials in `config/database.php` match your MAMP settings

### Game Not Starting

- Check browser console for JavaScript errors
- Ensure all game assets are properly loaded
- Try clearing browser cache and cookies

### Authentication Problems

- If registration fails, check if the username or email is already in use
- For login issues, use the password reset function
- Session problems can often be resolved by clearing browser cookies

## Security Notes

- This application uses password hashing for secure storage
- CSRF protection is implemented for forms
- Input sanitization helps prevent XSS attacks
- Session management includes protection against session fixation

## Credits

- Original Whack-A-Mole game assets by [mitri.dvp](https://www.mitri-dvp.com/)
- Authentication system developed as an extension to the game

## License

This project is for educational purposes only. Game assets and original game code retain their original licenses.