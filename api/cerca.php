<?php
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['specie'=>null,'specie_lista'=>[],'news'=>[],'media'=>[],'donazioni'=>[]]);
        exit();
    }
    $like = '%' . $q . '%';

    // SPECIE
    $st = $connessione->prepare("
        SELECT id_specie, nome, nome_scientifico, stato_conservazione,
               descrizione, immagine, famiglia, classe, dieta, dimensioni, peso
        FROM specie
        WHERE nome LIKE ? OR nome_scientifico LIKE ? OR famiglia LIKE ?
        LIMIT 8
    ");
    $st->execute([$like, $like, $like]);
    $rows = $st->fetchAll();
    $specie = $rows[0] ?? null;
    $specie_lista = $rows;

    // NEWS (join con ricercatore e utente per nome autore)
    $news = [];
    try {
        $st = $connessione->prepare("
            SELECT n.id_news, n.titolo, LEFT(n.contenuto,150) AS contenuto, n.data_pub,
                   u.nome AS nome_autore, u.cognome AS cognome_autore
            FROM news n
            JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
            JOIN utente u ON r.id_utente = u.id_utente
            WHERE n.titolo LIKE ? OR n.contenuto LIKE ?
            ORDER BY n.data_pub DESC LIMIT 5
        ");
        $st->execute([$like, $like]);
        $news = $st->fetchAll();
    } catch (PDOException $e) { /* se join fallisce, news vuote */ }

    // MEDIA
    $media = [];
    try {
        $st = $connessione->prepare("
            SELECT id_post, titolo, descrizione, url, data_pub
            FROM media
            WHERE titolo LIKE ? OR descrizione LIKE ?
            ORDER BY data_pub DESC LIMIT 6
        ");
        $st->execute([$like, $like]);
        $media = $st->fetchAll();
    } catch (PDOException $e) {}

    // PROGETTI DI DONAZIONE
    $donazioni = [];
    try {
        $st = $connessione->prepare("
            SELECT id_pd, titolo, obiettivo, budget, stato
            FROM progetto
            WHERE titolo LIKE ? OR obiettivo LIKE ?
            LIMIT 4
        ");
        $st->execute([$like, $like]);
        $donazioni = $st->fetchAll();
    } catch (PDOException $e) {}

    echo json_encode([
        'specie'       => $specie,
        'specie_lista' => $specie_lista,
        'news'         => $news,
        'media'        => $media,
        'donazioni'    => $donazioni,
    ]);

} catch (Throwable $e) {
    echo json_encode(['errore_globale' => $e->getMessage()]);
}