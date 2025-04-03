<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login";

// create a connection

$conn = new mysqli($servername,$username,$password,$dbname);
if($conn->connect_error){
    echo "connection failed";

} 

?>

