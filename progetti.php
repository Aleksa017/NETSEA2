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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;--coral:#e05a3a;}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);cursor:none;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{position:sticky;top:0;z-index:200;height:64px;display:flex;align-items:center;padding:0 2.5rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}
    .page-hero{padding:3rem 2.5rem 2rem;background:linear-gradient(180deg,rgba(7,30,51,.7),var(--ink));border-bottom:1px solid rgba(114,215,240,.08);}
    .page-hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(2rem,4vw,3rem);color:var(--pearl);font-weight:400;margin-bottom:.5rem;}
    .page-hero p{color:var(--muted);max-width:600px;line-height:1.7;}
    .main{max-width:900px;margin:2.5rem auto 5rem;padding:0 2.5rem;}

    .progetto-card{display:block;text-decoration:none;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.1);border-radius:16px;padding:1.75rem;margin-bottom:1.25rem;transition:border-color .2s,background .2s,transform .2s;}
    .progetto-card:hover{border-color:rgba(114,215,240,.3);background:rgba(11,61,94,.35);transform:translateY(-2px);}
    .prog-header{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.75rem;}
    .prog-titolo{font-family:'Cormorant Garamond',serif;font-size:1.5rem;color:var(--pearl);font-weight:400;}
    .prog-stato{padding:.25rem .8rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;flex-shrink:0;}
    .stato-attivo{background:rgba(44,184,155,.15);border:1px solid rgba(44,184,155,.3);color:var(--kelp);}
    .stato-concluso{background:rgba(93,154,184,.1);border:1px solid rgba(93,154,184,.2);color:var(--muted);}
    .prog-desc{color:var(--muted);font-size:.875rem;line-height:1.65;margin-bottom:1.25rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .prog-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
    .bar-wrap{flex:1;min-width:180px;}
    .bar-nums{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.4rem;}
    .bar-raccolto{color:var(--kelp);font-weight:600;}
    .bar-obiettivo{color:var(--muted);}
    .bar-track{height:5px;background:rgba(114,215,240,.08);border-radius:3px;overflow:hidden;}
    .bar-fill{height:100%;background:linear-gradient(90deg,var(--kelp),var(--wave));border-radius:3px;}
    .prog-cta{font-size:.82rem;color:var(--wave);display:flex;align-items:center;gap:.3rem;flex-shrink:0;}
    .empty{text-align:center;padding:4rem;color:var(--muted);}
    @media(max-width:600px){.main{padding:0 1.25rem 3rem;}.prog-header{flex-direction:column;}}
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