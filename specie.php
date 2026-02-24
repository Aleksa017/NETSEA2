<?php
require 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: index.php"); exit(); }

$stmt = $connessione->prepare("SELECT * FROM specie WHERE id_specie = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) { header("Location: index.php"); exit(); }

// Badge stato conservazione
$badge_map = [
    'CR' => ['label'=>'Critico',              'class'=>'badge-cr', 'color'=>'#e8836a'],
    'EN' => ['label'=>'In pericolo',          'class'=>'badge-en', 'color'=>'#e0a060'],
    'VU' => ['label'=>'Vulnerabile',          'class'=>'badge-vu', 'color'=>'#f0c040'],
    'NT' => ['label'=>'Quasi minacciato',     'class'=>'badge-nt', 'color'=>'#c8a830'],
    'LC' => ['label'=>'Minima preoccupazione','class'=>'badge-lc', 'color'=>'#2cb89b'],
    'DD' => ['label'=>'Dati insufficienti',   'class'=>'badge-dd', 'color'=>'#5d9ab8'],
];
$stato = strtoupper(trim($s['stato_conservazione'] ?? ''));
$badge = $badge_map[$stato] ?? ['label' => ($stato ?: 'N/D'), 'class'=>'badge-lc', 'color'=>'#5d9ab8'];

// News correlate
$news_correlate = [];
try {
    $st = $connessione->prepare("
        SELECT n.id_news, n.titolo, n.data_pub,
               u.nome AS nome_autore, u.cognome AS cognome_autore
        FROM news n
        JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
        JOIN utente u ON r.id_utente = u.id_utente
        WHERE n.titolo LIKE ? OR n.contenuto LIKE ?
        ORDER BY n.data_pub DESC LIMIT 4
    ");
    $like = '%' . $s['nome'] . '%';
    $st->execute([$like, $like]);
    $news_correlate = $st->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($s['nome']) ?> — NetSea</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--mid:#1267a0;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--coral:#e05a3a;--kelp:#2cb89b;--gold:#f0c040;--text:#c5e4f5;--muted:#5d9ab8;--ease:cubic-bezier(.25,.46,.45,.94);}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);cursor:none;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{position:sticky;top:0;z-index:200;height:64px;display:flex;align-items:center;padding:0 2.5rem;gap:1rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}

    /* HERO */
    .hero{padding:3.5rem 2.5rem 2.5rem;background:linear-gradient(180deg,rgba(7,30,51,.7) 0%,var(--ink) 100%);border-bottom:1px solid rgba(114,215,240,.08);}
    .hero-inner{max-width:1050px;margin:0 auto;display:grid;grid-template-columns:1fr 300px;gap:3rem;align-items:start;}

    /* IMMAGINE */
    .specie-img{width:100%;aspect-ratio:1;border-radius:16px;overflow:hidden;background:linear-gradient(135deg,var(--ocean),var(--deep));border:1px solid rgba(114,215,240,.15);display:flex;align-items:center;justify-content:center;font-size:7rem;}
    .specie-img img{width:100%;height:100%;object-fit:cover;}

    /* INFO */
    .badge{display:inline-block;padding:.25rem .85rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:1.1rem;border:1px solid;}
    .nome{font-family:'Cormorant Garamond',serif;font-size:clamp(2rem,4vw,3rem);color:var(--pearl);font-weight:400;line-height:1.1;margin-bottom:.35rem;}
    .nome-sci{font-style:italic;color:var(--muted);font-size:1.05rem;margin-bottom:1.5rem;}
    .desc{color:var(--text);font-size:.95rem;line-height:1.8;margin-bottom:2rem;}

    /* DATI TECNICI */
    .dati{display:flex;flex-wrap:wrap;gap:.65rem;margin-bottom:2rem;}
    .dato{background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.1);border-radius:10px;padding:.7rem 1rem;min-width:120px;}
    .dato-lbl{font-size:.68rem;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:.25rem;}
    .dato-val{font-size:.9rem;color:var(--pearl);font-weight:500;}

    /* SEZIONI */
    .main{max-width:1050px;margin:2.5rem auto 5rem;padding:0 2.5rem;}
    .sez-title{font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--pearl);font-weight:400;margin-bottom:1rem;padding-bottom:.6rem;border-bottom:1px solid rgba(114,215,240,.1);}

    .news-list{display:flex;flex-direction:column;gap:.6rem;margin-bottom:3rem;}
    .news-row{display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.07);border-radius:10px;padding:.9rem 1.2rem;text-decoration:none;transition:border-color .2s,background .2s;}
    .news-row:hover{border-color:rgba(114,215,240,.22);background:rgba(11,61,94,.4);}
    .news-row h4{color:var(--pearl);font-size:.88rem;font-weight:500;margin-bottom:.15rem;}
    .news-row p{color:var(--muted);font-size:.75rem;}
    .news-row .data{margin-left:auto;flex-shrink:0;font-size:.74rem;color:var(--muted);}
    .vuoto{color:var(--muted);font-size:.875rem;padding:1rem 0;}

    @media(max-width:768px){
      .hero-inner{grid-template-columns:1fr;}
      .specie-img{max-width:240px;margin:0 auto;}
      .main{padding:0 1.25rem 3rem;}
    }
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
  <a href="javascript:history.back()" class="nav-back">← Torna indietro</a>
</nav>

<div class="hero">
  <div class="hero-inner">

    <div>
      <span class="badge" style="color:<?= $badge['color'] ?>;border-color:<?= $badge['color'] ?>44;background:<?= $badge['color'] ?>18;">
        <?= htmlspecialchars($badge['label']) ?> (<?= htmlspecialchars($stato ?: 'N/D') ?>)
      </span>
      <h1 class="nome"><?= htmlspecialchars($s['nome']) ?></h1>
      <p class="nome-sci"><?= htmlspecialchars($s['nome_scientifico'] ?? '') ?></p>
      <p class="desc"><?= nl2br(htmlspecialchars($s['descrizione'] ?? 'Nessuna descrizione disponibile.')) ?></p>

      <div class="dati">
        <?php
        $campi = [
          'Famiglia'   => $s['famiglia']  ?? null,
          'Classe'     => $s['classe']    ?? null,
          'Dieta'      => $s['dieta']     ?? null,
          'Dimensioni' => $s['dimensioni'] ? $s['dimensioni'] . ' cm' : null,
          'Peso'       => $s['peso']      ? $s['peso'] . ' kg' : null,
        ];
        foreach ($campi as $lbl => $val):
          if (!$val) continue;
        ?>
        <div class="dato">
          <p class="dato-lbl"><?= $lbl ?></p>
          <p class="dato-val"><?= htmlspecialchars($val) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="specie-img">
      <?php if (!empty($s['immagine'])): ?>
        <img src="<?= htmlspecialchars($s['immagine']) ?>" alt="<?= htmlspecialchars($s['nome']) ?>">
      <?php else: ?>🐟<?php endif; ?>
    </div>

  </div>
</div>

<div class="main">
  <p class="sez-title">📰 News correlate</p>
  <div class="news-list">
    <?php if (empty($news_correlate)): ?>
      <p class="vuoto">Nessuna news correlata trovata.</p>
    <?php else: ?>
      <?php foreach ($news_correlate as $n): ?>
      <a href="news_detail.php?id=<?= $n['id_news'] ?>" class="news-row">
        <div>
          <h4><?= htmlspecialchars($n['titolo']) ?></h4>
          <p><?= htmlspecialchars(($n['nome_autore'] ?? '') . ' ' . ($n['cognome_autore'] ?? '')) ?></p>
        </div>
        <span class="data"><?= $n['data_pub'] ? date('d M Y', strtotime($n['data_pub'])) : '' ?></span>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();
</script>
</body>
</html>