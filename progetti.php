<?php
require 'config.php';
$progetti = $connessione->query("SELECT * FROM progetto ORDER BY data_i DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Progetti — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .proj-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.5rem; max-width:1200px; margin:0 auto; padding:2rem 1.5rem 5rem; }
    .proj-card { display:block; text-decoration:none; background:rgba(11,61,94,.22); border:1px solid rgba(114,215,240,.1); border-radius:16px; overflow:hidden; transition:transform .25s, border-color .25s, box-shadow .25s; }
    .proj-card:hover { transform:translateY(-5px); border-color:rgba(114,215,240,.28); box-shadow:0 16px 48px rgba(0,0,0,.4); }
    .proj-cover { position:relative; height:180px; background:linear-gradient(135deg,var(--ocean),var(--deep)); overflow:hidden; }
    .proj-cover img { width:100%; height:100%; object-fit:cover; transition:transform .4s; }
    .proj-card:hover .proj-cover img { transform:scale(1.05); }
    .proj-cover::after { content:''; position:absolute; inset:0; background:linear-gradient(0deg,rgba(4,17,30,.7) 0%,transparent 60%); }
    .proj-cover-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
    .proj-stato-badge { position:absolute; top:.75rem; right:.75rem; z-index:2; padding:.2rem .75rem; border-radius:20px; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; backdrop-filter:blur(6px); }
    .proj-stato-badge.attivo { background:rgba(44,184,155,.2); border:1px solid rgba(44,184,155,.4); color:#2cb89b; }
    .proj-stato-badge.concluso { background:rgba(93,154,184,.15); border:1px solid rgba(93,154,184,.3); color:#5d9ab8; }
    .proj-body { padding:1.25rem 1.4rem 1.4rem; }
    .proj-titolo { font-family:'Cormorant Garamond',serif; font-size:1.25rem; font-weight:400; color:var(--pearl); line-height:1.25; margin-bottom:.5rem; }
    .proj-desc { font-size:.82rem; color:var(--muted); line-height:1.6; margin-bottom:1.1rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .proj-bar-wrap { margin-bottom:.5rem; }
    .proj-bar-nums { display:flex; justify-content:space-between; font-size:.75rem; margin-bottom:.3rem; }
    .proj-bar-raccolto { color:var(--wave); font-weight:600; }
    .proj-bar-obiettivo { color:var(--muted); }
    .proj-bar-track { height:4px; background:rgba(114,215,240,.12); border-radius:4px; overflow:hidden; }
    .proj-bar-fill { height:100%; background:linear-gradient(90deg,var(--wave),var(--foam)); border-radius:4px; transition:width .5s; }
    .proj-cta { font-size:.78rem; color:var(--wave); text-align:right; margin-top:.6rem; }
    .proj-hero { padding:5.5rem 2rem 2.5rem; text-align:center; background:linear-gradient(180deg,rgba(4,17,30,.98),rgba(7,30,51,.6)); border-bottom:1px solid rgba(114,215,240,.07); }
    .proj-hero h1 { font-family:'Cormorant Garamond',serif; font-size:clamp(2.2rem,5vw,3.5rem); font-weight:400; color:var(--pearl); margin-bottom:.4rem; }
    .proj-hero p { color:var(--muted); max-width:560px; margin:0 auto; }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo"><img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));"></a>
  <a href="javascript:history.back()" class="nav-back">← Indietro</a>
</nav>

<div class="proj-hero">
  <h1>💚 Progetti di Donazione</h1>
  <p>Sostieni la ricerca e la protezione degli ecosistemi marini nel Mediterraneo.</p>
</div>

<div class="proj-grid">
  <?php if (empty($progetti)): ?>
    <div style="text-align:center;padding:5rem;color:var(--muted);"><div style="font-size:3rem;margin-bottom:1rem;">🌊</div><p>Nessun progetto disponibile al momento.</p></div>
  <?php else: ?>
    <?php foreach ($progetti as $don_i => $p):
      $raccolto  = (float)($p['raccolto'] ?? 0);
      $budget    = (float)($p['budget']   ?? 0);
      $perc      = $budget > 0 ? min(100, round($raccolto / $budget * 100)) : 0;
      $is_attivo = strtolower($p['stato'] ?? '') === 'attivo';
    ?>
    <a href="progetto_detail.php?id=<?= $p['id_pd'] ?>" class="proj-card">
      <div class="proj-cover">
        <?php
          $hasCoverP = !empty($p['immagine']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $p['immagine']);
          $proj_icons = ['🐋','🪸','🦈','🐢','🦑','🐬','🌊','🐠'];
        ?>
        <?php if ($hasCoverP): ?>
          <img src="<?= htmlspecialchars($p['immagine']) ?>" alt="">
        <?php else: ?>
          <div class="proj-cover-placeholder" style="background:linear-gradient(135deg,<?= $is_attivo ? '#062040,#0b5575' : '#1a0a1a,#2a0a2a' ?>);">
            <span style="font-size:5rem;opacity:.25;"><?= $proj_icons[$don_i % 8] ?></span>
          </div>
        <?php endif; ?>
        <span class="proj-stato-badge <?= $is_attivo ? 'attivo' : 'concluso' ?>">
          <?= $is_attivo ? '● Attivo' : '● Concluso' ?>
        </span>
      </div>
      <div class="proj-body">
        <h2 class="proj-titolo"><?= htmlspecialchars($p['titolo']) ?></h2>
        <p class="proj-desc"><?= htmlspecialchars($p['obiettivo'] ?? '') ?></p>
        <div class="proj-bar-wrap">
          <div class="proj-bar-nums">
            <span class="proj-bar-raccolto">€ <?= number_format($raccolto,0,',','.') ?></span>
            <span class="proj-bar-obiettivo">su € <?= number_format($budget,0,',','.') ?></span>
          </div>
          <div class="proj-bar-track"><div class="proj-bar-fill" style="width:<?= $perc ?>%"></div></div>
        </div>
        <p class="proj-cta">Scopri e dona →</p>
      </div>
    </a>
    <?php endforeach; ?>
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