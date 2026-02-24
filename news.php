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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);cursor:none;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{position:sticky;top:0;z-index:200;height:64px;display:flex;align-items:center;padding:0 2.5rem;gap:1rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}

    .page-hero{padding:3rem 2.5rem 2.5rem;background:linear-gradient(180deg,rgba(7,30,51,.7),var(--ink));border-bottom:1px solid rgba(114,215,240,.08);}
    .page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(2rem,4vw,3rem);color:var(--pearl);font-weight:400;margin-bottom:1.25rem;}

    /* BARRA RICERCA */
    .search-bar{display:flex;gap:.75rem;max-width:600px;}
    .search-bar input{flex:1;padding:.75rem 1.1rem;background:rgba(11,61,94,.35);border:1px solid rgba(114,215,240,.15);border-radius:10px;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.95rem;outline:none;transition:border-color .2s;}
    .search-bar input:focus{border-color:var(--wave);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
    .search-bar input::placeholder{color:var(--muted);}
    .search-bar button{padding:.75rem 1.5rem;background:var(--wave);color:var(--ink);border:none;border-radius:10px;font-family:'Outfit',sans-serif;font-weight:600;cursor:pointer;transition:background .2s;}
    .search-bar button:hover{background:var(--foam);}

    .main{max-width:900px;margin:2.5rem auto 5rem;padding:0 2.5rem;}
    .results-info{color:var(--muted);font-size:.875rem;margin-bottom:1.5rem;}
    .results-info strong{color:var(--foam);}

    /* CARD NEWS */
    .news-card{display:grid;grid-template-columns:200px 1fr;gap:0;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.1);border-radius:14px;overflow:hidden;margin-bottom:1.1rem;text-decoration:none;transition:border-color .2s,transform .2s;}
    .news-card:hover{border-color:rgba(114,215,240,.28);transform:translateY(-2px);}
    .news-card-img{background:linear-gradient(135deg,var(--ocean),var(--deep));display:flex;align-items:center;justify-content:center;font-size:4rem;min-height:130px;position:relative;overflow:hidden;}
    .news-card-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;}
    .news-card-body{padding:1.1rem 1.25rem;display:flex;flex-direction:column;justify-content:space-between;}
    .news-card-body h3{font-family:'Cormorant Garamond',serif;font-size:1.2rem;color:var(--pearl);font-weight:400;line-height:1.25;margin-bottom:.5rem;}
    .news-card-body p{color:var(--muted);font-size:.82rem;line-height:1.55;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.75rem;flex:1;}
    .news-card-footer{display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;}
    .news-autore{font-size:.78rem;color:var(--wave);}
    .news-meta{display:flex;gap:.75rem;font-size:.75rem;color:var(--muted);}

    .empty{text-align:center;padding:4rem;color:var(--muted);}
    .empty div{font-size:3rem;margin-bottom:1rem;}

    <?php if (isset($_SESSION['id']) && in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])): ?>
    .fab{position:fixed;bottom:2rem;right:2rem;z-index:100;background:var(--wave);color:var(--ink);border:none;border-radius:50px;padding:.85rem 1.5rem;font-family:'Outfit',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:.5rem;box-shadow:0 8px 30px rgba(27,159,212,.35);transition:all .2s;}
    .fab:hover{background:var(--foam);transform:translateY(-2px);}
    <?php endif; ?>

    @media(max-width:600px){.news-card{grid-template-columns:1fr;}.news-card-img{min-height:140px;}.main{padding:0 1.25rem 3rem;}}
  </style>
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