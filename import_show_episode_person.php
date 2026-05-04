<?php

include("components/_connection.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
            $stmtInsertShow = $con->prepare("INSERT INTO `Show` (Title, ReleaseDate, EndDate, MaturityRating, ProductionCompanyID, LanguageID, GenreID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtUpdateShow = $con->prepare("UPDATE `Show` SET ReleaseDate=?, EndDate=?, MaturityRating=?, ProductionCompanyID=?, LanguageID=?, GenreID=? WHERE ShowID=?");

            $stmtGetEp = $con->prepare("SELECT EpisodeID FROM Episode WHERE ShowID=? AND SeasonNumber=? AND EpisodeNumber=?");
            $stmtInsertEp = $con->prepare("INSERT INTO Episode (ShowID, SeasonNumber, EpisodeNumber, EpisodeTitle) VALUES (?, ?, ?, ?)");
            $stmtUpdateEp = $con->prepare("UPDATE Episode SET EpisodeTitle=? WHERE EpisodeID=?");

            $stmtGetPerson = $con->prepare("SELECT PersonID FROM Person WHERE ShowID=? AND Name=? AND Role=?");
            $stmtInsertPerson = $con->prepare("INSERT INTO Person (ShowID, MovieID, Role, Name, BornIn, Birthdate) VALUES (?, NULL, ?, ?, ?, ?)");
            $stmtUpdatePerson = $con->prepare("UPDATE Person SET BornIn=?, Birthdate=? WHERE PersonID=?");

            $checkProd = $con->prepare("SELECT ProductionCompanyID FROM ProductionCompany WHERE ProductionCompanyID = ?");
            $checkLang = $con->prepare("SELECT LanguageID FROM Language WHERE LanguageID = ?");
            $checkGenre = $con->prepare("SELECT GenreID FROM Genre WHERE GenreID = ?");

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
                $endDate = !empty($row[2]) ? $row[2] : null;
                $showstart = $row[0];
                $episodestart = $row[13];
                $personstart = $row[8];
                $prodID = (int)$row[4];
                $langID = (int)$row[5];
                $genreID = (int)$row[6];

                $checkProd->bind_param("i", $prodID); $checkProd->execute();
                $pExists = $checkProd->get_result()->num_rows > 0;

                $checkLang->bind_param("i", $langID); $checkLang->execute();
                $lExists = $checkLang->get_result()->num_rows > 0;

                $checkGenre->bind_param("i", $genreID); $checkGenre->execute();
                $gExists = $checkGenre->get_result()->num_rows > 0;

                if (!$pExists || !$lExists || !$gExists) {
                    $rows_skipped++;
                    continue;
                }

                    if(!isset($show_cache[$title])){
                        $valid_type_found = true;
                        // ERD: Show(ShowID, Title, ReleaseDate, EndDate, MaturityRating, ProductionCompanyID, LanguageID, GenreID)
                        $stmtGetShow->bind_param("s", $title);
                        $stmtGetShow->execute();
                        $result = $stmtGetShow->get_result();

                        if ($existingShow = $result->fetch_assoc()) {
                            $currentShowID = $existingShow['ShowID'];
                            $stmtUpdateShow->bind_param("sssiiii", $row[1], $endDate, $row[3], $row[4], $row[5], $row[6], $currentShowID);
                            $stmtUpdateShow->execute();
                            $rows_updated++;
                        } else {
                            $stmtInsertShow->bind_param("ssssiii", $row[0], $row[1], $endDate, $row[3], $row[4], $row[5], $row[6]);
                            $stmtInsertShow->execute();
                            $currentShowID = $con->insert_id;
                            $rows_inserted++;
                        }
                        $show_cache[$title] = $currentShowID;
                        $show_rows_processed++;

                    }
                    $currentShowID = $show_cache[$title];
                    if($currentShowID > 0){
                        if(!empty($episodestart)){
                            $valid_type_found = true;
                            // ERD: Episode(ShowID, EpisodeID, SeasonNumber, EpisodeNumber, EpisodeTitle)
                            $stmtGetEp->bind_param("iii", $currentShowID, $row[11], $row[12]);
                            $stmtGetEp->execute();
                            $resEp = $stmtGetEp->get_result();

                            if($existingEp = $resEp->fetch_assoc()){
                                $stmtUpdateEp->bind_param("si", $row[13], $existingEp['EpisodeID']);
                                $stmtUpdateEp->execute();
                                $rows_updated++;
                            } else {
                                $stmtInsertEp->bind_param("iiis", $currentShowID, $row[11], $row[12], $row[13]);
                                $stmtInsertEp->execute();
                                $rows_inserted++;
                            }
                            $ep_rows_processed++;
                        }
                        if(!empty($personstart)){
                            $stmtGetPerson->bind_param("iss", $currentShowID, $row[8], $row[7]);
                            $stmtGetPerson->execute();
                            $resPerson = $stmtGetPerson->get_result();

                            if($existingPerson = $resPerson->fetch_assoc()){
                                $stmtUpdatePerson->bind_param("ssi", $row[9], $row[10], $existingPerson['PersonID']);
                                $stmtUpdatePerson->execute();
                                $rows_updated++;
                            } else {
                                $stmtInsertPerson->bind_param("issss", $currentShowID, $row[7], $row[8], $row[9], $row[10]);
                                $stmtInsertPerson->execute();
                                $rows_inserted++;
                            }
                            $person_rows_processed++;
                        }
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
            echo "<div class='alert alert-danger' role='alert'>";
            echo "<h2>Import failure</h2>";
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
