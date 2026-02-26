<?php
session_start();

$host = 'localhost';
$db   = 'netseadb';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $connessione = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $errore) {
    error_log('Errore connessione DB: ' . $errore->getMessage());
    die('Errore connessione DB.');
}
