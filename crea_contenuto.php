<?php
require 'config.php';

// Only logged-in researchers or admins can publish content
if (!isset($_SESSION['id']) || !isset($_SESSION['ruolo']) || !in_array($_SESSION['ruolo'], ['ricercatore','admin'], true)) {
    die("Accesso negato. Devi essere un ricercatore o admin per creare contenuti.");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo = trim($_POST['titolo'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $tipo = $_POST['tipo'] ?? ''; // foto, video, documento, etc.
    
    if ($titolo === '' || $descrizione === '' || $url === '') {
        $errors[] = "Titolo, descrizione e URL sono obbligatori.";
    } elseif (!in_array($tipo, ['foto', 'video', 'documento', 'ricerca'], true)) {
        $errors[] = "Tipo di contenuto non valido.";
    } else {
        $stmt = $connessione->prepare("INSERT INTO media (titolo, descrizione, url, data_pub) VALUES (?, ?, ?, CURDATE())");
        $stmt->execute([$titolo, $descrizione, $url]);
        header('Location: crea_contenuto.php?ok=1');
        exit();
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
      :root{
        --ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;
        --text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;--coral:#e05a3a;--ease:cubic-bezier(.25,.46,.45,.94);
      }
      body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);padding:2rem 2.5rem;}
      h1{font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--pearl);margin-bottom:1.5rem;}
      .container{max-width:700px;}
      .alert{padding:1rem;margin:.5rem 0;border-radius:10px;}
      .alert-success{background:rgba(44,184,155,.1);border:1px solid rgba(44,184,155,.3);color:#3dd4ae;}
      .alert-error{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.3);color:#e8836a;}
      .form-group{margin-bottom:1.5rem;}
      label{display:block;margin-bottom:.5rem;color:var(--foam);font-weight:500;}
      input,textarea,select{width:100%;padding:.75rem;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.15);border-radius:8px;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.95rem;}
      input:focus,textarea:focus,select:focus{outline:none;border-color:var(--wave);background:rgba(11,61,94,.5);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
      select{cursor:pointer;}
      option{background:var(--deep);color:var(--pearl);}
      textarea{resize:vertical;}
      .btn{display:inline-block;padding:.8rem 1.5rem;background:var(--wave);color:var(--ink);border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;font-size:.95rem;}
      .btn:hover{background:var(--foam);transform:translateY(-2px);box-shadow:0 8px 20px rgba(27,159,212,.25);}
      .btn-back{background:rgba(114,215,240,.1);border:1px solid rgba(114,215,240,.2);color:var(--foam);margin-top:1rem;}
      .btn-back:hover{background:rgba(114,215,240,.2);transform:none;box-shadow:none;}
      a{color:var(--wave);text-decoration:none;transition:color .2s;}
      a:hover{color:var(--foam);}
      .hint{font-size:.85rem;color:var(--muted);margin-top:.25rem;}
    </style>
</head>
<body>
    <div class="container">
        <h1>✨ Pubblica un Contenuto</h1>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach($errors as $e): ?>
                    <p>❌ <?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['ok'])): ?>
            <p class="alert alert-success">✅ Contenuto pubblicato con successo.</p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Titolo:</label>
                <input type="text" name="titolo" required>
                <p class="hint">Titolo descrittivo del contenuto</p>
            </div>
            <div class="form-group">
                <label>Descrizione:</label>
                <textarea name="descrizione" rows="6" required></textarea>
                <p class="hint">Descrivi il contenuto in dettaglio</p>
            </div>
            <div class="form-group">
                <label>URL contenuto:</label>
                <input type="url" name="url" required>
                <p class="hint">Link al video, foto, PDF, ecc.</p>
            </div>
            <div class="form-group">
                <label>Tipo di contenuto:</label>
                <select name="tipo" required>
                    <option value="">— Scegli un tipo —</option>
                    <option value="foto">📸 Foto</option>
                    <option value="video">📹 Video</option>
                    <option value="documento">📄 Documento</option>
                    <option value="ricerca">🔬 Ricerca</option>
                </select>
                <p class="hint">Seleziona il tipo di contenuto che stai pubblicando</p>
            </div>
            <button type="submit" class="btn">✨ Pubblica</button>
        </form>
        <a href="index.php" class="btn btn-back" style="text-decoration:none;">← Torna alla home</a>
    </div>
</body>
</html>
