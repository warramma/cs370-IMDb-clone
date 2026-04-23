<?php include_once("components/_header.php"); ?>
<?php
    //login logic
if($_SERVER["REQUEST_METHOD"] == "POST"){
    //check for database connection
    //check for username in the database
    //grab the password hash from the database

    $hash =  password_hash("test", PASSWORD_BCRYPT);
    $enteredPassword = 'test';
    if (password_verify($enteredPassword, $hash)) {
        echo 'Password is valid!';
    } else {
        echo 'Invalid password.';
    }

}


?>
<div class="container">
    <div>
        <h1>Login</h1>
        <form method="post" enctype="multipart/form-data">
            <div class="input-group md-3">
                <label class="input-group-text" for="username" >Username:</label>
                <input id="username" type = "text"/>
            </div>
            <div class="input-group md-3">
                <label class = "input-group-text" for="password">Password:</label>
                <input id="password" type="password"/>
            </div>
            <input class="btn btn-primary" type="submit" value="Log In"/>
        </form>
    </div>

</div>


<?php include_once("components/_footer.php"); ?>
