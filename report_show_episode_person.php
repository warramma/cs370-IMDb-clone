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
        $people_str = implode("\n", $people);

    }
    if(sizeof($episodes) > 0){
        $episode_str = implode("\n", $episodes);
    }

    echo "<tr>\n";
    echo "  <td colspan='3' class='ps-5'>\n";
    echo "      People Involved: " . $people_str . "</br>\n";
    echo "      Episodes: " . $episode_str . "</br>\n";
    echo "  </td>\n";
    echo "</tr>\n";
}

$pageTitle = "Show, Episode, Person";
include('components/_header.php');
?>
<div class="container">
    <h1>Show, Episode, Person Report</h1>
    <?php
    $dummy_data = [
        ["name" => "Amy", "age" => 22, "gender" => "female", "pizza" => "Pepperoni", "pizzeria" => "Pizza Hut"],
        ["name" => "Amy", "age" => 22, "gender" => "female", "pizza" => "Mushroom", "pizzeria" => "Dominos"],
        ["name" => "Ben", "age" => 30, "gender" => "male", "pizza" => "Cheese", "pizzeria" => "Little Caesars"],
        ["name" => "Ben", "age" => 30, "gender" => "male", "pizza" => "Cheese", "pizzeria" => "Pizza Hut"],
        ["name" => "Cal", "age" => 25, "gender" => "male", "pizza" => "Supreme", "pizzeria" => "Papa Johns"]
    ];

    $final_data = [];
    $used_dummy = false;

    if ($connection_error) {
        $final_data = $dummy_data;
        $used_dummy = true;
        output_error("Database connection error: ", $connection_error_message);
    }
    else{
        try {
            $query = " SELECT t0.showID, t0.title, t0.releaseDate, t0.endDate, t0.maturityRating,"
                    . " t1.personid, t1.showid, t1.name, t1.role, "
                    . " t2.episodeid, t2.seasonNumber, t2.episodeNumber, t2.episodeTitle"
                . " FROM show t0"
                . " LEFT OUTER JOIN person t1 ON t0.showID = t1.showID"
                . " LEFT OUTER JOIN episode t2 on t0.showID = t2.showID";


            // example of user input:      . " WHERE t0.name = '" . $userSelectedName . "'"

            $result = mysqli_query($con, $query);
            //false can mean error or no records back
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = $result->fetch_assoc()) {
                    $final_data[] = $row;
                }
            } else {
                $final_data = $dummy_data;
                $used_dummy = true;
            }
        }catch(Exception $e){
            $final_data = $dummy_data;
            $used_dummy = true;
            echo "<div class='alert alert-danger'><strong>SQL Error:</strong> " . $e->getMessage() . "</div>";
        }

        }
    if ($used_dummy) {
        echo "<div class='alert alert-info'>Showing dummy data.</div>";
    }
    if(!empty($final_data)){
            output_table_open();
            $last_show = null; //as in last/previous name not your last name
            $people = array();
            $episodes = array();
            foreach ($final_data as $row) {
                $ep = array();
                $p = array();
            if ($last_show != $row["showid"]) {
                if ($last_show != null) {
                    output_person_details_row($pizzas, $pizzerias);
                }
                output_show_row($row["showid"], $row["title"], $row["releasedate"], $row["enddate"], $row["maturityrating"]);
                $p = array();
                $ep = array();
            }

            if (!empty($row["personid"]) && !in_array($row["personid"], $people)) {
                $person_str = "";
                if(sizeof($p) > 0){
                    $person_str = implode(" ", $p);

                }
                $people[] = $person_str;
            }
            if (!empty($row["episodeid"]) && !in_array($row["episodeid"], $episode)) {
                $episode_str = "";
                if(sizeof($ep) > 0){
                    $episode_str = implode(" ", $ep);

                }
                $episode[] = $episode_str;
            }
            $last_show = $row["showid"];
        }

        if ($last_show != null) {
            output_person_details_row($people, $episodes);
        }
        output_table_close();

    }



    ?>
</div>

<?php
include("components/_footer.php");
?>
