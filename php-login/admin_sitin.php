<?php
session_start();

// Include database connection
include("connect.php");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle sit-in form submission
if (isset($_POST['submit_sitin'])) {
    $student_id = $_POST['student_id'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $sessions = $_POST['sessions'];
    $date = date('Y-m-d H:i:s');

    // Check remaining sessions
    $check_sessions = "SELECT sessions_remaining FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $check_sessions);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);

    if ($user_data['sessions_remaining'] <= 0) {
        $error_message = "This student has no remaining sessions.";
    } else {
        // Insert into sitin table
        $insert_query = "INSERT INTO sitin (student_id, purpose, lab, sessions, date_created) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "sssis", $student_id, $purpose, $lab, $sessions, $date);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Sit-in request processed successfully!";
        } else {
            $error_message = "Error processing sit-in request.";
        }
    }
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$students = [];

if (!empty($search)) {
    $query = "SELECT *, sessions_remaining FROM users WHERE role != 'admin' AND 
             (ID LIKE ? OR Firstname LIKE ? OR Lastname LIKE ?)";
    $stmt = mysqli_prepare($conn, $query);
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Management - Admin Dashboard</title>
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

        .search-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .search-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: var(--secondary-color);
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .students-table th,
        .students-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .students-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--primary-color);
        }

        .students-table tr:hover {
            background-color: #f5f5f5;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sitin-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .sitin-btn:hover {
            background-color: var(--secondary-color);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close-btn {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1em;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
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

        .sessions-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }

        .sessions-empty {
            background-color: #dc3545;
        }

        .sitin-btn-disabled {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: not-allowed;
            opacity: 0.65;
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
        <div class="search-section">
            <h2><i class="fas fa-search"></i> Search Students</h2>
            <form method="GET" class="search-box">
                <input type="text" name="search" class="search-input" placeholder="Search by ID or name..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($students)): ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Sessions Left</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $student): ?>
                    <tr>
                        <td>
                            <img src="<?php echo !empty($student['PROFILE_IMG']) ? htmlspecialchars($student['PROFILE_IMG']) : 'images/default.jpg'; ?>" 
                                 alt="Student Photo" 
                                 class="student-avatar">
                        </td>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                        <td><?php echo htmlspecialchars($student['Firstname'] . ' ' . $student['Lastname']); ?></td>
                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                        <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                        <td>
                            <span class="sessions-badge <?php echo $student['sessions_remaining'] <= 0 ? 'sessions-empty' : ''; ?>">
                                <?php echo htmlspecialchars($student['sessions_remaining']); ?> sessions
                            </span>
                        </td>
                        <td>
                            <?php if ($student['sessions_remaining'] > 0): ?>
                            <button class="sitin-btn" onclick="openSitinForm('<?php echo $student['username']; ?>', '<?php echo htmlspecialchars($student['Firstname'] . ' ' . $student['Lastname']); ?>')">
                                <i class="fas fa-desktop"></i> Sit-in
                            </button>
                            <?php else: ?>
                            <button class="sitin-btn-disabled" disabled title="No sessions remaining">
                                <i class="fas fa-desktop"></i> Sit-in
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (!empty($search)): ?>
            <p>No students found matching your search.</p>
        <?php endif; ?>
    </div>

    <!-- Sit-in Modal -->
    <div id="sitinModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeSitinForm()">&times;</span>
            <h2>Sit-In Form</h2>
            <form method="POST" id="sitinForm">
                <input type="hidden" name="student_id" id="student_id">
                
                <div class="form-group">
                    <label>Student Name:</label>
                    <input type="text" id="student_name" readonly>
                </div>

                <div class="form-group">
                    <label>Remaining Sessions:</label>
                    <input type="text" id="sessions_remaining" readonly>
                </div>

                <div class="form-group">
                    <label>Purpose:</label>
                    <select name="purpose" required>
                        <option value="">Select Purpose</option>
                        <option value="Programming">Programming</option>
                        <option value="Research">Research</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Project">Project</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Lab:</label>
                    <select name="lab" required>
                        <option value="">Select Lab</option>
                        <option value="534">534</option>
                        <option value="535">535</option>
                        <option value="536">536</option>
                    </select>
                </div>

                <input type="hidden" name="sessions" value="1">

                <button type="submit" name="submit_sitin" class="submit-btn">
                    <i class="fas fa-check"></i> Process Sit-in
                </button>
            </form>
        </div>
    </div>

    <script>
        function openSitinForm(studentId, studentName) {
            // Get the sessions remaining from the table row
            const row = event.target.closest('tr');
            const sessionsText = row.querySelector('.sessions-badge').textContent;
            const sessions = parseInt(sessionsText);
            
            document.getElementById('sitinModal').style.display = 'block';
            document.getElementById('student_id').value = studentId;
            document.getElementById('student_name').value = studentName;
            document.getElementById('sessions_remaining').value = sessions + ' sessions remaining';
        }

        function closeSitinForm() {
            document.getElementById('sitinModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('sitinModal')) {
                closeSitinForm();
            }
        }
    </script>
</body>
</html> 