<?php
require 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: news.php"); exit(); }

$stmt = $connessione->prepare("
    SELECT n.*, u.nome AS nome_autore, u.cognome AS cognome_autore, r.qualifica
    FROM news n
    JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
    JOIN utente u ON r.id_ricercatore = u.id_utente
    WHERE n.id_news = ?
");
$stmt->execute([$id]);
$n = $stmt->fetch();
if (!$n) { header("Location: news.php"); exit(); }

// Incrementa visualizzazioni solo una volta per sessione per questa news
$viewed_key = 'viewed_news_' . $id;
if (empty($_SESSION[$viewed_key])) {
    $connessione->prepare("UPDATE news SET visualizzazioni = visualizzazioni + 1 WHERE id_news = ?")->execute([$id]);
    $_SESSION[$viewed_key] = true;
}

// Altre news dello stesso ricercatore
$altri = $connessione->prepare("
    SELECT id_news, titolo, data_pub FROM news
    WHERE id_ricercatore = ? AND id_news != ?
    ORDER BY data_pub DESC LIMIT 4
");
$altri->execute([$n['id_ricercatore'], $id]);
$altre_news = $altri->fetchAll();

$data = $n['data_pub'] ? date('d M Y', strtotime($n['data_pub'])) : '';
$autore = trim(($n['nome_autore'] ?? '') . ' ' . ($n['cognome_autore'] ?? ''));
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($n['titolo']) ?> — NetSea</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="news.php" class="nav-back">← Tutte le news</a>
</nav>

<!-- COPERTINA -->
<div class="cover">
  <?php if (!empty($n['copertina'])): ?>
    <?php if (preg_match('/\.(mp4|webm)$/i', $n['copertina'])): ?>
      <video src="<?= htmlspecialchars($n['copertina']) ?>" autoplay muted loop playsinline></video>
    <?php else: ?>
      <img src="<?= htmlspecialchars($n['copertina']) ?>" alt="<?= htmlspecialchars($n['titolo']) ?>">
    <?php endif; ?>
  <?php else: ?>
    <div class="cover-emoji">🌊</div>
  <?php endif; ?>
  <div class="cover-overlay"></div>
</div>

<div class="article-wrap">
  <div class="article-meta">
    <span class="meta-tag">📰 News</span>
    <span class="meta-date">📅 <?= $data ?></span>
    <span class="meta-views">👁 <?= (int)($n['visualizzazioni'] ?? 0) ?> visualizzazioni</span>
  </div>

  <h1><?= htmlspecialchars($n['titolo']) ?></h1>

  <div class="autore-box">
    <div class="autore-avatar">🔬</div>
    <div class="autore-info">
      <p><?= htmlspecialchars($autore) ?></p>
      <span><?= htmlspecialchars($n['qualifica'] ?? 'Ricercatore') ?></span>
    </div>
  </div>

  <div class="article-body">
    <?php
    // Rende il testo con paragrafi e supporto **grassetto** e _corsivo_
    $testo = htmlspecialchars($n['contenuto'] ?? '');
    $testo = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $testo);
    $testo = preg_replace('/_(.+?)_/s', '<em>$1</em>', $testo);
    $paragrafi = explode("\n\n", $testo);
    foreach ($paragrafi as $p_txt) {
        $p_txt = trim($p_txt);
        if ($p_txt === '') continue;
        if ($p_txt === '---') { echo '<hr style="border:none;border-top:1px solid rgba(114,215,240,.15);margin:1.5rem 0;">'; continue; }
        echo '<p>' . nl2br($p_txt) . '</p>';
    }
    ?>
  </div>

  <!-- ALTRE NEWS -->
  <?php if (!empty($altre_news)): ?>
  <div class="altre-news">
    <p class="altre-title">Altri articoli dello stesso ricercatore</p>
    <?php foreach ($altre_news as $an): ?>
    <a href="news_detail.php?id=<?= $an['id_news'] ?>" class="news-row">
      <span style="font-size:1.4rem;">📄</span>
      <h4><?= htmlspecialchars($an['titolo']) ?></h4>
      <span class="data"><?= $an['data_pub'] ? date('d M Y', strtotime($an['data_pub'])) : '' ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();
</script>
</body>
</html>