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

            echo "<pre>";

            for($x = 1; $x < count($lines); $x++){
                $parsed_csv_line = str_getcsv($lines[$x], ",", '"', "");

                if (!empty($parsed_csv_line)) {
                    echo implode(" | ", $parsed_csv_line) . PHP_EOL;
                }
            }

            echo "</pre>";
            $import_succeeded = true;
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