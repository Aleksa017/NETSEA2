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