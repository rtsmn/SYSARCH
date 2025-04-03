<?php
session_start();
include "connect.php";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture and sanitize form input
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if ($password == $user['password']) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Store user role in session

            // Redirect based on user role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password!";
        }
    } else {
        $error_message = "Invalid username or password!";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>HTML Login Form</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="main">
        <img src="UC logo.jpg" alt="Logo" style="width: 150px; height: auto;">
        <img src="CCS.png" alt="Logo" style="width: 130px; height: auto;">
        
        <h1>CSS Sit-in Monitoring System</h1>
        
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <label for="username">
                Username:
            </label>
            <input type="text" id="username" name="username" 
                placeholder="Enter your Username" required>

            <label for="password">
                Password:
            </label>
            <input type="password" id="password" name="password" 
                placeholder="Enter your Password" required>

            <div class="wrap">
                <button type="submit">
                    Login
                </button>
                <a href="register.php" style="text-decoration: none;">Register</a>
            </div>
        </form>
        
    </div>
</body>

</html>
