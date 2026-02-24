<?php
require "../config.php";

// Login
if(!isset($_SESSION["id"])) {
    echo json_encode(["success" => false, "errore" => "Login richiesto"]);
    exit();
}
$motivazione = $_POST["motivazione"];

// Richiesta con stato "in_attesa"
$query = $connessione->prepare(
    "INSERT INTO richieste_ricercatore(id_utente,motivazione,stato)
     VALUES(?,?,'in_attesa')"
);

$query->execute([$_SESSION["id"], $motivazione]);

echo json_encode(["success" => true]);
