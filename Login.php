<?php
require 'config.php';

// possibili URL di ritorno (solo percorsi relativi)
$redirect = '';
if (!empty($_GET['redirect'])) {
    // semplice sanitizzazione: permetti solo caratteri comuni e slash, punto e query
    $redirect = preg_replace('/[^A-Za-z0-9_\.\-\?=\&\/]/', '', $_GET['redirect']);
}

$errore_login = "";

// se già loggato e ho redirect, mandami subito là
if (isset($_SESSION['id']) && $redirect) {
    header('Location: ' . $redirect);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $errore_login = "Compila tutti i campi.";
    } else {
        $query = $connessione->prepare(
            "SELECT * FROM utente WHERE email = ? OR username = ? LIMIT 1"
        );
        $query->execute([$email, $email]);
        $utente = $query->fetch();

        if ($utente && password_verify($password, $utente["password_hash"])) {
            $_SESSION["id"]      = $utente["id_utente"];
            $_SESSION["nome"]    = $utente["nome"];
            $_SESSION["cognome"] = $utente["cognome"];
            $_SESSION["email"]   = $utente["email"];
            $_SESSION["is_admin"]= $utente["is_admin"];

            // Controlla se è ricercatore (esiste in tabella ricercatore)
            $st = $connessione->prepare("SELECT 1 FROM ricercatore WHERE id_ricercatore = ?");
            $st->execute([$utente["id_utente"]]);

            if ($utente["is_admin"] == 1) {
                $_SESSION["ruolo"] = "admin";
            } elseif ($st->fetch()) {
                $_SESSION["ruolo"] = "ricercatore";
            } else {
                $_SESSION["ruolo"] = "utente";
            }

            if ($redirect) {
                header('Location: ' . $redirect);
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $errore_login = "Email o password errati.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accedi — NetSea</title>
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
  <a href="index.php" class="nav-back">← Torna alla home</a>
</nav>
<div class="page">
  <div class="left-panel">
    <div class="bubbles" id="bubbles"></div>
    <div class="ocean-art">
      <span class="big-icon">🌊</span>
      <h2>Esplora gli abissi<br>del <em>nostro mare</em></h2>
      <p>Accedi per feed personalizzato, donazioni e — se sei ricercatore verificato — per pubblicare contenuti scientifici.</p>
    </div>
  </div>
  <div class="right-panel">
    <div class="form-card">
      <div class="form-card-header">
        <p class="eyebrow">🔐 Area riservata</p>
        <h1>Bentornato</h1>
        <p>Accedi al tuo account NetSea</p>
      </div>
      <?php if ($errore_login): ?>
        <div class="alert alert-err">❌ <?= htmlspecialchars($errore_login) ?></div>
      <?php endif; ?>
      <form method="POST" action="Login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>" novalidate id="loginForm">
        <div class="form-group">
          <label for="email">Email o Username</label>
          <div class="input-wrap">
            <span class="input-icon">✉️</span>
            <input type="text" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="mario.rossi@esempio.it" autocomplete="email">
          </div>
          <p class="field-error" id="err-email">Campo obbligatorio</p>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" id="password" name="password" placeholder="••••••••">
            <button type="button" class="pw-toggle" onclick="togglePw()">👁</button>
          </div>
          <p class="field-error" id="err-password">Campo obbligatorio</p>
        </div>
        <div class="form-row">
          <label class="checkbox-wrap"><input type="checkbox" name="ricordami"> Ricordami</label>
          <a href="#" class="forgot">Password dimenticata?</a>
        </div>
        <button type="submit" class="btn-submit">Accedi →</button>
      </form>
      <div class="divider">oppure</div>
      <div class="bottom-link">Non hai un account? <a href="Registrazione.php">Registrati gratis</a></div>
      <a href="Registrazione.php?tipo=ricercatore" class="researcher-cta">
        <span class="rc-icon">🔬</span>
        <div class="rc-text">
          <h4>Sei un ricercatore?</h4>
          <p>Richiedi l'accesso verificato per pubblicare contenuti scientifici</p>
        </div>
      </a>
      <div class="demo-pill">
        <strong>🧪 Account demo:</strong><br>
        <span class="demo-account" onclick="fillDemo('ricerca@demo.it')">Ricercatore</span>
        <br><small>password: <code>demo</code></small>
      </div>
    </div>
  </div>
</div>
<script>
const cursor=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cursor.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();
const bc=document.getElementById('bubbles');
for(let i=0;i<14;i++){const b=document.createElement('div'),s=Math.random()*60+20;b.className='bubble';b.style.cssText=`width:${s}px;height:${s}px;left:${Math.random()*100}%;bottom:-${s}px;animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*10}s`;bc.appendChild(b);}
function togglePw(){const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';}
function fillDemo(e){document.getElementById('email').value=e;document.getElementById('password').value='demo';}
document.getElementById('loginForm').addEventListener('submit',function(e){
  let ok=true;
  document.querySelectorAll('.field-error').forEach(el=>el.style.display='none');
  if(!document.getElementById('email').value.trim()){document.getElementById('err-email').style.display='block';ok=false;}
  if(!document.getElementById('password').value){document.getElementById('err-password').style.display='block';ok=false;}
  if(!ok)e.preventDefault();
});
</script>
</body>
</html>