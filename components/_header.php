<html lang="en">
<head>
    <title><?php echo isset($pageTitle) ? $pageTitle : "bDMI Introverts";?></title>
    <link href="css/bootstrap.css" rel="stylesheet"/>
    <link href="css/style.css" rel="stylesheet"/>
    <link href="assets/favicon.ico" rel="icon" type="image/x-icon"/>
    <script src="js/bootstrap.bundle.js"></script>
</head>
<body>
<ul class="nav header">
    <li class="nav-item"><img alt="bDMI" class="logo" src="assets/bDMI.svg"/></li>
    <li class="nav-item size"></li>
    <li class="nav-item left"><a class="nav-link" href="index.php">IMDb Home</a></li>
    <li class="nav-item dropdown left">
        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">Data Import</a>
        <ul class="dropdown-menu">
            <li class="dropdown-item drop"><a class="nav-link" href="import_movie_genre_soundtrack.php">Import Movie Data</a></li>
            <li class="dropdown-item drop"><a class="nav-link" href="import_show_episode_person.php">Import Show Data</a></li>
            <li class="dropdown-item drop"><a class="nav-link" href="import_user_production_language.php">Import User-Production-Language Data</a></li>
        </ul>
    </li>
    <li class="nav-item dropdown left">
        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">Reports</a>
        <ul class="dropdown-menu">
            <li class="dropdown-item drop"><a class="nav-link" href="report_movie_genre_soundtrack.php">Movie Report</a></li>
            <li class="dropdown-item drop"><a class="nav-link" href="report_show_episode_person.php">Show Report</a></li>
            <li class="dropdown-item drop"><a class="nav-link" href="report_user_production_language.php">User-Production-Language Report</a></li>
        </ul>
    </li>
    <li class="nav-item left last"><a class="nav-link" href="login.php">Login</a></li>
</ul>
