<?php
include("components/_connection.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$import_succeeded = false;
$import_attempted = false;
$import_error_message = "";
$rows_inserted = 0;
$rows_updated = 0;
$rows_skipped = 0;
$genre_rows_processed = 0;
$movie_rows_processed = 0;
$soundtrack_rows_processed = 0;

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalize_header($header) {
    $header = trim((string)$header);
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
    $header = str_replace('"', '', $header);
    $header = preg_replace('/\s+/', '', $header);
    return strtolower($header);
}

function get_cell($row, $map, $name) {
    $key = normalize_header($name);
    if (!isset($map[$key])) {
        return "";
    }
    $index = $map[$key];
    return isset($row[$index]) ? trim($row[$index]) : "";
}

function parse_money($value) {
    $value = trim((string)$value);
    $value = str_replace(array('$', ','), '', $value);
    if ($value === "") {
        return null;
    }
    return is_numeric($value) ? (float)$value : null;
}

function parse_release_date($value) {
    $value = trim((string)$value);
    if ($value === "") {
        return null;
    }

    if (is_numeric($value)) {
        $year = (int)round((float)$value);
        if ($year > 1800 && $year < 2200) {
            //return $year . "-01-01";
            return (string)$year;
        }
    }

    if (preg_match('/^\d{4}$/', $value)) {
        //return $value . "-01-01";
        return $value;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value, $m)) {
        //return $value;
        return $m[1];
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {
        //return sprintf("%04d-%02d-%02d", (int)$m[3], (int)$m[1], (int)$m[2]);
        return sprintf("%04d", (int)$m[3]);
    }

    return $value;
}

function parse_runtime($value) {
    $value = trim((string)$value);
    if ($value === "") {
        return null;
    }

    if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $value, $m)) {
        return sprintf("%02d:%02d:%02d", (int)$m[1], (int)$m[2], (int)$m[3]);
    }

    if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
        return sprintf("%02d:%02d:00", (int)$m[1], (int)$m[2]);
    }

    if (preg_match('/^(\d{1,2})h\s*(\d{1,2})m$/i', $value, $m)) {
        return sprintf("%02d:%02d:00", (int)$m[1], (int)$m[2]);
    }

    return $value;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $con = mysqli_connect(
                $_ENV["DB_HOST"],
                $_ENV["DB_USER"],
                $_ENV["DB_PASS"],
                $_ENV["DB_NAME"]
        );
        mysqli_set_charset($con, "utf8");
        $handle = fopen($_FILES["importFile"]["tmp_name"], "r");
        if ($handle === false) {
            throw new Exception("Could not open uploaded CSV.");
        }

        $headers = fgetcsv($handle, 0, ",", "\"", "\\");
        if ($headers === false) {
            throw new Exception("CSV file is empty.");
        }

        $map = array();
        foreach ($headers as $i => $header) {
            $map[normalize_header($header)] = $i;
        }

        $required = array(
                "name",
                "description",
                "movietitle",
                "releasedate",
                "runtime",
                "revenue",
                "maturityrating",
                "productioncompanyid",
                "languageid",
                "soundtracktitle",
                "composer",
                "length"
        );

        foreach ($required as $field) {
            if (!isset($map[$field])) {
                throw new Exception("CSV is missing required column: " . strtoupper($field));
            }
        }

        while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== false) {
            $genreName = get_cell($row, $map, "Name");
            $description = get_cell($row, $map, "Description");

            $movieTitle = get_cell($row, $map, "MovieTitle");
            $releaseDate = parse_release_date(get_cell($row, $map, "ReleaseDate"));
            $runtime = parse_runtime(get_cell($row, $map, "Runtime"));
            $revenue = parse_money(get_cell($row, $map, "Revenue"));
            $maturityRating = get_cell($row, $map, "MaturityRating");

            $productionCompanyRaw = get_cell($row, $map, "ProductionCompanyID");
            $productionCompanyID = ($productionCompanyRaw === "") ? null : ((int)$productionCompanyRaw + 1);

            $languageID = (int)get_cell($row, $map, "LanguageID");

            $soundtrackTitle = get_cell($row, $map, "SoundtrackTitle");
            $composer = get_cell($row, $map, "Composer");
            $lengthRaw = get_cell($row, $map, "Length");
            $length = ($lengthRaw === "" || !is_numeric($lengthRaw)) ? null : (float)$lengthRaw;

            if (
                    $description === "" ||
                    $genreName === "" ||
                    $movieTitle === "" ||
                    $releaseDate === null ||
                    $runtime === null ||
                    $revenue === null ||
                    $maturityRating === "" ||
                    $productionCompanyID === null ||
                    $productionCompanyID < 0 ||
                    $languageID <= 0 ||
                    $soundtrackTitle === "" ||
                    $length === null
            ) {
                $rows_skipped++;
                continue;
            }

            mysqli_begin_transaction($con);

            try {
                // 1. Genre: import only if Name and Description are filled.
                if ($genreName !== "" && $description !== "") {
                    $stmt = mysqli_prepare($con, "SELECT GenreID FROM Genre WHERE Name = ?");
                    mysqli_stmt_bind_param($stmt, "s", $genreName);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $existingGenre = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt);

                    if ($existingGenre) {
                        $genreID = (int)$existingGenre["GenreID"];

                        $stmt = mysqli_prepare($con, "UPDATE Genre SET Description = ? WHERE GenreID = ?");
                        mysqli_stmt_bind_param($stmt, "si", $description, $genreID);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        $rows_updated++;
                    } else {
                        $stmt = mysqli_prepare($con, "INSERT INTO Genre (Name, Description) VALUES (?, ?)");
                        mysqli_stmt_bind_param($stmt, "ss", $genreName, $description);
                        mysqli_stmt_execute($stmt);
                        $genreID = mysqli_insert_id($con);
                        mysqli_stmt_close($stmt);
                        $rows_inserted++;
                    }

                    $genre_rows_processed++;
                }
// 2. Movie: find or insert/update by Title. GenreID comes from DB (lookup/insert).                $stmt = mysqli_prepare($con, "SELECT MovieID FROM Movie WHERE Title = ?");
                $stmt = mysqli_prepare($con, "SELECT MovieID FROM Movie WHERE Title = ?");
                mysqli_stmt_bind_param($stmt, "s", $movieTitle);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $existingMovie = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($existingMovie) {
                    $movieID = (int)$existingMovie["MovieID"];

                    $stmt = mysqli_prepare(
                            $con,
                            "UPDATE Movie
         SET ReleaseDate = ?, Runtime = ?, Revenue = ?, MaturityRating = ?, ProductionCompanyID = ?, LanguageID = ?, GenreID = ?
         WHERE MovieID = ?"
                    );

                    mysqli_stmt_bind_param(
                            $stmt,
                            "ssdsiiii",
                            $releaseDate,
                            $runtime,
                            $revenue,
                            $maturityRating,
                            $productionCompanyID,
                            $languageID,
                            $genreID,
                            $movieID
                    );

                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $rows_updated++;
                } else {
                    $stmt = mysqli_prepare(
                            $con,
                            "INSERT INTO Movie
         (Title, ReleaseDate, Runtime, Revenue, MaturityRating, ProductionCompanyID, LanguageID, GenreID)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                            $stmt,
                            "ssdsiiii",
                            $movieTitle,
                            $releaseDate,
                            $runtime,
                            $revenue,
                            $maturityRating,
                            $productionCompanyID,
                            $languageID,
                            $genreID
                    );

                    mysqli_stmt_execute($stmt);
                    $movieID = mysqli_insert_id($con);
                    mysqli_stmt_close($stmt);

                    $rows_inserted++;
                }

                $movie_rows_processed++;

                // 3. Soundtrack: find or insert/update by MovieID + Title.
                $stmt = mysqli_prepare($con, "SELECT SoundtrackID FROM Soundtrack WHERE MovieID = ? AND Title = ?");
                mysqli_stmt_bind_param($stmt, "is", $movieID, $soundtrackTitle);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $existingSoundtrack = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($existingSoundtrack) {
                    $soundtrackID = (int)$existingSoundtrack["SoundtrackID"];

                    $stmt = mysqli_prepare($con, "UPDATE Soundtrack SET Composer = ?, Length = ? WHERE SoundtrackID = ?");
                    mysqli_stmt_bind_param($stmt, "sdi", $composer, $length, $soundtrackID);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $rows_updated++;
                } else {
                    $showID = null;
                    $stmt = mysqli_prepare(
                            $con,
                            "INSERT INTO Soundtrack (MovieID, ShowID, Composer, Title, Length)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($stmt, "iissd", $movieID, $showID, $composer, $soundtrackTitle, $length);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $rows_inserted++;
                }

                $soundtrack_rows_processed++;

                mysqli_commit($con);
            } catch (Exception $rowException) {
                mysqli_rollback($con);
                throw $rowException;

            }
        }

        fclose($handle);
        $import_succeeded = true;
    } catch (Exception $e) {
        $import_error_message = $e->getMessage() . " at " . $e->getFile() . " line " . $e->getLine();
    }

    $import_attempted = true;
}


$pageTitle = "Import Movie / Genre / Soundtrack CSV";
if (file_exists("components/_header.php")) {
    include("components/_header.php");
}
?>

    <div class="container">
        <h1>Import Movie / Genre / Soundtrack CSV</h1>
        <?php
        if($import_attempted){
            if ($import_succeeded) {
                echo "<br>";
                echo "<div class='alert alert-success' role='alert'>";
                echo "<h2>Import success</h2>";
                echo "<p>Inserted: " . h($rows_inserted) . "<br>Updated: " . h($rows_updated) . "<br>Skipped: " . h($rows_skipped) . "</p>";
                echo "<p>Genre rows processed: " . h($genre_rows_processed) . "<br>Movie rows processed: " . h($movie_rows_processed) . "<br>Soundtrack rows processed: " . h($soundtrack_rows_processed) . "</p>";
                echo "</div>";
            }
            else {
                echo "<br>";
                echo "<div class='alert alert-danger' role='alert'>";
                echo "<h2>Import failure</h2>";
                echo "<p>" . h($import_error_message) . "</p>";
                echo "</div>";
            }
        }
        ?>
        <p>Expected headers:</p>
        <code>GenreID,Name,Description,MovieTitle,ReleaseDate,Runtime,Revenue,MaturityRating,ProductionCompanyID,LanguageID,SoundtrackTitle,Composer,Length</code>
        <br><br>

        <form method="post" enctype="multipart/form-data">
            <div class="input-group md-3">
                <span class="input-group-text">File:</span>
                <input class="form-control" type="file" name="importFile" accept=".csv" required>
            </div>
            <br>
            <input type="submit" value="Upload Data" class="btn btn-primary">
        </form>


    </div>

<?php
if (file_exists("components/_footer.php")) {
    include("components/_footer.php");
}
?>