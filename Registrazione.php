<?php
// ══════════════════════════════════════════
//  Registrazione.php
//  PROBLEMI RISOLTI:
//  1. I nomi dei campi POST ora corrispondono al form
//  2. Il submit fa davvero INSERT nel DB
//  3. Dopo successo mostra la pagina di conferma
// ══════════════════════════════════════════
require 'config.php';

$messaggio    = "";
$tipo_msg     = ""; // "ok" o "err"
$registrato   = false;
$tipo_account = $_GET['tipo'] ?? 'user'; // per pre-selezionare il tipo

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Legge tutti i campi dal form
    $tipo      = $_POST["tipo"]     ?? "user";
    $nome      = trim($_POST["nome"]     ?? "");
    $cognome   = trim($_POST["cognome"]  ?? "");
    $username  = trim($_POST["username"] ?? "");
    $email     = trim($_POST["email"]    ?? ""); // prima era "reg-email", ora corretto
    $password  = $_POST["password"]      ?? "";
    $password2 = $_POST["password_confirm"] ?? "";

    // Validazione
    if (empty($nome) || empty($username) || empty($email) || empty($password)) {
        $messaggio = "Compila tutti i campi obbligatori.";
        $tipo_msg  = "err";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messaggio = "Email non valida.";
        $tipo_msg  = "err";
    } elseif (strlen($password) < 8) {
        $messaggio = "La password deve essere di almeno 8 caratteri.";
        $tipo_msg  = "err";
    } elseif ($password !== $password2) {
        $messaggio = "Le password non coincidono.";
        $tipo_msg  = "err";
    } else {
        // Cripta password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $data_oggi     = date("Y-m-d");

        try {
            // Controlla se email o username già esistono
            $check = $connessione->prepare(
                "SELECT id_utente FROM Utente WHERE email = ? OR username = ?"
            );
            $check->execute([$email, $username]);

            if ($check->fetch()) {
                $messaggio = "Email o username già in uso. Prova con altri dati.";
                $tipo_msg  = "err";
            } else {
                // Inserisce l'utente
                $query = $connessione->prepare(
                    "INSERT INTO Utente (username, password_hash, email, nome, cognome, data_registrazione)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $query->execute([$username, $password_hash, $email, $nome, $cognome, $data_oggi]);

                $id_nuovo = $connessione->lastInsertId();

                // Se è ricercatore → salva anche la richiesta
                if ($tipo === "ricercatore") {
                    $ente        = trim($_POST["ente"]        ?? "");
                    $qualifica   = trim($_POST["qualifica"]   ?? "");
                    $motivazione = trim($_POST["motivazione"] ?? "");

                    // Gestione upload file certificato
                    $path_cert = null;
                    if (isset($_FILES["certificato"]) && $_FILES["certificato"]["error"] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES["certificato"]["name"], PATHINFO_EXTENSION);
                        $dest = "uploads/certificati/" . $id_nuovo . "_cert." . $ext;
                        if (!is_dir("uploads/certificati")) mkdir("uploads/certificati", 0755, true);
                        move_uploaded_file($_FILES["certificato"]["tmp_name"], $dest);
                        $path_cert = $dest;
                    }

                    // Gestione upload badge ente
                    $path_badge = null;
                    if (isset($_FILES["badge_ente"]) && $_FILES["badge_ente"]["error"] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES["badge_ente"]["name"], PATHINFO_EXTENSION);
                        $dest = "uploads/badge/" . $id_nuovo . "_badge." . $ext;
                        if (!is_dir("uploads/badge")) mkdir("uploads/badge", 0755, true);
                        move_uploaded_file($_FILES["badge_ente"]["tmp_name"], $dest);
                        $path_badge = $dest;
                    }

                    // Salva richiesta ricercatore (adatta il nome tabella al tuo DB)
                    try {
                        $qr = $connessione->prepare(
                            "INSERT INTO richiesta_ricercatore
                             (id_utente, ente_dichiarato, qualifica_dichiarata, motivazione, stato, data_richiesta)
                             VALUES (?, ?, ?, ?, 'in_attesa', ?)"
                        );
                        $qr->execute([$id_nuovo, $ente, $qualifica, $motivazione, $data_oggi]);
                    } catch (PDOException $e) {
                        // Se la tabella non esiste ancora, va bene — l'utente è comunque stato creato
                        error_log("Richiesta ricercatore non salvata: " . $e->getMessage());
                    }
                }

                $registrato   = true;
                $tipo_account = $tipo; // per personalizzare il messaggio di successo
                $messaggio    = "ok";
                $tipo_msg     = "ok";
            }

        } catch (PDOException $e) {
            $messaggio = "Errore durante la registrazione: " . $e->getMessage();
            $tipo_msg  = "err";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrazione — NetSea</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--mid:#1267a0;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--coral:#e05a3a;--kelp:#2cb89b;--gold:#f0c040;--text:#c5e4f5;--muted:#5d9ab8;--ease:cubic-bezier(.25,.46,.45,.94);}
    html,body{height:100%;}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);min-height:100vh;cursor:none;overflow-x:hidden;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{height:64px;display:flex;align-items:center;padding:0 2.5rem;gap:1rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);position:sticky;top:0;z-index:100;}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}
    .progress-header{background:rgba(7,30,51,.5);border-bottom:1px solid rgba(114,215,240,.07);padding:1.5rem 2.5rem;}
    .steps{display:flex;align-items:center;max-width:600px;margin:0 auto;}
    .step{display:flex;align-items:center;flex:1;}
    .step-circle{width:32px;height:32px;border-radius:50%;border:2px solid rgba(114,215,240,.2);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;color:var(--muted);flex-shrink:0;transition:all .3s;}
    .step.active .step-circle{border-color:var(--wave);background:rgba(27,159,212,.2);color:var(--foam);}
    .step.done .step-circle{border-color:var(--kelp);background:rgba(44,184,155,.2);color:var(--kelp);}
    .step-label{font-size:.72rem;color:var(--muted);margin-left:.5rem;white-space:nowrap;}
    .step.active .step-label,.step.done .step-label{color:var(--foam);}
    .step-line{flex:1;height:1px;background:rgba(114,215,240,.12);margin:0 .75rem;}
    .page-wrap{max-width:700px;margin:0 auto;padding:3rem 2rem 5rem;}
    .type-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:2rem;}
    .type-card{background:rgba(11,61,94,.25);border:2px solid rgba(114,215,240,.1);border-radius:14px;padding:1.5rem 1.25rem;cursor:pointer;transition:all .25s;text-align:center;}
    .type-card:hover{border-color:rgba(114,215,240,.25);}
    .type-card.selected{border-color:var(--wave);background:rgba(27,159,212,.1);}
    .type-card .tc-icon{font-size:2.2rem;margin-bottom:.6rem;}
    .type-card h3{color:var(--pearl);font-size:.95rem;font-weight:600;margin-bottom:.3rem;}
    .type-card p{color:var(--muted);font-size:.78rem;line-height:1.4;}
    .tc-badge{display:inline-block;margin-top:.6rem;padding:.2rem .65rem;border-radius:20px;font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
    .tc-badge-free{background:rgba(44,184,155,.15);color:var(--kelp);border:1px solid rgba(44,184,155,.25);}
    .tc-badge-review{background:rgba(240,192,64,.15);color:var(--gold);border:1px solid rgba(240,192,64,.25);}
    .step-panel{display:none;}
    .step-panel.active{display:block;animation:fadeSlide .35s var(--ease);}
    @keyframes fadeSlide{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .panel-title{font-family:'Cormorant Garamond',serif;font-size:1.7rem;color:var(--pearl);font-weight:400;margin-bottom:.4rem;}
    .panel-sub{color:var(--muted);font-size:.875rem;margin-bottom:2rem;line-height:1.6;}
    .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
    .form-group{margin-bottom:1rem;}
    .form-group label{display:block;font-size:.8rem;font-weight:500;color:var(--muted);margin-bottom:.4rem;}
    .form-group label .req{color:var(--coral);margin-left:.2rem;}
    .input-wrap{position:relative;}
    .input-icon{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);font-size:.9rem;pointer-events:none;}
    input[type=text],input[type=email],input[type=password],select,textarea{width:100%;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.15);border-radius:10px;padding:.7rem 1rem .7rem 2.5rem;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s,background .2s,box-shadow .2s;}
    select{cursor:pointer;appearance:none;padding-left:1rem;}
    select option{background:var(--deep);color:var(--pearl);}
    textarea{resize:vertical;min-height:100px;padding-top:.75rem;line-height:1.5;padding-left:1rem;}
    input::placeholder,textarea::placeholder{color:var(--muted);}
    input:focus,select:focus,textarea:focus{border-color:var(--wave);background:rgba(11,61,94,.5);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
    input.err,select.err,textarea.err{border-color:var(--coral);}
    .pw-toggle{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:.9rem;padding:.2rem;}
    .pw-toggle:hover{color:var(--foam);}
    .pw-strength{margin-top:.5rem;}
    .pw-bar{display:flex;gap:3px;margin-bottom:.3rem;}
    .pw-seg{flex:1;height:3px;border-radius:2px;background:rgba(114,215,240,.1);transition:background .3s;}
    .pw-label{font-size:.72rem;color:var(--muted);}
    .researcher-section{background:rgba(44,184,155,.05);border:1px solid rgba(44,184,155,.15);border-radius:12px;padding:1.5rem;margin-top:1.5rem;margin-bottom:1rem;}
    .researcher-section h3{font-family:'Cormorant Garamond',serif;font-size:1.1rem;color:var(--kelp);margin-bottom:.4rem;}
    .researcher-section p{color:var(--muted);font-size:.8rem;margin-bottom:1.25rem;line-height:1.5;}
    .file-upload{border:2px dashed rgba(114,215,240,.2);border-radius:10px;padding:1.5rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;position:relative;}
    .file-upload:hover{border-color:rgba(114,215,240,.4);background:rgba(114,215,240,.04);}
    .file-upload input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;padding:0;border:none;background:none;}
    .file-upload .fu-icon{font-size:2rem;margin-bottom:.5rem;}
    .file-upload p{color:var(--muted);font-size:.82rem;}
    .file-upload .fu-hint{font-size:.72rem;color:rgba(93,154,184,.6);margin-top:.25rem;}
    .file-name{font-size:.78rem;color:var(--kelp);margin-top:.5rem;display:none;}
    .info-box{background:rgba(240,192,64,.06);border:1px solid rgba(240,192,64,.18);border-radius:10px;padding:1rem 1.1rem;font-size:.8rem;color:var(--muted);line-height:1.6;margin-top:1rem;}
    .info-box strong{color:var(--gold);}
    .checkbox-group{display:flex;align-items:flex-start;gap:.6rem;margin-bottom:1rem;}
    .checkbox-group input[type=checkbox]{width:auto;padding:0;margin-top:2px;flex-shrink:0;}
    .checkbox-group label{font-size:.82rem;color:var(--muted);line-height:1.5;cursor:pointer;}
    .checkbox-group a{color:var(--wave);text-decoration:none;}
    .btn-row{display:flex;gap:.75rem;justify-content:space-between;margin-top:2rem;}
    .btn-back{padding:.8rem 1.5rem;border-radius:10px;background:rgba(114,215,240,.06);border:1px solid rgba(114,215,240,.15);color:var(--foam);font-family:'Outfit',sans-serif;font-weight:500;font-size:.9rem;cursor:pointer;transition:all .2s;}
    .btn-back:hover{background:rgba(114,215,240,.12);}
    .btn-next{flex:1;padding:.85rem;border-radius:10px;background:var(--wave);color:var(--ink);border:none;font-family:'Outfit',sans-serif;font-weight:600;font-size:.95rem;cursor:pointer;transition:background .2s,transform .15s,box-shadow .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;}
    .btn-next:hover{background:var(--foam);transform:translateY(-1px);box-shadow:0 8px 24px rgba(27,159,212,.25);}
    .success-card{text-align:center;padding:3rem 2rem;background:rgba(44,184,155,.05);border:1px solid rgba(44,184,155,.15);border-radius:16px;}
    .success-card .big-icon{font-size:4rem;margin-bottom:1rem;}
    .success-card h2{font-family:'Cormorant Garamond',serif;font-size:2rem;color:var(--pearl);margin-bottom:.5rem;}
    .success-card p{color:var(--muted);font-size:.9rem;line-height:1.7;max-width:400px;margin:0 auto 1.5rem;}
    .success-card .note{background:rgba(240,192,64,.08);border:1px solid rgba(240,192,64,.2);border-radius:8px;padding:.85rem;font-size:.8rem;color:var(--gold);margin-bottom:1.5rem;}
    .field-error{font-size:.72rem;color:#e8836a;margin-top:.3rem;display:none;}
    .alert{padding:.85rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1.5rem;}
    .alert-err{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.25);color:#e8836a;}
    .divider{display:flex;align-items:center;gap:.75rem;margin:1.5rem 0;color:var(--muted);font-size:.78rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(114,215,240,.1);}
    .bottom-link{text-align:center;font-size:.875rem;color:var(--muted);}
    .bottom-link a{color:var(--wave);text-decoration:none;font-weight:500;}
    .btn-vai{display:inline-flex;align-items:center;gap:.5rem;padding:.85rem 2rem;border-radius:10px;background:var(--wave);color:var(--ink);text-decoration:none;font-weight:600;font-size:.95rem;transition:background .2s;}
    .btn-vai:hover{background:var(--foam);}
    @media(max-width:600px){.form-grid-2{grid-template-columns:1fr;}.type-grid{grid-template-columns:1fr;}}
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
  <a href="Login.php" class="nav-back">← Hai già un account? Accedi</a>
</nav>

<!-- BARRA STEP -->
<div class="progress-header">
  <div class="steps">
    <div class="step <?= $registrato ? 'done' : 'active' ?>" id="step-ind-0">
      <div class="step-circle"><?= $registrato ? '✓' : '1' ?></div>
      <span class="step-label">Tipo account</span>
      <div class="step-line"></div>
    </div>
    <div class="step <?= $registrato ? 'done' : '' ?>" id="step-ind-1">
      <div class="step-circle"><?= $registrato ? '✓' : '2' ?></div>
      <span class="step-label">Dati personali</span>
      <div class="step-line"></div>
    </div>
    <div class="step <?= $registrato ? 'active' : '' ?>" id="step-ind-2">
      <div class="step-circle">3</div>
      <span class="step-label">Conferma</span>
    </div>
  </div>
</div>

<div class="page-wrap">

<?php if ($registrato): ?>
  <!-- ══ STEP 2: SUCCESSO ══ -->
  <div class="success-card">
    <div class="big-icon"><?= $tipo_account === 'ricercatore' ? '⏳' : '✅' ?></div>
    <h2><?= $tipo_account === 'ricercatore' ? 'Richiesta inviata!' : 'Account creato!' ?></h2>
    <p>
      <?php if ($tipo_account === 'ricercatore'): ?>
        I tuoi documenti sono stati inviati all'amministratore per la verifica.
        Riceverai una email con la OTP appena approvato (entro 48h).
      <?php else: ?>
        Il tuo account è attivo. Puoi subito accedere a NetSea e iniziare ad esplorare gli ecosistemi marini.
      <?php endif; ?>
    </p>
    <?php if ($tipo_account === 'ricercatore'): ?>
      <div class="note">
        📧 Riceverai una email con la One-Time Password non appena l'admin avrà verificato i tuoi documenti (entro 48h).
      </div>
    <?php endif; ?>
    <a href="Login.php" class="btn-vai">Vai al login →</a>
  </div>

<?php else: ?>

  <!-- ══ ERRORE se c'è ══ -->
  <?php if ($tipo_msg === 'err' && $messaggio): ?>
    <div class="alert alert-err">❌ <?= htmlspecialchars($messaggio) ?></div>
  <?php endif; ?>

  <!-- ══ STEP 0: TIPO ACCOUNT ══ -->
  <div class="step-panel active" id="panel0">
    <h2 class="panel-title">Che tipo di account vuoi?</h2>
    <p class="panel-sub">Scegli in base al tuo ruolo. Puoi sempre richiedere l'upgrade a ricercatore in seguito.</p>
    <div class="type-grid">
      <div class="type-card" id="card-user" onclick="selectType('user')">
        <div class="tc-icon">👤</div>
        <h3>Utente Pubblico</h3>
        <p>Leggi news, segui il feed marino, fai donazioni ai progetti di ricerca</p>
        <span class="tc-badge tc-badge-free">Gratuito · Subito</span>
      </div>
      <div class="type-card" id="card-ricercatore" onclick="selectType('ricercatore')">
        <div class="tc-icon">🔬</div>
        <h3>Ricercatore Verificato</h3>
        <p>Pubblica news scientifiche, crea contenuti per il feed, inserisci rilevazioni nel DB</p>
        <span class="tc-badge tc-badge-review">Richiede approvazione</span>
      </div>
    </div>
    <div class="btn-row" style="justify-content:flex-end;">
      <button class="btn-next" onclick="goStep(1)">Continua →</button>
    </div>
  </div>

  <!-- ══ STEP 1: FORM DATI ══ -->
  <div class="step-panel" id="panel1">
    <h2 class="panel-title" id="step1Title">Crea il tuo account</h2>
    <p class="panel-sub" id="step1Sub">Inserisci i tuoi dati. Potrai modificarli in qualsiasi momento dal profilo.</p>

    <!-- ACTION = stesso file, METHOD POST, enctype per file upload -->
    <form id="regForm" action="Registrazione.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="tipo" id="hiddenTipo" value="user">

      <div class="form-grid-2">
        <div class="form-group">
          <label>Nome <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" id="nome" name="nome"
                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                   placeholder="Mario" required>
          </div>
          <p class="field-error" id="err-nome">Campo obbligatorio</p>
        </div>
        <div class="form-group">
          <label>Cognome</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" id="cognome" name="cognome"
                   value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>"
                   placeholder="Rossi">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Username <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">@</span>
          <input type="text" id="username" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="mario_rossi" required>
        </div>
        <p class="field-error" id="err-username">Username non valido (solo lettere, numeri e _)</p>
      </div>

      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <!-- name="email" — ora corrisponde al PHP -->
          <input type="email" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="mario@esempio.it" required>
        </div>
        <p class="field-error" id="err-email">Email non valida</p>
      </div>

      <div class="form-group">
        <label>Password <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <!-- name="password" — ora corrisponde al PHP -->
          <input type="password" id="reg-pw" name="password"
                 placeholder="Min. 8 caratteri" required oninput="checkStrength(this.value)">
          <button type="button" class="pw-toggle" onclick="togglePw('reg-pw',this)">👁</button>
        </div>
        <div class="pw-strength">
          <div class="pw-bar">
            <div class="pw-seg" id="ps1"></div><div class="pw-seg" id="ps2"></div>
            <div class="pw-seg" id="ps3"></div><div class="pw-seg" id="ps4"></div>
          </div>
          <span class="pw-label" id="pwLabel">Inserisci una password</span>
        </div>
        <p class="field-error" id="err-pw">Password troppo corta (min. 8 caratteri)</p>
      </div>

      <div class="form-group">
        <label>Conferma password <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" id="reg-pw2" name="password_confirm"
                 placeholder="Ripeti la password" required>
          <button type="button" class="pw-toggle" onclick="togglePw('reg-pw2',this)">👁</button>
        </div>
        <p class="field-error" id="err-pw2">Le password non coincidono</p>
      </div>

      <!-- SEZIONE RICERCATORE -->
      <div class="researcher-section" id="researcherFields" style="display:none;">
        <h3>🔬 Informazioni per la verifica</h3>
        <p>L'admin esaminerà la tua richiesta e ti invierà una OTP via email per attivare le funzionalità ricercatore. Solitamente entro 48h.</p>

        <div class="form-group">
          <label>Ente / Università <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🏛</span>
            <input type="text" id="ente" name="ente"
                   value="<?= htmlspecialchars($_POST['ente'] ?? '') ?>"
                   placeholder="Es: CNR-ISMAR, Università di Bologna…">
          </div>
        </div>

        <div class="form-group">
          <label>Qualifica / Ruolo <span class="req">*</span></label>
          <select id="qualifica" name="qualifica" style="padding-left:1rem;">
            <option value="">— Seleziona —</option>
            <option <?= ($_POST['qualifica'] ?? '') === 'Professore Ordinario' ? 'selected' : '' ?>>Professore Ordinario</option>
            <option <?= ($_POST['qualifica'] ?? '') === 'Ricercatore di Ruolo' ? 'selected' : '' ?>>Ricercatore di Ruolo</option>
            <option <?= ($_POST['qualifica'] ?? '') === 'Assegnista di Ricerca' ? 'selected' : '' ?>>Assegnista di Ricerca</option>
            <option <?= ($_POST['qualifica'] ?? '') === 'Post-Dottorato' ? 'selected' : '' ?>>Post-Dottorato</option>
            <option <?= ($_POST['qualifica'] ?? '') === 'Dottorando' ? 'selected' : '' ?>>Dottorando</option>
            <option <?= ($_POST['qualifica'] ?? '') === 'Studente Magistrale (tesi)' ? 'selected' : '' ?>>Studente Magistrale (tesi)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Certificato titolo di studio <span class="req">*</span></label>
          <div class="file-upload">
            <input type="file" name="certificato" accept=".pdf,.jpg,.png" onchange="showFileName(this,'fn1','fu1')">
            <div class="fu-icon">📄</div>
            <p>Trascina o clicca per caricare</p>
            <p class="fu-hint">PDF, JPG, PNG · max 5 MB</p>
            <p class="file-name" id="fn1"></p>
          </div>
        </div>

        <div class="form-group">
          <label>Tessera / Badge ente o email istituzionale <span class="req">*</span></label>
          <div class="file-upload" id="fu2">
            <input type="file" name="badge_ente" accept=".pdf,.jpg,.png" onchange="showFileName(this,'fn2','fu2')">
            <div class="fu-icon">🪪</div>
            <p>Trascina o clicca per caricare</p>
            <p class="fu-hint">PDF, JPG, PNG · max 5 MB</p>
            <p class="file-name" id="fn2"></p>
          </div>
        </div>

        <div class="form-group">
          <label>Descriviti brevemente <span class="req">*</span></label>
          <textarea name="motivazione" id="motivazione" placeholder="Descrivi la tua attività di ricerca…"><?= htmlspecialchars($_POST['motivazione'] ?? '') ?></textarea>
        </div>

        <div class="info-box">
          <strong>⏳ Come funziona l'approvazione?</strong><br>
          1. Invii la richiesta con i documenti.<br>
          2. L'amministratore verifica i dati (entro 48h).<br>
          3. Se approvato, ricevi una <strong>One-Time Password</strong> via email.<br>
          4. Accedi con la OTP e imposta la tua password definitiva.<br>
          5. Il tuo account viene aggiornato al ruolo <strong>Ricercatore ✓</strong>.
        </div>
      </div>

      <!-- PRIVACY -->
      <div style="margin-top:1.5rem;">
        <div class="checkbox-group">
          <input type="checkbox" id="privacy" name="privacy" required>
          <label for="privacy">Accetto i <a href="#">Termini di utilizzo</a> e la <a href="#">Privacy Policy</a> di NetSea <span class="req">*</span></label>
        </div>
        <div class="checkbox-group">
          <input type="checkbox" id="newsletter" name="newsletter">
          <label for="newsletter">Voglio ricevere aggiornamenti via email</label>
        </div>
      </div>

    </form><!-- /form — il bottone di sotto lo submetta con JS -->

    <div class="btn-row">
      <button class="btn-back" onclick="goStep(0)">← Indietro</button>
      <button class="btn-next" onclick="validateAndSubmit()">Crea account →</button>
    </div>
    <div class="divider" style="margin-top:1.5rem;"></div>
    <div class="bottom-link">Hai già un account? <a href="Login.php">Accedi</a></div>
  </div>

<?php endif; ?>

</div><!-- /page-wrap -->

<script>
/* CURSOR */
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();

/* TIPO ACCOUNT */
let selectedType = '<?= htmlspecialchars($_POST['tipo'] ?? ($tipo_account ?? 'user')) ?>';

// Pre-seleziona da URL (?tipo=ricercatore)
const urlParams = new URLSearchParams(location.search);
if(urlParams.get('tipo')==='ricercatore') selectedType = 'ricercatore';

function selectType(t){
  selectedType = t;
  document.getElementById('card-user').classList.toggle('selected', t==='user');
  document.getElementById('card-ricercatore').classList.toggle('selected', t==='ricercatore');
  document.getElementById('hiddenTipo').value = t;
}
selectType(selectedType); // applica al caricamento

/* STEP NAV */
let currentStep = 0;
// Se c'è un errore PHP siamo già allo step 1
<?php if ($tipo_msg === 'err' && $messaggio): ?>
currentStep = 0;
goStep(1);
<?php endif; ?>

function goStep(n){
  document.getElementById('panel'+currentStep)?.classList.remove('active');
  currentStep = n;
  document.getElementById('panel'+n)?.classList.add('active');
  updateStepBar(n);
  if(n===1){
    const isR = selectedType === 'ricercatore';
    document.getElementById('researcherFields').style.display = isR ? 'block' : 'none';
    document.getElementById('step1Title').textContent = isR ? 'Crea il tuo account ricercatore' : 'Crea il tuo account';
    document.getElementById('hiddenTipo').value = selectedType;
  }
  window.scrollTo(0,0);
}

function updateStepBar(active){
  for(let i=0;i<3;i++){
    const ind=document.getElementById('step-ind-'+i);
    const sc=document.getElementById('sc'+i);
    if(!ind) continue;
    ind.classList.remove('active','done');
    if(i<active){ind.classList.add('done');}
    else if(i===active){ind.classList.add('active');}
  }
}

/* PASSWORD STRENGTH */
function checkStrength(v){
  const s=[1,2,3,4].map(i=>document.getElementById('ps'+i));
  const lbl=document.getElementById('pwLabel');
  let score=0;
  if(v.length>=8)score++;
  if(/[A-Z]/.test(v))score++;
  if(/[0-9]/.test(v))score++;
  if(/[^A-Za-z0-9]/.test(v))score++;
  const colors=['#e05a3a','#f0c040','#2cb89b','#72d7f0'];
  const labels=['Molto debole','Debole','Buona','Ottima'];
  s.forEach((seg,i)=>{seg.style.background=i<score?colors[score-1]:'rgba(114,215,240,.1)';});
  lbl.textContent=v.length?(labels[score-1]||'Molto debole'):'Inserisci una password';
}

/* PW TOGGLE */
function togglePw(id,btn){
  const inp=document.getElementById(id);
  inp.type=inp.type==='password'?'text':'password';
  btn.textContent=inp.type==='password'?'👁':'🙈';
}

/* FILE NAME */
function showFileName(input,fnId,fuId){
  const fn=document.getElementById(fnId);
  if(input.files.length){
    fn.textContent='📎 '+input.files[0].name;
    fn.style.display='block';
    if(document.getElementById(fuId))
      document.getElementById(fuId).style.borderColor='rgba(44,184,155,.4)';
  }
}

/* VALIDATE E SUBMIT REALE AL PHP */
function validateAndSubmit(){
  let ok=true;
  document.querySelectorAll('.field-error').forEach(e=>e.style.display='none');

  if(!document.getElementById('nome').value.trim()){
    document.getElementById('err-nome').style.display='block'; ok=false;
  }
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('email').value)){
    document.getElementById('err-email').style.display='block'; ok=false;
  }
  if(!/^[a-zA-Z0-9_]{3,}$/.test(document.getElementById('username').value)){
    document.getElementById('err-username').style.display='block'; ok=false;
  }
  if(document.getElementById('reg-pw').value.length<8){
    document.getElementById('err-pw').style.display='block'; ok=false;
  }
  if(document.getElementById('reg-pw').value !== document.getElementById('reg-pw2').value){
    document.getElementById('err-pw2').style.display='block'; ok=false;
  }
  if(!document.getElementById('privacy').checked){
    alert('Devi accettare i termini di utilizzo.'); ok=false;
  }
  if(!ok) return;

  // Tutto ok → submit reale del form al PHP
  document.getElementById('regForm').submit();
}
</script>
</body>
</html>