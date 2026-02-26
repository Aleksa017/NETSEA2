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

// Habitat della specie
$habitat_specie = [];
try {
    $st = $connessione->prepare("
        SELECT h.*, l.nome AS luogo_nome, l.id_luogo
        FROM specie_habitat sh
        JOIN habitat h ON sh.id_habitat = h.id_habitat
        LEFT JOIN luogo l ON h.id_luogo = l.id_luogo
        WHERE sh.id_specie = ?
    ");
    $st->execute([$id]);
    $habitat_specie = $st->fetchAll();
} catch (PDOException $e) {}

// Minacce della specie
$minacce_specie = [];
try {
    $st = $connessione->prepare("
        SELECT m.*
        FROM specie_minaccia sm
        JOIN minaccia m ON sm.id_minaccia = m.id_minaccia
        WHERE sm.id_specie = ?
        ORDER BY m.tipo
    ");
    $st->execute([$id]);
    $minacce_specie = $st->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($s['nome']) ?> — NetSea</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<nav>
  <a href="index.php" class="nav-logo">
    <img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
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

<div class="main" style="max-width:900px;margin:0 auto;">
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

  <?php if (!empty($habitat_specie)): ?>
  <p class="sez-title" style="margin-top:2.5rem;">🌿 Habitat naturali</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;margin-bottom:2rem;">
    <?php foreach ($habitat_specie as $h): ?>
    <a href="luoghi.php<?= $h['id_luogo'] ? '?id='.$h['id_luogo'] : '' ?>"
       style="background:rgba(11,61,94,.25);border:1px solid rgba(114,215,240,.12);border-radius:12px;padding:1.1rem 1.25rem;text-decoration:none;display:block;transition:border-color .2s;"
       onmouseover="this.style.borderColor='rgba(114,215,240,.3)'" onmouseout="this.style.borderColor='rgba(114,215,240,.12)'">
      <p style="color:var(--wave);font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.3rem;">
        📏 <?= htmlspecialchars($h['range_habitat'] ?? '') ?>
        <?= $h['temperatura'] ? ' · 🌡️ ' . $h['temperatura'] . '°C' : '' ?>
      </p>
      <p style="color:var(--pearl);font-weight:500;font-size:.95rem;margin-bottom:.4rem;"><?= htmlspecialchars($h['nome']) ?></p>
      <p style="color:var(--muted);font-size:.78rem;line-height:1.55;"><?= htmlspecialchars(mb_substr($h['descrizione']??'',0,100)) ?>…</p>
      <?php if ($h['luogo_nome']): ?>
      <p style="color:rgba(114,215,240,.5);font-size:.72rem;margin-top:.5rem;">📍 <?= htmlspecialchars($h['luogo_nome']) ?></p>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($minacce_specie)): ?>
  <p class="sez-title" style="margin-top:1.5rem;">⚠️ Minacce principali</p>
  <div style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:3rem;">
    <?php foreach ($minacce_specie as $m): ?>
    <div style="display:flex;gap:1rem;align-items:flex-start;background:rgba(224,90,58,.05);border:1px solid rgba(224,90,58,.15);border-radius:10px;padding:1rem 1.25rem;">
      <span style="font-size:1.4rem;flex-shrink:0;">⚠️</span>
      <div>
        <p style="color:var(--pearl);font-weight:500;font-size:.9rem;margin-bottom:.25rem;"><?= htmlspecialchars($m['nome']) ?></p>
        <p style="color:#e0a060;font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;margin-bottom:.4rem;"><?= htmlspecialchars($m['tipo'] ?? '') ?></p>
        <p style="color:rgba(197,228,245,.75);font-size:.82rem;line-height:1.6;"><?= htmlspecialchars($m['descrizione'] ?? '') ?></p>
      </div>
    </div>
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