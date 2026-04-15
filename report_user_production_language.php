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
function output_table_open($headers){
    echo "<div class='table-responsive'>\n";
    echo "<table class='table table-bordered table-hover'>\n";
    echo "<thead class='table-dark'>\n";
    echo "<tr>\n";
    foreach($headers as $header){
        echo "<th>$header</th>\n";
    }
    echo "  </tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";
}
function output_table_close(){
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>";

}
function output_row($data_array){
    //will output row based on input
    echo "<tr class='table-primary'>\n";
    foreach($data_array as $row){
        echo "<td>$row</td>\n";
    }
    echo "</tr>\n";
}

function output_person_details_row ($pizzas, $pizzerias){
    $pizza_str = "None";
    $pizzerias_str = "None";
    if(sizeof($pizzas) > 0){
        $pizza_str = implode(",", $pizzas);

    }
    if(sizeof($pizzerias) > 0){
        $pizzeria_str = implode(",", $pizzerias);
    }

    echo "<tr>\n";
    echo "  <td colspan='3' class='ps-5'>\n";
    echo "      Pizzas Eaten: " . $pizza_str . "</br>\n";
    echo "      Pizzerias Frequented: " . $pizzerias_str . "</br>\n";
    echo "  </td>\n";
    echo "</tr>\n";
}

$pageTitle = "User, Production, Language";
include('components/_header.php');
?>
<div class="container">
    <h1>User, Production, Language Report</h1>
    <?php
        if($connection_error){
            output_error("Error connecting to the database: " . $connection_error_message);
        }else{
            echo "<h2>User Report</h2>\n";
            $user_res = mysqli_query($con, "SELECT Name, JoinDate, BirthDate FROM User");
            output_table_open(['Username', 'Join Date', 'Birth Date']);
            while($row = mysqli_fetch_assoc($user_res)){
                output_row([$row['Name'], $row['JoinDate'], $row['BirthDate']]);
            }
            output_table_close();

            echo "<h2>Production Companies</h2>\n";
            $prod_res = mysqli_query($con, "SELECT CompanyName, Headquarters, `Founded Date` FROM ProductionCompany");
            output_table_open(['Company Name', 'Headquarters', 'Founded Date']);
            while ($row = mysqli_fetch_assoc($prod_res)) {
                output_row([$row['CompanyName'], $row['Headquarters'], $row['Founded Date']]);
            }
            output_table_close();
            echo "<h2>Language Report</h2>\n";
            $lang_res = mysqli_query($con, "SELECT Language FROM Language");
            output_table_open(['Language']);
            while($row = mysqli_fetch_assoc($lang_res)){
                output_row([$row['Language']]);
            }
            output_table_close();
    }
    ?>
</div>

<?php
include("components/_footer.php");
?>