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
    $copertina = trim($_POST['copertina'] ?? '');

    // Upload file copertina (ha precedenza sull'URL)
    if (!empty($_FILES['copertina_file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['copertina_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $dir = __DIR__ . '/uploads/news/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = 'news_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['copertina_file']['tmp_name'], $dir . $fname)) {
                $copertina = 'uploads/news/' . $fname;
            }
        } else {
            $errors[] = "Formato file non supportato. Usa jpg, png, webp.";
        }
    }

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
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo"><img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));"></a>
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

  <form method="POST" enctype="multipart/form-data" id="newsForm">

    <div class="form-group">
      <label>Titolo *</label>
      <input type="text" name="titolo" value="<?= htmlspecialchars($_POST['titolo'] ?? '') ?>"
             placeholder="Es: Nuovo studio sui delfini del Mediterraneo" required>
    </div>

    <div class="form-group">
      <label>Copertina</label>
      <div style="display:flex;flex-direction:column;gap:.75rem;">
        <!-- Upload da file -->
        <div>
          <label style="font-size:.78rem;color:var(--muted);margin-bottom:.3rem;display:block;">📁 Carica un file</label>
          <input type="file" name="copertina_file" id="copertinaFile"
                 accept="image/jpeg,image/png,image/webp,image/gif"
                 style="color:var(--pearl);font-size:.85rem;">
        </div>
        <!-- Oppure URL -->
        <div>
          <label style="font-size:.78rem;color:var(--muted);margin-bottom:.3rem;display:block;">🔗 Oppure incolla un URL</label>
          <input type="url" name="copertina" id="copertinaInput"
                 value="<?= htmlspecialchars($_POST['copertina'] ?? '') ?>"
                 placeholder="https://esempio.com/foto.jpg">
        </div>
      </div>
      <p class="hint" style="margin-top:.5rem;">Il file ha precedenza sull'URL se entrambi sono inseriti.</p>
      <div class="preview-box" id="previewBox" style="margin-top:.75rem;">
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

// Preview da file locale
document.getElementById('copertinaFile').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    img.style.display='block'; vid.style.display='none';
    img.src = e.target.result; box.style.display='block';
    // Svuota URL se si sceglie file
    inp.value = '';
  };
  reader.readAsDataURL(file);
});

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