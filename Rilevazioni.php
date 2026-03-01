<?php
require 'config.php';

// ── Filtri ─────────────────────────────────────────────────────────────────
$id_luogo  = filter_input(INPUT_GET, 'luogo', FILTER_VALIDATE_INT) ?: null;
$parametro = trim($_GET['param'] ?? '');

// ── Luoghi che hanno dati ──────────────────────────────────────────────────
$luoghi = $connessione->query("
    SELECT l.id_luogo, l.nome,
           COUNT(DISTINCT r.id_rilevazione) AS n_ril
    FROM luogo l
    JOIN rilevazione_ambientale r ON r.id_luogo = l.id_luogo
    GROUP BY l.id_luogo ORDER BY l.nome
")->fetchAll();

// ── Parametri disponibili ──────────────────────────────────────────────────
$params_q = $id_luogo
    ? $connessione->prepare("SELECT DISTINCT parametro FROM rilevazione_ambientale WHERE id_luogo = ? ORDER BY parametro")
    : $connessione->prepare("SELECT DISTINCT parametro FROM rilevazione_ambientale ORDER BY parametro");
if ($id_luogo) $params_q->execute([$id_luogo]); else $params_q->execute();
$parametri_disponibili = $params_q->fetchAll(PDO::FETCH_COLUMN);

// ── Rilevazioni filtrate ───────────────────────────────────────────────────
$where = []; $params = [];
if ($id_luogo)  { $where[] = 'r.id_luogo = ?';  $params[] = $id_luogo; }
if ($parametro) { $where[] = 'r.parametro = ?'; $params[] = $parametro; }
$sql_where = $where ? 'WHERE '.implode(' AND ',$where) : '';

$st = $connessione->prepare("
    SELECT r.*, l.nome AS luogo_nome,
           u.nome AS r_nome, u.cognome AS r_cognome
    FROM rilevazione_ambientale r
    JOIN luogo l ON r.id_luogo = l.id_luogo
    LEFT JOIN utente u ON r.id_ricercatore = u.id_utente
    $sql_where
    ORDER BY r.data ASC, r.parametro
    LIMIT 300");
$st->execute($params);
$rilevazioni = $st->fetchAll();

// ── Dati grafico — una serie per ogni combinazione luogo+parametro ────────
// Quando non c'è filtro parametro mostriamo solo le serie di un parametro alla volta
// per evitare grafici illeggibili con unità diverse sull'asse Y.
// Se l'utente ha scelto un parametro specifico, mostriamo tutte le stazioni.
$chart_series = []; // chiave: "LuogoNome — Parametro"
foreach ($rilevazioni as $r) {
    if ($parametro) {
        // Confronto tra stazioni per lo stesso parametro
        $key = $r['luogo_nome'];
    } else {
        // Tutti i parametri di un luogo (o tutto: prima serie)
        $key = $r['parametro'];
    }
    if (!isset($chart_series[$key])) $chart_series[$key] = [];
    $chart_series[$key][] = ['x' => $r['data'], 'y' => (float)$r['valore']];
}
foreach ($chart_series as &$s) usort($s, fn($a,$b)=>strcmp($a['x'],$b['x']));
unset($s);

// ── Ultima rilevazione per ogni luogo+parametro (riepilogo) ──────────────
$ril_latest = $connessione->query("
    SELECT r1.id_luogo, l.nome AS luogo_nome, r1.parametro, r1.valore, r1.data
    FROM rilevazione_ambientale r1
    JOIN luogo l ON l.id_luogo = r1.id_luogo
    WHERE r1.data = (
        SELECT MAX(r2.data) FROM rilevazione_ambientale r2
        WHERE r2.id_luogo=r1.id_luogo AND r2.parametro=r1.parametro
    )
    ORDER BY l.nome, r1.parametro
")->fetchAll();

// Descrizioni brevi dei parametri per aiutare l'utente
$param_desc = [
    'Temperatura (°C)'          => 'Temperatura dell\'acqua in superficie o in profondità.',
    'Salinità (PSU)'            => 'Concentrazione di sali disciolti. L\'acqua di mare normale è 34–38 PSU.',
    'pH'                        => 'Acidità dell\'acqua. Valori normali 8.0–8.3; se scende indica acidificazione.',
    'Ossigeno disciolto (mg/L)' => 'Quantità di O₂ disponibile per gli organismi. Sotto 4 mg/L zona ipossica.',
    'Torbidità (NTU)'           => 'Limpidezza dell\'acqua. Valori alti indicano più particelle in sospensione.',
    'Microplastiche (part/m³)'  => 'Particelle di plastica per metro cubo d\'acqua. Indicatore di inquinamento.',
    'Nitrati (μmol/L)'          => 'Nutrienti inorganici. In eccesso causano proliferazione algale (eutrofizzazione).',
    'Clorofilla-a (μg/L)'       => 'Pigmento delle alghe, indicatore di biomassa fitoplanctonica e produttività.',
    'Velocità corrente (nodi)'  => 'Intensità della corrente marina in nodi.',
    'Pressione (bar)'           => 'Pressione dell\'acqua, aumenta con la profondità (~1 bar ogni 10 m).',
    'Profondità termoclina (m)' => 'Profondità a cui la temperatura scende bruscamente: confine fra strati.',
];

// Nome luogo selezionato
$luogo_nome_sel = '';
if ($id_luogo) {
    foreach ($luoghi as $l) { if ($l['id_luogo']==$id_luogo) { $luogo_nome_sel=$l['nome']; break; } }
}

// Unità del parametro selezionato (estratta tra parentesi)
$unita = '';
if ($parametro && preg_match('/\(([^)]+)\)/', $parametro, $m)) $unita = $m[1];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rilevazioni Ambientali — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
  <style>
    .ril-wrap { max-width:1100px; margin:0 auto; padding:5.5rem 1.5rem 4rem; }

    .ril-hero { margin-bottom:2.5rem; }
    .ril-hero h1 { font-family:'Cormorant Garamond',serif; font-size:clamp(2rem,4vw,3rem); font-weight:400; color:var(--pearl); margin-bottom:.4rem; }
    .ril-hero p { color:var(--muted); max-width:620px; line-height:1.7; font-size:.9rem; }

    /* Filtri */
    .filtri-bar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; margin-bottom:2rem; }
    .filtri-bar select { padding:.5rem 1.8rem .5rem .8rem; background:rgba(11,61,94,.35); border:1px solid rgba(114,215,240,.15); border-radius:8px; color:var(--pearl); font-family:'Outfit',sans-serif; font-size:.82rem; cursor:pointer; outline:none; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2372d7f0' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .55rem center; }
    .filtri-bar select:focus { border-color:var(--wave); }
    .btn-reset { padding:.5rem .9rem; background:transparent; border:1px solid rgba(114,215,240,.15); border-radius:8px; color:var(--muted); font-size:.78rem; cursor:pointer; font-family:'Outfit',sans-serif; text-decoration:none; }
    .btn-reset:hover { color:var(--foam); border-color:var(--wave); }
    .count-label { font-size:.75rem; color:var(--muted); margin-left:auto; }

    /* Grafico */
    .chart-box { background:rgba(11,61,94,.2); border:1px solid rgba(114,215,240,.1); border-radius:16px; padding:1.5rem; margin-bottom:2.5rem; }
    .chart-title { font-size:.88rem; color:var(--pearl); font-weight:500; margin-bottom:.25rem; }
    .chart-sub { font-size:.75rem; color:var(--muted); margin-bottom:1.25rem; }
    .chart-container { position:relative; height:280px; }
    .param-desc-box { margin-top:1rem; padding:.75rem 1rem; background:rgba(27,159,212,.05); border-left:3px solid rgba(27,159,212,.3); border-radius:0 8px 8px 0; font-size:.8rem; color:rgba(197,228,245,.7); line-height:1.6; }

    /* Riepilogo */
    .section-eyebrow { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--wave); margin-bottom:1rem; }
    .riepilogo-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:.85rem; margin-bottom:2.5rem; }
    .rbox { background:rgba(11,61,94,.18); border:1px solid rgba(114,215,240,.09); border-radius:10px; padding:.9rem 1.1rem; display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem; }
    .rbox-info { flex:1; min-width:0; }
    .rbox-param { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--wave); margin-bottom:.2rem; }
    .rbox-luogo { font-size:.82rem; color:var(--pearl); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .rbox-val { font-family:'Cormorant Garamond',serif; font-size:1.6rem; color:var(--pearl); line-height:1; white-space:nowrap; }
    .rbox-data { font-size:.68rem; color:var(--muted); margin-top:.25rem; }

    /* Tabella */
    .ril-table-wrap { overflow-x:auto; }
    .ril-table { width:100%; border-collapse:collapse; font-size:.82rem; }
    .ril-table th { text-align:left; padding:.6rem 1rem; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--wave); border-bottom:1px solid rgba(114,215,240,.12); white-space:nowrap; }
    .ril-table td { padding:.7rem 1rem; border-bottom:1px solid rgba(114,215,240,.05); color:var(--muted); vertical-align:middle; }
    .ril-table tr:hover td { background:rgba(27,159,212,.04); }
    .ril-table .val { font-family:'Cormorant Garamond',serif; font-size:1.1rem; color:var(--pearl); }
    .param-badge { display:inline-block; padding:.15rem .55rem; border-radius:20px; font-size:.68rem; font-weight:600; background:rgba(27,159,212,.1); border:1px solid rgba(27,159,212,.2); color:var(--wave); white-space:nowrap; }
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

<div class="ril-wrap">

  <div class="ril-hero">
    <h1>Rilevazioni Ambientali</h1>
    <p>I ricercatori NetSea effettuano misurazioni periodiche in diversi punti del Mediterraneo per monitorare la salute degli ecosistemi. Ogni rilevazione registra un parametro fisico o chimico dell'acqua in una data e in un luogo specifico. Usa i filtri per esplorare i dati per zona o per tipo di misura.</p>
  </div>

  <!-- Filtri -->
  <form method="GET" action="rilevazioni.php" id="filterForm">
    <div class="filtri-bar">
      <select name="luogo" onchange="this.form.submit()">
        <option value="">Tutte le stazioni</option>
        <?php foreach($luoghi as $l): ?>
          <option value="<?= $l['id_luogo'] ?>" <?= $id_luogo==$l['id_luogo']?'selected':'' ?>>
            <?= htmlspecialchars($l['nome']) ?> (<?= $l['n_ril'] ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <select name="param" onchange="this.form.submit()">
        <option value="">Tutti i parametri</option>
        <?php foreach($parametri_disponibili as $par): ?>
          <option value="<?= htmlspecialchars($par) ?>" <?= $parametro===$par?'selected':'' ?>>
            <?= htmlspecialchars($par) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <?php if($id_luogo||$parametro): ?>
        <a href="rilevazioni.php" class="btn-reset">Azzera filtri</a>
      <?php endif; ?>

      <span class="count-label"><strong><?= count($rilevazioni) ?></strong> rilevazioni</span>
    </div>
  </form>

  <!-- Grafico andamento temporale -->
  <?php if (!empty($chart_series)): ?>
  <div class="chart-box">
    <p class="chart-title">
      <?php if ($parametro): ?>
        Andamento di <strong><?= htmlspecialchars($parametro) ?></strong>
        <?= $luogo_nome_sel ? ' — ' . htmlspecialchars($luogo_nome_sel) : ' — confronto tra stazioni' ?>
      <?php elseif ($id_luogo): ?>
        Tutti i parametri rilevati a <strong><?= htmlspecialchars($luogo_nome_sel) ?></strong>
      <?php else: ?>
        Distribuzione temporale delle rilevazioni
      <?php endif; ?>
    </p>
    <p class="chart-sub">
      <?php if ($parametro): ?>
        Asse Y: <?= htmlspecialchars($unita) ?> — Asse X: data di rilevazione.
        <?= count($chart_series) > 1 ? 'Ogni linea è una stazione diversa.' : 'I punti collegati mostrano l\'andamento nel tempo.' ?>
      <?php elseif ($id_luogo): ?>
        Attenzione: i parametri hanno unità diverse (°C, PSU, pH…). Questo grafico mostra l'andamento relativo, non confronta i valori assoluti tra parametri.
      <?php else: ?>
        Seleziona un luogo o un parametro per un grafico più leggibile.
      <?php endif; ?>
    </p>
    <div class="chart-container">
      <canvas id="rilvChart"></canvas>
    </div>
    <?php if ($parametro && isset($param_desc[$parametro])): ?>
    <div class="param-desc-box">
      <strong><?= htmlspecialchars($parametro) ?>:</strong> <?= htmlspecialchars($param_desc[$parametro]) ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Riepilogo ultime rilevazioni per stazione -->
  <?php if (empty($id_luogo) && empty($parametro)): ?>
  <p class="section-eyebrow">Ultime rilevazioni per stazione</p>
  <div class="riepilogo-grid">
    <?php foreach(array_slice($ril_latest, 0, 18) as $rl): ?>
    <div class="rbox">
      <div class="rbox-info">
        <p class="rbox-param"><?= htmlspecialchars($rl['parametro']) ?></p>
        <p class="rbox-luogo"><?= htmlspecialchars($rl['luogo_nome']) ?></p>
        <p class="rbox-data"><?= date('d M Y', strtotime($rl['data'])) ?></p>
      </div>
      <div>
        <p class="rbox-val"><?= number_format($rl['valore'],2,',','.') ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Tabella dati -->
  <p class="section-eyebrow">Dati <?= $parametro ? htmlspecialchars($parametro) : ($id_luogo ? 'di '.htmlspecialchars($luogo_nome_sel) : 'recenti') ?></p>
  <?php if (empty($rilevazioni)): ?>
    <p style="color:var(--muted);padding:2rem;text-align:center;">Nessuna rilevazione trovata.</p>
  <?php else: ?>
  <div class="ril-table-wrap">
    <table class="ril-table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Luogo</th>
          <th>Parametro</th>
          <th>Valore</th>
          <th>Rilevato da</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach(array_slice(array_reverse($rilevazioni),0,100) as $r): ?>
        <tr>
          <td><?= date('d M Y', strtotime($r['data'])) ?></td>
          <td><a href="luoghi.php?id=<?= $r['id_luogo'] ?>" style="color:var(--wave);text-decoration:none;"><?= htmlspecialchars($r['luogo_nome']) ?></a></td>
          <td><span class="param-badge"><?= htmlspecialchars($r['parametro']) ?></span></td>
          <td class="val"><?= number_format($r['valore'],2,',','.') ?></td>
          <td><?= htmlspecialchars(trim(($r['r_nome']??'').' '.($r['r_cognome']??'')) ?: 'N/D') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';cur.style.opacity='1';ring.style.opacity='1';});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();

// ── Grafico ────────────────────────────────────────────────────────────────
const rawData = <?= json_encode($chart_series) ?>;
const ctx = document.getElementById('rilvChart');
if (ctx && Object.keys(rawData).length) {
  const colors = ['#72d7f0','#2cb89b','#f0c040','#e8836a','#c5e4f5','#5d9ab8','#1b9fd4','#e0a060','#9b8fd4'];

  const datasets = Object.entries(rawData).map(([label, points], i) => ({
    label,
    data: points.map(p => ({ x: p.x, y: p.y })),
    borderColor: colors[i % colors.length],
    backgroundColor: colors[i % colors.length] + '20',
    borderWidth: 2.5,
    pointRadius: 5,
    pointHoverRadius: 8,
    pointBackgroundColor: colors[i % colors.length],
    tension: 0.35,
    fill: false,
  }));

  new Chart(ctx, {
    type: 'line',
    data: { datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          labels: { color: '#c5e4f5', font: { family: 'Outfit', size: 12 }, padding: 16, usePointStyle: true }
        },
        tooltip: {
          backgroundColor: 'rgba(4,17,30,.95)',
          borderColor: 'rgba(114,215,240,.2)', borderWidth: 1,
          titleColor: '#72d7f0', bodyColor: '#c5e4f5', padding: 10,
          callbacks: {
            label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}`
          }
        }
      },
      scales: {
        x: {
          type: 'time',
          time: { unit: 'day', displayFormats: { day: 'dd MMM' } },
          ticks: { color: '#5d9ab8', font: { size: 11 }, maxTicksLimit: 10 },
          grid: { color: 'rgba(114,215,240,.06)' }
        },
        y: {
          ticks: { color: '#5d9ab8', font: { size: 11 } },
          grid: { color: 'rgba(114,215,240,.08)' },
          beginAtZero: false
        }
      }
    }
  });
}
</script>
</body>
</html>