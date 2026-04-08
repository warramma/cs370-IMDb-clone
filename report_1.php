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
    echo "<table class='table table-bordered table-hover pizzaDataTable'>\n";
    echo "<thead class='table-dark'>\n";
    echo "<tr class='pizzaDataHeaderRow'>\n";
    echo "  <th>Name</th>\n";
    echo "  <th>Age</th>\n";
    echo "  <th>Gender</th>\n";
    echo "  </tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";
}
function output_table_close(){
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>";

}
function output_person_row($name, $age, $gender){
    //will output row based on input
    echo "<tr class='table-primary PizzaDataRow'>\n";
    echo "  <td class='fw-bold'>" . $name . "</td>\n";
    echo "  <td>" . $age . "</td>\n";
    echo "  <td>" . $gender . "</td>\n";
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

$pageTitle = "Report 1";
include('components/_header.php');
?>
<div class="container">
    <h1>Data Report 1</h1>
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
            $query = " SELECT t0.name, t0.age, t0.gender, t1.pizza, t2. pizzeria"
                . " FROM person t0"
                . " LEFT OUTER JOIN eats t1 ON t0.name = t1.name"
                . " LEFT OUTER JOIN frequents t2 on t0.name = t2.name";


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
            $last_name = null; //as in last/previous name not your last name
            $pizzas = array();
            $pizzerias = array();
            foreach ($final_data as $row) {
            if ($last_name != $row['name']) {
                if ($last_name != null) {
                    output_person_details_row($pizzas, $pizzerias);
                }
                output_person_row($row['name'], $row['age'], $row['gender']);
                $pizzas = array();
                $pizzerias = array();
            }

            if (!empty($row['pizza']) && !in_array($row['pizza'], $pizzas)) {
                $pizzas[] = $row['pizza'];
            }
            if (!empty($row['pizzeria']) && !in_array($row['pizzeria'], $pizzerias)) {
                $pizzerias[] = $row['pizzeria'];
            }
            $last_name = $row['name'];
        }

        if ($last_name != null) {
            output_person_details_row($pizzas, $pizzerias);
        }
        output_table_close();

    }



    ?>
</div>

<?php
include("components/_footer.php");
?>