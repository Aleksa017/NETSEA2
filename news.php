<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_news') {
  if (!isset($_SESSION['id'])) { header('HTTP/1.1 403 Forbidden'); exit(); }
  $id_news = (int)($_POST['id_news'] ?? 0);
  if (!$id_news) { header('Location: news.php'); exit(); }
  try {
    $chk = $connessione->prepare('SELECT id_ricercatore, copertina FROM news WHERE id_news = ?');
    $chk->execute([$id_news]); $row = $chk->fetch();
    if (!$row) { header('Location: news.php'); exit(); }
    $isAdmin  = isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin';
    $isAuthor = isset($_SESSION['id']) && (int)$_SESSION['id'] === (int)$row['id_ricercatore'];
    if (!($isAdmin || $isAuthor)) { header('HTTP/1.1 403 Forbidden'); exit(); }
    if (!empty($row['copertina'])) { $fp = __DIR__.'/'.$row['copertina']; if (is_file($fp)) @unlink($fp); }
    $connessione->prepare('DELETE FROM news WHERE id_news = ?')->execute([$id_news]);
    header('Location: news.php?deleted=1'); exit();
  } catch (Exception $e) { header('Location: news.php'); exit(); }
}

$q = trim($_GET['q'] ?? '');
$like = '%'.$q.'%';
if ($q) {
  $stmt = $connessione->prepare("
    SELECT n.*, u.nome AS nome_autore, u.cognome AS cognome_autore
    FROM news n JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
    JOIN utente u ON r.id_ricercatore = u.id_utente
    WHERE n.titolo LIKE ? OR n.contenuto LIKE ? ORDER BY n.data_pub DESC");
  $stmt->execute([$like, $like]);
} else {
  $stmt = $connessione->query("
    SELECT n.*, u.nome AS nome_autore, u.cognome AS cognome_autore
    FROM news n JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
    JOIN utente u ON r.id_ricercatore = u.id_utente ORDER BY n.data_pub DESC");
}
$tutte_news = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News Marine — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .news-hero { padding:5.5rem 2rem 3rem; text-align:center; background:linear-gradient(180deg,rgba(4,17,30,.98),rgba(7,30,51,.6)); border-bottom:1px solid rgba(114,215,240,.07); }
    .news-hero h1 { font-family:'Cormorant Garamond',serif; font-size:clamp(2.2rem,5vw,3.5rem); font-weight:400; color:var(--pearl); margin-bottom:.4rem; }
    .news-hero p { color:var(--muted); margin-bottom:2rem; }
    .news-search { display:flex; gap:.5rem; max-width:540px; margin:0 auto .75rem; }
    .news-search input { flex:1; padding:.75rem 1.2rem; background:rgba(11,61,94,.4); border:1px solid rgba(114,215,240,.18); border-radius:12px; color:var(--pearl); font-family:'Outfit',sans-serif; font-size:.95rem; outline:none; }
    .news-search input:focus { border-color:var(--wave); box-shadow:0 0 0 3px rgba(27,159,212,.12); }
    .news-search input::placeholder { color:var(--muted); }
    .news-search button { padding:.75rem 1.5rem; background:var(--wave); color:var(--ink); border:none; border-radius:12px; font-family:'Outfit',sans-serif; font-weight:600; cursor:pointer; }
    .news-search button:hover { background:var(--foam); }
    .news-wrap { max-width:1200px; margin:0 auto; padding:2.5rem 1.5rem 5rem; }
    .news-results { font-size:.78rem; color:var(--muted); margin-bottom:1.5rem; }
    .news-results strong { color:var(--wave); }
    .news-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.5rem; }
    @media(min-width:768px){ .news-grid .ncard-wrap:first-child { grid-column:span 2; } }
    .ncard-wrap { position:relative; }
    .ncard { border-radius:16px; overflow:hidden; background:rgba(11,61,94,.25); border:1px solid rgba(114,215,240,.1); text-decoration:none; display:block; transition:transform .25s,border-color .25s,box-shadow .25s; height:100%; }
    .ncard:hover { transform:translateY(-4px); border-color:rgba(114,215,240,.28); box-shadow:0 16px 48px rgba(0,0,0,.4); }
    .ncard-img { position:relative; width:100%; height:200px; overflow:hidden; background:linear-gradient(135deg,var(--ocean),var(--deep)); }
    .ncard-wrap:first-child .ncard-img { height:280px; }
    .ncard-img img { width:100%; height:100%; object-fit:cover; transition:transform .4s; }
    .ncard:hover .ncard-img img { transform:scale(1.05); }
    .ncard-img-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:5rem; opacity:.15; }
    .ncard-img::after { content:''; position:absolute; inset:0; background:linear-gradient(0deg,rgba(4,17,30,.65) 0%,transparent 60%); }
    .ncard-body { padding:1.1rem 1.3rem 1.3rem; }
    .ncard-meta { display:flex; align-items:center; gap:.5rem; font-size:.72rem; color:var(--muted); margin-bottom:.55rem; flex-wrap:wrap; }
    .ncard-autore { color:var(--wave); font-weight:500; }
    .ncard-title { font-family:'Cormorant Garamond',serif; font-size:1.2rem; font-weight:400; color:var(--pearl); line-height:1.25; margin-bottom:.45rem; }
    .ncard-wrap:first-child .ncard-title { font-size:1.5rem; }
    .ncard-excerpt { font-size:.82rem; color:var(--muted); line-height:1.6; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .ncard-wrap:first-child .ncard-excerpt { -webkit-line-clamp:3; }
    .ncard-footer { display:flex; align-items:center; justify-content:space-between; margin-top:.85rem; padding-top:.75rem; border-top:1px solid rgba(114,215,240,.07); font-size:.75rem; color:var(--muted); }
    .ncard-read { color:var(--wave); }
    .ncard-del { position:absolute; top:.75rem; right:.75rem; z-index:10; background:rgba(4,17,30,.75); border:1px solid rgba(255,60,60,.3); color:#f66; padding:.3rem .65rem; border-radius:6px; font-size:.72rem; cursor:pointer; backdrop-filter:blur(4px); }
    .empty-state { text-align:center; padding:5rem 2rem; color:var(--muted); }
    .empty-state div { font-size:3.5rem; margin-bottom:1rem; }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div><div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo"><img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));"></a>
  <a href="javascript:history.back()" class="nav-back">← Indietro</a>
</nav>

<div class="news-hero">
  <h1>📰 News Marine</h1>
  <p>Aggiornamenti dalla ricerca oceanografica nel Mediterraneo</p>
  <form class="news-search" method="GET" action="news.php">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cerca per titolo o contenuto…" autocomplete="off">
    <button type="submit">Cerca</button>
  </form>
  <?php if($q): ?><a href="news.php" style="font-size:.78rem;color:var(--muted);">✕ Cancella ricerca</a><?php endif; ?>
</div>

<div class="news-wrap">
  <?php if($q): ?><p class="news-results"><strong><?= count($tutte_news) ?></strong> risultati per "<strong><?= htmlspecialchars($q) ?></strong>"</p><?php endif; ?>
  <?php if(empty($tutte_news)): ?>
    <div class="empty-state"><div>🌊</div><p><?= $q ? 'Nessuna news trovata.' : 'Nessuna news pubblicata ancora.' ?></p></div>
  <?php else: ?>
  <div class="news-grid">
    <?php foreach($tutte_news as $n):
      $data   = $n['data_pub'] ? date('d M Y', strtotime($n['data_pub'])) : '';
      $autore = trim(($n['nome_autore']??'').' '.($n['cognome_autore']??''));
      $isImg  = !empty($n['copertina']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $n['copertina']);
      $canDel = false;
      if (isset($_SESSION['id'])) {
        if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') $canDel = true;
        elseif ((int)$_SESSION['id'] === (int)($n['id_ricercatore']??0)) $canDel = true;
      }
    ?>
    <div class="ncard-wrap">
      <a href="news_detail.php?id=<?= $n['id_news'] ?>" class="ncard">
        <div class="ncard-img">
          <?php if($isImg): ?><img src="<?= htmlspecialchars($n['copertina']) ?>" alt="">
          <?php else: ?><div class="ncard-img-placeholder">🌊</div><?php endif; ?>
        </div>
        <div class="ncard-body">
          <div class="ncard-meta">
            <span class="ncard-autore">🔬 <?= htmlspecialchars($autore ?: 'NetSea') ?></span>
            <span>·</span><span>📅 <?= $data ?></span>
          </div>
          <h2 class="ncard-title"><?= htmlspecialchars($n['titolo']) ?></h2>
          <p class="ncard-excerpt"><?= htmlspecialchars(mb_substr($n['contenuto']??'',0,180)) ?></p>
          <div class="ncard-footer">
            <span>👁 <?= (int)($n['visualizzazioni']??0) ?></span>
            <span class="ncard-read">Leggi →</span>
          </div>
        </div>
      </a>
      <?php if($canDel): ?>
      <form method="POST" onsubmit="return confirm('Eliminare questa news?')">
        <input type="hidden" name="action" value="delete_news">
        <input type="hidden" name="id_news" value="<?= (int)$n['id_news'] ?>">
        <button class="ncard-del" type="submit">Elimina</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php if (isset($_SESSION['id']) && in_array($_SESSION['ruolo']??'', ['ricercatore','admin'])): ?>
<a href="crea_news.php" class="fab">✏️ Pubblica news</a>
<?php endif; ?>
<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';cur.style.opacity='1';ring.style.opacity='1';});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();
</script>
</body></html>