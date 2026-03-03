<?php
require 'config.php';

if (!isset($_SESSION['id'])) {
    header('Location: Login.php'); exit();
}

$id_utente = (int)$_SESSION['id'];
$errors = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_foto') {
        if (!empty($_FILES['foto']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
                $errors[] = 'Formato non supportato. Usa JPG, PNG o GIF.';
            } elseif ($_FILES['foto']['size'] > 2*1024*1024) {
                $errors[] = 'File troppo grande. Massimo 2 MB.';
            } else {
                $dir = 'uploads/profili/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $filename = $dir . $id_utente . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $filename)) {
                    $connessione->prepare("UPDATE utente SET foto_profilo=? WHERE id_utente=?")
                        ->execute([$filename, $id_utente]);
                    $success = true;
                } else {
                    $errors[] = 'Errore nel salvataggio del file.';
                }
            }
        }
    } elseif ($action === 'aggiorna_dati') {
        $nome    = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        if (!$nome || !$cognome) $errors[] = 'Nome e cognome obbligatori.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida.';
        if (!$errors) {
            $connessione->prepare("UPDATE utente SET nome=?,cognome=?,email=? WHERE id_utente=?")
                ->execute([$nome, $cognome, $email, $id_utente]);
            $success = true;
        }
    } elseif ($action === 'cambia_password') {
        $old = $_POST['password_attuale'] ?? '';
        $new = $_POST['password_nuova'] ?? '';
        $cfm = $_POST['password_conferma'] ?? '';
        $st = $connessione->prepare("SELECT password FROM utente WHERE id_utente=?");
        $st->execute([$id_utente]);
        $hash = $st->fetchColumn();
        if (!password_verify($old, $hash)) $errors[] = 'Password attuale errata.';
        elseif (strlen($new) < 6)          $errors[] = 'La nuova password deve avere almeno 6 caratteri.';
        elseif ($new !== $cfm)             $errors[] = 'Le password non coincidono.';
        else {
            $connessione->prepare("UPDATE utente SET password=? WHERE id_utente=?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $id_utente]);
            $success = true;
        }
    }
}

// DATI UTENTE 
$st = $connessione->prepare("SELECT * FROM utente WHERE id_utente=?");
$st->execute([$id_utente]); $utente = $st->fetch();
if (!$utente) { header('Location: logout.php'); exit(); }

// Post piaciuti
$st = $connessione->prepare("
    SELECT m.id_post, m.titolo, m.url, u.nome AS nome_pub, u.cognome AS cognome_pub
    FROM like_media lm
    INNER JOIN media m ON lm.id_post = m.id_post
    LEFT JOIN utente u ON m.id_utente = u.id_utente
    WHERE lm.id_utente = ?
    ORDER BY m.data_pub DESC ");
$st->execute([$id_utente]); $post_piaciuti = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profilo — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .prof-wrap { max-width:860px; margin:0 auto; padding:5.5rem 1.5rem 4rem; }
    .prof-hero { display:flex; align-items:center; gap:1.5rem; margin-bottom:2.5rem; padding:1.5rem 1.75rem; background:rgba(11,61,94,.22); border:1px solid rgba(114,215,240,.1); border-radius:16px; }
    .prof-avatar { width:72px; height:72px; border-radius:50%; background:rgba(27,159,212,.12); border:2px solid rgba(114,215,240,.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
    .prof-avatar img { width:100%; height:100%; object-fit:cover; }
    .prof-avatar svg { width:32px; height:32px; color:rgba(114,215,240,.5); }
    .prof-name { font-family:'Cormorant Garamond',serif; font-size:1.6rem; color:var(--pearl); font-weight:400; margin-bottom:.15rem; }
    .prof-sub { font-size:.8rem; color:var(--muted); }
    .prof-role { display:inline-block; margin-top:.4rem; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; padding:.15rem .6rem; border-radius:20px; background:rgba(27,159,212,.1); border:1px solid rgba(27,159,212,.2); color:var(--wave); }

    .section-title { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--wave); margin-bottom:1rem; margin-top:2rem; }
    .prof-card { background:rgba(11,61,94,.18); border:1px solid rgba(114,215,240,.09); border-radius:14px; padding:1.5rem; margin-bottom:1rem; }

    /* Flash */
    .flash-ok  { padding:.7rem 1.1rem; border-radius:10px; background:rgba(44,184,155,.1); border:1px solid rgba(44,184,155,.25); color:#2cb89b; font-size:.85rem; margin-bottom:1.25rem; }
    .flash-err { padding:.7rem 1.1rem; border-radius:10px; background:rgba(232,131,106,.1); border:1px solid rgba(232,131,106,.25); color:#e8836a; font-size:.85rem; margin-bottom:1.25rem; }

    /* Form */
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    @media(max-width:600px){ .form-row { grid-template-columns:1fr; } }
    .form-group { display:flex; flex-direction:column; gap:.35rem; margin-bottom:.85rem; }
    .form-group label { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); }
    .form-group input { padding:.6rem .9rem; background:rgba(11,61,94,.4); border:1px solid rgba(114,215,240,.15); border-radius:9px; color:var(--pearl); font-family:'Outfit',sans-serif; font-size:.88rem; outline:none; transition:border-color .2s; }
    .form-group input:focus { border-color:var(--wave); }
    .form-group input[type="file"] { padding:.5rem .7rem; font-size:.82rem; }
    .hint { font-size:.72rem; color:rgba(114,215,240,.35); margin-top:.2rem; }
    .prof-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1.4rem; background:rgba(27,159,212,.15); border:1px solid rgba(27,159,212,.3); border-radius:9px; color:var(--wave); font-family:'Outfit',sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; transition:background .2s; }
    .prof-btn:hover { background:rgba(27,159,212,.28); }
    .prof-btn.danger { background:rgba(232,131,106,.1); border-color:rgba(232,131,106,.3); color:#e8836a; }
    .prof-btn.danger:hover { background:rgba(232,131,106,.22); }

    /* Post piaciuti */
    .liked-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:.75rem; }
    .liked-card { background:rgba(11,61,94,.25); border:1px solid rgba(114,215,240,.08); border-radius:10px; overflow:hidden; text-decoration:none; transition:border-color .2s,transform .2s; display:block; }
    .liked-card:hover { border-color:rgba(114,215,240,.25); transform:translateY(-2px); }
    .liked-thumb { height:100px; background:linear-gradient(135deg,var(--ocean),var(--deep)); position:relative; overflow:hidden; }
    .liked-thumb img { position:absolute;inset:0;width:100%;height:100%;object-fit:cover; }
    .liked-info { padding:.6rem .75rem; }
    .liked-title { color:var(--pearl); font-size:.8rem; font-weight:500; line-height:1.3; margin-bottom:.2rem; display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
    .liked-author { color:var(--muted); font-size:.68rem; }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <img src="uploads/logos/logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="javascript:history.back()" class="nav-back">← Indietro</a>
</nav>

<div class="prof-wrap">

  <?php if ($success): ?>
    <div class="flash-ok">Modifiche salvate correttamente.</div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="flash-err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>

  <!-- Hero profilo -->
  <div class="prof-hero">
    <div class="prof-avatar">
      <?php if (!empty($utente['foto_profilo'])): ?>
        <img src="<?= htmlspecialchars($utente['foto_profilo']) ?>" alt="Foto profilo">
      <?php else: ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      <?php endif; ?>
    </div>
    <div>
      <p class="prof-name"><?= htmlspecialchars($utente['nome'].' '.$utente['cognome']) ?></p>
      <p class="prof-sub">@<?= htmlspecialchars($utente['username']) ?> · Iscritto dal <?= date('d M Y', strtotime($utente['data_registrazione'])) ?></p>
      <span class="prof-role"><?= htmlspecialchars(ucfirst($utente['ruolo'] ?? 'utente')) ?></span>
    </div>
  </div>

  <!-- Foto profilo -->
  <p class="section-title">Foto profilo</p>
  <div class="prof-card">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload_foto">
      <div class="form-group">
        <label>Carica immagine</label>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/gif" required>
        <span class="hint">JPG, PNG o GIF · max 2 MB</span>
      </div>
      <button type="submit" class="prof-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Carica foto
      </button>
    </form>
  </div>

  <!-- Dati personali -->
  <p class="section-title">Informazioni personali</p>
  <div class="prof-card">
    <form method="POST">
      <input type="hidden" name="action" value="aggiorna_dati">
      <div class="form-row">
        <div class="form-group">
          <label>Nome</label>
          <input type="text" name="nome" value="<?= htmlspecialchars($utente['nome']) ?>" required>
        </div>
        <div class="form-group">
          <label>Cognome</label>
          <input type="text" name="cognome" value="<?= htmlspecialchars($utente['cognome']) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($utente['email']) ?>" required>
      </div>
      <button type="submit" class="prof-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Salva modifiche
      </button>
    </form>
  </div>

  <!-- Cambio password -->
  <p class="section-title">Cambio password</p>
  <div class="prof-card">
    <form method="POST">
      <input type="hidden" name="action" value="cambia_password">
      <div class="form-group">
        <label>Password attuale</label>
        <input type="password" name="password_attuale" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Nuova password</label>
          <input type="password" name="password_nuova" required>
        </div>
        <div class="form-group">
          <label>Conferma password</label>
          <input type="password" name="password_conferma" required>
        </div>
      </div>
      <button type="submit" class="prof-btn danger">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Cambia password
      </button>
    </form>
  </div>

  <!-- Post piaciuti -->
  <?php if (!empty($post_piaciuti)): ?>
  <p class="section-title">Contenuti che ti piacciono</p>
  <div class="liked-grid">
    <?php foreach ($post_piaciuti as $lp):
      $isImg = !empty($lp['url']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $lp['url']);
      $pub   = trim(($lp['nome_pub']??'').' '.($lp['cognome_pub']??'')) ?: 'NetSea';
    ?>
    <a href="feed.php" class="liked-card">
      <div class="liked-thumb">
        <?php if ($isImg): ?>
          <img src="<?= htmlspecialchars($lp['url']) ?>" alt="">
        <?php endif; ?>
      </div>
      <div class="liked-info">
        <p class="liked-title"><?= htmlspecialchars($lp['titolo'] ?? '') ?></p>
        <p class="liked-author">di <?= htmlspecialchars($pub) ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="margin-top:2.5rem;">
    <a href="index.php" style="color:var(--muted);font-size:.85rem;text-decoration:none;">← Torna alla home</a>
  </div>

</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';cur.style.opacity='1';ring.style.opacity='1';});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(loop);})();
</script>
</body>
</html>