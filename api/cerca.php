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

    // HABITAT
    $habitat = [];
    try {
        $st = $connessione->prepare("
            SELECT h.id_habitat, h.nome, h.descrizione, h.range_habitat, h.temperatura,
                   l.nome AS luogo_nome
            FROM habitat h
            LEFT JOIN luogo l ON h.id_luogo = l.id_luogo
            WHERE h.nome LIKE ? OR h.descrizione LIKE ?
            LIMIT 4
        ");
        $st->execute([$like, $like]);
        $habitat = $st->fetchAll();
    } catch (PDOException $e) {}

    // LUOGHI
    $luoghi = [];
    try {
        $st = $connessione->prepare("
            SELECT l.id_luogo, l.nome, l.tipo, l.oceano, l.profondita,
                   p.nome AS paese_nome
            FROM luogo l
            LEFT JOIN paese p ON l.id_paese = p.id_paese
            WHERE l.nome LIKE ? OR l.oceano LIKE ? OR l.tipo LIKE ?
            LIMIT 4
        ");
        $st->execute([$like, $like, $like]);
        $luoghi = $st->fetchAll();
    } catch (PDOException $e) {}

    // MINACCE
    $minacce = [];
    try {
        $st = $connessione->prepare("
            SELECT m.id_minaccia, m.nome, m.tipo, m.descrizione,
                   COUNT(sm.id_specie) AS n_specie
            FROM minaccia m
            LEFT JOIN specie_minaccia sm ON sm.id_minaccia = m.id_minaccia
            WHERE m.nome LIKE ? OR m.descrizione LIKE ? OR m.tipo LIKE ?
            GROUP BY m.id_minaccia
            LIMIT 4
        ");
        $st->execute([$like, $like, $like]);
        $minacce = $st->fetchAll();
    } catch (PDOException $e) {}

    // RILEVAZIONI AMBIENTALI
    $rilevazioni_cerca = [];
    try {
        $st = $connessione->prepare("
            SELECT r.parametro, r.valore, r.data, l.nome AS luogo_nome, l.id_luogo
            FROM rilevazione_ambientale r
            JOIN luogo l ON r.id_luogo = l.id_luogo
            WHERE r.parametro LIKE ? OR l.nome LIKE ?
            ORDER BY r.data DESC LIMIT 4
        ");
        $st->execute([$like, $like]);
        $rilevazioni_cerca = $st->fetchAll();
    } catch (PDOException $e) {}

    echo json_encode([
        'specie'       => $specie,
        'specie_lista' => $specie_lista,
        'news'         => $news,
        'media'        => $media,
        'donazioni'    => $donazioni,
        'habitat'      => $habitat,
        'luoghi'       => $luoghi,
        'minacce'      => $minacce,
        'rilevazioni'  => $rilevazioni_cerca,
    ]);

} catch (Throwable $e) {
    echo json_encode(['errore_globale' => $e->getMessage()]);
}