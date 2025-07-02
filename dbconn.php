<?php
    define("HOSTNAME", "localhost");
    define("USERNAME", "root");
    define("PASSWORD", "");
    define("DATABASE", "job_board");

    $connection = mysqli_connect(HOSTNAME, USERNAME, PASSWORD, DATABASE);
    
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }
    else{
        echo "";
    }
?>