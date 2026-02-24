<?php
require 'config.php';

// make sure donazione primary key is auto_increment (fix missing in dump)
try {
    // first attempt: modify adding AUTO_INCREMENT and PK
    $connessione->exec("ALTER TABLE donazione MODIFY id_donazione INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
} catch (PDOException $e) {
    // se fallisce proviamo con change
    error_log("Auto-inc attempt1 failed: " . $e->getMessage());
    try {
        $connessione->exec("ALTER TABLE donazione CHANGE id_donazione id_donazione INT NOT NULL AUTO_INCREMENT");
    } catch (PDOException $e2) {
        error_log("Auto-inc attempt2 failed: " . $e2->getMessage());
        // ultima risorsa: non possiamo fare nulla, continuerà a generare errori ma verranno mostrati
    }
}

// validazione parametro id prima di qualsiasi redirect
$id_pd = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pd) {
    // elenco progetti generico (nessun login richiesto)
    $stmt = $connessione->query("SELECT * FROM progetto");
    $progetti = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Progetti di Donazione — NetSea</title>
      <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
      <style>
        body{font-family:'Outfit',sans-serif;background:#04111e;color:#e8f6fc;}
        nav{padding:1rem;background:rgba(4,17,30,.95);}
        nav a{color:#e8f6fc;text-decoration:none;}
        .list{max-width:800px;margin:2rem auto;padding:0 1rem;}
        .list a{display:block;padding:.75rem 1rem;margin:.4rem 0;border:1px solid rgba(114,215,240,.2);border-radius:8px;color:#e8f6fc;text-decoration:none;transition:background .2s;}
        .list a:hover{background:rgba(114,215,240,.1);}
      </style>
    </head>
    <body>
    <nav><a href="index.php">← Home</a></nav>
    <div class="list">
      <h1>Progetti di Donazione</h1>
      <?php foreach($progetti as $p): ?>
        <a href="progetti.php?id=<?= $p['id_pd'] ?>"><?= htmlspecialchars($p['titolo']) ?></a>
      <?php endforeach; ?>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// se non siamo loggati inviamo al form di login **con redirect corretto**
if (!isset($_SESSION['id'])) {
    $redir = 'progetti.php?id=' . $id_pd;
    header('Location: Login.php?redirect=' . urlencode($redir));
    exit();
}

// Recupera progetto con totale donazioni
$stmt = $connessione->prepare("
    SELECT p.*,
           COALESCE(SUM(d.importo), 0) AS raccolto,
           COUNT(d.id_donazione)       AS num_donatori
    FROM progetto p
    LEFT JOIN donazione d ON d.id_pd = p.id_pd
    WHERE p.id_pd = ?
    GROUP BY p.id_pd
");
$stmt->execute([$id_pd]);
$p = $stmt->fetch();
if (!$p) { header("Location: progetti.php"); exit(); }

$pct = $p['budget'] > 0 ? min(100, round($p['raccolto'] / $p['budget'] * 100)) : 0;
$rimanente = max(0, $p['budget'] - $p['raccolto']);

// ── ELABORAZIONE DONAZIONE ───────────────────────────────────────────
$successo = false;
$errore   = '';
$metodo_sel = '';  // per mostrare metodo nella pagina di conferma

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $importo = floatval(str_replace(',', '.', $_POST['importo'] ?? 0));
    $importo_custom = floatval(str_replace(',', '.', $_POST['importo_custom'] ?? 0));
    $metodo = $_POST['metodo'] ?? '';
    $metodo_sel = $metodo; // ricordiamo il metodo scelto

    // Se ha scelto importo personalizzato usa quello
    if (isset($_POST['importo_custom']) && $_POST['importo_custom'] !== '') {
        $importo = $importo_custom;
    }

    if (!in_array($metodo, ['carta','bonifico','paypal'], true)) {
        $errore = "Seleziona un metodo di pagamento.";
    } elseif ($importo <= 0) {
        $errore = "Inserisci un importo valido.";
    } elseif ($importo > 10000) {
        $errore = "Importo massimo consentito: € 10.000.";
    } else {
        try {
            $ins = $connessione->prepare("
                INSERT INTO donazione (importo, data, id_utente, id_pd)
                VALUES (?, CURDATE(), ?, ?)
            ");
            $ins->execute([$importo, $_SESSION['id'], $id_pd]);
            $successo = true;

            // Ricarica dati aggiornati
            $stmt->execute([$id_pd]);
            $p = $stmt->fetch();
            $pct = $p['budget'] > 0 ? min(100, round($p['raccolto'] / $p['budget'] * 100)) : 0;
            $rimanente = max(0, $p['budget'] - $p['raccolto']);

        } catch (PDOException $e) {
            error_log("Donazione error: " . $e->getMessage());
            // if duplicate key (typically id_donazione = 0) try manual id
            if (strpos($e->getMessage(), '1062') !== false) {
                try {
                    $next = $connessione->query("SELECT COALESCE(MAX(id_donazione),0)+1 FROM donazione")->fetchColumn();
                    $ins2 = $connessione->prepare("\
                        INSERT INTO donazione (id_donazione, importo, data, id_utente, id_pd)\
                        VALUES (?, ?, CURDATE(), ?, ?)\
                    ");
                    $ins2->execute([$next, $importo, $_SESSION['id'], $id_pd]);
                    $successo = true;

                    // ricarica
                    $stmt->execute([$id_pd]);
                    $p = $stmt->fetch();
                    $pct = $p['budget'] > 0 ? min(100, round($p['raccolto'] / $p['budget'] * 100)) : 0;
                    $rimanente = max(0, $p['budget'] - $p['raccolto']);
                } catch (PDOException $e2) {
                    error_log("Donazione retry error: " . $e2->getMessage());
                    $errore = "Errore durante la donazione. Riprova. (" . $e2->getMessage() . ")";
                }
            } else {
                $errore = "Errore durante la donazione. Riprova. ";
                $errore .= "(" . $e->getMessage() . ")";
            }
        }
    }
}

$nome_utente = $_SESSION['nome'] ?? 'Utente';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dona — <?= htmlspecialchars($p['titolo']) ?> — NetSea</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
      --ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;
      --wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;
      --coral:#e05a3a;--kelp:#2cb89b;--gold:#f0c040;
      --text:#c5e4f5;--muted:#5d9ab8;
      --ease:cubic-bezier(.25,.46,.45,.94);
    }
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);cursor:none;min-height:100vh;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}

    nav{position:sticky;top:0;z-index:200;height:64px;display:flex;align-items:center;padding:0 2.5rem;gap:1rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}

    /* LAYOUT */
    .page{max-width:980px;margin:0 auto;padding:3rem 2rem 5rem;display:grid;grid-template-columns:1fr 380px;gap:2.5rem;align-items:start;}
    @media(max-width:768px){.page{grid-template-columns:1fr;}}

    /* LEFT — info progetto */
    .proj-card{background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.1);border-radius:16px;overflow:hidden;margin-bottom:1.5rem;}
    .proj-top{height:180px;background:linear-gradient(135deg,#0b3d5e,#071e33);display:flex;align-items:center;justify-content:center;font-size:5rem;position:relative;}
    .proj-body{padding:1.5rem;}
    .proj-body h2{font-family:'Cormorant Garamond',serif;font-size:1.5rem;color:var(--pearl);margin-bottom:.5rem;}
    .proj-body p{color:var(--muted);font-size:.875rem;line-height:1.7;margin-bottom:1.25rem;}

    .prog-bar{background:rgba(255,255,255,.08);border-radius:20px;height:8px;overflow:hidden;margin-bottom:.6rem;}
    .prog-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,var(--kelp),var(--wave));transition:width 1.4s var(--ease);}
    .prog-meta{display:flex;justify-content:space-between;font-size:.8rem;color:var(--muted);margin-bottom:.4rem;}
    .prog-meta strong{color:var(--kelp);font-size:1rem;}
    .prog-stats{display:flex;gap:1.5rem;margin-top:.75rem;}
    .prog-stat{text-align:center;}
    .prog-stat .num{font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--foam);display:block;}
    .prog-stat .lbl{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);}

    /* RIGHT — FORM */
    .form-card{background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.12);border-radius:16px;padding:1.75rem;position:sticky;top:80px;}
    .form-card h3{font-family:'Cormorant Garamond',serif;font-size:1.3rem;color:var(--pearl);margin-bottom:.3rem;}
    .form-card .sub{color:var(--muted);font-size:.8rem;margin-bottom:1.5rem;}

    /* IMPORTI RAPIDI */
    .quick-amounts{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-bottom:1.25rem;}
    .qa{
      padding:.65rem;border-radius:10px;border:1.5px solid rgba(114,215,240,.15);
      background:rgba(11,61,94,.3);color:var(--foam);font-size:.9rem;font-weight:600;
      cursor:pointer;transition:all .2s;text-align:center;
      font-family:'Outfit',sans-serif;
    }
    .qa:hover,.qa.selected{border-color:var(--wave);background:rgba(27,159,212,.15);color:var(--pearl);}

    .divider{display:flex;align-items:center;gap:.75rem;margin:.75rem 0;color:var(--muted);font-size:.75rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(114,215,240,.1);}

    /* CAMPO IMPORTO */
    .input-group{position:relative;margin-bottom:1.25rem;}
    .euro{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:1rem;font-weight:600;}
    input[type=number]{
      width:100%;background:rgba(11,61,94,.3);border:1.5px solid rgba(114,215,240,.15);
      border-radius:10px;padding:.75rem 1rem .75rem 2rem;
      color:var(--pearl);font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:600;
      outline:none;transition:border-color .2s,background .2s,box-shadow .2s;
    }
    input[type=number]:focus{border-color:var(--wave);background:rgba(11,61,94,.5);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
    input[type=number]::placeholder{color:var(--muted);font-weight:400;}

    /* METODO PAGAMENTO (simulato) */
    .metodi{display:flex;gap:.5rem;margin-bottom:1.25rem;}
    .metodo{
      flex:1;padding:.65rem;border-radius:10px;border:1.5px solid rgba(114,215,240,.12);
      background:rgba(11,61,94,.25);color:var(--muted);font-size:.78rem;
      cursor:pointer;transition:all .2s;text-align:center;
      font-family:'Outfit',sans-serif;
    }
    .metodo:hover,.metodo.sel{border-color:var(--wave);background:rgba(27,159,212,.1);color:var(--foam);}
    .metodo .m-icon{font-size:1.3rem;display:block;margin-bottom:.2rem;}

    /* NOTA SIMULAZIONE */
    .sim-note{
      background:rgba(240,192,64,.07);border:1px solid rgba(240,192,64,.18);
      border-radius:8px;padding:.75rem 1rem;font-size:.75rem;
      color:#c8a830;margin-bottom:1.25rem;line-height:1.5;
    }

    /* BOTTONE */
    .btn-submit{
      width:100%;padding:.9rem;border-radius:10px;background:var(--wave);
      color:var(--ink);font-family:'Outfit',sans-serif;font-weight:700;
      font-size:1rem;border:none;cursor:pointer;
      transition:background .2s,transform .15s,box-shadow .2s;
      display:flex;align-items:center;justify-content:center;gap:.6rem;
    }
    .btn-submit:hover{background:var(--foam);transform:translateY(-1px);box-shadow:0 8px 24px rgba(27,159,212,.25);}

    .alert{padding:.85rem 1rem;border-radius:8px;font-size:.85rem;margin-bottom:1rem;}
    .alert-err{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.25);color:#e8836a;}

    /* SUCCESSO */
    .success-overlay{
      position:fixed;inset:0;z-index:500;
      background:rgba(4,17,30,.97);backdrop-filter:blur(20px);
      display:flex;align-items:center;justify-content:center;
      animation:fadeIn .4s var(--ease);
    }
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}
    .success-box{
      text-align:center;max-width:440px;padding:3rem 2rem;
      background:rgba(11,61,94,.3);border:1px solid rgba(44,184,155,.2);
      border-radius:20px;animation:slideUp .5s var(--ease);
    }
    @keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
    .success-box .big{font-size:4rem;margin-bottom:1rem;display:block;}
    .success-box h2{font-family:'Cormorant Garamond',serif;font-size:2rem;color:var(--pearl);margin-bottom:.5rem;}
    .success-box p{color:var(--muted);font-size:.9rem;line-height:1.7;margin-bottom:.5rem;}
    .success-box .importo-ok{font-family:'Cormorant Garamond',serif;font-size:2.5rem;color:var(--kelp);margin:.75rem 0;}
    .btn-ok{display:inline-flex;align-items:center;gap:.5rem;padding:.8rem 2rem;border-radius:10px;background:var(--kelp);color:var(--ink);text-decoration:none;font-weight:700;font-size:.95rem;margin-top:1.25rem;transition:background .2s;}
    .btn-ok:hover{background:#3dd4ae;}
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- OVERLAY SUCCESSO -->
<?php if ($successo): ?>
<div class="success-overlay" id="successOverlay">
  <div class="success-box">
    <span class="big">🌊</span>
    <h2>Grazie, <?= htmlspecialchars($nome_utente) ?>!</h2>
    <p>La tua donazione per</p>
    <p><strong style="color:var(--pearl);"><?= htmlspecialchars($p['titolo']) ?></strong></p>
    <div class="importo-ok">€ <?= number_format(floatval($_POST['importo_custom'] ?: $_POST['importo']), 2, ',', '.') ?></div>
    <?php if ($metodo_sel): ?>
      <p>Metodo scelto: <strong><?= htmlspecialchars(ucfirst($metodo_sel)) ?></strong></p>
    <?php endif; ?>
    <p>è stata registrata con successo. Insieme stiamo proteggendo i nostri oceani. 💚</p>
    <div style="margin-top:1.25rem;background:rgba(44,184,155,.1);border:1px solid rgba(44,184,155,.2);border-radius:10px;padding:.75rem;font-size:.78rem;color:#3dd4ae;">
      Progetto al <strong><?= $pct ?>%</strong> — € <?= number_format($p['raccolto'], 0, ',', '.') ?> / € <?= number_format($p['budget'], 0, ',', '.') ?>
    </div>
    <a href="progetti.php" class="btn-ok">Vedi tutti i progetti →</a>
  </div>
</div>
<?php endif; ?>

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

<div class="page">
  <!-- LEFT: info progetto -->
  <div>
    <div class="proj-card">
      <div class="proj-top">🐬</div>
      <div class="proj-body">
        <h2><?= htmlspecialchars($p['titolo']) ?></h2>
        <p><?= htmlspecialchars($p['obiettivo'] ?? '') ?></p>

        <div class="prog-bar">
          <div class="prog-fill" style="width:0%" data-pct="<?= $pct ?>"></div>
        </div>
        <div class="prog-meta">
          <span><strong>€ <?= number_format($p['raccolto'], 0, ',', '.') ?></strong></span>
          <span>€ <?= number_format($p['budget'], 0, ',', '.') ?></span>
        </div>

        <div class="prog-stats">
          <div class="prog-stat">
            <span class="num"><?= $pct ?>%</span>
            <span class="lbl">Completato</span>
          </div>
          <div class="prog-stat">
            <span class="num"><?= $p['num_donatori'] ?></span>
            <span class="lbl">Donatori</span>
          </div>
          <div class="prog-stat">
            <span class="num">€ <?= number_format($rimanente, 0, ',', '.') ?></span>
            <span class="lbl">Mancanti</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: form donazione -->
  <div>
    <div class="form-card">
      <h3>💚 Fai una donazione</h3>
      <p class="sub">Loggato come <strong style="color:var(--foam);"><?= htmlspecialchars($nome_utente) ?></strong></p>

      <?php if ($errore): ?>
        <div class="alert alert-err">❌ <?= htmlspecialchars($errore) ?></div>
      <?php endif; ?>

      <form method="POST" id="donaForm">

        <!-- IMPORTI RAPIDI -->
        <div class="quick-amounts">
          <button type="button" class="qa" onclick="setImporto(5, this)">€ 5</button>
          <button type="button" class="qa" onclick="setImporto(10, this)">€ 10</button>
          <button type="button" class="qa" onclick="setImporto(25, this)">€ 25</button>
          <button type="button" class="qa" onclick="setImporto(50, this)">€ 50</button>
          <button type="button" class="qa" onclick="setImporto(100, this)">€ 100</button>
          <button type="button" class="qa" onclick="setImporto(250, this)">€ 250</button>
        </div>

        <div class="divider">oppure inserisci importo</div>

        <!-- IMPORTO PERSONALIZZATO -->
        <div class="input-group">
          <span class="euro">€</span>
          <input type="number" name="importo_custom" id="importo_custom"
                 placeholder="Importo personalizzato"
                 min="1" max="10000" step="0.01"
                 oninput="deselezionaQuick()">
        </div>
        <input type="hidden" name="importo" id="importo_hidden" value="">

        <div class="divider">metodo di pagamento</div>

        <!-- METODO PAGAMENTO (simulato) -->
        <div class="metodi">
          <button type="button" class="metodo sel" data-val="carta" onclick="selMetodo(this)">
            <span class="m-icon">💳</span>Carta
          </button>
          <button type="button" class="metodo" data-val="bonifico" onclick="selMetodo(this)">
            <span class="m-icon">🏦</span>Bonifico
          </button>
          <button type="button" class="metodo" data-val="paypal" onclick="selMetodo(this)">
            <span class="m-icon">📱</span>PayPal
          </button>
        </div>
        <input type="hidden" name="metodo" id="metodo_hidden" value="carta">

        <div class="sim-note">
          ⚠️ <strong>Simulazione:</strong> questo è un progetto scolastico. Nessun pagamento reale verrà effettuato.
        </div>

        <button type="submit" class="btn-submit" onclick="return prepareSubmit()">
          💚 Conferma Donazione
        </button>
      </form>
    </div>
  </div>
</div>

<script>
/* CURSOR */
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();

/* PROGRESS BAR */
const fill=document.querySelector('.prog-fill');
setTimeout(()=>fill.style.width=fill.dataset.pct+'%',200);

/* IMPORTI RAPIDI */
let selectedImporto = null;
function setImporto(val, btn){
  document.querySelectorAll('.qa').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected');
  selectedImporto = val;
  document.getElementById('importo_hidden').value = val;
  document.getElementById('importo_custom').value = '';
}
function deselezionaQuick(){
  document.querySelectorAll('.qa').forEach(b=>b.classList.remove('selected'));
  selectedImporto = null;
  document.getElementById('importo_hidden').value = '';
}

/* METODO */
function selMetodo(btn){
  document.querySelectorAll('.metodo').forEach(b=>b.classList.remove('sel'));
  btn.classList.add('sel');
  // memorizza valore reale nel campo nascosto
  document.getElementById('metodo_hidden').value = btn.dataset.val || '';
}

/* SUBMIT */
function prepareSubmit(){
  const custom = document.getElementById('importo_custom').value;
  const hidden = document.getElementById('importo_hidden').value;
  const metodo = document.getElementById('metodo_hidden').value;
  if(!custom && !hidden){
    alert('Seleziona o inserisci un importo!');
    return false;
  }
  if(!metodo){
    alert('Seleziona un metodo di pagamento!');
    return false;
  }
  return true;
}
</script>
</body>
</html>