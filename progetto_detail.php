<?php
require 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: progetti.php"); exit(); }

$stmt = $connessione->prepare("SELECT * FROM progetto WHERE id_pd = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header("Location: progetti.php"); exit(); }

// ── AZIONE DONAZIONE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dona'])) {
    if (!isset($_SESSION['id'])) {
        header("Location: Login.php?redirect=progetto_detail.php?id=$id"); exit();
    }
    $importo = (float)str_replace(',', '.', $_POST['importo'] ?? 0);
    if ($importo >= 1 && $importo <= 10000) {
        $connessione->prepare("UPDATE progetto SET raccolto = raccolto + ? WHERE id_pd = ?")
                    ->execute([$importo, $id]);
    }
    header("Location: progetto_detail.php?id=$id&ok=1"); exit();
}

$raccolto = (float)($p['raccolto'] ?? 0);
$budget   = (float)($p['budget']   ?? 0);
$perc     = $budget > 0 ? min(100, round($raccolto / $budget * 100)) : 0;
$is_attivo = strtolower($p['stato'] ?? '') === 'attivo';
$data_inizio = $p['data_i'] ? date('d M Y', strtotime($p['data_i'])) : '—';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($p['titolo']) ?> — NetSea</title>
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
  <a href="progetti.php" class="nav-back">← Tutti i progetti</a>
</nav>

<?php if (isset($_GET['ok'])): ?>
  <div class="flash" style="margin-top:1rem;">✅ Grazie per la tua donazione! Il contributo è stato registrato.</div>
<?php endif; ?>

<div class="hero">
  <div class="hero-inner">
    <span class="stato-badge <?= $is_attivo ? 'stato-attivo' : 'stato-concluso' ?>">
      <?= $is_attivo ? '🟢 Progetto attivo' : '⚫ Progetto concluso' ?>
    </span>
    <h1><?= htmlspecialchars($p['titolo']) ?></h1>
    <p class="data-inizio">📅 Avviato il <?= $data_inizio ?></p>

    <!-- PROGRESS -->
    <div class="progress-box">
      <div class="prog-nums">
        <div class="prog-raccolto">
          € <?= number_format($raccolto, 2, ',', '.') ?>
          <span>raccolti</span>
        </div>
        <div class="prog-budget">
          obiettivo: <strong>€ <?= number_format($budget, 2, ',', '.') ?></strong>
        </div>
      </div>
      <div class="bar-track">
        <div class="bar-fill" style="width:<?= $perc ?>%"></div>
      </div>
      <p class="prog-perc"><strong><?= $perc ?>%</strong> dell'obiettivo raggiunto</p>
    </div>

    <!-- DONAZIONE -->
    <?php if ($is_attivo): ?>
      <div class="dona-section">
        <h3>💚 Sostieni questo progetto</h3>
        <p>Il tuo contributo va direttamente alla ricerca e alla protezione degli ecosistemi marini.</p>
        <?php if (isset($_SESSION['id'])): ?>
          <form method="POST" class="dona-form">
            <div class="dona-input-wrap">
              <span>€</span>
              <input type="number" name="importo" class="dona-input"
                     placeholder="10.00" min="1" max="10000" step="0.01" required>
            </div>
            <button type="submit" name="dona" class="btn-dona">💚 Dona ora</button>
            <p class="hint">Min. €1 — Max. €10.000</p>
          </form>
        <?php else: ?>
          <p class="login-hint">
            <a href="Login.php?redirect=progetto_detail.php?id=<?= $id ?>">Accedi</a> per donare a questo progetto.
          </p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p style="color:var(--muted);font-size:.875rem;margin-top:1rem;">Questo progetto è concluso. Grazie a tutti i donatori! 🙏</p>
    <?php endif; ?>
  </div>
</div>

<div class="main">
  <!-- DATI RAPIDI -->
  <div class="dati">
    <div class="dato">
      <p class="dato-lbl">Stato</p>
      <p class="dato-val"><?= htmlspecialchars(ucfirst($p['stato'] ?? '—')) ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Avvio</p>
      <p class="dato-val"><?= $data_inizio ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Raccolto</p>
      <p class="dato-val" style="color:var(--kelp);">€ <?= number_format($raccolto,2,',','.') ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Obiettivo</p>
      <p class="dato-val">€ <?= number_format($budget,2,',','.') ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Completamento</p>
      <p class="dato-val"><?= $perc ?>%</p>
    </div>
  </div>

  <!-- DESCRIZIONE OBIETTIVO -->
  <p class="sez-title">🎯 Obiettivo del progetto</p>
  <p class="obiettivo-text"><?= htmlspecialchars($p['obiettivo'] ?? 'Nessuna descrizione disponibile.') ?></p>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();
</script>
</body>
</html>