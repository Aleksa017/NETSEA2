<?php
require "../config.php";

// Solo admin può approvare
controllaRuolo("admin");

$idRichiesta = $_POST["id_richiesta"];
$idUtente = $_POST["id_utente"];

// Genero OTP casuale a 6 cifre
$otp = rand(100000,999999);

$connessione->prepare(
    "UPDATE richieste_ricercatore
     SET stato='approvata'
     WHERE id=?"
)->execute([$idRichiesta]);

// Aggiorno ruolo utente e salvo OTP
$connessione->prepare(
    "UPDATE utenti
     SET ruolo='ricercatore', otp=?
     WHERE id=?"
)->execute([$otp,$idUtente]);

echo json_encode([
    "success" => true,
    "otp_generata" => $otp
]);
