<?php
// Database connection
include 'connect.php';

// Fetch today's sit-in records
$date_today = date('Y-m-d');
$query = "SELECT sitin.id, sitin.student_id, 
                 CONCAT(users.Firstname, ' ', users.Lastname) AS name, 
                 sitin.purpose, sitin.lab, sitin.sessions, sitin.date_created, sitin.end_time 
          FROM sitin
          INNER JOIN users ON sitin.student_id = users.id
          WHERE sitin.date_created = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date_today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $records[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Sit-in Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        
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

    <h1>Today's Sit-in Records</h1>
    <table>
        <thead>
            <tr>
                <th>Sit-in ID</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Purpose</th>
                <th>Lab</th>
                <th>Sessions</th>
                <th>Log In</th>
                <th>Log Out</th>
            </tr>
        </thead> 

        <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['id']); ?></td>
                    <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($record['name']); ?></td>
                    <td><?php echo htmlspecialchars($record['purpose']); ?></td>
                    <td><?php echo htmlspecialchars($record['lab']); ?></td>
                    <td><?php echo htmlspecialchars($record['sessions']); ?></td>
                    <td><?php echo htmlspecialchars($record['date_created']); ?></td>
                    <td><?php echo htmlspecialchars($record['end_time']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>