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
            $import_attempted = true;

            $stmtShow = $con->prepare("INSERT IGNORE INTO Show (Title, ReleaseDate, EndDate, MaturityRating, ProductionCompanyID, LanguageID, GenreID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtEp = $con->prepare("INSERT IGNORE INTO Episode (ShowID, SeasonNumber, EpisodeNumber, EpisodeTitle) VALUES (?, ?, ?, ?)");
            $stmtPerson = $con->prepare("INSERT IGNORE INTO Person (ShowID, `Role`, `Name`, BornIn, Birthdate) VALUES (?, ?, ?, ?, ?)");

            $rows_added = 0;

            for ($x = 1; $x < count($lines); $x++) {
                $row = str_getcsv($lines[$x], ",", '"', "");
                $showstart = $row[0];
                $episodestart = $row[12];
                $personstart = $row[7];

                    if(!empty($showstart)){
                        // ERD: Show(ShowID, Title, ReleaseDate, EndDate, MaturityRating, ProductionCompanyID, LanguageID, GenreID)
                        $stmtShow-> bind_param("sssssss", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]); //bind parameters, expecting one string.
                        $stmtShow->execute();
                        $rows_added += $stmtShow->affected_rows;
                    }
                    if(!empty($episodestart)){
                        // ERD: Episode(ShowID, EpisodeID, SeasonNumber, EpisodeNumber, EpisodeTitle)
                        $stmtEp->bind_param("ssss", $row[12], $row[13], $row[14], $row[15]); //bind parameters, type is 3 strings.
                        $stmtEp->execute();
                        $rows_added += $stmtShow->affected_rows;
                    }
                    if(!empty($personstart)){
                        // ERD: Person(PersonID, ShowID, MovieID, Role, Name, BornIn, Birthdate)
                        $stmtPerson->bind_param("sssss", $row[7], $row[8], $row[9], $row[10], $row[11]);
                        $stmtPerson->execute();
                        $rows_added += $stmtShow->affected_rows;
                    }

            }

            if ($rows_added == 0) {
                $import_succeeded = false;
                $import_error_message = "Invalid File Format: No valid 'Show', 'Episode', or 'Person' records were found. Please check your CSV column structure.";
            } else {
                $import_succeeded = true;
            }
        }
        catch(Error $e){
            $import_error_message = $e->getMessage()
                ." at:" . $e->getFile()." at line ".$e->getLine();
        }
        if($import_attempted == true){
            if($import_succeeded == true){
                ?>
                <h1><span style="color:green;">Import Succeeded</span></h1>
                <?php
            }else{
                ?>
                <h1>Import Failed</h1>
                <?php echo $import_error_message; ?>
                <br/>
                <?php
            }
        }
    }
}
$pageTitle = "Import Show Data";

include('components/_header.php');
?>
<div class="container">
    <h1>Import Show Data</h1>
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
<?php
include("components/_footer.php");
?>
