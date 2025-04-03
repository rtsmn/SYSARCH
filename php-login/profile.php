<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
include("connect.php");
$username = $_SESSION["username"];

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST["firstname"];
    $midname = $_POST["midname"];
    $lastname = $_POST["lastname"];
    $course = $_POST["course"];
    $year = $_POST["year"];

    // Handle file upload (profile picture)
    if (isset($_FILES["profile_img"]) && $_FILES["profile_img"]["error"] == 0) {
        $targetDir = "images/";
        $fileName = basename($_FILES["profile_img"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow only image file types
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];
        if (in_array(strtolower($fileType), $allowedTypes)) {
            if (move_uploaded_file($_FILES["profile_img"]["tmp_name"], $targetFilePath)) {
                // Successfully uploaded the file
            } else {
                echo "There was an error uploading your file.";
                exit();
            }
        } else {
            echo "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
            exit();
        }
    } else {
        // Keep existing image if no new file is uploaded
        $query = "SELECT PROFILE_IMG FROM users WHERE username=?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $targetFilePath = $row["PROFILE_IMG"];
    }

    // Update user details in the database using prepared statements
    $query = "UPDATE users SET Firstname=?, Midname=?, Lastname=?, course=?, year_level=?, PROFILE_IMG=? WHERE username=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssssss", $firstname, $midname, $lastname, $course, $year, $targetFilePath, $username);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: profile.php?success=1");
        exit();
    } else {
        echo "Error updating profile: " . mysqli_error($conn);
    }
}

// Fetch user details for display
$query = "SELECT Firstname, Midname, Lastname, course, year_level, PROFILE_IMG FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $firstname = $row["Firstname"];
    $midname = $row["Midname"];
    $lastname = $row["Lastname"];
    $course = $row["course"];
    $year = $row["year_level"];
    $profile_img = !empty($row["PROFILE_IMG"]) ? $row["PROFILE_IMG"] : "images/default.jpg"; // Default image if none exists
} else {
    echo "User not found!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: rgb(230, 233, 241);
        }
        .navbar {
            background-color: #144c94;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: larger;
        }
        .navbar a:hover {
            color: yellow;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            flex-direction: column;
            padding: 20px;
        }
        .card {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
            text-align: center;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #144c94;
        }
        input, select {
            width: 90%;
            padding: 8px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #144c94;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #0f3a6d;
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
            overflow: auto;
            background-color: rgb(0,0,0); 
            background-color: rgba(0,0,0,0.4); 
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            text-align: center;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

    </style>
</head>
<body>
    <div class="navbar">
        <a>Dashboard</a>
        <div>
            <a href="dashboard.php">Home</a>
            <a href="profile.php">Edit Profile</a>
            <a href="history.php">History</a>
            <a href="Reservation.php">Reservation</a>
            <a href="login.php" style="color: orange;">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h3>Edit Profile</h3>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Picture" class="profile-img"><br>
                <input type="file" name="profile_img" accept="image/*"><br>

                <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                <input type="text" name="midname" value="<?php echo htmlspecialchars($midname); ?>">
                <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                
                <select name="course">
                    <option value="BSCS" <?php if ($course == "BSCS") echo "selected"; ?>>BSCS</option>
                    <option value="BSIT" <?php if ($course == "BSIT") echo "selected"; ?>>BSIT</option>
                    <option value="BSIS" <?php if ($course == "BSIS") echo "selected"; ?>>BSIS</option>
                </select>

                <select name="year">
                    <option value="1" <?php if ($year == "1") echo "selected"; ?>>1st Year</option>
                    <option value="2" <?php if ($year == "2") echo "selected"; ?>>2nd Year</option>
                    <option value="3" <?php if ($year == "3") echo "selected"; ?>>3rd Year</option>
                    <option value="4" <?php if ($year == "4") echo "selected"; ?>>4th Year</option>
                </select>

                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Profile updated successfully!</p>
        </div>
    </div>

    <script>
        var modal = document.getElementById("successModal");
        var span = document.getElementsByClassName("close")[0];

        <?php if (isset($_GET["success"])): ?>
            modal.style.display = "block";
        <?php endif; ?>

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>