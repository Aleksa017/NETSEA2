<?php
session_start();

$host = "localhost";
$db   = "NetseaDB";
$user = "root";
$pass = "";

try {
    $connessione = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user, $pass
    );
    $connessione->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connessione->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $errore) {
    die("Errore connessione DB: " . $errore->getMessage());
}
?>