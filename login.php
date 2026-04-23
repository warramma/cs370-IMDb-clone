<?php include_once("components/_header.php"); ?>
<?php
    //login logic
if($_SERVER["REQUEST_METHOD"] == "POST"){
    //get data from the form
    $user = $_POST["username"];
    $pass = $_POST["password"];
    //check for database connection
    include("components/_connection.php");
    $connection_error = false;
    $connection_error_message = "";

    $con = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']); //<----VERY IMPORTANT!!

    if(mysqli_connect_errno()){
        $connection_error = true;
        $connection_error_message = "Error connecting to the database: " . mysqli_connect_error();
    }

    //check for username in the database
    //grab the password hash from the database
    $hash = mysqli_query($con, "SELECT PasswordHash FROM User WHERE Username = '$user'");

//    $admin = password_hash("admin", PASSWORD_BCRYPT);
//    echo $admin;
//    echo "<br>";
//    $enteredPassword = 'admin';
    if (password_verify($pass, $hash)) {
        echo 'Password is valid!';
    } else {
        echo 'Invalid password.';
    }

}


?>
<div class="container">
    <div>
        <h1>Login</h1>
        <form method="post" enctype="multipart/form-data" action="login.php">
            <div class="input-group md-3">
                <label class="input-group-text" for="username" >Username:</label>
                <input id="username" type = "text" name="username"/>
            </div>
            <div class="input-group md-3">
                <label class = "input-group-text" for="password">Password:</label>
                <input id="password" type="password" name="password"/>
            </div>
            <input class="btn btn-primary" type="submit" value="Log In"/>
        </form>
    </div>

</div>


<?php include_once("components/_footer.php"); ?>
