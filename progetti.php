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
  <h1>💚 Progetti di Donazione</h1>
  <p>Sostieni la ricerca e la protezione degli ecosistemi marini. Clicca su un progetto per saperne di più e donare.</p>
</div>

<div class="main">
  <?php if (empty($progetti)): ?>
    <div class="empty">
      <div style="font-size:3rem;margin-bottom:1rem;">🌊</div>
      <p>Nessun progetto disponibile al momento.</p>
    </div>
  <?php else: ?>
    <?php foreach ($progetti as $p):
      $raccolto  = (float)($p['raccolto'] ?? 0);
      $budget    = (float)($p['budget']   ?? 0);
      $perc      = $budget > 0 ? min(100, round($raccolto / $budget * 100)) : 0;
      $is_attivo = strtolower($p['stato'] ?? '') === 'attivo';
    ?>
    <a href="progetto_detail.php?id=<?= $p['id_pd'] ?>" class="progetto-card">
      <div class="prog-header">
        <h2 class="prog-titolo"><?= htmlspecialchars($p['titolo']) ?></h2>
        <span class="prog-stato <?= $is_attivo ? 'stato-attivo' : 'stato-concluso' ?>">
          <?= $is_attivo ? '🟢 Attivo' : '⚫ Concluso' ?>
        </span>
      </div>
      <p class="prog-desc"><?= htmlspecialchars($p['obiettivo'] ?? '') ?></p>
      <div class="prog-footer">
        <div class="bar-wrap">
          <div class="bar-nums">
            <span class="bar-raccolto">€ <?= number_format($raccolto,2,',','.') ?></span>
            <span class="bar-obiettivo">/ € <?= number_format($budget,2,',','.') ?></span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?= $perc ?>%"></div></div>
        </div>
        <span class="prog-cta">Scopri e dona →</span>
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