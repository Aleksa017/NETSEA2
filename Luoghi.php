<?php
require 'config.php';

// ── Filtri lista ───────────────────────────────────────────────────────────
$f_tipo   = trim($_GET['tipo']   ?? '');
$f_oceano = trim($_GET['oceano'] ?? '');
$f_q      = trim($_GET['q']     ?? '');

// ── Dettaglio singolo luogo ────────────────────────────────────────────────
$id    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$luogo = null; $habitat_luogo = []; $specie_habitat = [];

if ($id) {
    $st = $connessione->prepare("
        SELECT l.*, p.nome AS paese_nome, p.continente
        FROM luogo l LEFT JOIN paese p ON l.id_paese = p.id_paese
        WHERE l.id_luogo = ?");
    $st->execute([$id]); $luogo = $st->fetch();

    if ($luogo) {
        // Habitat di questo luogo con specie collegate
        $st = $connessione->prepare("
            SELECT h.*, COUNT(sh.id_specie) AS n_specie
            FROM habitat h
            LEFT JOIN specie_habitat sh ON sh.id_habitat = h.id_habitat
            WHERE h.id_luogo = ?
            GROUP BY h.id_habitat ORDER BY h.range_habitat");
        $st->execute([$id]); $habitat_luogo = $st->fetchAll();

        // Specie collegate tramite habitat (specie_habitat)
        $st = $connessione->prepare("
            SELECT DISTINCT s.id_specie, s.nome, s.nome_scientifico,
                   s.stato_conservazione, s.immagine, s.classe,
                   h.nome AS habitat_nome
            FROM habitat h
            JOIN specie_habitat sh ON sh.id_habitat = h.id_habitat
            JOIN specie s ON s.id_specie = sh.id_specie
            WHERE h.id_luogo = ?
            ORDER BY s.nome");
        $st->execute([$id]); $specie_habitat = $st->fetchAll();
    }
}

// ── Lista luoghi con filtri ────────────────────────────────────────────────
$where = []; $params = [];
if ($f_tipo)   { $where[] = 'l.tipo = ?';       $params[] = $f_tipo; }
if ($f_oceano) { $where[] = 'l.oceano = ?';     $params[] = $f_oceano; }
if ($f_q)      { $where[] = 'l.nome LIKE ?';    $params[] = '%'.$f_q.'%'; }

$sql = "SELECT l.*, p.nome AS paese_nome,
        COUNT(DISTINCT h.id_habitat) AS n_habitat,
        COUNT(DISTINCT sh.id_specie) AS n_specie
    FROM luogo l
    LEFT JOIN paese p ON l.id_paese = p.id_paese
    LEFT JOIN habitat h ON h.id_luogo = l.id_luogo
    LEFT JOIN specie_habitat sh ON sh.id_habitat = h.id_habitat"
    . ($where ? ' WHERE '.implode(' AND ',$where) : '')
    . " GROUP BY l.id_luogo ORDER BY l.nome";
$st = $connessione->prepare($sql); $st->execute($params);
$luoghi = $st->fetchAll();

// Valori per i filtri
$tipi   = $connessione->query("SELECT DISTINCT tipo FROM luogo WHERE tipo IS NOT NULL ORDER BY tipo")->fetchAll(PDO::FETCH_COLUMN);
$oceani = $connessione->query("SELECT DISTINCT oceano FROM luogo WHERE oceano IS NOT NULL ORDER BY oceano")->fetchAll(PDO::FETCH_COLUMN);

$badge_col = ['CR'=>'#e8836a','EN'=>'#e0a060','VU'=>'#f0c040','NT'=>'#c8a830','LC'=>'#2cb89b','DD'=>'#5d9ab8'];
// Icone SVG inline per tipo (nessuna emoji)
$tipo_svg = [
    'mare'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 12 Q6 8 10 12 Q14 16 18 12 Q22 8 26 12"/><path d="M2 17 Q6 13 10 17 Q14 21 18 17"/></svg>',
    'golfo'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 20 Q12 4 21 20"/></svg>',
    'stretto'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 8h18M3 16h18"/></svg>',
    'fossa'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3v18M5 10l7 10 7-10"/></svg>',
    'arcipelago'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="14" r="3"/><circle cx="16" cy="10" r="2"/><circle cx="19" cy="17" r="2"/></svg>',
    'canale'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 12h18M7 8v8M17 8v8"/></svg>',
    'costa'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 18 Q8 10 14 12 Q18 13 21 8"/></svg>',
    'laguna'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><ellipse cx="12" cy="14" rx="9" ry="5"/><path d="M7 12 Q12 6 17 12"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luoghi Marini — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .luoghi-wrap { max-width:1100px; margin:0 auto; padding:5.5rem 1.5rem 4rem; }

    /* Hero */
    .luoghi-hero { margin-bottom:2.5rem; }
    .luoghi-hero h1 { font-family:'Cormorant Garamond',serif; font-size:clamp(2rem,4vw,3rem); font-weight:400; color:var(--pearl); margin-bottom:.4rem; }
    .luoghi-hero p { color:var(--muted); max-width:560px; line-height:1.7; }

    /* Barra filtri */
    .filtri-bar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; margin-bottom:2rem; }
    .filtri-bar input { flex:1; min-width:180px; max-width:280px; padding:.55rem 1rem; background:rgba(11,61,94,.4); border:1px solid rgba(114,215,240,.18); border-radius:10px; color:var(--pearl); font-family:'Outfit',sans-serif; font-size:.875rem; outline:none; }
    .filtri-bar input::placeholder { color:var(--muted); }
    .filtri-bar input:focus { border-color:var(--wave); }
    .filtri-bar select { padding:.5rem .85rem .5rem .75rem; background:rgba(11,61,94,.35); border:1px solid rgba(114,215,240,.15); border-radius:8px; color:var(--pearl); font-family:'Outfit',sans-serif; font-size:.82rem; cursor:pointer; outline:none; appearance:none; padding-right:1.8rem; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2372d7f0' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .55rem center; }
    .filtri-bar select:focus { border-color:var(--wave); }
    .btn-reset { padding:.5rem .9rem; background:transparent; border:1px solid rgba(114,215,240,.15); border-radius:8px; color:var(--muted); font-size:.78rem; cursor:pointer; font-family:'Outfit',sans-serif; text-decoration:none; transition:all .2s; }
    .btn-reset:hover { color:var(--foam); border-color:var(--wave); }
    .count-label { font-size:.75rem; color:var(--muted); margin-left:auto; white-space:nowrap; }

    /* Griglia card luoghi */
    .luoghi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.25rem; }
    .luogo-card { background:rgba(11,61,94,.22); border:1px solid rgba(114,215,240,.1); border-radius:16px; padding:1.4rem 1.5rem; text-decoration:none; display:block; transition:transform .2s,border-color .2s,box-shadow .2s; }
    .luogo-card:hover { transform:translateY(-4px); border-color:rgba(114,215,240,.28); box-shadow:0 12px 40px rgba(0,0,0,.3); }
    .luogo-icon { width:40px; height:40px; margin-bottom:.85rem; color:var(--wave); opacity:.7; }
    .luogo-icon svg { width:100%; height:100%; }
    .luogo-tipo { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--wave); margin-bottom:.3rem; }
    .luogo-nome { font-family:'Cormorant Garamond',serif; font-size:1.35rem; color:var(--pearl); font-weight:400; margin-bottom:.3rem; line-height:1.2; }
    .luogo-meta { font-size:.78rem; color:var(--muted); margin-bottom:.9rem; }
    .luogo-chips { display:flex; flex-wrap:wrap; gap:.4rem; }
    .chip { font-size:.68rem; padding:.2rem .6rem; border-radius:20px; background:rgba(27,159,212,.08); border:1px solid rgba(114,215,240,.12); color:rgba(197,228,245,.7); }

    /* === DETTAGLIO LUOGO === */
    .back-link { display:inline-flex; align-items:center; gap:.4rem; color:var(--wave); font-size:.85rem; text-decoration:none; margin-bottom:1.75rem; transition:color .2s; }
    .back-link:hover { color:var(--foam); }
    .detail-tipo { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--wave); margin-bottom:.3rem; }
    .detail-h1 { font-family:'Cormorant Garamond',serif; font-size:clamp(2rem,4vw,3rem); color:var(--pearl); font-weight:400; margin-bottom:.3rem; }
    .detail-sub { color:var(--muted); font-size:.88rem; margin-bottom:2rem; }

    .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.85rem; margin-bottom:2.5rem; }
    .stat-box { background:rgba(27,159,212,.06); border:1px solid rgba(114,215,240,.12); border-radius:10px; padding:.9rem 1.1rem; }
    .stat-num { font-family:'Cormorant Garamond',serif; font-size:2rem; color:var(--wave); line-height:1; margin-bottom:.2rem; }
    .stat-lbl { font-size:.72rem; color:var(--muted); }

    .detail-cols { display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-top:2rem; }
    @media(max-width:700px){ .detail-cols { grid-template-columns:1fr; } }

    .section-eyebrow { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--wave); margin-bottom:.9rem; }

    .habitat-card { background:rgba(11,61,94,.2); border:1px solid rgba(114,215,240,.1); border-radius:12px; padding:1.1rem 1.25rem; margin-bottom:.85rem; }
    .hab-nome { font-size:.95rem; color:var(--pearl); font-weight:500; margin-bottom:.25rem; }
    .hab-range { font-size:.72rem; color:var(--wave); margin-bottom:.5rem; font-weight:600; }
    .hab-desc { font-size:.8rem; color:rgba(197,228,245,.7); line-height:1.6; }
    .hab-footer { margin-top:.55rem; font-size:.72rem; color:var(--muted); display:flex; gap:1rem; }

    .specie-row { display:flex; align-items:center; gap:.85rem; padding:.75rem .9rem; background:rgba(11,61,94,.18); border:1px solid rgba(114,215,240,.07); border-radius:10px; margin-bottom:.5rem; text-decoration:none; transition:border-color .2s; }
    .specie-row:hover { border-color:rgba(114,215,240,.22); }
    .sp-thumb { width:42px; height:42px; border-radius:7px; object-fit:cover; flex-shrink:0; }
    .sp-thumb-ph { width:42px; height:42px; border-radius:7px; background:rgba(27,159,212,.1); flex-shrink:0; }
    .sp-nome { color:var(--pearl); font-size:.875rem; font-weight:500; margin-bottom:.1rem; }
    .sp-sci { color:var(--muted); font-size:.72rem; font-style:italic; }
    .sp-badge { font-size:.62rem; font-weight:700; padding:.15rem .5rem; border-radius:10px; white-space:nowrap; }

    .ril-btn { display:inline-flex; align-items:center; gap:.5rem; margin-top:1.5rem; padding:.65rem 1.3rem; background:rgba(27,159,212,.1); border:1px solid rgba(27,159,212,.25); border-radius:9px; color:var(--wave); text-decoration:none; font-size:.85rem; transition:background .2s; }
    .ril-btn:hover { background:rgba(27,159,212,.2); }

    .empty-state { color:var(--muted); text-align:center; padding:3rem; font-size:.875rem; background:rgba(11,61,94,.12); border-radius:12px; border:1px solid rgba(114,215,240,.07); }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="javascript:history.back()" class="nav-back">← Indietro</a>
</nav>

<div class="luoghi-wrap">

<?php if ($luogo): ?>
<!-- ══ DETTAGLIO LUOGO ══════════════════════════════════════════════════ -->

<a href="luoghi.php" class="back-link">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
  Tutti i luoghi
</a>

<div>
  <p class="detail-tipo"><?= htmlspecialchars(ucfirst($luogo['tipo'] ?? '')) ?></p>
  <h1 class="detail-h1"><?= htmlspecialchars($luogo['nome']) ?></h1>
  <p class="detail-sub">
    <?= htmlspecialchars($luogo['oceano'] ?? '') ?>
    <?php if($luogo['paese_nome']): ?> · <?= htmlspecialchars($luogo['paese_nome']) ?><?php endif; ?>
    <?php if($luogo['continente']): ?> · <?= htmlspecialchars($luogo['continente']) ?><?php endif; ?>
  </p>
</div>

<div class="stats-row">
  <?php if ($luogo['area']): ?>
  <div class="stat-box">
    <p class="stat-num"><?= number_format($luogo['area'],0,',','.') ?></p>
    <p class="stat-lbl">km² di superficie</p>
  </div>
  <?php endif; ?>
  <?php if ($luogo['profondita']): ?>
  <div class="stat-box">
    <p class="stat-num"><?= number_format($luogo['profondita'],0,',','.') ?></p>
    <p class="stat-lbl">m profondità massima</p>
  </div>
  <?php endif; ?>
  <div class="stat-box">
    <p class="stat-num"><?= count($habitat_luogo) ?></p>
    <p class="stat-lbl">habitat presenti</p>
  </div>
  <div class="stat-box">
    <p class="stat-num"><?= count($specie_habitat) ?></p>
    <p class="stat-lbl">specie registrate</p>
  </div>
</div>

<a href="rilevazioni.php?luogo=<?= $luogo['id_luogo'] ?>" class="ril-btn">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
  Vedi dati ambientali rilevati qui
</a>

<div class="detail-cols" style="margin-top:2rem;">

  <!-- Habitat -->
  <div>
    <p class="section-eyebrow">Habitat presenti</p>
    <?php if (empty($habitat_luogo)): ?>
      <p style="color:var(--muted);font-size:.875rem;">Nessun habitat registrato.</p>
    <?php else: ?>
      <?php foreach ($habitat_luogo as $h): ?>
      <div class="habitat-card">
        <p class="hab-nome"><?= htmlspecialchars($h['nome']) ?></p>
        <p class="hab-range"><?= htmlspecialchars($h['range_habitat'] ?? '') ?></p>
        <p class="hab-desc"><?= htmlspecialchars(mb_substr($h['descrizione']??'',0,180)) ?>…</p>
        <div class="hab-footer">
          <?php if ($h['temperatura']): ?>
            <span><?= $h['temperatura'] ?>°C media</span>
          <?php endif; ?>
          <span><?= $h['n_specie'] ?> specie collegate</span>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Specie per habitat -->
  <div>
    <p class="section-eyebrow">Specie che vivono qui</p>
    <?php if (empty($specie_habitat)): ?>
      <p style="color:var(--muted);font-size:.875rem;">Nessuna specie collegata agli habitat di questo luogo.</p>
    <?php else: ?>
      <?php foreach ($specie_habitat as $sp):
        $stato = strtoupper($sp['stato_conservazione'] ?? '');
        $col   = $badge_col[$stato] ?? '#5d9ab8';
      ?>
      <a href="specie.php?id=<?= $sp['id_specie'] ?>" class="specie-row">
        <?php if ($sp['immagine']): ?>
          <img src="<?= htmlspecialchars($sp['immagine']) ?>" alt="" class="sp-thumb">
        <?php else: ?>
          <div class="sp-thumb-ph"></div>
        <?php endif; ?>
        <div style="flex:1;min-width:0;">
          <p class="sp-nome"><?= htmlspecialchars($sp['nome']) ?></p>
          <p class="sp-sci"><?= htmlspecialchars($sp['nome_scientifico'] ?? '') ?></p>
          <p style="font-size:.68rem;color:rgba(114,215,240,.4);margin-top:.15rem;"><?= htmlspecialchars($sp['habitat_nome'] ?? '') ?></p>
        </div>
        <?php if ($stato): ?>
          <span class="sp-badge" style="color:<?= $col ?>;border:1px solid <?= $col ?>44;background:<?= $col ?>18;"><?= $stato ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<?php else: ?>
<!-- ══ LISTA LUOGHI ═════════════════════════════════════════════════════ -->

<div class="luoghi-hero">
  <h1>Luoghi Marini</h1>
  <p>Mari, golfi, stretti e fondali del Mediterraneo monitorati da NetSea.<br>Clicca su un luogo per vedere habitat e specie presenti.</p>
</div>

<!-- Filtri -->
<form method="GET" action="luoghi.php">
  <div class="filtri-bar">
    <input type="text" name="q" value="<?= htmlspecialchars($f_q) ?>" placeholder="Cerca per nome…">

    <select name="tipo" onchange="this.form.submit()">
      <option value="">Tutti i tipi</option>
      <?php foreach($tipi as $t): ?>
        <option value="<?= htmlspecialchars($t) ?>" <?= $f_tipo===$t?'selected':'' ?>><?= htmlspecialchars(ucfirst($t)) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="oceano" onchange="this.form.submit()">
      <option value="">Tutti i bacini</option>
      <?php foreach($oceani as $o): ?>
        <option value="<?= htmlspecialchars($o) ?>" <?= $f_oceano===$o?'selected':'' ?>><?= htmlspecialchars($o) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if($f_tipo||$f_oceano||$f_q): ?>
      <a href="luoghi.php" class="btn-reset">Azzera</a>
    <?php endif; ?>

    <span class="count-label"><strong><?= count($luoghi) ?></strong> luoghi</span>
  </div>
</form>

<?php if (empty($luoghi)): ?>
  <div class="empty-state">Nessun luogo trovato.</div>
<?php else: ?>
<div class="luoghi-grid">
  <?php foreach ($luoghi as $l): ?>
  <a href="luoghi.php?id=<?= $l['id_luogo'] ?>" class="luogo-card">
    <div class="luogo-icon"><?= $tipo_svg[$l['tipo']??''] ?? $tipo_svg['mare'] ?></div>
    <p class="luogo-tipo"><?= htmlspecialchars(ucfirst($l['tipo'] ?? '')) ?></p>
    <p class="luogo-nome"><?= htmlspecialchars($l['nome']) ?></p>
    <p class="luogo-meta">
      <?= htmlspecialchars($l['oceano'] ?? '') ?>
      <?= $l['profondita'] ? '· fino a ' . number_format($l['profondita'],0,',','.') . ' m' : '' ?>
    </p>
    <div class="luogo-chips">
      <span class="chip"><?= $l['n_habitat'] ?> habitat</span>
      <span class="chip"><?= $l['n_specie'] ?> specie</span>
      <?php if($l['area']): ?>
        <span class="chip"><?= number_format($l['area'],0,',','.') ?> km²</span>
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