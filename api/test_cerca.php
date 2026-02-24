<?php
require_once dirname(__DIR__) . '/config.php';
echo "<h2>Colonne tabella utente</h2><ul>";
$cols = $connessione->query("DESCRIBE utente")->fetchAll();
foreach ($cols as $c) echo "<li><b>{$c['Field']}</b> — {$c['Type']}</li>";
echo "</ul>";
echo "<h2>Colonne tabella ricercatore</h2><ul>";
$cols = $connessione->query("DESCRIBE ricercatore")->fetchAll();
foreach ($cols as $c) echo "<li><b>{$c['Field']}</b> — {$c['Type']}</li>";
echo "</ul>";
?>



