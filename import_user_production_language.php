<?php

include("components/_connection.php");
$import_attempted = false;
$import_succeeded = false;
$import_error_message = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $import_attempted = true;
    $con = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']); //<----VERY IMPORTANT!!
    if(mysqli_connect_errno()){
        $import_error_message = "Error connecting to the database: " . mysqli_connect_error();
    }
    else{
        try {
            $lines = file($_FILES['importFile']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $stmtLang = $con->prepare("INSERT IGNORE INTO Language (Language) VALUES (?)");
            $stmtProd = $con->prepare("INSERT IGNORE INTO ProductionCompany (CompanyName, Headquarters, `Founded Date`) VALUES (?, ?, ?)");
            $stmtUser = $con->prepare("INSERT IGNORE INTO User (`Username`, PasswordHash, `JoinDate`, Email, Birthdate) VALUES (?, ?, ?, ?, ?)");

            for ($x = 1; $x < count($lines); $x++) {
                $row = str_getcsv($lines[$x], ",", '"', "");
                if (empty($row[0])) continue;
                $type = $row[0]; //get record type from current row, index 0 (so first column)

                if($type == 'Language'){
                    // ERD: Language(LanguageID, Language)
                    $stmtLang-> bind_param("s", $row[1]); //bind parameters, expecting one string.
                    $stmtLang->execute();
                }
                elseif ($type == 'Production') {
                    // ERD: Production Company(Production CompanyID, CompanyName, Headquarters, FoundedDate)
                    $stmtProd->bind_param("sss", $row[1], $row[2], $row[3]); //bind parameters, type is 3 strings.
                    $stmtProd->execute();
                }
                elseif ($type == 'User') {
                    // ERD: User(UserID, Username, PasswordHash, Join Date, Email, Birthdate)
                    $stmtUser->bind_param("sssss", $row[1], $row[2], $row[3], $row[4], $row[5]);
                    $stmtUser->execute();
                }
            }

            echo "</pre>";
            $import_succeeded = true;
        }
        catch(Exception $e){
            $import_error_message = $e->getMessage()
                ." at:" . $e->getFile()." at line ".$e->getLine();
        }

    }
}
$pageTitle = "Import User-Production-Language Data";

include('components/_header.php');
?>
<div class="container">
    <?php if($import_attempted): ?>
        <?php if($import_succeeded): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> The file was processed. Existing records were skipped, and new records were added.
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>Import Failed:</strong> <?php echo htmlspecialchars($import_error_message); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <h1>Import User-Production-Language Data</h1>
    <form method="post" enctype="multipart/form-data">
        <div class="input-group md-3">
            <span class="input-group-text">File:</span>
            <input class="form-control" type="file" name="importFile"/>
        </div>
        <input type="submit" value="Upload Data"/>
    </form>
    <?php
    echo "Php is working on playground" ;
    ?>
</div>
<?php
include("components/_footer.php");
?>