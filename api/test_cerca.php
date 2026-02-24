<?php
require_once dirname(__DIR__) . '/config.php';
$tables = ['progetto', 'news', 'media', 'utente', 'ricercatore', 'like_media'];
foreach ($tables as $t) {
    echo "<h3>$t</h3><ul>";
    try {
        foreach ($connessione->query("DESCRIBE $t")->fetchAll() as $c)
            echo "<li><b>{$c['Field']}</b> — {$c['Type']}</li>";
    } catch(Exception $e) { echo "<li style='color:red'>{$e->getMessage()}</li>"; }
    echo "</ul>";
}
?>














































