<?php
require 'config.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])) {
    header('Location: Login.php?redirect=crea_contenuto.php'); exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo      = trim($_POST['titolo']      ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $url         = trim($_POST['url']         ?? '');
    $tipo        = $_POST['tipo'] ?? '';

    if ($titolo === '')      $errors[] = "Il titolo è obbligatorio.";
    if ($descrizione === '') $errors[] = "La descrizione è obbligatoria.";
    if ($url === '')         $errors[] = "L'URL del contenuto è obbligatorio.";
    if (!in_array($tipo, ['foto','video','documento','ricerca']))
                             $errors[] = "Seleziona un tipo di contenuto.";

    if (empty($errors)) {
        $stmt = $connessione->prepare(
            "INSERT INTO media (titolo, descrizione, url, data_pub, id_utente, visualizzazioni)
             VALUES (?, ?, ?, CURDATE(), ?, 0)"
        );
        $stmt->execute([$titolo, $descrizione, $url, $_SESSION['id']]);
        header('Location: feed.php'); exit(); // dopo la pubblicazione vai dritto al feed
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pubblica Contenuto — NetSea</title>
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
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}
    .main{max-width:720px;margin:3rem auto;padding:0 2rem 5rem;}
    h1{font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--pearl);font-weight:400;margin-bottom:.5rem;}
    .subtitle{color:var(--muted);font-size:.9rem;margin-bottom:2rem;}
    .alert{padding:1rem 1.2rem;border-radius:10px;margin-bottom:1.5rem;}
    .alert-err{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.3);color:#e8836a;}

    /* TIPO SELECTOR — card cliccabili */
    .tipo-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.5rem;}
    .tipo-option{display:none;}
    .tipo-label{display:flex;flex-direction:column;align-items:center;gap:.5rem;padding:1rem .5rem;background:rgba(11,61,94,.25);border:1px solid rgba(114,215,240,.12);border-radius:12px;cursor:pointer;transition:all .2s;text-align:center;}
    .tipo-label .icon{font-size:2rem;}
    .tipo-label .lbl{font-size:.78rem;color:var(--muted);font-weight:500;}
    .tipo-option:checked + .tipo-label{background:rgba(27,159,212,.18);border-color:rgba(27,159,212,.5);color:var(--foam);}
    .tipo-option:checked + .tipo-label .lbl{color:var(--foam);}
    .tipo-label:hover{border-color:rgba(114,215,240,.3);background:rgba(11,61,94,.4);}

    .form-group{margin-bottom:1.5rem;}
    .form-group label{display:block;font-size:.82rem;font-weight:600;color:var(--foam);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.06em;}
    .hint{font-size:.78rem;color:var(--muted);margin-top:.35rem;}
    input[type=text],input[type=url],textarea{width:100%;padding:.8rem 1rem;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.15);border-radius:10px;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.95rem;outline:none;transition:border-color .2s,box-shadow .2s;}
    input:focus,textarea:focus{border-color:var(--wave);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
    textarea{resize:vertical;min-height:130px;line-height:1.7;}

    /* ANTEPRIMA URL */
    .preview-box{margin-top:.75rem;border-radius:10px;overflow:hidden;border:1px solid rgba(114,215,240,.15);display:none;max-height:280px;background:rgba(11,61,94,.3);}
    .preview-box img,.preview-box video{width:100%;max-height:280px;object-fit:cover;display:block;}

    .actions{display:flex;gap:.75rem;align-items:center;margin-top:2rem;}
    .btn-submit{padding:.9rem 2rem;background:var(--wave);color:var(--ink);border:none;border-radius:10px;font-family:'Outfit',sans-serif;font-weight:700;font-size:1rem;cursor:pointer;transition:all .2s;}
    .btn-submit:hover{background:var(--foam);transform:translateY(-1px);}
    .btn-sec{padding:.9rem 1.5rem;background:transparent;border:1px solid rgba(114,215,240,.2);color:var(--muted);border-radius:10px;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block;font-size:.95rem;}
    .btn-sec:hover{border-color:rgba(114,215,240,.4);color:var(--foam);}

    @media(max-width:500px){.tipo-grid{grid-template-columns:repeat(2,1fr);}}
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
  <a href="feed.php" class="nav-back">← Feed</a>
</nav>

<div class="main">
  <h1>✨ Pubblica un Contenuto</h1>
  <p class="subtitle">Il contenuto apparirà nel feed marino — visibile a tutti gli utenti.</p>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-err"><?php foreach($errors as $e) echo "❌ ".htmlspecialchars($e)."<br>"; ?></div>
  <?php endif; ?>

  <form method="POST" id="contentForm">

    <!-- TIPO -->
    <div class="form-group">
      <label>Tipo di contenuto *</label>
      <div class="tipo-grid">
        <input class="tipo-option" type="radio" name="tipo" id="t-foto"    value="foto"    required>
        <label class="tipo-label" for="t-foto"><span class="icon">📸</span><span class="lbl">Foto</span></label>

        <input class="tipo-option" type="radio" name="tipo" id="t-video"   value="video">
        <label class="tipo-label" for="t-video"><span class="icon">📹</span><span class="lbl">Video</span></label>

        <input class="tipo-option" type="radio" name="tipo" id="t-doc"     value="documento">
        <label class="tipo-label" for="t-doc"><span class="icon">📄</span><span class="lbl">Documento</span></label>

        <input class="tipo-option" type="radio" name="tipo" id="t-ricerca" value="ricerca">
        <label class="tipo-label" for="t-ricerca"><span class="icon">🔬</span><span class="lbl">Ricerca</span></label>
      </div>
    </div>

    <!-- TITOLO -->
    <div class="form-group">
      <label>Titolo *</label>
      <input type="text" name="titolo" value="<?= htmlspecialchars($_POST['titolo'] ?? '') ?>"
             placeholder="Es: Avvistamento raro di foca monaca a Lampedusa" required>
    </div>

    <!-- DESCRIZIONE -->
    <div class="form-group">
      <label>Descrizione *</label>
      <textarea name="descrizione" placeholder="Descrivi il contenuto, il contesto, dove e quando è stato ripreso…"><?= htmlspecialchars($_POST['descrizione'] ?? '') ?></textarea>
    </div>

    <!-- URL con anteprima -->
    <div class="form-group">
      <label>URL del contenuto *</label>
      <input type="url" name="url" id="urlInput"
             value="<?= htmlspecialchars($_POST['url'] ?? '') ?>"
             placeholder="https://esempio.com/foto.jpg  oppure  video.mp4">
      <p class="hint">Incolla il link diretto alla foto (jpg, png, webp) o al video (mp4). Apparirà come sfondo nel feed.</p>
      <div class="preview-box" id="previewBox">
        <img id="previewImg" src="" alt="anteprima" style="display:none;">
        <video id="previewVid" src="" controls style="display:none;"></video>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn-submit">📤 Pubblica nel feed</button>
      <a href="feed.php" class="btn-sec">Annulla</a>
    </div>
  </form>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();

// ANTEPRIMA URL
const urlInp = document.getElementById('urlInput');
const box    = document.getElementById('previewBox');
const img    = document.getElementById('previewImg');
const vid    = document.getElementById('previewVid');

function aggiornaPreview() {
  const url = urlInp.value.trim();
  if (!url) { box.style.display='none'; return; }
  const isVid = /\.(mp4|webm|ogg)$/i.test(url);
  const isImg = /\.(jpg|jpeg|png|gif|webp)$/i.test(url);
  if (isVid) {
    img.style.display='none'; vid.style.display='block';
    vid.src=url; box.style.display='block';
  } else if (isImg) {
    vid.style.display='none'; img.style.display='block';
    img.src=url;
    img.onerror=()=>{ box.style.display='none'; };
    box.style.display='block';
  } else {
    box.style.display='none';
  }
}
urlInp.addEventListener('input', aggiornaPreview);
// se c'è già un valore (errore form) mostra subito
aggiornaPreview();

// Auto-seleziona tipo da URL
urlInp.addEventListener('input', () => {
  const url = urlInp.value.trim();
  if (/\.(mp4|webm|ogg)$/i.test(url)) document.getElementById('t-video').checked = true;
  else if (/\.(jpg|jpeg|png|gif|webp)$/i.test(url)) document.getElementById('t-foto').checked = true;
});
</script>
</body>
</html>