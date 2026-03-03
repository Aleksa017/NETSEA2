<?php
require 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: news.php"); exit(); }

$stmt = $connessione->prepare("
    SELECT n.*, u.nome AS nome_autore, u.cognome AS cognome_autore, r.qualifica, n.link_donazione, (SELECT COUNT(*) FROM visualizzazione_news vn WHERE vn.id_news = n.id_news) AS visualizzazioni
    FROM news n
    INNER JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
    INNER JOIN utente u ON r.id_ricercatore = u.id_utente
    WHERE n.id_news = ?
");
$stmt->execute([$id]);
$n = $stmt->fetch();
if (!$n) { header("Location: news.php"); exit(); }

// Incrementa visualizzazioni
// Registra visualizzazione news
$id_utente_vis = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
$view_key_n = 'viewed_news_'.$id;
if (empty($_SESSION[$view_key_n])) {
    $connessione->prepare("INSERT INTO visualizzazione_news (id_news, id_utente) VALUES (?, ?)")->execute([$id, $id_utente_vis]);
    $_SESSION[$view_key_n] = true;
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

// Cerca un progetto correlato — stesso ricercatore OPPURE parole chiave comuni nel titolo
// Prima cerca per ricercatore, poi allarga a tutti i progetti attivi

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
  <a href="index.php" class="nav-logo"><img src="uploads/logos/logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));"></a>
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
    <span class="meta-tag">News</span>
    <span class="meta-date"><?= $data ?></span>
    <span class="meta-views"><?= (int)($n['visualizzazioni'] ?? 0) ?> visualizzazioni</span>
  </div>

  <h1><?= htmlspecialchars($n['titolo']) ?></h1>

  <div class="autore-box">
    <div class="autore-avatar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          <line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>
        </svg>
      </div>
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

  <!-- BOX DONAZIONE COLLEGATA -->
  <?php if ($proj): ?>
  <?php
    $rac = (float)($proj['raccolto'] ?? 0);
    $bud = (float)($proj['budget'] ?? 1);
    $pct = $bud > 0 ? min(100, round($rac / $bud * 100)) : 0;
    $is_urgente = ($proj['stato'] ?? '') === 'urgente';
  ?>
  <div style="margin:2.5rem 0;padding:1.5rem 1.75rem;background:rgba(11,61,94,.25);border:1px solid <?= $is_urgente ? 'rgba(232,131,106,.3)' : 'rgba(44,184,155,.2)' ?>;border-radius:16px;">
    <p style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:<?= $is_urgente ? '#e8836a' : '#2cb89b' ?>;margin-bottom:.6rem;">
      <?= $is_urgente ? 'Progetto urgente collegato' : 'Sostieni la ricerca' ?>
    </p>
    <h3 style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--pearl);font-weight:400;margin-bottom:.4rem;">
      <?= htmlspecialchars($proj['titolo']) ?>
    </h3>
    <p style="font-size:.82rem;color:var(--muted);line-height:1.6;margin-bottom:1.1rem;">
      <?= htmlspecialchars(mb_substr($proj['obiettivo'] ?? '', 0, 160)) ?>…
    </p>
    <div style="background:rgba(4,17,30,.4);border-radius:6px;height:6px;margin-bottom:.5rem;overflow:hidden;">
      <div style="height:100%;width:<?= $pct ?>%;background:<?= $is_urgente ? 'linear-gradient(90deg,#e8836a,#e0a060)' : 'linear-gradient(90deg,var(--wave),var(--kelp))' ?>;border-radius:6px;transition:width .6s;"></div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;font-size:.78rem;margin-bottom:1.1rem;">
      <span style="color:var(--pearl);"><strong>€ <?= number_format($rac,0,',','.') ?></strong> raccolti</span>
      <span style="color:var(--muted);"><?= $pct ?>% di € <?= number_format($bud,0,',','.') ?></span>
    </div>
    <a href="progetto_detail.php?id=<?= $proj['id_pd'] ?>"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.4rem;background:<?= $is_urgente ? 'rgba(232,131,106,.15)' : 'rgba(27,159,212,.15)' ?>;border:1px solid <?= $is_urgente ? 'rgba(232,131,106,.35)' : 'rgba(27,159,212,.35)' ?>;border-radius:9px;color:<?= $is_urgente ? '#e8836a' : 'var(--wave)' ?>;text-decoration:none;font-size:.85rem;font-weight:600;transition:background .2s;"
       onmouseover="this.style.background='<?= $is_urgente ? 'rgba(232,131,106,.28)' : 'rgba(27,159,212,.28)' ?>'"
       onmouseout="this.style.background='<?= $is_urgente ? 'rgba(232,131,106,.15)' : 'rgba(27,159,212,.15)' ?>'">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
      </svg>
      Sostieni questo progetto
    </a>
  </div>
  <?php endif; ?>

  <!-- LINK DONAZIONE -->
  <?php if (!empty($n['link_donazione'])): ?>
  <div style="margin:2rem 0;padding:1.25rem 1.5rem;background:rgba(44,184,155,.07);border:1px solid rgba(44,184,155,.2);border-radius:12px;display:flex;align-items:center;gap:1rem;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2cb89b" stroke-width="1.8">
      <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
    </svg>
    <div>
      <p style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#2cb89b;margin-bottom:.2rem;">Sostieni la ricerca</p>
      <a href="<?= htmlspecialchars($n['link_donazione']) ?>" target="_blank"
         style="color:var(--pearl);font-size:.9rem;text-decoration:none;border-bottom:1px solid rgba(114,215,240,.2);">
        <?= htmlspecialchars($n['link_donazione']) ?> →
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- ALTRE NEWS -->
  <?php if (!empty($altre_news)): ?>
  <div class="altre-news">
    <p class="altre-title">Altri articoli dello stesso ricercatore</p>
    <?php foreach ($altre_news as $an): ?>
    <a href="news_detail.php?id=<?= $an['id_news'] ?>" class="news-row">
      
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