<?php
require 'config.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])) {
    header('Location: Login.php?redirect=crea_news.php'); exit();
}

// Trova id_ricercatore dell'utente loggato
$stR = $connessione->prepare("SELECT id_ricercatore FROM ricercatore WHERE id_ricercatore = ?");
$stR->execute([$_SESSION['id']]);
$ricercatore = $stR->fetch();
if (!$ricercatore && ($_SESSION['ruolo'] ?? '') !== 'admin') {
    die("Profilo ricercatore non trovato. Contatta l'amministratore.");
}
$id_ric = $ricercatore['id_ricercatore'] ?? $_SESSION['id'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo    = trim($_POST['titolo']    ?? '');
    $contenuto = trim($_POST['contenuto'] ?? '');
    $copertina = trim($_POST['copertina'] ?? ''); // URL immagine/video copertina

    if ($titolo === '')    $errors[] = "Il titolo è obbligatorio.";
    if ($contenuto === '') $errors[] = "Il contenuto è obbligatorio.";

    if (empty($errors)) {
        $stmt = $connessione->prepare(
            "INSERT INTO news (titolo, contenuto, copertina, data_pub, id_ricercatore, visualizzazioni)
             VALUES (?, ?, ?, CURDATE(), ?, 0)"
        );
        $stmt->execute([$titolo, $contenuto, $copertina ?: null, $id_ric]);
        header('Location: crea_news.php?ok=1'); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pubblica News — NetSea</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);cursor:none;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{height:64px;display:flex;align-items:center;padding:0 2.5rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;}
    .nav-back:hover{color:var(--foam);}
    .main{max-width:750px;margin:3rem auto;padding:0 2rem 5rem;}
    h1{font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--pearl);font-weight:400;margin-bottom:2rem;}
    .alert{padding:1rem 1.2rem;border-radius:10px;margin-bottom:1.5rem;}
    .alert-ok{background:rgba(44,184,155,.1);border:1px solid rgba(44,184,155,.3);color:#3dd4ae;}
    .alert-err{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.3);color:#e8836a;}
    .form-group{margin-bottom:1.5rem;}
    .form-group label{display:block;font-size:.82rem;font-weight:600;color:var(--foam);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.06em;}
    .hint{font-size:.78rem;color:var(--muted);margin-top:.35rem;}
    input[type=text],input[type=url],textarea{width:100%;padding:.8rem 1rem;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.15);border-radius:10px;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.95rem;outline:none;transition:border-color .2s,box-shadow .2s;}
    input:focus,textarea:focus{border-color:var(--wave);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
    textarea{resize:vertical;min-height:200px;line-height:1.7;}
    /* PREVIEW COPERTINA */
    .preview-box{margin-top:.75rem;border-radius:10px;overflow:hidden;border:1px solid rgba(114,215,240,.15);display:none;max-height:300px;}
    .preview-box img,.preview-box video{width:100%;max-height:300px;object-fit:cover;display:block;}
    /* TOOLBAR EDITOR */
    .toolbar{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.5rem;}
    .toolbar button{padding:.3rem .7rem;background:rgba(11,61,94,.4);border:1px solid rgba(114,215,240,.15);border-radius:6px;color:var(--muted);cursor:pointer;font-size:.82rem;transition:all .2s;}
    .toolbar button:hover{border-color:rgba(114,215,240,.3);color:var(--foam);}
    .btn-submit{padding:.9rem 2rem;background:var(--wave);color:var(--ink);border:none;border-radius:10px;font-family:'Outfit',sans-serif;font-weight:700;font-size:1rem;cursor:pointer;transition:all .2s;}
    .btn-submit:hover{background:var(--foam);transform:translateY(-1px);}
    .btn-sec{padding:.9rem 1.5rem;background:transparent;border:1px solid rgba(114,215,240,.2);color:var(--muted);border-radius:10px;font-family:'Outfit',sans-serif;cursor:pointer;margin-left:.75rem;transition:all .2s;text-decoration:none;display:inline-block;}
    .btn-sec:hover{border-color:rgba(114,215,240,.35);color:var(--foam);}
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
  <a href="index.php" class="nav-back">← Home</a>
</nav>

<div class="main">
  <h1>📰 Pubblica una News</h1>

  <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-ok">✅ News pubblicata con successo!</div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-err"><?php foreach($errors as $e) echo "❌ ".htmlspecialchars($e)."<br>"; ?></div>
  <?php endif; ?>

  <form method="POST" id="newsForm">

    <div class="form-group">
      <label>Titolo *</label>
      <input type="text" name="titolo" value="<?= htmlspecialchars($_POST['titolo'] ?? '') ?>"
             placeholder="Es: Nuovo studio sui delfini del Mediterraneo" required>
    </div>

    <div class="form-group">
      <label>Copertina — URL immagine o video</label>
      <input type="url" name="copertina" id="copertinaInput"
             value="<?= htmlspecialchars($_POST['copertina'] ?? '') ?>"
             placeholder="https://esempio.com/foto.jpg  oppure  .../video.mp4">
      <p class="hint">Incolla un link a una foto (jpg, png, webp) o video (mp4, youtube). Lascia vuoto se non hai una copertina.</p>
      <div class="preview-box" id="previewBox">
        <img id="previewImg" src="" alt="anteprima">
        <video id="previewVid" src="" controls style="display:none;"></video>
      </div>
    </div>

    <div class="form-group">
      <label>Contenuto *</label>
      <div class="toolbar">
        <button type="button" onclick="wrap('**','**')"><b>G</b></button>
        <button type="button" onclick="wrap('_','_')"><i>C</i></button>
        <button type="button" onclick="inserisci('\n\n--- \n\n')">— Separatore</button>
        <button type="button" onclick="inserisci('\n🔬 ')">🔬</button>
        <button type="button" onclick="inserisci('\n📍 ')">📍</button>
      </div>
      <textarea name="contenuto" id="contenuto"
                placeholder="Scrivi il contenuto della notizia..."><?= htmlspecialchars($_POST['contenuto'] ?? '') ?></textarea>
      <p class="hint">Puoi usare **grassetto** e _corsivo_. I paragrafi si separano con una riga vuota.</p>
    </div>

    <div>
      <button type="submit" class="btn-submit">📤 Pubblica</button>
      <a href="index.php" class="btn-sec">Annulla</a>
    </div>
  </form>
</div>

<script>
// CURSOR
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();

// PREVIEW COPERTINA
const inp = document.getElementById('copertinaInput');
const box = document.getElementById('previewBox');
const img = document.getElementById('previewImg');
const vid = document.getElementById('previewVid');

inp.addEventListener('input', () => {
  const url = inp.value.trim();
  if (!url) { box.style.display='none'; return; }
  const isVideo = /\.(mp4|webm|ogg)$/i.test(url) || url.includes('youtube') || url.includes('youtu.be');
  if (isVideo) {
    img.style.display='none'; vid.style.display='block';
    vid.src = url; box.style.display='block';
  } else {
    vid.style.display='none'; img.style.display='block';
    img.src = url; img.onerror = () => box.style.display='none';
    box.style.display='block';
  }
});

// TOOLBAR EDITOR
function wrap(a, b) {
  const ta = document.getElementById('contenuto');
  const s = ta.selectionStart, e = ta.selectionEnd;
  const sel = ta.value.slice(s, e);
  ta.value = ta.value.slice(0,s) + a + sel + b + ta.value.slice(e);
  ta.focus(); ta.selectionStart = s+a.length; ta.selectionEnd = e+a.length;
}
function inserisci(testo) {
  const ta = document.getElementById('contenuto');
  const s = ta.selectionStart;
  ta.value = ta.value.slice(0,s) + testo + ta.value.slice(s);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + testo.length;
}
</script>
</body>
</html>