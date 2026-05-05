<?php

include("components/_connection.php");
$connection_error = false;
$connection_error_message = "";

$con = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']); //<----VERY IMPORTANT!!

if(mysqli_connect_errno()){
    $connection_error = true;
    $connection_error_message = "Error connecting to the database: " . mysqli_connect_error();
}

function output_error($title, $error){
    echo "<span style = 'color: red; '>\n";
    echo "<h2>$title</h2>\n";
    echo "<h4>$error</h4>\n";
    echo "</span>";
}
function output_table_open(){
    echo "<div class='table-responsive'>\n";
    echo "<table class='table table-bordered table-hover showDataTable'>\n";
    echo "<thead class='table-dark'>\n";
    echo "<tr class='showDataHeaderRow'>\n";
    echo "  <th>ShowID</th>\n";
    echo "  <th>Title</th>\n";
    echo "  <th>ReleaseDate</th>\n";
    echo "  <th>EndDate</th>\n";
    echo "  <th>MaturityRating</th>\n";
    echo "  </tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";
}
function output_table_close(){
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>";

}
function output_show_row($showid, $title, $releasedate, $enddate, $maturityrating){
    //will output row based on input
    echo "<tr class='table-primary ShowDataRow'>\n";
    echo "  <td class='fw-bold'>" . $showid . "</td>\n";
    echo "  <td>" . $title . "</td>\n";
    echo "  <td>" . $releasedate . "</td>\n";
    echo "  <td>" . $enddate . "</td>\n";
    echo "  <td>" . $maturityrating . "</td>\n";
    echo "</tr>\n";
}

function output_person_details_row ($people, $episodes){
    $people_str = "None";
    $episode_str = "None";
    if(sizeof($people) > 0){
        $people_str = implode("<br>", $people);

    }
    if(sizeof($episodes) > 0){
        $episode_str = implode("<br>", $episodes);
    }

    echo "<tr>\n";
    echo "  <td colspan='5' class='ps-5 bg-light'>\n";
    echo " <strong>   People Involved: </strong><br>" . $people_str . "<br><br>\n";
    echo "  <strong>    Episodes: </strong><br>" . $episode_str . "<br>\n";
    echo "  </td>\n";
    echo "</tr>\n";
}

$pageTitle = "Show, Episode, Person";
include('components/_header.php');
?>
    <div class="container mt-4">
        <h1 class="mb-1">Show, Episode, Person Report</h1>
        <?php
        $final_data = [];

        if ($connection_error) {
            output_error("Database connection error: ", $connection_error_message);
        }
        else{
            try {
                $query = " SELECT t0.ShowID, t0.Title, t0.ReleaseDate, t0.EndDate, t0.MaturityRating,"
                        . " t1.PersonID, t1.name, t1.role, "
                        . " t2.EpisodeID, t2.SeasonNumber, t2.EpisodeNumber, t2.EpisodeTitle"
                        . " FROM `show` t0"
                        . " LEFT OUTER JOIN person t1 ON t0.ShowID = t1.ShowID"
                        . " LEFT OUTER JOIN episode t2 on t0.ShowID = t2.ShowID";


                // example of user input:      . " WHERE t0.name = '" . $userSelectedName . "'"

                $result = mysqli_query($con, $query);
                //false can mean error or no records back
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $final_data[] = $row;
                    }
                } else {
                    echo "<div class='alert alert-info'>No data found in the database.</div>";
                }
            }catch(Exception $e){
                echo "<div class='alert alert-danger'><strong>SQL Error:</strong> " . $e->getMessage() . "</div>";
            }

        }
        if(!empty($final_data)){
            output_table_open();
            $last_show = null; //as in last/previous name not your last name
            $people = array();
            $episodes = array();
            foreach ($final_data as $row) {
                if ($last_show !== $row["ShowID"]) {
                    if ($last_show != null) {
                        output_person_details_row($people, $episodes);
                    }
                    output_show_row($row["ShowID"], $row["Title"], $row["ReleaseDate"], $row["EndDate"], $row["MaturityRating"]);
                    $people = array();
                    $episodes = array();
                    $last_show = $row["ShowID"];
                }

                if (!empty($row["PersonID"]) && !in_array($row["PersonID"], $people)) {
                    $p_info = htmlspecialchars($row["name"] . " (as " . $row["role"] . ")");
                    if (!in_array($p_info, $people)) {
                        $people[] = $p_info;
                    }
                }
                if (!empty($row["EpisodeID"]) && !in_array($row["EpisodeID"], $episodes)) {
                    $e_info = htmlspecialchars("S" . $row["SeasonNumber"] . "E" . $row["EpisodeNumber"] . ": " . $row["EpisodeTitle"]);
                    if (!in_array($e_info, $episodes)) {
                        $episodes[] = $e_info;
                    }
                }
            }

            if ($last_show !== null) {
                output_person_details_row($people, $episodes);
            }

            output_table_close();

        }



        ?>
    </div>

<?php
include("components/_footer.php");
?>
