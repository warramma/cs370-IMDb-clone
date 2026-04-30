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
$show_rows_processed = 0;
$ep_rows_processed = 0;
$person_rows_processed = 0;

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
            $stmtGetShow = $con->prepare("SELECT ShowID FROM `Show` WHERE Title = ?");
            $stmtShow = $con->prepare("INSERT IGNORE INTO `Show` (Title, ReleaseDate, EndDate, MaturityRating, ProductionCompanyID, LanguageID, GenreID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtEp = $con->prepare("INSERT IGNORE INTO Episode (ShowID, SeasonNumber, EpisodeNumber, EpisodeTitle) VALUES (?, ?, ?, ?)");
            $stmtPerson = $con->prepare("INSERT IGNORE INTO Person (ShowID, MovieID, `Role`, `Name`, BornIn, Birthdate) VALUES (?, NULL, ?, ?, ?, ?)");

            $valid_type_found = false;
            $show_cache = [];
            $person_cache = [];

            for ($x = 1; $x < count($lines); $x++) {
                $row = str_getcsv($lines[$x], ",", '"', "");
                if (count($row) < 14 || empty($row[0])) {
                    $rows_skipped++;
                    continue;
                }
                $title = $row[0];
                $showstart = $row[0];
                $episodestart = $row[13];
                $personstart = $row[8];

                    if(!isset($show_cache[$title])){
                        $valid_type_found = true;
                        // ERD: Show(ShowID, Title, ReleaseDate, EndDate, MaturityRating, ProductionCompanyID, LanguageID, GenreID)
                        $stmtGetShow->bind_param("s", $title);
                        $stmtGetShow->execute();
                        $result = $stmtGetShow->get_result();

                        if ($result->num_rows > 0) {
                            $show_cache[$title] = $result->fetch_assoc()['ShowID'];
                        }else{
                            $endDate = !empty($row[2]) ? $row[2] : null;
                            $stmtShow->bind_param("ssssiii", $row[0], $row[1], $endDate, $row[3], $row[4], $row[5], $row[6]);
                            $stmtShow->execute();
                            $show_cache[$title] = $con->insert_id;
                            if($stmtGetShow->affected_rows > 0) $rows_inserted++;
                            else $rows_skipped++;

                            $show_rows_processed++;
                        }
//                        $stmtShow-> bind_param("sssssss", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]); //bind parameters, expecting one string.
//                        $stmtShow->execute();

                    }
                    $currentShowID = $show_cache[$title];
                    if($currentShowID > 0){
                        if(!empty($episodestart)){
                            $valid_type_found = true;
                            // ERD: Episode(ShowID, EpisodeID, SeasonNumber, EpisodeNumber, EpisodeTitle)
                            $stmtEp->bind_param("iiis", $currentShowID, $row[11], $row[12], $row[13]); //bind parameters, type is 3 strings.
                            $stmtEp->execute();
                            if($stmtEp->affected_rows > 0) $rows_inserted++;
                            else $rows_skipped++;

                            $ep_rows_processed++;
                        }
                        if(!empty($personstart)){
                            $role = $row[7];
                            $name = $row[8];
                            $personKey = $currentShowID . "_" . $role . "_" . $name;
                            if(!isset($person_cache[$personKey])){
                                $valid_type_found = true;
                                // ERD: Person(PersonID, ShowID, MovieID, Role, Name, BornIn, Birthdate)
                                $stmtPerson->bind_param("issss", $currentShowID, $row[7], $row[8], $row[9], $row[10]);
                                $stmtPerson->execute();
                                if($stmtPerson->affected_rows > 0) $rows_inserted++;
                                else $rows_skipped++;

                                $person_rows_processed++;
                                $person_cache[$personKey] = $con->insert_id;
                            }

                        }
                    }else{
                        $rows_skipped++;
                    }


            }

            if ($rows_inserted == 0 && $rows_skipped > 0 && ($rows_skipped == count($lines) - 1) && !$valid_type_found) {
                $import_succeeded = false;
                $import_error_message = "Invalid File Format: No valid 'Show', 'Episode', or 'Person' records were found. Please check your CSV column structure.";
            } else {
                $import_succeeded = true;
            }
        }
        catch(Exception $e){
            $import_error_message = $e->getMessage()
                ." at:" . $e->getFile()." at line ".$e->getLine();
            $import_succeeded = false;
        }
//        if($import_attempted == true){
//            if($import_succeeded == true){
//                ?>
<!--                <h1><span style="color:green;">Import Succeeded</span></h1>-->
<!--                --><?php
//            }else{
//                ?>
<!--                <h1>Import Failed</h1>-->
<!--                --><?php //echo $import_error_message; ?>
<!--                <br/>-->
<!--                --><?php
//            }
//        }
    }
}
$pageTitle = "Import Show Data";

include('components/_header.php');
?>
<div class="container">
    <h1>Import Show Data</h1>
    <?php
    if($import_attempted){
        if ($import_succeeded) {
            echo "<br>";
            echo "<div class='alert alert-success' role='alert'>";
            echo "<h2>Import success</h2>";
            echo "<p>Inserted: " . h($rows_inserted) . "<br>Updated: " . h($rows_updated) . "<br>Skipped: " . h($rows_skipped) . "</p>";
            echo "<p>Show rows processed: " . h($show_rows_processed) . "<br>Episode rows processed: " . h($ep_rows_processed) . "<br>Person rows processed: " . h($person_rows_processed) . "</p>";
            echo "</div>";
        } else {
            echo "<br>";
            echo "<h2>Import failure</h2>";
            echo "<div class='alert alert-danger' role='alert'>";
            echo "<p>" . h($import_error_message) . "</p>";
            echo "</div>";
        }
    }
    ?>
    <p>Expected headers:</p>
    <code>Title, ReleaseDate, EndDate, MaturityRating, ProductionCompanyID, LanguageID, GenreID, Role, Name,
    BornIn, Birthdate, SeasonNumber, EpisodeNumber, EpisodeTitle</code>
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
