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