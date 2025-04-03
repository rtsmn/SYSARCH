<?php
session_start();

// Include database connection
include("connect.php");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get the logged-in admin's username
$username = $_SESSION['username'];

// Fetch admin information
$query = "SELECT Firstname, Lastname, PROFILE_IMG FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin_info = mysqli_fetch_assoc($result);

// Fetch total users count
$total_users_query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
$total_users_result = mysqli_query($conn, $total_users_query);
$total_users = mysqli_fetch_assoc($total_users_result)['total'];

// Fetch recent announcements
$announcements_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$announcements_result = mysqli_query($conn, $announcements_query);

if (!$announcements_result) {
    // If query fails, create the announcements table
    $create_table_query = "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        posted_by VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table_query);
    $announcements_result = mysqli_query($conn, $announcements_query);
}

// Handle announcement deletion if requested
if (isset($_POST['delete_announcement'])) {
    $announcement_id = $_POST['announcement_id'];
    $delete_query = "DELETE FROM announcements WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $announcement_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?message=Announcement deleted successfully");
    exit();
}

// Handle new announcement submission
if (isset($_POST['add_announcement'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $posted_by = $admin_info['Firstname'] . ' ' . $admin_info['Lastname'];
    
    $insert_query = "INSERT INTO announcements (title, content, posted_by) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "sss", $title, $content, $posted_by);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?message=Announcement added successfully");
    exit();
}

// Handle announcement edit
if (isset($_POST['edit_announcement'])) {
    $announcement_id = $_POST['announcement_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $update_query = "UPDATE announcements SET title = ?, content = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssi", $title, $content, $announcement_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?message=Announcement updated successfully");
    exit();
}

// Fetch users by course statistics
$course_stats_query = "SELECT COURSE as course, COUNT(*) as count FROM users WHERE role != 'admin' GROUP BY COURSE";
$course_stats_result = mysqli_query($conn, $course_stats_query);

if (!$course_stats_result) {
    // If query fails, set an empty array to avoid the error
    $course_stats_result = [];
    error_log("Course statistics query failed: " . mysqli_error($conn));
}

// Handle user deletion if requested
if (isset($_POST['delete_user'])) {
    $user_to_delete = $_POST['username'];
    $delete_query = "DELETE FROM users WHERE username = ? AND role != 'admin'";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "s", $user_to_delete);
    mysqli_stmt_execute($stmt);
    header("Location: admin_dashboard.php?message=User deleted successfully");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CSS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #144c94;
            --secondary-color: #2c6a85;
            --accent-color: #ffd700;
            --danger-color: #dc3545;
            --success-color: #28a745;
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .welcome-text h1 {
            margin: 0;
            font-size: 2em;
        }

        .welcome-text p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-profile img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid var(--accent-color);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stats-card {
            text-align: center;
        }

        .stats-icon {
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .stats-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stats-label {
            color: #666;
            margin-top: 5px;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .users-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--primary-color);
        }

        .users-table tr:hover {
            background-color: #f5f5f5;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .course-stats {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .course-stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .course-stat-label {
            font-weight: 500;
        }

        .course-stat-value {
            color: var(--primary-color);
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
            }

            .navbar-links {
                margin-top: 15px;
            }

            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .add-btn:hover {
            background-color: #218838;
        }

        .announcement-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .add-announcement-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .add-announcement-form input,
        .add-announcement-form textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }

        .add-announcement-form textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
        }

        .submit-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .cancel-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .announcements-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .announcement-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .announcement-content {
            flex: 1;
        }

        .announcement-content h4 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
        }

        .announcement-content p {
            margin: 0 0 10px 0;
            color: #666;
        }

        .announcement-meta {
            display: flex;
            gap: 15px;
            color: #888;
            font-size: 0.9em;
        }

        .announcement-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-announcements {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .edit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-right: 5px;
        }

        .edit-btn:hover {
            background-color: var(--secondary-color);
        }

        .announcement-actions {
            display: flex;
            gap: 5px;
        }

        .edit-form {
            display: none;
            margin-top: 10px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .edit-form.active {
            display: block;
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
        <a href="admin_feedback.php"><i class="fas fa-feedback"></i> Feedback</a>
        <a href="logout.php" style="color: var(--accent-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

    <div class="main-content">
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome, <?php echo htmlspecialchars($admin_info['Firstname'] . ' ' . $admin_info['Lastname']); ?></h1>
                <p>Admin Dashboard Overview</p>
            </div>
            <div class="admin-profile">
                <img src="<?php echo !empty($admin_info['PROFILE_IMG']) ? htmlspecialchars($admin_info['PROFILE_IMG']) : 'images/default.jpg'; ?>" alt="Admin Profile">
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card stats-card">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number"><?php echo $total_users; ?></div>
                <div class="stats-label">Total Users</div>
            </div>

            <div class="card">
                <h3><i class="fas fa-chart-pie"></i> Course Distribution</h3>
                <div class="course-stats">
                    <?php 
                    if (is_object($course_stats_result) && mysqli_num_rows($course_stats_result) > 0):
                        while($course = mysqli_fetch_assoc($course_stats_result)): 
                    ?>
                    <div class="course-stat-item">
                        <span class="course-stat-label"><?php echo htmlspecialchars($course['course']); ?></span>
                        <span class="course-stat-value"><?php echo $course['count']; ?> users</span>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <div class="course-stat-item">
                        <span class="course-stat-label">No course data available</span>
                        <span class="course-stat-value">0 users</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="announcement-header">
                <h3><i class="fas fa-bullhorn"></i> Recent Announcements</h3>
                <button class="add-btn" onclick="showAnnouncementForm()">
                    <i class="fas fa-plus"></i> Add Announcement
                </button>
            </div>

            <!-- Add Announcement Form -->
            <div id="announcementForm" style="display: none;" class="announcement-form">
                <form method="POST" class="add-announcement-form">
                    <input type="text" name="title" placeholder="Announcement Title" required>
                    <textarea name="content" placeholder="Announcement Content" required></textarea>
                    <div class="form-buttons">
                        <button type="submit" name="add_announcement" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Post
                        </button>
                        <button type="button" onclick="hideAnnouncementForm()" class="cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>

            <div class="announcements-list">
                <?php 
                if (is_object($announcements_result) && mysqli_num_rows($announcements_result) > 0):
                    while($announcement = mysqli_fetch_assoc($announcements_result)): 
                ?>
                <div class="announcement-item" id="announcement-<?php echo $announcement['id']; ?>">
                    <div class="announcement-content">
                        <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                        <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                        <div class="announcement-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($announcement['posted_by']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                        </div>
                        
                        <!-- Edit Form -->
                        <div class="edit-form" id="edit-form-<?php echo $announcement['id']; ?>">
                            <form method="POST" class="add-announcement-form">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <input type="text" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                                <textarea name="content" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                                <div class="form-buttons">
                                    <button type="submit" name="edit_announcement" class="submit-btn">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="button" onclick="hideEditForm(<?php echo $announcement['id']; ?>)" class="cancel-btn">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="announcement-actions">
                        <button onclick="showEditForm(<?php echo $announcement['id']; ?>)" class="edit-btn">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                            <button type="submit" name="delete_announcement" class="delete-btn" onclick="return confirm('Are you sure you want to delete this announcement?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                ?>
                <div class="no-announcements">
                    <p>No announcements available</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showAnnouncementForm() {
            document.getElementById('announcementForm').style.display = 'block';
        }

        function hideAnnouncementForm() {
            document.getElementById('announcementForm').style.display = 'none';
        }

        function showEditForm(id) {
            // Hide all other edit forms first
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
            // Show the selected edit form
            document.getElementById('edit-form-' + id).style.display = 'block';
        }

        function hideEditForm(id) {
            document.getElementById('edit-form-' + id).style.display = 'none';
        }
    </script>
</body>
</html> 