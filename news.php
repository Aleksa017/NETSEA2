<?php
require 'config.php';

// Ricerca
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

if ($q) {
    $stmt = $connessione->prepare("
        SELECT n.*, u.nome AS nome_autore, u.cognome AS cognome_autore
        FROM news n
        JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
        JOIN utente u ON r.id_ricercatore = u.id_utente
        WHERE n.titolo LIKE ? OR n.contenuto LIKE ?
        ORDER BY n.data_pub DESC
    ");
    $stmt->execute([$like, $like]);
} else {
    $stmt = $connessione->query("
        SELECT n.*, u.nome AS nome_autore, u.cognome AS cognome_autore
        FROM news n
        JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
        JOIN utente u ON r.id_ricercatore = u.id_utente
        ORDER BY n.data_pub DESC
    ");
}
$tutte_news = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News — NetSea</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <svg viewBox="0 0 40 40" fill="none">
      <circle cx="20" cy="20" r="18" fill="rgba(27,159,212,.15)" stroke="rgba(114,215,240,.3)" stroke-width="1"/>
      <path d="M8 22 Q12 16 16 22 Q20 28 24 22 Q28 16 32 22" stroke="#72d7f0" stroke-width="2" fill="none" stroke-linecap="round"/>
    </svg>
    NetSea
  </a>
  <a href="javascript:history.back()" class="nav-back">← Indietro</a>
</nav>

<div class="page-hero">
  <h1>📰 News Marine</h1>
  <form class="search-bar" method="GET" action="news.php">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Cerca per titolo o contenuto…" autocomplete="off">
    <button type="submit">Cerca</button>
  </form>
</div>

<div class="main">
  <?php if ($q): ?>
    <p class="results-info">
      <?= count($tutte_news) ?> risultati per <strong>"<?= htmlspecialchars($q) ?>"</strong>
      — <a href="news.php" style="color:var(--muted);font-size:.82rem;">Cancella ricerca</a>
    </p>
  <?php endif; ?>

  <?php if (empty($tutte_news)): ?>
    <div class="empty">
      <div>🌊</div>
      <p><?= $q ? 'Nessuna news trovata per questa ricerca.' : 'Nessuna news pubblicata ancora.' ?></p>
    </div>
  <?php else: ?>
    <?php foreach ($tutte_news as $n):
      $data   = $n['data_pub'] ? date('d M Y', strtotime($n['data_pub'])) : '';
      $autore = trim(($n['nome_autore'] ?? '') . ' ' . ($n['cognome_autore'] ?? ''));
      $isImg  = !empty($n['copertina']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $n['copertina']);
    ?>
    <a href="news_detail.php?id=<?= $n['id_news'] ?>" class="news-card">
      <div class="news-card-img">
        <?php if ($isImg): ?>
          <img src="<?= htmlspecialchars($n['copertina']) ?>" alt="">
        <?php else: ?>
          🌊
        <?php endif; ?>
      </div>
      <div class="news-card-body">
        <h3><?= htmlspecialchars($n['titolo']) ?></h3>
        <p><?= htmlspecialchars(mb_substr($n['contenuto'] ?? '', 0, 150)) ?>…</p>
        <div class="news-card-footer">
          <span class="news-autore">🔬 <?= htmlspecialchars($autore) ?></span>
          <div class="news-meta">
            <span>📅 <?= $data ?></span>
            <span>👁 <?= (int)($n['visualizzazioni'] ?? 0) ?></span>
          </div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if (isset($_SESSION['id']) && in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])): ?>
  <a href="crea_news.php" class="fab">✏️ Pubblica news</a>
<?php endif; ?>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();
</script>
</body>
</html>