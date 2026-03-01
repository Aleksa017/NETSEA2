<?php
require 'config.php';

// Filtri
$filter_famiglia = trim($_GET['famiglia'] ?? '');
$filter_classe   = trim($_GET['classe'] ?? '');
$filter_stato    = trim($_GET['stato'] ?? '');
$filter_habitat  = (int)($_GET['habitat'] ?? 0);
$filter_q        = trim($_GET['q'] ?? '');

// Costruisci query
$where = []; $params = [];
if ($filter_famiglia) { $where[] = 's.famiglia = ?'; $params[] = $filter_famiglia; }
if ($filter_classe)   { $where[] = 's.classe = ?';   $params[] = $filter_classe; }
if ($filter_stato)    { $where[] = 's.stato_conservazione = ?'; $params[] = $filter_stato; }
if ($filter_q)        { $where[] = '(s.nome LIKE ? OR s.nome_scientifico LIKE ?)'; $like = '%'.$filter_q.'%'; $params[] = $like; $params[] = $like; }
if ($filter_habitat)  { $where[] = 'EXISTS (SELECT 1 FROM specie_habitat sh WHERE sh.id_specie=s.id_specie AND sh.id_habitat=?)'; $params[] = $filter_habitat; }

$sql = "SELECT s.* FROM specie s" . ($where ? ' WHERE '.implode(' AND ',$where) : '') . " ORDER BY s.nome";
$st = $connessione->prepare($sql);
$st->execute($params);
$specie = $st->fetchAll();

// Valori per i filtri
$famiglie  = $connessione->query("SELECT DISTINCT famiglia FROM specie WHERE famiglia IS NOT NULL ORDER BY famiglia")->fetchAll(PDO::FETCH_COLUMN);
$classi    = $connessione->query("SELECT DISTINCT classe   FROM specie WHERE classe IS NOT NULL ORDER BY classe")->fetchAll(PDO::FETCH_COLUMN);
$stati     = $connessione->query("SELECT DISTINCT stato_conservazione FROM specie WHERE stato_conservazione IS NOT NULL ORDER BY stato_conservazione")->fetchAll(PDO::FETCH_COLUMN);
$habitats  = $connessione->query("SELECT id_habitat, nome FROM habitat ORDER BY nome")->fetchAll();

$badge_map = [
    'CR'=>['Critico','#e8836a'],
    'EN'=>['In pericolo','#e0a060'],
    'VU'=>['Vulnerabile','#f0c040'],
    'NT'=>['Quasi min.','#c8a830'],
    'LC'=>['Minima preoc.','#2cb89b'],
    'DD'=>['Dati insuff.','#5d9ab8'],
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Specie Marine — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .specie-hero {
      padding: 5.5rem 2.5rem 2.5rem;
      text-align: center;
      background: linear-gradient(180deg, rgba(4,17,30,.95) 0%, rgba(7,30,51,.7) 100%);
      border-bottom: 1px solid rgba(114,215,240,.08);
    }
    .specie-hero h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.2rem, 4vw, 3.2rem);
      font-weight: 400; color: var(--pearl);
      margin-bottom: .4rem;
    }
    .specie-hero p { color: var(--muted); font-size: .95rem; margin-bottom: 1.75rem; }

    /* Barra ricerca */
    .s-search {
      display: flex; gap: .5rem; max-width: 420px; margin: 0 auto 1.25rem;
    }
    .s-search input {
      flex: 1; padding: .7rem 1rem;
      background: rgba(11,61,94,.4); border: 1px solid rgba(114,215,240,.18);
      border-radius: 10px; color: var(--pearl); font-family: 'Outfit', sans-serif;
      font-size: .9rem; outline: none;
    }
    .s-search input:focus { border-color: var(--wave); }
    .s-search input::placeholder { color: var(--muted); }
    .s-search button {
      padding: .7rem 1.3rem; background: var(--wave); color: var(--ink);
      border: none; border-radius: 10px; font-family: 'Outfit', sans-serif;
      font-weight: 600; cursor: pointer; font-size: .875rem;
    }

    /* Filtri chip */
    .filtri-row {
      display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; justify-content: center;
    }
    .filtri-row label { font-size: .72rem; color: var(--muted); margin-right: -.2rem; }
    .filtri-row select {
      padding: .4rem .7rem; background: rgba(11,61,94,.35);
      border: 1px solid rgba(114,215,240,.15); border-radius: 8px;
      color: var(--pearl); font-family: 'Outfit', sans-serif; font-size: .78rem;
      cursor: pointer; outline: none; appearance: none; max-width: 160px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2372d7f0' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right .5rem center;
      padding-right: 1.6rem;
    }
    .filtri-row select:focus { border-color: var(--wave); }
    .btn-reset-f {
      padding: .45rem .9rem; background: transparent;
      border: 1px solid rgba(114,215,240,.15); border-radius: 8px;
      color: var(--muted); font-size: .78rem; cursor: pointer;
      font-family: 'Outfit', sans-serif; transition: all .2s; text-decoration: none;
      display: inline-flex; align-items: center; gap: .3rem;
    }
    .btn-reset-f:hover { color: var(--foam); border-color: var(--wave); }

    /* Counter */
    .sp-count {
      font-size: .78rem; color: var(--muted); padding: 1.5rem 2.5rem .75rem;
    }
    .sp-count strong { color: var(--wave); }

    /* Griglia specie */
    .specie-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.1rem;
      padding: 0 2.5rem 4rem;
    }
    @media(max-width:600px){ .specie-grid { grid-template-columns: repeat(2,1fr); padding: 0 1rem 3rem; } }

    .sp-card {
      position: relative; border-radius: 14px; overflow: hidden;
      aspect-ratio: 3/4;
      background: rgba(11,61,94,.3);
      border: 1px solid rgba(114,215,240,.1);
      cursor: pointer; text-decoration: none; display: block;
      transition: transform .25s, border-color .25s, box-shadow .25s;
    }
    .sp-card:hover {
      transform: translateY(-5px) scale(1.02);
      border-color: rgba(114,215,240,.3);
      box-shadow: 0 16px 48px rgba(0,0,0,.45);
    }
    .sp-card img {
      width: 100%; height: 100%; object-fit: cover;
      transition: transform .4s;
    }
    .sp-card:hover img { transform: scale(1.06); }
    .sp-card-placeholder {
      width: 100%; height: 100%;
      background: linear-gradient(135deg, var(--ocean), var(--deep));
      display: flex; align-items: center; justify-content: center;
      font-size: 4rem; opacity: .35;
    }
    .sp-card-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(0deg, rgba(4,17,30,.92) 0%, rgba(4,17,30,.3) 50%, transparent 100%);
    }
    .sp-card-body {
      position: absolute; bottom: 0; left: 0; right: 0;
      padding: .9rem 1rem;
    }
    .sp-badge {
      display: inline-block; font-size: .58rem; font-weight: 700;
      padding: .15rem .55rem; border-radius: 20px; margin-bottom: .35rem;
      text-transform: uppercase; letter-spacing: .06em;
    }
    .sp-nome {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.05rem; font-weight: 500;
      color: var(--pearl); line-height: 1.2; margin-bottom: .15rem;
    }
    .sp-sci {
      font-size: .68rem; color: rgba(197,228,245,.5);
      font-style: italic; white-space: nowrap;
      overflow: hidden; text-overflow: ellipsis;
    }

    /* Empty */
    .sp-empty {
      grid-column: 1/-1; text-align: center;
      padding: 4rem 2rem; color: var(--muted);
    }
    .sp-empty div { font-size: 3.5rem; margin-bottom: 1rem; }
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

<div class="specie-hero">
  <h1>Specie Marine</h1>
  <p>Esplora le specie monitorate dai ricercatori NetSea nel Mediterraneo.</p>

  <!-- Ricerca -->
  <form method="GET" action="specie_list.php" id="filtroForm">
    <div class="s-search">
      <input type="text" name="q" value="<?= htmlspecialchars($filter_q) ?>"
             placeholder="Cerca per nome o nome scientifico…" autocomplete="off">
      <button type="submit">Cerca</button>
    </div>
    <!-- Filtri -->
    <div class="filtri-row">
      <label>Famiglia</label>
      <select name="famiglia" onchange="this.form.submit()">
        <option value="">Tutte</option>
        <?php foreach($famiglie as $f): ?>
        <option value="<?= htmlspecialchars($f) ?>" <?= $filter_famiglia===$f?'selected':'' ?>>
          <?= htmlspecialchars($f) ?>
        </option>
        <?php endforeach; ?>
      </select>

      <label>Classe</label>
      <select name="classe" onchange="this.form.submit()">
        <option value="">Tutte</option>
        <?php foreach($classi as $cl): ?>
        <option value="<?= htmlspecialchars($cl) ?>" <?= $filter_classe===$cl?'selected':'' ?>>
          <?= htmlspecialchars($cl) ?>
        </option>
        <?php endforeach; ?>
      </select>

      <label>Stato</label>
      <select name="stato" onchange="this.form.submit()">
        <option value="">Tutti</option>
        <?php foreach($stati as $st): ?>
        <option value="<?= htmlspecialchars($st) ?>" <?= $filter_stato===$st?'selected':'' ?>>
          <?= htmlspecialchars($st) ?> — <?= $badge_map[$st][0] ?? $st ?>
        </option>
        <?php endforeach; ?>
      </select>

      <?php if (!empty($habitats)): ?>
      <label>Habitat</label>
      <select name="habitat" onchange="this.form.submit()">
        <option value="0">Tutti</option>
        <?php foreach($habitats as $h): ?>
        <option value="<?= $h['id_habitat'] ?>" <?= $filter_habitat==$h['id_habitat']?'selected':'' ?>>
          <?= htmlspecialchars($h['nome']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

      <?php if($filter_famiglia||$filter_classe||$filter_stato||$filter_habitat||$filter_q): ?>
      <a href="specie_list.php" class="btn-reset-f">✕ Reset filtri</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<p class="sp-count">
  <strong><?= count($specie) ?></strong> specie trovate
  <?php if($filter_q||$filter_famiglia||$filter_classe||$filter_stato||$filter_habitat): ?>
    — filtri attivi
  <?php endif; ?>
</p>

<div class="specie-grid">
<?php if(empty($specie)): ?>
  <div class="sp-empty">
    <div>🔭</div>
    <p>Nessuna specie trovata con i filtri selezionati.</p>
    <a href="specie_list.php" style="color:var(--wave);">Rimuovi filtri</a>
  </div>
<?php else: ?>
  <?php foreach($specie as $s):
    $stato = strtoupper($s['stato_conservazione'] ?? '');
    $badge = $badge_map[$stato] ?? null;
    $hasImg = !empty($s['immagine']);
  ?>
  <a href="specie.php?id=<?= $s['id_specie'] ?>" class="sp-card">
    <?php if($hasImg): ?>
      <img src="<?= htmlspecialchars($s['immagine']) ?>" alt="<?= htmlspecialchars($s['nome']) ?>" loading="lazy">
    <?php else: ?>
      <div class="sp-card-placeholder">🐟</div>
    <?php endif; ?>
    <div class="sp-card-overlay"></div>
    <div class="sp-card-body">
      <?php if($badge): ?>
      <div class="sp-badge" style="color:<?= $badge[1] ?>;border:1px solid <?= $badge[1] ?>44;background:<?= $badge[1] ?>1a;">
        <?= $stato ?>
      </div>
      <?php endif; ?>
      <p class="sp-nome"><?= htmlspecialchars($s['nome']) ?></p>
      <p class="sp-sci"><?= htmlspecialchars($s['nome_scientifico'] ?? '') ?></p>
    </div>
  </a>
  <?php endforeach; ?>
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