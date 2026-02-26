<?php
require 'config.php';

// ── Parametri filtro ───────────────────────────────────────────────────────
$id_luogo   = filter_input(INPUT_GET, 'luogo',     FILTER_VALIDATE_INT) ?: null;
$parametro  = trim($_GET['param'] ?? '');

// ── Lista luoghi disponibili ───────────────────────────────────────────────
$luoghi = $connessione->query("
    SELECT l.id_luogo, l.nome, l.tipo,
           COUNT(DISTINCT r.id_rilevazione) AS n_ril
    FROM luogo l
    LEFT JOIN rilevazione_ambientale r ON r.id_luogo = l.id_luogo
    GROUP BY l.id_luogo HAVING n_ril > 0
    ORDER BY l.nome
")->fetchAll();

// ── Parametri disponibili (per il luogo selezionato o tutti) ──────────────
$params_query = $id_luogo
    ? "SELECT DISTINCT parametro FROM rilevazione_ambientale WHERE id_luogo = $id_luogo ORDER BY parametro"
    : "SELECT DISTINCT parametro FROM rilevazione_ambientale ORDER BY parametro";
$parametri_disponibili = $connessione->query($params_query)->fetchAll(PDO::FETCH_COLUMN);

// ── Rilevazioni filtrate ───────────────────────────────────────────────────
$where  = [];
$params = [];
if ($id_luogo)  { $where[] = 'r.id_luogo = ?';   $params[] = $id_luogo; }
if ($parametro) { $where[] = 'r.parametro = ?';  $params[] = $parametro; }
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $connessione->prepare("
    SELECT r.*, l.nome AS luogo_nome, l.tipo AS luogo_tipo,
           u.nome AS r_nome, u.cognome AS r_cognome
    FROM rilevazione_ambientale r
    JOIN luogo l ON r.id_luogo = l.id_luogo
    LEFT JOIN utente u ON r.id_ricercatore = u.id_utente
    $sql_where
    ORDER BY r.data DESC, r.parametro
    LIMIT 200
");
$st->execute($params);
$rilevazioni = $st->fetchAll();

// ── Dati per il grafico (serie temporali per parametro) ───────────────────
// Raggruppa per parametro → array di {data, valore}
$chart_data = [];
foreach ($rilevazioni as $r) {
    $p = $r['parametro'];
    if (!isset($chart_data[$p])) $chart_data[$p] = [];
    $chart_data[$p][] = ['x' => $r['data'], 'y' => (float)$r['valore'], 'luogo' => $r['luogo_nome']];
}
// Ordina ogni serie per data
foreach ($chart_data as &$serie) usort($serie, fn($a,$b) => strcmp($a['x'],$b['x']));
unset($serie);

// ── Ultime rilevazioni per luogo (riepilogo) ─────────────────────────────
$riepilogo = $connessione->query("
    SELECT r.id_luogo, l.nome AS luogo_nome, r.parametro,
           r.valore, r.data
    FROM rilevazione_ambientale r
    JOIN luogo l ON r.id_luogo = l.id_luogo
    INNER JOIN (
        SELECT id_luogo, parametro, MAX(data) AS max_data
        FROM rilevazione_ambientale
        GROUP BY id_luogo, parametro
    ) latest ON r.id_luogo = latest.id_luogo
           AND r.parametro = latest.parametro
           AND r.data = latest.max_data
    ORDER BY l.nome, r.parametro
")->fetchAll();

// Raggruppa riepilogo per luogo
$riep_by_luogo = [];
foreach ($riepilogo as $row) {
    $riep_by_luogo[$row['luogo_nome']][] = $row;
}

$tipo_icon = ['mare'=>'🌊','golfo'=>'🏖️','stretto'=>'🌉','fossa'=>'🕳️','arcipelago'=>'🏝️','canale'=>'⚓','costa'=>'🏔️','laguna'=>'🦢'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rilevazioni Ambientali — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <style>
    .filtri {
      display: flex; flex-wrap: wrap; gap: .75rem;
      background: rgba(11,61,94,.25); border: 1px solid rgba(114,215,240,.1);
      border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 2rem;
    }
    .filtri label { font-size: .78rem; color: var(--muted); display: block; margin-bottom: .3rem; }
    .filtri select {
      background: rgba(4,17,30,.6); border: 1px solid rgba(114,215,240,.15);
      color: var(--pearl); border-radius: 8px; padding: .5rem .9rem;
      font-family: 'Outfit', sans-serif; font-size: .875rem; cursor: pointer;
      min-width: 200px;
    }
    .filtri select:focus { outline: none; border-color: var(--wave); }
    .filtri .btn-reset {
      align-self: flex-end; padding: .5rem 1rem;
      border: 1px solid rgba(114,215,240,.2); background: transparent;
      color: var(--muted); border-radius: 8px; cursor: pointer; font-size: .8rem;
      font-family: 'Outfit', sans-serif; transition: all .2s;
    }
    .filtri .btn-reset:hover { color: var(--foam); border-color: var(--wave); }

    .chart-wrap {
      background: rgba(11,61,94,.2); border: 1px solid rgba(114,215,240,.1);
      border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;
    }
    .chart-wrap h3 { font-size: .85rem; color: var(--wave); margin-bottom: 1rem;
      text-transform: uppercase; letter-spacing: .08em; }

    .ril-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .ril-table th {
      text-align: left; color: var(--muted); font-size: .72rem;
      text-transform: uppercase; letter-spacing: .08em;
      padding: .6rem 1rem; border-bottom: 1px solid rgba(114,215,240,.1);
    }
    .ril-table td {
      padding: .75rem 1rem; border-bottom: 1px solid rgba(114,215,240,.05);
      color: rgba(197,228,245,.85);
    }
    .ril-table tr:hover td { background: rgba(114,215,240,.03); }
    .param-badge {
      display: inline-block; padding: .15rem .65rem; border-radius: 20px;
      font-size: .72rem; font-weight: 600;
      background: rgba(27,159,212,.12); border: 1px solid rgba(27,159,212,.25);
      color: var(--wave);
    }
    .valore-num { font-family: 'Cormorant Garamond', serif; font-size: 1.1rem; color: var(--pearl); }

    .riep-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
    .riep-card { background: rgba(11,61,94,.2); border: 1px solid rgba(114,215,240,.1); border-radius: 12px; padding: 1.25rem; }
    .riep-luogo { font-size: 1rem; color: var(--pearl); font-weight: 500; margin-bottom: .75rem; }
    .riep-row { display: flex; justify-content: space-between; align-items: center; padding: .35rem 0; border-bottom: 1px solid rgba(114,215,240,.04); }
    .riep-row:last-child { border-bottom: none; }
    .riep-param { font-size: .78rem; color: var(--muted); }
    .riep-val { font-size: .88rem; color: var(--pearl); font-weight: 500; }
    .riep-data { font-size: .7rem; color: rgba(114,215,240,.4); }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<nav>
  <a href="index.php" class="nav-logo">
    <img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="luoghi.php" class="nav-back">← Luoghi & Habitat</a>
</nav>

<div class="main" style="max-width:1100px;margin:0 auto;padding:5.5rem 1.5rem 4rem;">

  <div style="margin-bottom:2rem;">
    <p class="section-eyebrow">🔬 Monitoraggio</p>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:2.5rem;color:var(--pearl);font-weight:400;margin-bottom:.4rem;">
      Rilevazioni Ambientali
    </h1>
    <p style="color:var(--muted);max-width:600px;line-height:1.7;">
      Dati raccolti dai ricercatori NetSea: temperatura, salinità, pH, ossigeno disciolto
      e concentrazione di microplastiche nei mari del Mediterraneo.
    </p>
  </div>

  <!-- FILTRI -->
  <form method="GET" action="rilevazioni.php">
    <div class="filtri">
      <div>
        <label>Filtra per luogo</label>
        <select name="luogo" onchange="this.form.submit()">
          <option value="">— Tutti i luoghi —</option>
          <?php foreach ($luoghi as $l): ?>
          <option value="<?= $l['id_luogo'] ?>" <?= $id_luogo == $l['id_luogo'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($l['nome']) ?> (<?= $l['n_ril'] ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Filtra per parametro</label>
        <select name="param" onchange="this.form.submit()">
          <option value="">— Tutti i parametri —</option>
          <?php foreach ($parametri_disponibili as $p): ?>
          <option value="<?= htmlspecialchars($p) ?>" <?= $parametro === $p ? 'selected' : '' ?>>
            <?= htmlspecialchars($p) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($id_luogo || $parametro): ?>
      <a href="rilevazioni.php" class="btn-reset" style="text-decoration:none;display:flex;align-items:flex-end;">✕ Reset</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- GRAFICO (mostra se c'è un parametro selezionato o pochi dati) -->
  <?php if (!empty($chart_data)): ?>
  <div class="chart-wrap">
    <h3>📈 Andamento temporale<?= $parametro ? ' — ' . htmlspecialchars($parametro) : '' ?></h3>
    <canvas id="rilvChart" style="max-height:320px;"></canvas>
  </div>
  <?php endif; ?>

  <!-- RIEPILOGO ULTIME RILEVAZIONI PER LUOGO (solo se nessun filtro attivo) -->
  <?php if (!$id_luogo && !$parametro && !empty($riep_by_luogo)): ?>
  <p style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--wave);margin-bottom:1rem;">
    📋 Ultime rilevazioni per luogo
  </p>
  <div class="riep-grid">
    <?php foreach ($riep_by_luogo as $luogo_nome => $rows): ?>
    <div class="riep-card">
      <p class="riep-luogo">🌊 <?= htmlspecialchars($luogo_nome) ?></p>
      <?php foreach ($rows as $r): ?>
      <div class="riep-row">
        <span class="riep-param"><?= htmlspecialchars($r['parametro']) ?></span>
        <span>
          <span class="riep-val"><?= number_format($r['valore'], 1, ',', '.') ?></span>
          <span class="riep-data"> · <?= date('d/m', strtotime($r['data'])) ?></span>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- TABELLA RILEVAZIONI -->
  <p style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--wave);margin-bottom:1rem;">
    📊 <?= count($rilevazioni) ?> rilevazioni<?= $id_luogo || $parametro ? ' filtrate' : '' ?>
  </p>

  <?php if (empty($rilevazioni)): ?>
    <p style="color:var(--muted);padding:2rem 0;">Nessuna rilevazione trovata. Esegui prima <code>rilevazioni.sql</code> in phpMyAdmin.</p>
  <?php else: ?>
  <div style="background:rgba(11,61,94,.15);border:1px solid rgba(114,215,240,.08);border-radius:14px;overflow:hidden;">
    <table class="ril-table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Luogo</th>
          <th>Parametro</th>
          <th>Valore</th>
          <th>Ricercatore</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rilevazioni as $r): ?>
        <tr>
          <td><?= date('d M Y', strtotime($r['data'])) ?></td>
          <td>
            <a href="luoghi.php?id=<?= $r['id_luogo'] ?>" style="color:var(--wave);text-decoration:none;">
              <?= htmlspecialchars($r['luogo_nome']) ?>
            </a>
          </td>
          <td><span class="param-badge"><?= htmlspecialchars($r['parametro']) ?></span></td>
          <td><span class="valore-num"><?= number_format($r['valore'], 2, ',', '.') ?></span></td>
          <td style="color:var(--muted);font-size:.8rem;">
            <?= htmlspecialchars(trim(($r['r_nome']??'') . ' ' . ($r['r_cognome']??'')) ?: '—') ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
// Cursore
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';cur.style.opacity='1';ring.style.opacity='1';});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();

// Grafico
const chartData = <?= json_encode($chart_data) ?>;
const ctx = document.getElementById('rilvChart');
if (ctx && Object.keys(chartData).length) {
  const colors = ['#72d7f0','#2cb89b','#f0c040','#e8836a','#c5e4f5','#5d9ab8','#1b9fd4','#e0a060'];
  const datasets = Object.entries(chartData).map(([param, points], i) => ({
    label: param,
    data: points.map(p => ({ x: p.x, y: p.y })),
    borderColor: colors[i % colors.length],
    backgroundColor: colors[i % colors.length] + '22',
    borderWidth: 2,
    pointRadius: 4,
    pointHoverRadius: 6,
    tension: 0.3,
  }));
  new Chart(ctx, {
    type: 'line',
    data: { datasets },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { labels: { color: '#c5e4f5', font: { family: 'Outfit', size: 12 } } },
        tooltip: {
          backgroundColor: 'rgba(4,17,30,.92)',
          borderColor: 'rgba(114,215,240,.2)', borderWidth: 1,
          titleColor: '#72d7f0', bodyColor: '#c5e4f5',
        }
      },
      scales: {
        x: {
          type: 'category',
          ticks: { color: '#5d9ab8', font: { size: 11 } },
          grid: { color: 'rgba(114,215,240,.06)' }
        },
        y: {
          ticks: { color: '#5d9ab8', font: { size: 11 } },
          grid: { color: 'rgba(114,215,240,.08)' }
        }
      }
    }
  });
}
</script>
</body>
</html>