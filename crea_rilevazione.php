<?php
require 'config.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])) {
    header('Location: Login.php?redirect=crea_rilevazione.php'); exit();
}

$stR = $connessione->prepare("SELECT id_ricercatore FROM ricercatore WHERE id_ricercatore=?");
$stR->execute([$_SESSION['id']]);
$ric = $stR->fetch();
if (!$ric && ($_SESSION['ruolo'] ?? '') !== 'admin') {
    die("Profilo ricercatore non trovato.");
}
$id_ric = $ric['id_ricercatore'] ?? $_SESSION['id'];

$luoghi = $connessione->query("SELECT id_luogo, nome FROM luogo ORDER BY nome")->fetchAll();

// Parametri standard suggeriti
$parametri_suggeriti = [
    'Temperatura (°C)',
    'Salinità (PSU)',
    'pH',
    'Ossigeno disciolto (mg/L)',
    'Torbidità (NTU)',
    'Clorofilla-a (μg/L)',
    'Nitrati (μmol/L)',
    'Microplastiche (part/m³)',
    'Velocità corrente (nodi)',
    'Pressione (bar)',
    'Profondità termoclina (m)',
];

$errors = []; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parametro = trim($_POST['parametro_custom'] ?? '') ?: trim($_POST['parametro'] ?? '');
    $valore    = str_replace(',', '.', trim($_POST['valore'] ?? ''));
    $data      = trim($_POST['data'] ?? '');
    $id_luogo  = (int)($_POST['id_luogo'] ?? 0);

    if (!$parametro)           $errors[] = 'Seleziona o scrivi un parametro.';
    if (!is_numeric($valore))  $errors[] = 'Il valore deve essere un numero.';
    if (!$data)                $errors[] = 'La data è obbligatoria.';
    if (!$id_luogo)            $errors[] = 'Seleziona un luogo.';

    if (!$errors) {
        $connessione->prepare(
            "INSERT INTO rilevazione_ambientale (parametro, valore, data, id_ricercatore, id_luogo)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$parametro, (float)$valore, $data, $id_ric, $id_luogo]);
        $ok = true;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuova Rilevazione — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .wrap { max-width:680px; margin:0 auto; padding:5.5rem 1.5rem 4rem; }
    .page-title { font-family:'Cormorant Garamond',serif; font-size:2.2rem; font-weight:400; color:var(--pearl); margin-bottom:.3rem; }
    .page-sub { color:var(--muted); font-size:.875rem; margin-bottom:2.5rem; }
    .card { background:rgba(11,61,94,.2); border:1px solid rgba(114,215,240,.1); border-radius:14px; padding:1.5rem; margin-bottom:1rem; }
    .section-label { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--wave); margin-bottom:.9rem; }
    .form-group { display:flex; flex-direction:column; gap:.35rem; margin-bottom:1rem; }
    .form-group label { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); }
    .form-group input, .form-group select {
      padding:.6rem .9rem; background:rgba(11,61,94,.4); border:1px solid rgba(114,215,240,.15);
      border-radius:9px; color:var(--pearl); font-family:'Outfit',sans-serif; font-size:.875rem; outline:none; transition:border-color .2s;
    }
    .form-group input:focus, .form-group select:focus { border-color:var(--wave); }
    .hint { font-size:.72rem; color:rgba(114,215,240,.35); }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    @media(max-width:560px){ .form-row { grid-template-columns:1fr; } }
    .flash-ok  { padding:.75rem 1rem; border-radius:10px; background:rgba(44,184,155,.1); border:1px solid rgba(44,184,155,.25); color:#2cb89b; font-size:.85rem; margin-bottom:1.25rem; }
    .flash-err { padding:.75rem 1rem; border-radius:10px; background:rgba(232,131,106,.1); border:1px solid rgba(232,131,106,.3); color:#e8836a; font-size:.85rem; margin-bottom:1.25rem; }
    .btn-submit { display:inline-flex; align-items:center; gap:.5rem; padding:.7rem 1.6rem; background:rgba(27,159,212,.15); border:1px solid rgba(27,159,212,.3); border-radius:10px; color:var(--wave); font-family:'Outfit',sans-serif; font-size:.9rem; font-weight:600; cursor:pointer; transition:background .2s; }
    .btn-submit:hover { background:rgba(27,159,212,.28); }
    .param-pills { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:.75rem; }
    .param-pill { padding:.3rem .75rem; border-radius:20px; border:1px solid rgba(114,215,240,.18); background:transparent; color:var(--muted); font-size:.75rem; cursor:pointer; transition:all .15s; font-family:'Outfit',sans-serif; }
    .param-pill:hover { border-color:rgba(114,215,240,.4); color:var(--pearl); }
    .param-pill.active { background:rgba(27,159,212,.15); border-color:var(--wave); color:var(--wave); }
    #customParamWrap { display:none; margin-top:.5rem; }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <img src="uploads/logos/logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="rilevazioni.php" class="nav-back">← Tutte le rilevazioni</a>
</nav>

<div class="wrap">
  <h1 class="page-title">Nuova rilevazione</h1>
  <p class="page-sub">Inserisci una misurazione ambientale effettuata sul campo.</p>

  <?php if ($ok): ?>
    <div class="flash-ok">Rilevazione salvata. <a href="crea_rilevazione.php" style="color:#2cb89b;font-weight:600;">Inserisci un'altra →</a></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="flash-err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="card">
      <p class="section-label">Parametro misurato</p>
      <p style="font-size:.78rem;color:var(--muted);margin-bottom:.6rem;">Scegli uno dei parametri standard o scrivi il tuo:</p>
      <div class="param-pills">
        <?php foreach ($parametri_suggeriti as $par): ?>
          <button type="button" class="param-pill <?= (($_POST['parametro'] ?? '') === $par) ? 'active' : '' ?>"
                  onclick="selParam(this, '<?= htmlspecialchars($par, ENT_QUOTES) ?>')">
            <?= htmlspecialchars($par) ?>
          </button>
        <?php endforeach; ?>
        <button type="button" class="param-pill" onclick="selCustom()">+ Altro</button>
      </div>
      <input type="hidden" name="parametro" id="parametroInput" value="<?= htmlspecialchars($_POST['parametro'] ?? '') ?>">
      <div id="customParamWrap">
        <input type="text" name="parametro_custom" id="parametroCustom"
               placeholder="Es. Fosfati (μmol/L)"
               value="<?= htmlspecialchars($_POST['parametro_custom'] ?? '') ?>">
      </div>
    </div>

    <div class="card">
      <p class="section-label">Dati misurazione</p>
      <div class="form-row">
        <div class="form-group">
          <label>Valore *</label>
          <input type="number" name="valore" step="any"
                 value="<?= htmlspecialchars($_POST['valore'] ?? '') ?>"
                 placeholder="es. 18.5" required>
          <span class="hint">Usa il punto o la virgola come decimale</span>
        </div>
        <div class="form-group">
          <label>Data *</label>
          <input type="date" name="data"
                 value="<?= htmlspecialchars($_POST['data'] ?? date('Y-m-d')) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Luogo *</label>
        <select name="id_luogo">
          <option value="">— Seleziona luogo —</option>
          <?php foreach ($luoghi as $l): ?>
            <option value="<?= $l['id_luogo'] ?>" <?= (($_POST['id_luogo'] ?? '') == $l['id_luogo']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($l['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <button type="submit" class="btn-submit">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Salva rilevazione
    </button>
  </form>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';cur.style.opacity='1';ring.style.opacity='1';});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();

function selParam(btn, val) {
  document.querySelectorAll('.param-pill').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('parametroInput').value = val;
  document.getElementById('customParamWrap').style.display = 'none';
  document.getElementById('parametroCustom').value = '';
}
function selCustom() {
  document.querySelectorAll('.param-pill').forEach(b => b.classList.remove('active'));
  document.getElementById('parametroInput').value = '';
  document.getElementById('customParamWrap').style.display = 'block';
  document.getElementById('parametroCustom').focus();
}
// Se la pagina ricarica con un valore custom, mostra il campo
window.addEventListener('DOMContentLoaded', () => {
  const custom = document.getElementById('parametroCustom').value;
  if (custom) { document.getElementById('customParamWrap').style.display = 'block'; }
});
</script>
</body>
</html>