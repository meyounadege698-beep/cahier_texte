<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "mon projet";

$connexion = new mysqli ($servername, $username, $password, $database);

if ($connexion->connect_error) {
    die("3LQ CONNEXION Q ECHOER");

}

echo "connexion reussit a la base de donnee  mercie ";

?>