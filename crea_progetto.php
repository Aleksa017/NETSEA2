<?php
require 'config.php';

// Solo ricercatori e admin
if (!isset($_SESSION['id']) || !in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])) {
    header('Location: Login.php?redirect=crea_progetto.php'); exit();
}

// Ricerca id_ente del ricercatore loggato
$stR = $connessione->prepare("SELECT r.id_ricercatore, r.id_ente, e.nome AS nome_ente
    FROM ricercatore r LEFT JOIN ente_di_ricerca e ON e.id_ente = r.id_ente
    WHERE r.id_ricercatore = ?");
$stR->execute([$_SESSION['id']]);
$ric = $stR->fetch();

if (!$ric && ($_SESSION['ruolo'] ?? '') !== 'admin') {
    die("Profilo ricercatore non trovato. Contatta l'amministratore.");
}

// Lista enti (per admin che può scegliere, o per mostrare l'ente del ricercatore)
$enti = $connessione->query("SELECT id_ente, nome FROM ente_di_ricerca ORDER BY nome")->fetchAll();

$errors = []; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo    = trim($_POST['titolo']    ?? '');
    $obiettivo = trim($_POST['obiettivo'] ?? '');
    $budget    = (float)str_replace(',', '.', $_POST['budget'] ?? 0);
    $stato     = ($_POST['stato'] ?? '') === 'urgente' ? 'urgente' : 'attivo';
    $id_ente   = filter_input(INPUT_POST, 'id_ente', FILTER_VALIDATE_INT);

    // Immagine: solo URL esterno (non upload file, per portabilità)
    $immagine = filter_var(trim($_POST['immagine_url'] ?? ''), FILTER_VALIDATE_URL) ?: null;

    if (!$titolo)       $errors[] = 'Il titolo è obbligatorio.';
    if (!$obiettivo)    $errors[] = "L'obiettivo è obbligatorio.";
    if ($budget < 100)  $errors[] = 'Il budget minimo è €100.';
    if (!$id_ente)      $errors[] = "Seleziona l'ente sponsor.";

    if (!$errors) {
        $connessione->prepare(
            "INSERT INTO progetto (titolo, obiettivo, budget, raccolto, stato, data_i, immagine)
             VALUES (?, ?, ?, 0, ?, CURDATE(), ?)"
        )->execute([$titolo, $obiettivo, $budget, $stato, $immagine]);

        $id_nuovo = $connessione->lastInsertId();

        // Inserisci sponsorizzazione
        $connessione->prepare("INSERT INTO sponsorizzazione (id_ente, id_pd) VALUES (?, ?)")
                    ->execute([$id_ente, $id_nuovo]);

        header("Location: progetto_detail.php?id=$id_nuovo&created=1"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crea Progetto — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .wrap { max-width:760px; margin:0 auto; padding:5.5rem 1.5rem 4rem; }
    .page-title { font-family:'Cormorant Garamond',serif; font-size:2.2rem; font-weight:400; color:var(--pearl); margin-bottom:.4rem; }
    .page-sub { color:var(--muted); font-size:.875rem; margin-bottom:2.5rem; }
    .card { background:rgba(11,61,94,.2); border:1px solid rgba(114,215,240,.1); border-radius:14px; padding:1.5rem; margin-bottom:1rem; }
    .section-label { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--wave); margin-bottom:1rem; }
    .form-group { display:flex; flex-direction:column; gap:.35rem; margin-bottom:1rem; }
    .form-group label { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); }
    .form-group input, .form-group textarea, .form-group select {
      padding:.6rem .9rem; background:rgba(11,61,94,.4); border:1px solid rgba(114,215,240,.15);
      border-radius:9px; color:var(--pearl); font-family:'Outfit',sans-serif; font-size:.875rem; outline:none; transition:border-color .2s;
    }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color:var(--wave); }
    .form-group textarea { min-height:120px; resize:vertical; line-height:1.6; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    @media(max-width:580px){ .form-row { grid-template-columns:1fr; } }
    .hint { font-size:.72rem; color:rgba(114,215,240,.35); margin-top:.1rem; }
    .flash-err { padding:.75rem 1rem; border-radius:10px; background:rgba(232,131,106,.1); border:1px solid rgba(232,131,106,.3); color:#e8836a; font-size:.85rem; margin-bottom:1.25rem; }
    .ente-info { padding:.6rem .9rem; background:rgba(27,159,212,.06); border:1px solid rgba(27,159,212,.15); border-radius:9px; font-size:.875rem; color:var(--pearl); }
    .btn-submit { display:inline-flex; align-items:center; gap:.5rem; padding:.7rem 1.6rem; background:rgba(27,159,212,.15); border:1px solid rgba(27,159,212,.3); border-radius:10px; color:var(--wave); font-family:'Outfit',sans-serif; font-size:.9rem; font-weight:600; cursor:pointer; transition:background .2s; }
    .btn-submit:hover { background:rgba(27,159,212,.28); }
    .stato-btns { display:flex; gap:.6rem; }
    .stato-btn { padding:.5rem 1.1rem; border-radius:8px; border:1px solid rgba(114,215,240,.18); background:transparent; color:var(--muted); font-family:'Outfit',sans-serif; font-size:.82rem; cursor:pointer; transition:all .2s; }
    .stato-btn.active-attivo { background:rgba(27,159,212,.15); border-color:rgba(27,159,212,.4); color:var(--wave); }
    .stato-btn.active-urgente { background:rgba(232,131,106,.12); border-color:rgba(232,131,106,.4); color:#e8836a; }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="progetti.php" class="nav-back">← Tutti i progetti</a>
</nav>

<div class="wrap">
  <h1 class="page-title">Nuovo progetto</h1>
  <p class="page-sub">Il progetto sarà sponsorizzato dal tuo ente di ricerca e visibile a tutti gli utenti NetSea.</p>

  <?php if ($errors): ?>
    <div class="flash-err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="stato" id="statoInput" value="<?= htmlspecialchars($_POST['stato'] ?? 'attivo') ?>">

    <!-- Ente sponsor -->
    <div class="card">
      <p class="section-label">Ente sponsor</p>
      <?php if ($ric && $ric['id_ente'] && ($_SESSION['ruolo'] ?? '') !== 'admin'): ?>
        <!-- Ricercatore: ente fisso, quello del suo profilo -->
        <input type="hidden" name="id_ente" value="<?= $ric['id_ente'] ?>">
        <div class="ente-info">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--wave)" stroke-width="2" style="vertical-align:middle;margin-right:.4rem;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
          <?= htmlspecialchars($ric['nome_ente'] ?? '') ?>
        </div>
      <?php else: ?>
        <!-- Admin: può scegliere qualsiasi ente -->
        <div class="form-group">
          <label>Seleziona ente</label>
          <select name="id_ente">
            <option value="">— Scegli ente —</option>
            <?php foreach ($enti as $e): ?>
              <option value="<?= $e['id_ente'] ?>" <?= (($_POST['id_ente'] ?? '') == $e['id_ente']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>

    <!-- Dati progetto -->
    <div class="card">
      <p class="section-label">Informazioni progetto</p>

      <div class="form-group">
        <label>Titolo *</label>
        <input type="text" name="titolo" value="<?= htmlspecialchars($_POST['titolo'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label>Obiettivo *</label>
        <textarea name="obiettivo" placeholder="Descrivi cosa si vuole raggiungere con questo progetto..."><?= htmlspecialchars($_POST['obiettivo'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Budget necessario (€) *</label>
          <input type="number" name="budget" value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>"
                 min="100" step="100" placeholder="es. 15000">
          <span class="hint">Importo totale necessario per completare il progetto</span>
        </div>
        <div class="form-group">
          <label>Priorità</label>
          <div class="stato-btns">
            <button type="button" class="stato-btn <?= (($_POST['stato'] ?? 'attivo') === 'attivo') ? 'active-attivo' : '' ?>"
                    onclick="setStato('attivo',this)">Normale</button>
            <button type="button" class="stato-btn <?= (($_POST['stato'] ?? '') === 'urgente') ? 'active-urgente' : '' ?>"
                    onclick="setStato('urgente',this)">Urgente</button>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Immagine copertina <span style="color:var(--muted);font-weight:400;">(opzionale)</span></label>
        <input type="url" name="immagine_url" value="<?= htmlspecialchars($_POST['immagine_url'] ?? '') ?>"
               placeholder="https://esempio.com/foto.jpg">
        <span class="hint">Incolla un URL pubblico dell'immagine — così è visibile da qualsiasi PC</span>
      </div>
    </div>

    <button type="submit" class="btn-submit">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Pubblica progetto
    </button>
  </form>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';cur.style.opacity='1';ring.style.opacity='1';});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();

function setStato(val, btn) {
  document.getElementById('statoInput').value = val;
  document.querySelectorAll('.stato-btn').forEach(b => b.className = 'stato-btn');
  btn.classList.add('active-' + val);
}
</script>
</body>
</html>