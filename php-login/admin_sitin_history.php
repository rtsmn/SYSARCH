<?php
session_start();

// Include database connection
include("connect.php");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle filters
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT s.*, u.Firstname, u.Lastname, u.PROFILE_IMG, u.course, u.year_level 
          FROM sitin s 
          JOIN users u ON s.student_id = u.username 
          WHERE s.status = 'completed'";

// Add filters
if (!empty($date_filter)) {
    $query .= " AND DATE(s.date_created) = ?";
}
if (!empty($course_filter)) {
    $query .= " AND u.course = ?";
}
if (!empty($search)) {
    $query .= " AND (u.username LIKE ? OR u.Firstname LIKE ? OR u.Lastname LIKE ?)";
}

$query .= " ORDER BY s.date_created DESC";

// Prepare and execute query
$stmt = mysqli_prepare($conn, $query);

// Bind parameters if they exist
if (!empty($date_filter) || !empty($course_filter) || !empty($search)) {
    $types = '';
    $params = array();
    
    if (!empty($date_filter)) {
        $types .= 's';
        $params[] = $date_filter;
    }
    if (!empty($course_filter)) {
        $types .= 's';
        $params[] = $course_filter;
    }
    if (!empty($search)) {
        $types .= 'sss';
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch available courses for filter
$courses_query = "SELECT DISTINCT course FROM users WHERE role != 'admin' AND course IS NOT NULL";
$courses_result = mysqli_query($conn, $courses_query);
$courses = [];
while ($row = mysqli_fetch_assoc($courses_result)) {
    if (!empty($row['course'])) {
        $courses[] = $row['course'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History - Admin Dashboard</title>
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

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-item {
            flex: 1;
            min-width: 200px;
        }

        .filter-item input,
        .filter-item select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }

        .search-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: var(--secondary-color);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--primary-color);
        }

        .history-table tr:hover {
            background-color: #f5f5f5;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .duration {
            font-weight: 500;
            color: var(--primary-color);
        }

        .no-records {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #666;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }

            .filter-item {
                width: 100%;
            }

            .history-table {
                display: block;
                overflow-x: auto;
            }
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
        <h1 class="page-title">
            <i class="fas fa-history"></i>
            Sit-in History
        </h1>

        <form method="GET" class="filters">
            <div class="filter-item">
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="filter-item">
                <select name="course">
                    <option value="">All Courses</option>
                    <?php foreach($courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course); ?>" <?php echo $course_filter === $course ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <input type="text" name="search" placeholder="Search by ID or name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
            </button>
        </form>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>Date</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($session = mysqli_fetch_assoc($result)): 
                        $start_time = strtotime($session['date_created']);
                        $end_time = strtotime($session['end_time']);
                        $duration = $end_time - $start_time;
                        $hours = floor($duration / 3600);
                        $minutes = floor(($duration % 3600) / 60);
                    ?>
                    <tr>
                        <td>
                            <img src="<?php echo !empty($session['PROFILE_IMG']) ? htmlspecialchars($session['PROFILE_IMG']) : 'images/default.jpg'; ?>" 
                                 alt="Student Photo" 
                                 class="student-avatar">
                        </td>
                        <td><?php echo htmlspecialchars($session['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($session['Firstname'] . ' ' . $session['Lastname']); ?></td>
                        <td><?php echo htmlspecialchars($session['course']); ?></td>
                        <td><?php echo htmlspecialchars($session['purpose']); ?></td>
                        <td><?php echo htmlspecialchars($session['lab']); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($session['date_created'])); ?></td>
                        <td class="duration"><?php echo "{$hours}h {$minutes}m"; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-records">
                <i class="fas fa-info-circle"></i>
                <p>No sit-in history records found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 