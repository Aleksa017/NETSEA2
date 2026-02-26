<?php
require 'config.php';

// ── Carica tutti i luoghi con habitat e osservazioni collegate ─────────────
$luoghi = $connessione->query("
    SELECT l.*, p.nome AS paese_nome, p.continente,
           COUNT(DISTINCT o.id_osservazione) AS n_osservazioni,
           COUNT(DISTINCT h.id_habitat)      AS n_habitat
    FROM luogo l
    LEFT JOIN paese p ON l.id_paese = p.id_paese
    LEFT JOIN osservazione o ON o.id_luogo = l.id_luogo
    LEFT JOIN habitat h ON h.id_luogo = l.id_luogo
    GROUP BY l.id_luogo
    ORDER BY l.nome
")->fetchAll();

// ── Dettaglio singolo luogo ────────────────────────────────────────────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$luogo = null;
$habitat_luogo = [];
$specie_osservate = [];

if ($id) {
    $st = $connessione->prepare("
        SELECT l.*, p.nome AS paese_nome, p.continente
        FROM luogo l LEFT JOIN paese p ON l.id_paese = p.id_paese
        WHERE l.id_luogo = ?
    ");
    $st->execute([$id]);
    $luogo = $st->fetch();

    if ($luogo) {
        // Habitat di questo luogo
        $st = $connessione->prepare("
            SELECT h.*, COUNT(sh.id_specie) AS n_specie
            FROM habitat h
            LEFT JOIN specie_habitat sh ON sh.id_habitat = h.id_habitat
            WHERE h.id_luogo = ?
            GROUP BY h.id_habitat
        ");
        $st->execute([$id]);
        $habitat_luogo = $st->fetchAll();

        // Specie osservate in questo luogo
        $st = $connessione->prepare("
            SELECT DISTINCT s.id_specie, s.nome, s.nome_scientifico,
                   s.stato_conservazione, s.immagine,
                   MAX(o.data) AS ultima_osservazione
            FROM osservazione o
            JOIN specie s ON o.id_specie = s.id_specie
            WHERE o.id_luogo = ?
            GROUP BY s.id_specie
            ORDER BY ultima_osservazione DESC
        ");
        $st->execute([$id]);
        $specie_osservate = $st->fetchAll();
    }
}

$badge_col = ['CR'=>'#e8836a','EN'=>'#e0a060','VU'=>'#f0c040','NT'=>'#c8a830','LC'=>'#2cb89b','DD'=>'#5d9ab8'];
$tipo_icon = ['mare'=>'🌊','golfo'=>'🏖️','stretto'=>'🌉','fossa'=>'🕳️','arcipelago'=>'🏝️','canale'=>'⚓','costa'=>'🏔️','laguna'=>'🦢'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luoghi & Habitat — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .luoghi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.25rem;
      margin-top: 2rem;
    }
    .luogo-card {
      background: rgba(11,61,94,.25);
      border: 1px solid rgba(114,215,240,.1);
      border-radius: 16px;
      padding: 1.5rem;
      cursor: pointer;
      text-decoration: none;
      display: block;
      transition: transform .2s, border-color .2s, box-shadow .2s;
    }
    .luogo-card:hover {
      transform: translateY(-3px);
      border-color: rgba(114,215,240,.3);
      box-shadow: 0 12px 40px rgba(0,0,0,.3);
    }
    .luogo-icon { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
    .luogo-tipo {
      font-size: .68rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .1em; color: var(--wave); margin-bottom: .4rem;
    }
    .luogo-nome { font-family: 'Cormorant Garamond',serif; font-size: 1.4rem;
      color: var(--pearl); font-weight: 400; margin-bottom: .3rem; }
    .luogo-meta { font-size: .8rem; color: var(--muted); margin-bottom: 1rem; }
    .luogo-stats { display: flex; gap: 1.5rem; }
    .luogo-stat { font-size: .78rem; color: rgba(197,228,245,.7); }
    .luogo-stat strong { color: var(--wave); font-size: 1rem; display: block; }

    /* Dettaglio */
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; }
    @media(max-width:700px){ .detail-grid { grid-template-columns: 1fr; } }

    .habitat-card {
      background: rgba(11,61,94,.2);
      border: 1px solid rgba(114,215,240,.1);
      border-radius: 12px; padding: 1.25rem;
      margin-bottom: 1rem;
    }
    .habitat-nome { font-size: 1rem; color: var(--pearl); font-weight: 500; margin-bottom: .4rem; }
    .habitat-range { font-size: .75rem; color: var(--wave); margin-bottom: .5rem; }
    .habitat-desc { font-size: .83rem; color: rgba(197,228,245,.75); line-height: 1.65; }
    .habitat-temp { font-size: .75rem; color: var(--muted); margin-top: .5rem; }

    .specie-row {
      display: flex; align-items: center; gap: 1rem;
      padding: .85rem 1rem;
      background: rgba(11,61,94,.2);
      border: 1px solid rgba(114,215,240,.07);
      border-radius: 10px; margin-bottom: .6rem;
      text-decoration: none;
      transition: border-color .2s;
    }
    .specie-row:hover { border-color: rgba(114,215,240,.25); }
    .specie-row img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
    .specie-row .emoji { width: 44px; height: 44px; border-radius: 8px; background: rgba(27,159,212,.1); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .specie-row .info { flex: 1; }
    .specie-row .s-nome { color: var(--pearl); font-size: .9rem; font-weight: 500; }
    .specie-row .s-sci  { color: var(--muted); font-size: .75rem; font-style: italic; }
    .specie-row .badge-sm { font-size: .65rem; font-weight: 700; padding: .15rem .5rem; border-radius: 10px; }

    .stat-box {
      background: rgba(27,159,212,.06);
      border: 1px solid rgba(114,215,240,.12);
      border-radius: 10px; padding: 1rem 1.25rem;
      display: flex; align-items: center; gap: 1rem;
    }
    .stat-box .num { font-family: 'Cormorant Garamond',serif; font-size: 2.2rem; color: var(--wave); line-height: 1; }
    .stat-box .lbl { font-size: .78rem; color: var(--muted); }

    .back-link { display: inline-flex; align-items: center; gap: .4rem; color: var(--wave);
      font-size: .85rem; text-decoration: none; margin-bottom: 1.5rem; }
    .back-link:hover { color: var(--foam); }

    .section-tag { font-size: .7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .12em; color: var(--wave); margin-bottom: 1rem; }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<nav>
  <a href="index.php" class="nav-logo">
    <img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="javascript:history.back()" class="nav-back">← Torna indietro</a>
  <a href="rilevazioni.php" style="color:var(--wave);font-size:.85rem;margin-left:auto;">📈 Rilevazioni</a>
</nav>

<div class="main" style="max-width:1100px;margin:0 auto;padding:5.5rem 1.5rem 4rem;">

<?php if ($luogo): ?>
  <!-- ── DETTAGLIO SINGOLO LUOGO ─────────────────────────────────────── -->
  <a href="luoghi.php" class="back-link">← Tutti i luoghi</a>

  <div style="margin-bottom:2rem;">
    <p class="luogo-tipo"><?= $tipo_icon[$luogo['tipo']??''] ?? '🌊' ?> <?= htmlspecialchars(ucfirst($luogo['tipo'] ?? '')) ?></p>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:2.8rem;color:var(--pearl);font-weight:400;margin-bottom:.3rem;">
      <?= htmlspecialchars($luogo['nome']) ?>
    </h1>
    <p style="color:var(--muted);font-size:.9rem;">
      <?= htmlspecialchars($luogo['paese_nome'] ?? '') ?>
      <?= $luogo['continente'] ? '· ' . htmlspecialchars($luogo['continente']) : '' ?>
      · <?= htmlspecialchars($luogo['oceano'] ?? '') ?>
    </p>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2.5rem;">
    <?php if ($luogo['area']): ?>
    <div class="stat-box">
      <span class="num"><?= number_format($luogo['area'],0,',','.') ?></span>
      <span class="lbl">km² di superficie</span>
    </div>
    <?php endif; ?>
    <?php if ($luogo['profondita']): ?>
    <div class="stat-box">
      <span class="num"><?= number_format($luogo['profondita'],0,',','.') ?></span>
      <span class="lbl">m profondità massima</span>
    </div>
    <?php endif; ?>
    <div class="stat-box">
      <span class="num"><?= count($habitat_luogo) ?></span>
      <span class="lbl">habitat presenti</span>
    </div>
    <div class="stat-box">
      <span class="num"><?= count($specie_osservate) ?></span>
      <span class="lbl">specie osservate</span>
    </div>
  </div>

  <div class="detail-grid">
    <!-- Link rilevazioni -->
    <a href="rilevazioni.php?luogo=<?= $luogo['id_luogo'] ?>"
       style="display:inline-flex;align-items:center;gap:.5rem;margin-bottom:2rem;padding:.6rem 1.2rem;background:rgba(27,159,212,.1);border:1px solid rgba(27,159,212,.25);border-radius:8px;color:var(--wave);text-decoration:none;font-size:.85rem;transition:background .2s;"
       onmouseover="this.style.background='rgba(27,159,212,.2)'" onmouseout="this.style.background='rgba(27,159,212,.1)'">
      📈 Vedi rilevazioni ambientali →
    </a>
  </div>
  <div class="detail-grid">

  <!-- Habitat -->
    <div>
      <p class="section-tag">🏔️ Habitat presenti</p>
      <?php if (empty($habitat_luogo)): ?>
        <p style="color:var(--muted);font-size:.875rem;">Nessun habitat registrato per questo luogo.</p>
      <?php else: ?>
        <?php foreach ($habitat_luogo as $h): ?>
        <div class="habitat-card">
          <p class="habitat-nome"><?= htmlspecialchars($h['nome']) ?></p>
          <p class="habitat-range">📏 <?= htmlspecialchars($h['range_habitat'] ?? '') ?></p>
          <p class="habitat-desc"><?= htmlspecialchars(mb_substr($h['descrizione']??'',0,160)) ?>…</p>
          <?php if ($h['temperatura']): ?>
          <p class="habitat-temp">🌡️ Temperatura media: <?= $h['temperatura'] ?>°C · <?= $h['n_specie'] ?> specie collegate</p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Specie osservate -->
    <div>
      <p class="section-tag">🔭 Specie osservate in questo luogo</p>
      <?php if (empty($specie_osservate)): ?>
        <p style="color:var(--muted);font-size:.875rem;">Nessuna osservazione registrata.</p>
      <?php else: ?>
        <?php foreach ($specie_osservate as $sp):
          $stato = strtoupper($sp['stato_conservazione'] ?? '');
          $col = $badge_col[$stato] ?? '#5d9ab8';
        ?>
        <a href="specie.php?id=<?= $sp['id_specie'] ?>" class="specie-row">
          <?php if ($sp['immagine']): ?>
            <img src="<?= htmlspecialchars($sp['immagine']) ?>" alt="">
          <?php else: ?>
            <div class="emoji">🐟</div>
          <?php endif; ?>
          <div class="info">
            <p class="s-nome"><?= htmlspecialchars($sp['nome']) ?></p>
            <p class="s-sci"><?= htmlspecialchars($sp['nome_scientifico'] ?? '') ?></p>
            <p style="font-size:.72rem;color:var(--muted);margin-top:.2rem;">
              Ultima osservazione: <?= $sp['ultima_osservazione'] ? date('d M Y', strtotime($sp['ultima_osservazione'])) : 'N/D' ?>
            </p>
          </div>
          <span class="badge-sm" style="color:<?= $col ?>;border:1px solid <?= $col ?>44;background:<?= $col ?>18;border-radius:20px;padding:.2rem .6rem;">
            <?= $stato ?: 'N/D' ?>
          </span>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

<?php else: ?>
  <!-- ── LISTA TUTTI I LUOGHI ────────────────────────────────────────── -->
  <div style="margin-bottom:2.5rem;">
    <p class="section-eyebrow">🌍 Esplora</p>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:2.5rem;color:var(--pearl);font-weight:400;margin-bottom:.5rem;">
      Luoghi & Habitat Marini
    </h1>
    <p style="color:var(--muted);max-width:600px;line-height:1.7;">
      Esplora i mari, golfi e fondali del Mediterraneo monitorati dai ricercatori NetSea.
      Scopri quali specie vivono in ogni area e quali habitat le ospitano.
    </p>
  </div>

  <?php if (empty($luoghi)): ?>
    <p style="color:var(--muted);">Nessun luogo nel database. Esegui prima <code>habitat_luoghi_minacce.sql</code>.</p>
  <?php else: ?>
  <div class="luoghi-grid">
    <?php foreach ($luoghi as $l):
      $ico = $tipo_icon[$l['tipo']??''] ?? '🌊';
    ?>
    <a href="luoghi.php?id=<?= $l['id_luogo'] ?>" class="luogo-card">
      <span class="luogo-icon"><?= $ico ?></span>
      <p class="luogo-tipo"><?= htmlspecialchars(ucfirst($l['tipo'] ?? '')) ?></p>
      <p class="luogo-nome"><?= htmlspecialchars($l['nome']) ?></p>
      <p class="luogo-meta">
        <?= htmlspecialchars($l['paese_nome'] ?? '') ?>
        <?= $l['profondita'] ? '· fino a ' . number_format($l['profondita'],0,',','.') . ' m' : '' ?>
      </p>
      <div class="luogo-stats">
        <div class="luogo-stat">
          <strong><?= $l['n_habitat'] ?></strong>habitat
        </div>
        <div class="luogo-stat">
          <strong><?= $l['n_osservazioni'] ?></strong>osservazioni
        </div>
        <?php if ($l['area']): ?>
        <div class="luogo-stat">
          <strong><?= number_format($l['area'],0,',','.') ?></strong>km²
        </div>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php endif; ?>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';cur.style.opacity='1';ring.style.opacity='1';});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();
</script>
</body>
</html>