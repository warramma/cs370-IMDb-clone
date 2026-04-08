<html lang = "en">
<head>
    <title><?php echo isset($pageTitle) ? $pageTitle : "Pizza Database"; ?></title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <script src="js/bootstrap.bundle.js"></script>
</head>
<body>
<ul class="nav">
    <li class="nav-item"><a class="nav-link" href="index.php">IMDb Home</a></li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">Data Import</a>
        <ul class="dropdown-menu">
            <li class="dropdown-item"><a class="nav-link" href="import_1.php">Import Data</a></li>
            <li class="dropdown-item"><a class="nav-link" href="#">Import Other Data 1</a></li>
            <li class="dropdown-item"><a class="nav-link" href="#">Import Other Data 2</a></li>
        </ul>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">Reports</a>
        <ul class="dropdown-menu">
            <li class="dropdown-item"><a class="nav-link" href="report_1.php">View Report 1</a></li>
            <li class="dropdown-item"><a class="nav-link" href="#">View Report 2</a></li>
            <li class="dropdown-item"><a class="nav-link" href="#">View Report 3</a></li>
        </ul>
    </li>

</ul>


