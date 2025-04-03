<?php
session_start();

// Include database connection
include("connect.php");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Create sitin table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS sitin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    purpose VARCHAR(100) NOT NULL,
    lab VARCHAR(50) NOT NULL,
    sessions INT NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME NULL,
    status VARCHAR(20) DEFAULT 'active'
)";

if (!mysqli_query($conn, $create_table_query)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Handle ending a session
if (isset($_POST['end_session'])) {
    $sitin_id = $_POST['sitin_id'];
    $student_id = $_POST['student_id'];
    $end_time = date('Y-m-d H:i:s');

    // Update sitin table
    $update_query = "UPDATE sitin SET end_time = ?, status = 'completed' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $end_time, $sitin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Deduct one session from the student's remaining sessions
        $update_sessions = "UPDATE users SET sessions_remaining = sessions_remaining - 1 WHERE username = ?";
        $stmt = mysqli_prepare($conn, $update_sessions);
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);
        
        $success_message = "Session ended successfully!";
    } else {
        $error_message = "Error ending session.";
    }
}

// Fetch current active sit-in sessions
$query = "SELECT s.*, u.Firstname, u.Lastname, u.PROFILE_IMG, u.course, u.year_level 
          FROM sitin s 
          JOIN users u ON s.student_id = u.username 
          WHERE s.status = 'active' 
          ORDER BY s.date_created DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching sessions: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Sit-in Sessions - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #144c94;
            --secondary-color: #2c6a85;
            --accent-color: #ffd700;
            --light-bg: #f4f6f8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-bg);
        }

        .navbar {
        background-color: var(--primary-color);
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
        display: flex;
        align-items: center;
        color: white;
        text-decoration: none;
        font-size: 1.5em;
        font-weight: bold;
        }

        .navbar-brand img {
        height: 40px;
        margin-right: 10px;
        }

        .navbar-links {
            display: flex;
            gap: 20px; /* Adds spacing between links */
        }

        .navbar-links a {
            color: white;
            text-decoration: none;
            font-size: 1em;
            display: flex;
            align-items: center;
        }

        .navbar-links a:hover {
            color: var(--accent-color);
        }

        .main-content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .session-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .student-details h3 {
            margin: 0;
            color: var(--primary-color);
        }

        .student-details p {
            margin: 5px 0;
            color: #666;
        }

        .session-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .detail-label {
            color: #666;
        }

        .detail-value {
            color: var(--primary-color);
            font-weight: 500;
        }

        .end-session-btn {
            width: 100%;
            padding: 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        .end-session-btn:hover {
            background-color: #c82333;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-sessions {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #666;
        }

        .timer {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="#" class="navbar-brand">
        <img src="UC logo.jpg" alt="UC Logo">
        CSS Sit-in Monitoring System
    </a>
    <div class="navbar-links">
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="admin_students.php"><i class="fas fa-users"></i> Students</a>
        <a href="admin_sitin.php"><i class="fas fa-desktop"></i> Sit-in</a>
        <a href="admin_current_sitin.php"><i class="fas fa-clock"></i> Current Sessions</a>
        <a href="todays_sitin_records.php"><i class="fas fa-calendar-day"></i> View Sit-In Records</a>
        <a href="admin_sitin_history.php"><i class="fas fa-history"></i> History</a>
        <a href="feedback.php"><i class="fas fa-feedback"></i> Feedback</a>
        <a href="logout.php" style="color: var(--accent-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

    <div class="main-content">
        <h1 class="page-title">
            <i class="fas fa-clock"></i>
            Current Sit-in Sessions
        </h1>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="sessions-grid">
                <?php while($session = mysqli_fetch_assoc($result)): ?>
                    <div class="session-card">
                        <div class="student-info">
                            <img src="<?php echo !empty($session['PROFILE_IMG']) ? htmlspecialchars($session['PROFILE_IMG']) : 'images/default.jpg'; ?>" 
                                 alt="Student Photo" 
                                 class="student-avatar">
                            <div class="student-details">
                                <h3><?php echo htmlspecialchars($session['Firstname'] . ' ' . $session['Lastname']); ?></h3>
                                <p><?php echo htmlspecialchars($session['course']) . ' - ' . htmlspecialchars($session['year_level']); ?></p>
                            </div>
                        </div>
                        <div class="session-details">
                            <div class="detail-item">
                                <span class="detail-label">Student ID:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($session['student_id']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Purpose:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($session['purpose']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Lab:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($session['lab']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Start Time:</span>
                                <span class="detail-value"><?php echo date('h:i A', strtotime($session['date_created'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Duration:</span>
                                <span class="detail-value timer" id="timer-<?php echo $session['id']; ?>">
                                    Calculating...
                                </span>
                            </div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to end this session?');">
                            <input type="hidden" name="sitin_id" value="<?php echo $session['id']; ?>">
                            <input type="hidden" name="student_id" value="<?php echo $session['student_id']; ?>">
                            <button type="submit" name="end_session" class="end-session-btn">
                                <i class="fas fa-stop-circle"></i> End Session
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-sessions">
                <i class="fas fa-info-circle"></i>
                <p>No active sit-in sessions at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Update timers for each session
        function updateTimers() {
            document.querySelectorAll('.timer').forEach(timer => {
                const sessionId = timer.id.split('-')[1];
                const startTime = new Date('<?php echo $session['date_created']; ?>').getTime();
                const now = new Date().getTime();
                const duration = now - startTime;

                const hours = Math.floor(duration / (1000 * 60 * 60));
                const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));
                
                timer.textContent = `${hours}h ${minutes}m`;
            });
        }

        // Update timers every minute
        setInterval(updateTimers, 60000);
        updateTimers();
    </script>
</body>
</html> 