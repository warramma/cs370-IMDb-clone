<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include("components/_connection.php");

$con = mysqli_connect(
        $_ENV["DB_HOST"],
        $_ENV["DB_USER"],
        $_ENV["DB_PASS"],
        $_ENV["DB_NAME"]
);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "
SELECT
    g.GenreID,
    g.Name AS GenreName,
    g.Description,
    m.MovieID,
    m.Title AS MovieTitle,
    m.ReleaseDate,
    m.Runtime,
    m.Revenue,
    m.MaturityRating,
    s.SoundtrackID,
    s.Title AS SoundtrackTitle,
    s.Composer,
    s.Length
FROM Genre g
LEFT JOIN Movie m ON g.GenreID = m.GenreID
LEFT JOIN Soundtrack s ON m.MovieID = s.MovieID
ORDER BY g.Name, m.Title, s.Title
";

$result = mysqli_query($con, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($con));
}

$currentGenreID = null;
$currentMovieID = null;
$openMovieTable = false;

$pageTitle = "Movie, Genre, Soundtrack Report";
include("components/_header.php");
?>

    <div class="container mt-4">
    <h1 class="mb-1">Movie, Genre, Soundtrack Report</h1>

<?php while ($row = mysqli_fetch_assoc($result)) { ?>

    <?php if ($currentGenreID !== $row["GenreID"]) { ?>

        <?php if ($openMovieTable) { ?>
            </tbody>
            </table>
            </div>
            <?php $openMovieTable = false; ?>
        <?php } ?>

        <?php if ($currentGenreID !== null) { ?>
            </div>
        <?php } ?>

        <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white">
            <strong>GenreID:</strong> <?php echo htmlspecialchars($row["GenreID"]); ?> |
            <strong>Name:</strong> <?php echo htmlspecialchars($row["GenreName"]); ?>
        </div>

        <div class="card-body">
        <p class="mb-3">
            <strong>Description:</strong>
            <?php echo htmlspecialchars($row["Description"]); ?>
        </p>

        <?php
        $currentGenreID = $row["GenreID"];
        $currentMovieID = null;
    }
    ?>

    <?php if ($row["MovieID"] !== null && $currentMovieID !== $row["MovieID"]) { ?>

        <?php if ($openMovieTable) { ?>
            </tbody>
            </table>
            </div>
            <?php $openMovieTable = false; ?>
        <?php } ?>

        <div class="mb-4 border-start border-4 ps-3">
        <h5 class="fw-bold mb-1">
            MovieID <?php echo htmlspecialchars($row["MovieID"]); ?>:
            <?php echo htmlspecialchars($row["MovieTitle"]); ?>
        </h5>

        <div class="text-muted mb-2">
            Release Date: <?php echo htmlspecialchars($row["ReleaseDate"]); ?> |
            Runtime: <?php echo htmlspecialchars($row["Runtime"]); ?> |
            Revenue: $<?php echo number_format((float)$row["Revenue"]); ?> |
            Rating: <?php echo htmlspecialchars($row["MaturityRating"]); ?>
        </div>

        <div class="table-responsive">
        <table class="table table-bordered table-hover showDataTable">
        <thead class="table-dark">
        <tr class="showDataHeaderRow">
            <th>SoundtrackID</th>
            <th>Title</th>
            <th>Composer</th>
            <th>Length</th>
        </tr>
        </thead>
        <tbody>
        </div>

        <?php
        $currentMovieID = $row["MovieID"];
        $openMovieTable = true;
    }
    ?>

    <?php if ($row["SoundtrackID"] !== null) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row["SoundtrackID"]); ?></td>
            <td><?php echo htmlspecialchars($row["SoundtrackTitle"]); ?></td>
            <td><?php echo htmlspecialchars($row["Composer"]); ?></td>
            <td><?php echo htmlspecialchars($row["Length"]); ?></td>
        </tr>
    <?php } elseif ($row["MovieID"] !== null) { ?>
        <tr>
            <td colspan="4" class="text-muted fst-italic">
                No soundtrack available.
            </td>
        </tr>
    <?php } ?>

<?php } ?>

<?php if ($openMovieTable) { ?>
    </tbody>
    </table>
    </div>
<?php } ?>

<?php if ($currentGenreID !== null) { ?>
    </div>
<?php } ?>

    </div>

<?php include("components/_footer.php"); ?>
