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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;--coral:#e05a3a;--gold:#f0c040;}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);cursor:none;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{position:sticky;top:0;z-index:200;height:64px;display:flex;align-items:center;padding:0 2.5rem;gap:1rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}

    /* HERO */
    .hero{padding:3.5rem 2.5rem 3rem;background:linear-gradient(160deg,rgba(7,30,51,.8) 0%,var(--ink) 100%);border-bottom:1px solid rgba(114,215,240,.08);}
    .hero-inner{max-width:900px;margin:0 auto;}

    .stato-badge{display:inline-block;padding:.3rem .9rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:1.1rem;}
    .stato-attivo{background:rgba(44,184,155,.15);border:1px solid rgba(44,184,155,.35);color:var(--kelp);}
    .stato-concluso{background:rgba(93,154,184,.1);border:1px solid rgba(93,154,184,.2);color:var(--muted);}

    h1{font-family:'Cormorant Garamond',serif;font-size:clamp(2rem,4vw,3rem);color:var(--pearl);font-weight:400;line-height:1.15;margin-bottom:.75rem;}
    .data-inizio{color:var(--muted);font-size:.85rem;margin-bottom:2rem;}

    /* PROGRESS */
    .progress-box{background:rgba(11,61,94,.25);border:1px solid rgba(114,215,240,.12);border-radius:14px;padding:1.75rem;margin-bottom:2rem;}
    .prog-nums{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;}
    .prog-raccolto{font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--kelp);font-weight:400;}
    .prog-raccolto span{font-size:1rem;color:var(--muted);font-family:'Outfit',sans-serif;}
    .prog-budget{color:var(--muted);font-size:.9rem;}
    .prog-budget strong{color:var(--pearl);}
    .bar-track{height:10px;background:rgba(114,215,240,.08);border-radius:5px;overflow:hidden;margin-bottom:.6rem;}
    .bar-fill{height:100%;background:linear-gradient(90deg,var(--kelp),var(--wave));border-radius:5px;transition:width .6s cubic-bezier(.25,.46,.45,.94);}
    .prog-perc{font-size:.82rem;color:var(--muted);}
    .prog-perc strong{color:var(--foam);}

    /* FORM DONAZIONE */
    .dona-section{background:linear-gradient(135deg,rgba(44,184,155,.08),rgba(27,159,212,.05));border:1px solid rgba(44,184,155,.2);border-radius:14px;padding:1.75rem;margin-bottom:2rem;}
    .dona-section h3{font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--pearl);font-weight:400;margin-bottom:.5rem;}
    .dona-section p{color:var(--muted);font-size:.875rem;margin-bottom:1.25rem;line-height:1.6;}
    .dona-form{display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;}
    .dona-input-wrap{position:relative;flex:1;min-width:140px;max-width:220px;}
    .dona-input-wrap span{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--muted);}
    .dona-input{width:100%;padding:.7rem 1rem .7rem 2rem;background:rgba(11,61,94,.4);border:1px solid rgba(114,215,240,.15);border-radius:10px;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.95rem;outline:none;transition:border-color .2s;}
    .dona-input:focus{border-color:var(--wave);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
    .btn-dona{padding:.7rem 1.75rem;background:var(--kelp);color:var(--ink);border:none;border-radius:10px;font-family:'Outfit',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;transition:all .2s;}
    .btn-dona:hover{background:#3dd4ae;transform:translateY(-1px);box-shadow:0 6px 20px rgba(44,184,155,.3);}
    .hint{font-size:.75rem;color:var(--muted);}
    .login-hint{color:var(--muted);font-size:.875rem;}
    .login-hint a{color:var(--wave);text-decoration:none;}

    /* OBIETTIVO */
    .main{max-width:900px;margin:2.5rem auto 5rem;padding:0 2.5rem;}
    .sez-title{font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--pearl);font-weight:400;margin-bottom:1rem;padding-bottom:.6rem;border-bottom:1px solid rgba(114,215,240,.1);}
    .obiettivo-text{color:var(--text);font-size:.95rem;line-height:1.85;white-space:pre-line;}

    /* DATI SCHEDA */
    .dati{display:flex;flex-wrap:wrap;gap:.65rem;margin-bottom:2rem;}
    .dato{background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.1);border-radius:10px;padding:.7rem 1.1rem;min-width:130px;}
    .dato-lbl{font-size:.68rem;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:.25rem;}
    .dato-val{font-size:.9rem;color:var(--pearl);font-weight:500;}

    .flash{padding:.9rem 1.2rem;border-radius:10px;margin-bottom:1.5rem;background:rgba(44,184,155,.1);border:1px solid rgba(44,184,155,.3);color:#3dd4ae;max-width:900px;margin-left:auto;margin-right:auto;}

    @media(max-width:600px){.hero{padding:2rem 1.25rem;}.main{padding:0 1.25rem 3rem;}.prog-raccolto{font-size:1.7rem;}.dona-form{flex-direction:column;align-items:stretch;}}
  </style>
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