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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--coral:#e05a3a;--kelp:#2cb89b;--text:#c5e4f5;--muted:#5d9ab8;}
    html,body{height:100%;}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);min-height:100vh;cursor:none;display:flex;flex-direction:column;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{height:64px;display:flex;align-items:center;padding:0 2.5rem;gap:1rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);flex-shrink:0;}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}
    .page{flex:1;display:grid;grid-template-columns:1fr 1fr;min-height:calc(100vh - 64px);}
    .left-panel{background:linear-gradient(160deg,#041828 0%,#0a3a5a 40%,#062040 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:4rem;position:relative;overflow:hidden;}
    .left-panel::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 50% 40%,rgba(27,159,212,.18) 0%,transparent 70%);}
    .ocean-art{position:relative;z-index:1;text-align:center;}
    .ocean-art .big-icon{font-size:6rem;display:block;margin-bottom:1.5rem;animation:float 5s ease-in-out infinite;}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
    .ocean-art h2{font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--pearl);font-weight:400;line-height:1.2;margin-bottom:1rem;}
    .ocean-art h2 em{font-style:italic;color:var(--foam);}
    .ocean-art p{color:var(--muted);font-size:.95rem;line-height:1.7;max-width:340px;margin:0 auto;}
    .bubbles{position:absolute;inset:0;pointer-events:none;overflow:hidden;}
    .bubble{position:absolute;border-radius:50%;background:rgba(114,215,240,.08);border:1px solid rgba(114,215,240,.15);animation:rise linear infinite;}
    @keyframes rise{from{transform:translateY(100vh) scale(0);opacity:0}20%{opacity:1}to{transform:translateY(-10vh) scale(1.2);opacity:0}}
    .right-panel{display:flex;align-items:center;justify-content:center;padding:3rem 2rem;background:rgba(7,30,51,.2);}
    .form-card{width:100%;max-width:420px;}
    .form-card-header{margin-bottom:2rem;}
    .eyebrow{font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;color:var(--wave);margin-bottom:.5rem;}
    .form-card-header h1{font-family:'Cormorant Garamond',serif;font-size:2rem;color:var(--pearl);font-weight:400;margin-bottom:.4rem;}
    .form-card-header p{color:var(--muted);font-size:.875rem;}
    .form-group{margin-bottom:1.1rem;}
    .form-group label{display:block;font-size:.8rem;font-weight:500;color:var(--muted);margin-bottom:.45rem;}
    .input-wrap{position:relative;}
    .input-icon{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);font-size:.95rem;pointer-events:none;}
    input[type=text],input[type=password]{width:100%;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.15);border-radius:10px;padding:.7rem 1rem .7rem 2.6rem;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s,background .2s,box-shadow .2s;}
    input::placeholder{color:var(--muted);}
    input:focus{border-color:var(--wave);background:rgba(11,61,94,.5);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
    .pw-toggle{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:.9rem;}
    .field-error{font-size:.75rem;color:#e8836a;margin-top:.35rem;display:none;}
    .form-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;}
    .checkbox-wrap{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--muted);cursor:pointer;}
    .checkbox-wrap input{width:auto;padding:0;}
    .forgot{font-size:.82rem;color:var(--wave);text-decoration:none;}
    .btn-submit{width:100%;padding:.85rem;border-radius:10px;background:var(--wave);color:var(--ink);font-family:'Outfit',sans-serif;font-weight:600;font-size:1rem;border:none;cursor:pointer;transition:background .2s,transform .15s;display:flex;align-items:center;justify-content:center;gap:.6rem;}
    .btn-submit:hover{background:var(--foam);transform:translateY(-1px);}
    .divider{display:flex;align-items:center;gap:.75rem;margin:1.5rem 0;color:var(--muted);font-size:.78rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(114,215,240,.1);}
    .alert{padding:.8rem 1rem;border-radius:8px;font-size:.85rem;margin-bottom:1rem;}
    .alert-err{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.25);color:#e8836a;}
    .bottom-link{text-align:center;margin-top:1.5rem;font-size:.875rem;color:var(--muted);}
    .bottom-link a{color:var(--wave);text-decoration:none;font-weight:500;}
    .researcher-cta{background:linear-gradient(135deg,rgba(44,184,155,.08),rgba(27,159,212,.06));border:1px solid rgba(44,184,155,.2);border-radius:10px;padding:1rem;margin-top:1.25rem;display:flex;align-items:center;gap:.85rem;text-decoration:none;transition:border-color .2s;}
    .researcher-cta:hover{border-color:rgba(44,184,155,.4);}
    .researcher-cta .rc-icon{font-size:1.5rem;flex-shrink:0;}
    .researcher-cta .rc-text h4{color:var(--kelp);font-size:.85rem;font-weight:600;margin-bottom:.15rem;}
    .researcher-cta .rc-text p{color:var(--muted);font-size:.75rem;line-height:1.4;}
    .demo-pill{background:rgba(27,159,212,.07);border:1px solid rgba(27,159,212,.15);border-radius:8px;padding:.85rem 1rem;font-size:.78rem;color:var(--muted);margin-top:1.5rem;line-height:1.6;}
    .demo-pill strong{color:var(--wave);}
    .demo-pill code{background:rgba(114,215,240,.1);border-radius:4px;padding:.1rem .4rem;color:var(--foam);}
    .demo-account{cursor:pointer;color:var(--wave);text-decoration:underline;margin-right:.5rem;}
    @media(max-width:768px){.page{grid-template-columns:1fr;}.left-panel{display:none;}}
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