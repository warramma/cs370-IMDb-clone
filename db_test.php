<?php
$servername = "localhost";
$username = "your_schema_name"; // Use the user you created [cite: 112]
$password = "your_password";
$dbname = "your_schema_name";   // Use the schema name you chose [cite: 111]

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully!";

mysqli_close($conn);
?>
