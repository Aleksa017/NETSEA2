<?php
require 'config.php';
// Gestione eliminazione news (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_news') {
  if (!isset($_SESSION['id'])) {
    header('HTTP/1.1 403 Forbidden'); exit('Forbidden');
  }
  $id_news = (int)($_POST['id_news'] ?? 0);
  if (!$id_news) { header('Location: news.php'); exit(); }
  try {
    // Controlla autore della news
    $chk = $connessione->prepare('SELECT id_ricercatore, copertina FROM news WHERE id_news = ?');
    $chk->execute([$id_news]);
    $row = $chk->fetch();
    if (!$row) { header('Location: news.php'); exit(); }
    $id_ric = (int)$row['id_ricercatore'];
    $isAdmin = (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin');
    $isAuthor = (isset($_SESSION['id']) && (int)$_SESSION['id'] === $id_ric);
    if (!($isAdmin || $isAuthor)) { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
    // Elimina copertina dal filesystem (opzionale)
    if (!empty($row['copertina'])) {
      $fpath = __DIR__ . '/' . $row['copertina'];
      if (is_file($fpath)) @unlink($fpath);
    }
    // Elimina la news
    $del = $connessione->prepare('DELETE FROM news WHERE id_news = ?');
    $del->execute([$id_news]);
    header('Location: news.php?deleted=1'); exit();
  } catch (Exception $e) {
    header('Location: news.php?deleted=0'); exit();
  }
}

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
    <div class="news-card" style="position:relative;">
      <a href="news_detail.php?id=<?= $n['id_news'] ?>" class="news-card-link" style="display:flex;gap:1rem;text-decoration:none;color:inherit;">
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
      <?php
        // Mostra pulsante elimina solo se l'utente è admin o autore della news
        $canDelete = false;
        if (isset($_SESSION['id'])) {
          if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') $canDelete = true;
          elseif ((int)$_SESSION['id'] === (int)($n['id_ricercatore'] ?? 0)) $canDelete = true;
        }
      ?>
      <?php if ($canDelete): ?>
        <form method="POST" style="position:absolute;top:8px;right:8px;" onsubmit="return confirm('Eliminare questa news? Questa operazione è irreversibile.');">
          <input type="hidden" name="action" value="delete_news">
          <input type="hidden" name="id_news" value="<?= (int)$n['id_news'] ?>">
          <button type="submit" title="Elimina" style="background:transparent;border:1px solid rgba(255,255,255,.08);color:#f66;padding:.35rem .5rem;border-radius:6px;cursor:pointer;">Elimina</button>
        </form>
      <?php endif; ?>
    </div>
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