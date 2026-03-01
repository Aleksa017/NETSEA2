<?php
require 'config.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])) {
    header('Location: Login.php?redirect=crea_contenuto.php'); exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo      = trim($_POST['titolo']      ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $tipo        = $_POST['tipo'] ?? '';

    if ($titolo === '')      $errors[] = "Il titolo è obbligatorio.";
    if ($descrizione === '') $errors[] = "La descrizione è obbligatoria.";
    if (!in_array($tipo, ['foto','video','documento','ricerca']))
                             $errors[] = "Seleziona un tipo di contenuto.";

    $url_finale = '';
    $ha_file = isset($_FILES['media_file']) && $_FILES['media_file']['error'] !== UPLOAD_ERR_NO_FILE;
    $ha_url  = trim($_POST['url'] ?? '') !== '';

    if (!$ha_file && !$ha_url) {
        $errors[] = "Carica un file oppure inserisci un URL.";
    }

    // ── Gestione upload file ──────────────────────────────
    if ($ha_file && empty($errors)) {
        $file = $_FILES['media_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Errore upload (codice " . $file['error'] . "). Controlla che il file non superi i limiti.";
        } else {
            // Estensioni consentite per tipo
            $ext_ok = [
                'foto'      => ['jpg','jpeg','png','gif','webp'],
                'video'     => ['mp4','webm','mov'],
                'documento' => ['pdf'],
                'ricerca'   => ['pdf','jpg','jpeg','png'],
            ];
            $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $consentiti = $ext_ok[$tipo] ?? [];

            if (!in_array($ext, $consentiti)) {
                $errors[] = "Formato .$ext non valido per \"$tipo\". Usa: " . implode(', ', $consentiti) . ".";
            } else {
                // Limite dimensione: 500MB video, 20MB resto
                $max_bytes = ($tipo === 'video') ? 500 * 1024 * 1024 : 20 * 1024 * 1024;
                if ($file['size'] > $max_bytes) {
                    $errors[] = "File troppo grande. Massimo " . ($tipo === 'video' ? '500' : '20') . " MB.";
                } else {
                    $dir = __DIR__ . '/uploads/media/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);

                    $nome_safe = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                    $nome_file = time() . '_' . substr($nome_safe, 0, 40) . '.' . $ext;
                    $dest      = $dir . $nome_file;

                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $url_finale = 'uploads/media/' . $nome_file;
                    } else {
                        $errors[] = "Impossibile salvare il file. Controlla i permessi della cartella uploads/media/.";
                    }
                }
            }
        }
    } elseif ($ha_url && empty($errors)) {
        $url_finale = trim($_POST['url']);
    }

    // ── Salva nel DB ──────────────────────────────────────
    if (empty($errors) && $url_finale !== '') {
        $stmt = $connessione->prepare(
            "INSERT INTO media (titolo, descrizione, url, data_pub, id_utente, visualizzazioni)
             VALUES (?, ?, ?, CURDATE(), ?, 0)"
        );
        $stmt->execute([$titolo, $descrizione, $url_finale, $_SESSION['id']]);
        header('Location: feed.php'); exit();
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
  <style>
    .upload-area {
      border: 2px dashed rgba(114,215,240,.2);
      border-radius: 14px;
      padding: 2.5rem 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      background: rgba(11,61,94,.15);
      position: relative;
    }
    .upload-area:hover, .upload-area.drag {
      border-color: var(--wave);
      background: rgba(27,159,212,.07);
    }
    .upload-area input[type=file] {
      position: absolute; inset: 0; opacity: 0;
      cursor: pointer; width: 100%; height: 100%;
    }
    .upload-icon { font-size: 3rem; margin-bottom: .5rem; display: block; }
    .upload-area h3 { color: var(--pearl); font-size: 1rem; margin: 0 0 .3rem; }
    .upload-area .hint-fmt { font-size: .78rem; color: rgba(114,215,240,.45); margin-top: .4rem; }

    .file-chosen {
      display: none; margin-top: .75rem;
      padding: .6rem 1rem;
      background: rgba(44,184,155,.08);
      border: 1px solid rgba(44,184,155,.2);
      border-radius: 8px; font-size: .82rem;
      color: var(--kelp);
      align-items: center; gap: .6rem;
    }
    .file-chosen span.size { margin-left: auto; color: var(--muted); }

    .sep {
      display: flex; align-items: center; gap: .75rem;
      color: var(--muted); font-size: .78rem; margin: 1.5rem 0;
    }
    .sep::before, .sep::after {
      content: ''; flex: 1; height: 1px;
      background: rgba(114,215,240,.1);
    }

    .warn-size {
      display: none; margin-top: .5rem;
      padding: .5rem .8rem;
      background: rgba(220,80,80,.07);
      border: 1px solid rgba(220,80,80,.2);
      border-radius: 8px; font-size: .8rem; color: #e07070;
    }
  </style>
</head>
<body>
<div class="cursor" id="cursor" style="opacity:0;"></div>
<div class="cursor-ring" id="cursorRing" style="opacity:0;"></div>

<nav>
  <a href="index.php" class="nav-logo"><img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));"></a>
  <a href="feed.php" class="nav-back">← Feed</a>
</nav>

<div class="main">
  <h1>✨ Pubblica un Contenuto</h1>
  <p class="subtitle">Il contenuto apparirà nel feed marino — visibile a tutti gli utenti.</p>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-err">
      <?php foreach($errors as $e) echo "❌ " . htmlspecialchars($e) . "<br>"; ?>
    </div>
  <?php endif; ?>

  <!-- enctype OBBLIGATORIO per upload file -->
  <form method="POST" enctype="multipart/form-data" id="contentForm">

    <!-- TIPO -->
    <div class="form-group">
      <label>Tipo di contenuto *</label>
      <div class="tipo-grid">
        <input class="tipo-option" type="radio" name="tipo" id="t-foto"    value="foto"      required <?= ($_POST['tipo']??'')==='foto'      ?'checked':'' ?>>
        <label class="tipo-label" for="t-foto"><span class="icon">📸</span><span class="lbl">Foto</span></label>

        <input class="tipo-option" type="radio" name="tipo" id="t-video"   value="video"     <?= ($_POST['tipo']??'')==='video'     ?'checked':'' ?>>
        <label class="tipo-label" for="t-video"><span class="icon">🎬</span><span class="lbl">Video</span></label>

        <input class="tipo-option" type="radio" name="tipo" id="t-doc"     value="documento" <?= ($_POST['tipo']??'')==='documento' ?'checked':'' ?>>
        <label class="tipo-label" for="t-doc"><span class="icon">📄</span><span class="lbl">Documento</span></label>

        <input class="tipo-option" type="radio" name="tipo" id="t-ricerca" value="ricerca"   <?= ($_POST['tipo']??'')==='ricerca'   ?'checked':'' ?>>
        <label class="tipo-label" for="t-ricerca"><span class="icon">🔬</span><span class="lbl">Ricerca</span></label>
      </div>
    </div>

    <!-- TITOLO -->
    <div class="form-group">
      <label>Titolo *</label>
      <input type="text" name="titolo"
             value="<?= htmlspecialchars($_POST['titolo'] ?? '') ?>"
             placeholder="Es: Avvistamento raro di foca monaca a Lampedusa" required>
    </div>

    <!-- DESCRIZIONE -->
    <div class="form-group">
      <label>Descrizione *</label>
      <textarea name="descrizione"
                placeholder="Descrivi il contenuto, il contesto, dove e quando è stato ripreso…"><?= htmlspecialchars($_POST['descrizione'] ?? '') ?></textarea>
    </div>

    <!-- UPLOAD FILE -->
    <div class="form-group">
      <label>Carica un file dal tuo dispositivo</label>

      <div class="upload-area" id="uploadArea">
        <input type="file" name="media_file" id="fileInput">
        <span class="upload-icon" id="uploadIcon">☁️</span>
        <h3>Trascina il file qui, oppure clicca per selezionarlo</h3>
        <p class="hint">Video scaricati da TikTok, foto subacquee, PDF di ricerca…</p>
        <p class="hint-fmt" id="fmtHint">Seleziona prima il tipo per vedere i formati accettati</p>
      </div>

      <div class="file-chosen" id="fileChosen">
        <span>📎</span>
        <span id="fcName"></span>
        <span class="size" id="fcSize"></span>
      </div>
      <div class="warn-size" id="warnSize"></div>
    </div>

    <!-- OPPURE URL -->
    <div class="sep">oppure inserisci un URL esterno</div>

    <div class="form-group">
      <input type="url" name="url" id="urlInput"
             value="<?= htmlspecialchars($_POST['url'] ?? '') ?>"
             placeholder="https://esempio.com/video.mp4">
      <p class="hint">Solo se non hai il file fisico — link diretto a jpg, png, mp4.</p>
    </div>

    <!-- ANTEPRIMA -->
    <div id="previewBox" style="display:none; margin-top:1rem; border-radius:12px; overflow:hidden;">
      <img id="previewImg" src="" alt="" style="display:none; width:100%; max-height:300px; object-fit:cover;">
      <video id="previewVid" src="" controls muted style="display:none; width:100%; max-height:300px;"></video>
    </div>

    <div class="actions" style="margin-top:2rem;">
      <button type="submit" class="btn-submit" id="submitBtn">📤 Pubblica nel feed</button>
      <a href="feed.php" class="btn-sec">Annulla</a>
    </div>
  </form>
</div>

<script>
// Cursore
const cur = document.getElementById('cursor'), ring = document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove', e => {
  mx=e.clientX; my=e.clientY;
  cur.style.left=mx+'px'; cur.style.top=my+'px';
  cur.style.opacity='1'; ring.style.opacity='1';
});
(function loop(){ rx+=(mx-rx)*.12; ry+=(my-ry)*.12;
  ring.style.left=rx+'px'; ring.style.top=ry+'px';
  requestAnimationFrame(loop); })();

// Formati per tipo
const formati = {
  foto:      { accept:'image/*',                          label:'JPG, PNG, WEBP, GIF — max 20 MB',  maxMB:20  },
  video:     { accept:'video/mp4,video/webm,.mov,.mp4',   label:'MP4, WEBM, MOV — max 500 MB',      maxMB:500 },
  documento: { accept:'.pdf',                             label:'PDF — max 20 MB',                  maxMB:20  },
  ricerca:   { accept:'.pdf,image/*',                     label:'PDF, JPG, PNG — max 20 MB',        maxMB:20  },
};
const icons = { foto:'🖼️', video:'🎬', documento:'📄', ricerca:'🔬' };

const fileInput  = document.getElementById('fileInput');
const fmtHint    = document.getElementById('fmtHint');
const uploadIcon = document.getElementById('uploadIcon');

function tipoSelezionato() {
  const r = document.querySelector('input[name=tipo]:checked');
  return r ? r.value : null;
}

document.querySelectorAll('input[name=tipo]').forEach(r => r.addEventListener('change', () => {
  const t = formati[r.value];
  if (!t) return;
  fileInput.accept  = t.accept;
  fmtHint.textContent = t.label;
  uploadIcon.textContent = icons[r.value] || '☁️';
}));

// Selezione file → anteprima
const fcName    = document.getElementById('fcName');
const fcSize    = document.getElementById('fcSize');
const fileChosen= document.getElementById('fileChosen');
const warnSize  = document.getElementById('warnSize');
const previewBox= document.getElementById('previewBox');
const previewImg= document.getElementById('previewImg');
const previewVid= document.getElementById('previewVid');

fileInput.addEventListener('change', function() {
  const f = this.files[0]; if (!f) return;

  const tipo  = tipoSelezionato();
  const maxMB = tipo ? (formati[tipo]?.maxMB ?? 20) : 20;
  const sizeMB = (f.size / 1024 / 1024).toFixed(1);

  fcName.textContent = f.name;
  fcSize.textContent = sizeMB + ' MB';
  fileChosen.style.display = 'flex';

  if (parseFloat(sizeMB) > maxMB) {
    warnSize.textContent = `⚠️ File troppo grande: ${sizeMB} MB. Massimo ${maxMB} MB per questo tipo.`;
    warnSize.style.display = 'block';
  } else {
    warnSize.style.display = 'none';
  }

  // Anteprima locale immediata
  const objURL = URL.createObjectURL(f);
  if (f.type.startsWith('video/')) {
    previewVid.src = objURL; previewVid.style.display = 'block';
    previewImg.style.display = 'none';
    previewBox.style.display = 'block';
    // auto-seleziona tipo video se non già selezionato
    if (!tipoSelezionato()) document.getElementById('t-video').checked = true;
  } else if (f.type.startsWith('image/')) {
    previewImg.src = objURL; previewImg.style.display = 'block';
    previewVid.style.display = 'none';
    previewBox.style.display = 'block';
  } else {
    previewBox.style.display = 'none';
  }
});

// Drag & drop
const area = document.getElementById('uploadArea');
area.addEventListener('dragover',  e => { e.preventDefault(); area.classList.add('drag'); });
area.addEventListener('dragleave', () => area.classList.remove('drag'));
area.addEventListener('drop', e => {
  e.preventDefault(); area.classList.remove('drag');
  if (e.dataTransfer.files.length) {
    fileInput.files = e.dataTransfer.files;
    fileInput.dispatchEvent(new Event('change'));
  }
});

// Anteprima URL esterno
document.getElementById('urlInput').addEventListener('input', function() {
  const url = this.value.trim();
  if (!url) { previewBox.style.display='none'; return; }
  if (/\.(mp4|webm|mov)$/i.test(url)) {
    previewVid.src=url; previewVid.style.display='block';
    previewImg.style.display='none'; previewBox.style.display='block';
  } else if (/\.(jpg|jpeg|png|gif|webp)$/i.test(url)) {
    previewImg.src=url; previewImg.style.display='block';
    previewVid.style.display='none'; previewBox.style.display='block';
    previewImg.onerror = () => previewBox.style.display='none';
  }
});

// Blocca submit se file troppo grande
document.getElementById('contentForm').addEventListener('submit', function(e) {
  const f = fileInput.files[0];
  if (!f) return;
  const tipo  = tipoSelezionato();
  const maxMB = tipo ? (formati[tipo]?.maxMB ?? 20) : 20;
  if (f.size / 1024 / 1024 > maxMB) {
    e.preventDefault();
    warnSize.textContent = `⚠️ File troppo grande. Massimo ${maxMB} MB.`;
    warnSize.style.display = 'block';
    window.scrollTo(0, warnSize.offsetTop - 100);
  }
});
</script>
</body>
</html>