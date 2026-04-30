<?php

include("components/_connection.php");

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$import_attempted = false;
$import_succeeded = false;
$import_error_message = "";
$rows_inserted = 0;
$rows_skipped = 0;
$rows_updated = 0;
$user_rows_processed = 0;
$prod_rows_processed = 0;
$lang_rows_processed = 0;

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $import_attempted = true;
    $con = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']); //<----VERY IMPORTANT!!
    if(mysqli_connect_errno()){
        $import_error_message = "Error connecting to the database: " . mysqli_connect_error();
    }
    else{
        try {
            $lines = file($_FILES['importFile']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $stmtGetUser = $con->prepare("SELECT UserID FROM User WHERE Username = ?");
            $stmtInsertUser = $con->prepare("INSERT INTO User (Username, PasswordHash, JoinDate, Email, Birthdate) VALUES (?, ?, ?, ?, ?)");
            $stmtUpdateUser = $con->prepare("UPDATE User SET PasswordHash=?, JoinDate=?, Email=?, Birthdate=? WHERE UserID=?");

            $stmtGetProd = $con->prepare("SELECT ProductionCompanyID FROM ProductionCompany WHERE CompanyName = ?");
            $stmtInsertProd = $con->prepare("INSERT INTO ProductionCompany (CompanyName, Headquarters, `Founded Date`) VALUES (?, ?, ?)");
            $stmtUpdateProd = $con->prepare("UPDATE ProductionCompany SET Headquarters=?, `Founded Date`=? WHERE ProductionCompanyID=?");

            $stmtGetLang = $con->prepare("SELECT LanguageID FROM Language WHERE Language = ?");
            $stmtInsertLang = $con->prepare("INSERT INTO Language (Language) VALUES (?)");

            $valid_type_found = false;

            for ($x = 1; $x < count($lines); $x++) {
                $row = str_getcsv($lines[$x], ",", '"', "");
                if (empty($row) || !isset($row[0]) || trim($row[0]) === "") {
                    $rows_skipped++;
                    continue;
                }

                $type = trim($row[0]);

                if($type == 'Language'){
                    $valid_type_found = True;
                    // ERD: Language(LanguageID, Language)
                    $stmtGetLang-> bind_param("s", $row[1]); //bind parameters, expecting one string.
                    $stmtGetLang->execute();
                    if($stmtGetLang->get_result()->num_rows == 0){
                        $stmtInsertLang->bind_param("s", $row[1]);
                        $stmtInsertLang->execute();
                        $rows_inserted++;
                    } else {
                        $rows_skipped++;
                    }
                    $lang_rows_processed++;
                }
                elseif ($type == 'Production') {
                    $valid_type_found = True;
                    // ERD: Production Company(Production CompanyID, CompanyName, Headquarters, FoundedDate)
                    $foundedDate = (!empty($row[3])) ? $row[3] : null;
                    $stmtGetProd->bind_param("s", $row[1]); //bind parameters, type is 3 strings.
                    $stmtGetProd->execute();
                    $res = $stmtGetProd->get_result();
                    if($existing = $res->fetch_assoc()){
                    $stmtUpdateProd->bind_param("ssi", $row[2], $foundedDate, $existing['ProductionCompanyID']);
                    $stmtUpdateProd->execute();
                    $rows_updated++;
                } else {
                        $stmtInsertProd->bind_param("sss", $row[1], $row[2], $foundedDate);
                        $stmtInsertProd->execute();
                        $rows_inserted++;
                    }

                    $prod_rows_processed++;
                }
                elseif ($type == 'User') {
                    $valid_type_found = True;
                    // ERD: User(UserID, Username, PasswordHash, Join Date, Email, Birthdate)
                    $stmtGetUser->bind_param("s", $row[1]);
                    $stmtGetUser->execute();
                    $res = $stmtGetUser->get_result();
                    if($existing = $res->fetch_assoc()){
                    $stmtUpdateUser->bind_param("ssssi", $row[2], $row[3], $row[4], $row[5], $existing['UserID']);
                    $stmtUpdateUser->execute();
                    $rows_updated++;
                } else {
                        $stmtInsertUser->bind_param("sssss", $row[1], $row[2], $row[3], $row[4], $row[5]);
                        $stmtInsertUser->execute();
                        $rows_inserted++;
                    }
                    $user_rows_processed++;
                }
                else {
                    // This row didn't match any known RecordType
                    $rows_skipped++;
                }
            }

            if ($rows_inserted == 0 && $rows_skipped > 0 && ($rows_skipped == count($lines) - 1) && !$valid_type_found) {
                $import_succeeded = false;
                $import_error_message = "Invalid File Format: No valid 'Language', 'Production', or 'User' records were found. Please check your CSV column structure.";
            } else {
                $import_succeeded = true;
            }
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
    <h1>Import User-Production-Language Data</h1>
    <?php
    if($import_attempted){
        if ($import_succeeded) {
            echo "<br>";
            echo "<div class='alert alert-success' role='alert'>";
            echo "<h2>Import success</h2>";
            echo "<p>Inserted: " . h($rows_inserted) . "<br>Updated: " . h($rows_updated) . "<br>Skipped: " . h($rows_skipped) . "</p>";
            echo "<p>User rows processed: " . h($user_rows_processed) . "<br>Production company rows processed: " . h($prod_rows_processed) . "<br>Language rows processed: " . h($lang_rows_processed) . "</p>";
            echo "</div>";
        } else {
            echo "<br>";
            echo "<div class='alert alert-danger' role='alert'>";
            echo "<h2>Import failure</h2>";
            echo "<p>" . h($import_error_message) . "</p>";
            echo "</div>";
        }
    }
    ?>
    <p>Expected headers:</p>
    <code>RecordType, Content</code>
    <br><br>
    <form method="post" enctype="multipart/form-data">
        <div class="input-group md-3">
            <span class="input-group-text">File:</span>
            <input class="form-control" type="file" name="importFile" accept=".csv" required/>
        </div>
        <br>
        <input type="submit" value="Upload Data" class="btn btn-primary"/>
    </form>
</div>
<?php
include("components/_footer.php");
?>